<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start();
require_once 'Db.php';
require_once 'User.php';
require_once 'Validator.php';

use App\Validator;

$pdo = Db::getPdoInstance();

session_cache_limiter('none');
session_start();

$error_message = [];
$error_message_files = [];

$id = $_GET['id'] ?? $_POST['id'] ?? null;
$user = new User($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_POST = $user->findById($id);
    $_POST['birth_date_raw'] = $_POST['birth_date'] ?? '';
} else {
    if (empty($_POST['birth_date']) && !empty($_POST['birth_date_raw'])) {
        $_POST['birth_date'] = $_POST['birth_date_raw'];
    }

    // ✅ バリデーション実行（ここでチェック）
    $validator = new Validator();
    $isValidData  = $validator->validateData("edit", $_POST);
    $isValidFiles = $validator->validateFiles($_FILES);

    if ($isValidData && $isValidFiles) {
        // ✅ 成功時のみファイル保存処理 & セッションセット
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $documentData = [];

        foreach (['document1', 'document2'] as $key) {
            if (isset($_FILES[$key]) && is_uploaded_file($_FILES[$key]['tmp_name'])) {
                $filename = uniqid() . '_' . basename($_FILES[$key]['name']);
                $target = $uploadDir . $filename;

                if (move_uploaded_file($_FILES[$key]['tmp_name'], $target)) {
                    $documentData[$key] = [
                        'path' => $target,
                        'type' => $_FILES[$key]['type'],
                        'name' => $_FILES[$key]['name'],
                    ];
                }
            }
        }

        $_SESSION['document_data'] = $documentData;
        $_SESSION['edit_data'] = $_POST;

        session_write_close();

        error_log('Redirecting to update.php');

        header('Location: update.php');
        exit;
    } else {
        // ❌ バリデーションエラー時はエラーメッセージを表示
        $error_message = $validator->getErrors();
        $error_message_files = $validator->getErrorsFiles();
        echo "<pre>";
        print_r($error_message);
        print_r($error_message_files);
        echo "</pre>";
        exit;
    }
}

unset($_FILES);

// 4.html の描画
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>mini System</title>
    <link rel="stylesheet" href="style_new.css">
    <script src="postalcodesearch.js"></script>
    <script src="contact.js"></script>
</head>

