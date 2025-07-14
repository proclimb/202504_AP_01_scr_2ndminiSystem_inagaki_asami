<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Validator
{
    private $error_message = [];

    // メインのバリデーション処理
    public function validate($data)
    {
        $this->error_message = [];

        // 名前
        if (empty($data['name'])) {
            $this->error_message['name'] = '名前が入力されていません';
        } elseif (mb_strlen($data['name']) > 20) {
            $this->error_message['name'] = '名前は20文字以内で入力してください';
        } elseif (!preg_match('/^[ぁ-んァ-ヶー一-龠々〆〤 　]+$/u', $data['name'])) {
            $this->error_message['name'] = '名前は日本語のみで入力してください（記号・数字・半角文字は使用できません）';
        }

        // ふりがな
        if (empty($data['kana'])) {
            $this->error_message['kana'] = 'ふりがなが入力されていません';
        } elseif (preg_match('/[^ぁ-んー　 ]/u', $data['kana'])) {
            $this->error_message['kana'] = 'ひらがなで入力してください';
        } elseif (mb_strlen($data['kana']) > 20) {
            $this->error_message['kana'] = 'ふりがなは20文字以内で入力してください';
        }

        // 生年月日
        if (empty($data['birth_year']) || empty($data['birth_month']) || empty($data['birth_day'])) {
            $this->error_message['birth_date'] = '生年月日が入力されていません';
        } elseif (!$this->isValidDate($data['birth_year'], $data['birth_month'], $data['birth_day'])) {
            $this->error_message['birth_date'] = '生年月日が正しくありません';
        } else {
            $birth_timestamp = strtotime(sprintf('%04d-%02d-%02d', $data['birth_year'], $data['birth_month'], $data['birth_day']));
            if ($birth_timestamp > time()) {
                $this->error_message['birth_date'] = '生年月日が正しくありません（未来日です）';
            }
        }

        // 郵便番号
        if (empty($data['postal_code'])) {
            $this->error_message['postal_code'] = '郵便番号が入力されていません';
        } elseif (!preg_match('/^[0-9]{3}-[0-9]{4}$/', $data['postal_code'])) {
            $this->error_message['postal_code'] = '郵便番号が正しくありません';
        }

        // 住所
        if (empty($data['prefecture']) || empty($data['city_town'])) {
            $this->error_message['address'] = '住所(都道府県もしくは市区町村・番地)が入力されていません';
        } elseif (mb_strlen($data['prefecture']) > 10) {
            $this->error_message['address'] = '都道府県は10文字以内で入力してください';
        } elseif (mb_strlen($data['city_town']) > 50 || mb_strlen($data['building'] ?? '') > 50) {
            $this->error_message['address'] = '市区町村・番地もしくは建物名は50文字以内で入力してください';
        } else {
            // 整合性チェック：郵便番号から住所取得して照合
            $api_address = $this->lookupAddressByPostalCode($data['postal_code']);

            if ($api_address) {
                $checkadd = $api_address['city'] . $api_address['town'];
                if ($data['prefecture'] !== $api_address['prefecture']) {
                    $this->error_message['address'] = '郵便番号と都道府県が一致しません';
                } elseif (strpos($data['city_town'], $checkadd) === false) {
                    $this->error_message['address'] = '郵便番号と市区町村が一致しません';
                }
            } else {
                $this->error_message['postal_code'] = '郵便番号から住所を取得できませんでした';
            }
        }

        // 電話番号
        if (empty($data['tel'])) {
            $this->error_message['tel'] = '電話番号が入力されていません';
        } elseif (
            !preg_match('/^0\d{1,4}-\d{1,4}-\d{3,4}$/', $data['tel']) ||
            mb_strlen($data['tel']) < 12 ||
            mb_strlen($data['tel']) > 13
        ) {
            $this->error_message['tel'] = '電話番号は12~13桁で正しく入力してください';
        }

        // メールアドレス
        if (empty($data['email'])) {
            $this->error_message['email'] = 'メールアドレスが入力されていません';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->error_message['email'] = '有効なメールアドレスを入力してください';
        }

        return empty($this->error_message);
    }

    // エラーメッセージ取得
    public function getErrors()
    {
        return $this->error_message;
    }

    // 生年月日の日付整合性チェック
    private function isValidDate($year, $month, $day)
    {
        return checkdate((int)$month, (int)$day, (int)$year);
    }

    // 郵便番号から住所を取得
    private function lookupAddressByPostalCode($postal_code)
    {
        $cleaned = str_replace('-', '', $postal_code);
        $url = "https://zipcloud.ibsnet.co.jp/api/search?zipcode=" . urlencode($cleaned);
        $json = @file_get_contents($url);
        $data = json_decode($json, true);

        if (!empty($data['results'][0])) {
            return [
                'prefecture' => $data['results'][0]['address1'],
                'city' => $data['results'][0]['address2'],
                'town' => $data['results'][0]['address3']
            ];
        }
        return null;
    }
}
