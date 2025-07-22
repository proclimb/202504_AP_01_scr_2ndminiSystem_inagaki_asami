<?php
require_once 'Db.php';
require_once 'Address.php';
require_once 'Allowedoldchars.php';

use App\Address\UserAddress;

class Validator
{
    /**
     * @var array DB接続用
     * @var array エラーメッセージ格納用
     *
     * DB接続用のPDOオブジェクトをグローバル変数で使用しない場合は
     * コンストラクタで受け取るようにしてください。
     * 上記の場合、global $pdo; の行はコメントアウトしてください。
     */
    // private PDO $pdo;
    private $error_message = [];
    private $error_message_files = [];

    //DB接続情報
    // public function __construct(PDO $pdo)
    // {
    //     $this->pdo = $pdo;
    // }

    /**
     * フォームデータ全体をバリデートし、エラー配列を返す
     *
     * @param array $data 入力データ
     * @return array エラーメッセージ配列
     */
    public function validateData($form, $data)
    {
        $this->error_message = [];

        // 名前
        $noSpaceName = preg_replace('/[\s　]+/u', '', $data['name'] ?? '');
        if (empty($noSpaceName)) {
            $this->error_message['name'] = '名前が入力されていません';
        } elseif (preg_match('/^[\s　]|[\s　]$/u', $data['name'])) {
            $this->error_message['name'] = '名前の先頭または末尾にスペースを含めないでください';
        } elseif (mb_strlen($data['name']) > 20) {
            $this->error_message['name'] = '名前は20文字以内で入力してください';
        } else {
            // AllowedOldChars クラスから旧字体リストを取得
            $allowedOldChars = AllowedOldChars::get();
            $quotedOldChars = preg_quote($allowedOldChars, '/');
            // 漢字・ひらがな・カタカナ・長音符・スペース・旧字体のみ
            $pattern = '/^[\p{Han}\p{Hiragana}\p{Katakana}ー\s　' . $quotedOldChars . ']+$/u';
            if (!preg_match($pattern, $data['name'])) {
                $this->error_message['name'] = '名前は漢字・ひらがな・カタカナのみ使用可能です';
            }
        }

        // ふりがな
        $noSpaceKana = preg_replace('/[\s　]+/u', '', $data['kana'] ?? '');
        if (empty($noSpaceKana)) {
            $this->error_message['kana'] = 'ふりがなが入力されていません';
        } elseif (preg_match('/^[\s　]|[\s　]$/u', $data['kana'])) {
            $this->error_message['kana'] = 'ふりがなの先頭または末尾にスペースを含めないでください';
        } elseif (mb_strlen($data['kana']) > 20) {
            $this->error_message['kana'] = 'ふりがなは20文字以内で入力してください';
        } else {
            // ひらがな・長音符・スペースのみ
            if (!preg_match('/^[ぁ-んー\s　]+$/u', $data['kana'])) {
                $this->error_message['kana'] = 'ひらがなで入力してください';
            }
        }

        // 生年月日
        if ($form != "edit") {
            if (empty($data['birth_year']) || empty($data['birth_month']) || empty($data['birth_day'])) {
                $this->error_message['birth_date'] = '生年月日が入力されていません';
            } elseif (!$this->isValidDate($data['birth_year'] ?? '', $data['birth_month'] ?? '', $data['birth_day'] ?? '')) {
                $this->error_message['birth_date'] = '生年月日が正しくありません';
            } else {
                // 未来日のチェック
                $birthDate = sprintf('%04d-%02d-%02d', $data['birth_year'], $data['birth_month'], $data['birth_day']);
                if ($birthDate > date('Y-m-d')) {
                    $this->error_message['birth_date'] = '生年月日に未来日は指定できません';
                }
            }
        }

        // 郵便番号
        if (empty($data['postal_code'])) {
            $this->error_message['postal_code'] = '郵便番号が入力されていません';
        } elseif (!preg_match('/^[0-9]{3}-[0-9]{4}$/', $data['postal_code'] ?? '')) {
            $this->error_message['postal_code'] = '郵便番号は「XXX-XXXX」の形式で入力してください';
        }

        // 住所
        if (empty($data['prefecture']) || empty($data['city_town'])) {
            $this->error_message['address'] = '住所(都道府県もしくは市区町村・番地)が入力されていません';
        } elseif (mb_strlen($data['prefecture']) > 10) {
            $this->error_message['address'] = '都道府県は10文字以内で入力してください';
        } elseif (mb_strlen($data['city_town']) > 50 || mb_strlen($data['building']) > 50) {
            $this->error_message['address'] = '市区町村・番地もしくは建物名は50文字以内で入力してください';
        } else {
            // 郵便番号と住所の組み合わせをDBで確認
            // DB接続のためにグローバル変数を使用（globalと宣言することによって、外部の$pdo変数を参照できるようにする）
            global $pdo;
            if (!isset($this->error_message['postal_code']) || !isset($this->error_message['address'])) {
                $checkAddress = new UserAddress($pdo);
                if (!$checkAddress->checkAddressMatch($data['postal_code'], $data['prefecture'], $data['city_town'])) {
                    $this->error_message['address'] = '郵便番号と住所の組み合わせが一致しません';
                }
            }
        }

        // 電話番号
        if (empty($data['tel'])) {
            $this->error_message['tel'] = '電話番号が入力されていません';
        } elseif (
            !preg_match('/^0\d{1,4}-\d{1,4}-\d{3,4}$/', $data['tel'] ?? '') ||
            mb_strlen($data['tel']) < 12 ||
            mb_strlen($data['tel']) > 13
        ) {
            $this->error_message['tel'] = '電話番号は12~13桁(例:XXX-XXXX-XXXX)で正しく入力してください';
        }

        // メールアドレス
        if (empty($data['email'])) {
            $this->error_message['email'] = 'メールアドレスが入力されていません';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->error_message['email'] = '有効なメールアドレスを入力してください';
        }

        return empty($this->error_message);
    }