<body>
    <div>
        <h1>mini System</h1>
    </div>

    <body>



        <div>
            <h2>更新・削除画面</h2>
        </div>
        <div>
            <form action="edit.php" method="post" name="form" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $_POST['id'] ?>">
                <h1 class="contact-title">更新内容入力</h1>
                <p>更新内容をご入力の上、「更新」ボタンをクリックしてください。</p>
                <p>削除する場合は「削除」ボタンをクリックしてください。</p>
                <div>
                    <div>
                        <label>お名前<span>必須</span></label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            placeholder="例）山田太郎"
                            value="<?= htmlspecialchars($_POST['name']) ?>">
                        <?php if (isset($error_message['name'])) : ?>
                            <div class="error-msg">
                                <?= htmlspecialchars($error_message['name']) ?></div>
                        <?php endif ?>
                    </div>
                    <div>
                        <label>ふりがな<span>必須</span></label>
                        <input
                            type="text"
                            name="kana"
                            id="kana"
                            placeholder="例）やまだたろう"
                            value="<?= htmlspecialchars($_POST['kana']) ?>">
                        <?php if (isset($error_message['kana'])) : ?>
                            <div class="error-msg">
                                <?= htmlspecialchars($error_message['kana']) ?></div>
                        <?php endif ?>
                    </div>
                    <div>
                        <label>性別<span>必須</span></label>
                        <?php $_POST['gender_flag'] ?? '1'; ?>
                        <label class="gender">
                            <input
                                type="radio"
                                name="gender_flag"
                                value='1'
                                <?= ($_POST['gender_flag'] ?? '1') == '1'
                                    ? 'checked' : '' ?>>男性</label>
                        <label class="gender">
                            <input
                                type="radio"
                                name="gender_flag"
                                value='2'
                                <?= ($_POST['gender_flag'] ?? '') == '2'
                                    ? 'checked' : '' ?>>女性</label>
                        <label class="gender">
                            <input
                                type="radio"
                                name="gender_flag"
                                value='3'
                                <?= ($_POST['gender_flag'] ?? '') == '3'
                                    ? 'checked' : '' ?>>その他</label>
                    </div>
                    <div>

                        <label>生年月日<span>必須</span></label>
                        <?php
                        $birthDateRaw = $_POST['birth_date'] ?? $_POST['birth_date_raw'] ?? '';
                        $birthDateFormatted = '';
                        if (!empty($birthDateRaw) && $birthDateRaw !== '0000-00-00' && strtotime($birthDateRaw)) {
                            $birthDateFormatted = date('Y年n月j日', strtotime($birthDateRaw));
                        }
                        ?>
                        <!-- 表示用（編集不可） -->
                        <input
                            type="text"
                            name="birth_date"
                            value="<?= htmlspecialchars($birthDateFormatted) ?>"
                            readonly
                            class="readonly-field">

                        <!-- サーバー送信用（非表示） -->
                        <input
                            type="hidden"
                            name="birth_date"
                            value="<?= htmlspecialchars($birthDateRaw) ?>">

                        <?php
                        // birthDateRaw が "YYYY-MM-DD" 形式であることを前提に分割
                        $birthYear = '';
                        $birthMonth = '';
                        $birthDay = '';
                        if (!empty($birthDateRaw) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthDateRaw)) {
                            list($birthYear, $birthMonth, $birthDay) = explode('-', $birthDateRaw);
                        }
                        ?>

                        <input type="hidden" name="birth_year" value="<?= htmlspecialchars($birthYear) ?>">
                        <input type="hidden" name="birth_month" value="<?= htmlspecialchars($birthMonth) ?>">
                        <input type="hidden" name="birth_day" value="<?= htmlspecialchars($birthDay) ?>">
                    </div>


                    <div>
                        <label>郵便番号<span>必須</span></label>
                        <div id="postalWrapper" class="postal-row">
                            <input
                                class="half-width"
                                type="text"
                                name="postal_code"
                                id="postal_code"
                                placeholder="例）100-0001"
                                value="<?= htmlspecialchars($_POST['postal_code'] ?? '') ?>">
                            <button type="button"
                                class="postal-code-search"
                                id="searchAddressBtn">住所検索</button>
                        </div>
                        <?php if (isset($error_message['postal_code'])) : ?>
                            <div class="error-msg2">
                                <?= htmlspecialchars($error_message['postal_code']) ?></div>
                        <?php endif ?>
                    </div>

                    <label>住所<span>必須</span></label>
                    <div class="address-block">
                        <input
                            type="text"
                            name="prefecture"
                            id="prefecture"
                            placeholder="都道府県"
                            value="<?= htmlspecialchars($_POST['prefecture'] ?? '') ?>">
                        <input
                            type="text"
                            name="city_town"
                            id="city_town"
                            placeholder="市区町村・番地"
                            value="<?= htmlspecialchars($_POST['city_town'] ?? '') ?>">
                        <input
                            type="text"
                            name="building"
                            placeholder="建物名・部屋番号  **省略可**"
                            value="<?= htmlspecialchars($_POST['building'] ?? '') ?>">
                        <?php if (isset($error_message['address'])) : ?>
                            <div class="error-msg">
                                <?= htmlspecialchars($error_message['address']) ?>
                            </div>
                        <?php endif ?>
                    </div>



                    <div>
                        <label>電話番号<span>必須</span></label>
                        <input
                            type="text"
                            name="tel"
                            id="tel"
                            placeholder="例）000-000-0000"
                            value="<?= htmlspecialchars($_POST['tel']) ?>">
                        <?php if (isset($error_message['tel'])) : ?>
                            <div class="error-msg">
                                <?= htmlspecialchars($error_message['tel']) ?></div>
                        <?php endif ?>
                    </div>
                    <div>
                        <label>メールアドレス<span>必須</span></label>
                        <input
                            type="text"
                            name="email"
                            id="email"
                            placeholder="例）guest@example.com"
                            value="<?= htmlspecialchars($_POST['email']) ?>">
                        <?php if (isset($error_message['email'])) : ?>
                            <div class="error-msg">
                                <?= htmlspecialchars($error_message['email']) ?></div>
                        <?php endif ?>
                    </div>
                    <div>
                        <label>本人確認書類（表）</label>
                        <input
                            type="file"
                            name="document1"
                            id="document1"
                            accept="image/png, image/jpeg, image/jpg">
                        <span id="filename1" class="filename-display"></span>
                        <div class="preview-container">
                            <img id="preview1" src="#" alt="プレビュー画像１" style="display: none; max-width: 200px; margin-top: 8px;">
                        </div>
                        <!-- エラー時はドキュメントを保持せず、破棄する -->
                        <?php if (isset($error_message_files['document1'])) : ?>
                            <div class="error-msg">
                                <?= htmlspecialchars($error_message_files['document1']) ?></div>
                        <?php endif ?>
                    </div>

                    <div>
                        <label>本人確認書類（裏）</label>
                        <input
                            type="file"
                            name="document2"
                            id="document2"
                            accept="image/png, image/jpeg, image/jpg">
                        <span id="filename2" class="filename-display"></span>
                        <div class="preview-container">
                            <img id="preview2" src="#" alt="プレビュー画像２" style="display: none; max-width: 200px; margin-top: 8px;">
                        </div>
                        <!-- エラー時はドキュメントを保持せず、破棄する -->
                        <?php if (isset($error_message_files['document2'])) : ?>
                            <div class="error-msg">
                                <?= htmlspecialchars($error_message_files['document2']) ?></div>
                        <?php endif ?>

                    </div>
                </div>
                <button type="submit">更新</button>
                <a href="dashboard.php">
                    <input type="button" value="ダッシュボードに戻る">
                </a>

            </form>
            <form action="delete.php" method="post" name="delete">
                <input type="hidden" name="id" value="<?php echo $_POST['id'] ?>">
                <button type="submit">削除</button>
            </form>
        </div>




    </body>

</html>
<?php
ob_end_flush();
?>