    public function validateFiles(array $files)
    {

        $this->error_message_files = [];

        // 本人確認書類(表)
        if ($files['document1']['error'] !== UPLOAD_ERR_NO_FILE) {
            if (!isValidFilesize($files['document1'])) {
                // ファイルサイズチェック
                $this->error_message_files['document1'] = '2MB以上はアップロードできません';
            } elseif (!isValidFileExtension($files['document1'])) {
                // ファイル拡張子チェック
                $this->error_message_files['document1'] = 'ファイル形式は PNG,JPEG,jpg のいずれかのみ許可されています';
            }
        }

        // 本人確認書類(裏)
        if ($files['document2']['error'] !== UPLOAD_ERR_NO_FILE) {
            if (!isValidFilesize($files['document2'])) {
                // ファイルサイズチェック
                $this->error_message_files['document2'] = '2MB以上はアップロードできません';
            } elseif (!isValidFileExtension($files['document2'])) {
                // ファイル拡張子チェック
                $this->error_message_files['document2'] = 'ファイル形式は PNG,JPEG,jpg のいずれかのみ許可されています';
            }
        }

        return empty($this->error_message_files);
    }

    /**
     * 入力値のエラーメッセージを取得
     *
     * @param string $key エラーキー
     * @return string|null エラーメッセージ、存在しない場合は null
     */
    public function getErrors()
    {
        return $this->error_message;
    }

    /**
     * 入力値のエラーメッセージを取得(本人確認書類用)
     *
     * @param string $key エラーキー
     * @return string|null エラーメッセージ、存在しない場合は null
     */
    public function getErrorsFiles()
    {
        return $this->error_message_files;
    }


    /**
     * 生年月日の日付整合性チェック
     *
     * @param string $year 年
     * @param string $month 月
     * @param string $day 日
     * @return bool 日付が正しい場合は true、そうでない場合は false
     */
    private function isValidDate($year, $month, $day)
    {
        return checkdate((int)$month, (int)$day, (int)$year);
    }
}

// ファイルサイズのチェック2MBまで
function isValidFilesize($file)
{
    $maxSize = 2 * 1024 * 1024; // 2MB
    if ($file['size'] > $maxSize) {
        return false; // ファイルサイズが2MBを超えている
    }
    return true; // ファイルサイズは許容範囲内
}

// ファイルの拡張子チェック
function isValidFileExtension($file)
{
    $allowedExtensions = ['jpeg', 'jpg', 'png'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        return false; // 許可されていない拡張子
    }
    return true; // 許可された拡張子
}

class AllowedOldChars
{
    public static function get(): string
    {
        // 旧字体など許可したい文字を列挙（例）
        return '﨑神都塚';
    }
}

namespace App\Address;

class UserAddress
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function checkAddressMatch(string $postalCode, string $prefecture, string $cityTown): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM addresses
             WHERE postal_code = :postal_code
             AND prefecture = :prefecture
             AND city_town = :city_town"
        );
        $stmt->execute([
            ':postal_code' => $postalCode,
            ':prefecture' => $prefecture,
            ':city_town' => $cityTown,
        ]);
        $count = $stmt->fetchColumn();
        return $count > 0;
    }
}
