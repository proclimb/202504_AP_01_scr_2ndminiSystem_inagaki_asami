<?php

namespace App;


require_once 'Db.php';
require_once 'Address.php';


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Validator
{
    private array $error_message = [];
    private array $error_message_files = [];

    public function validateData(string $form, array $data): bool
    {
        $this->error_message = [];

        // 名前
        $name = trim($data['name'] ?? '');
        $noSpaceName = preg_replace('/[\s　]+/u', '', $name);
        if (empty($noSpaceName)) {
            $this->error_message['name'] = '名前が入力されていません';
        } elseif (preg_match('/^[\s　]|[\s　]$/u', $data['name'])) {
            $this->error_message['name'] = '名前の先頭または末尾にスペースを含めないでください';
        } elseif (mb_strlen($data['name']) > 20) {
            $this->error_message['name'] = '名前は20文字以内で入力してください';
        } elseif (!preg_match('/^[ぁ-んァ-ン一-龯ー\s　]+$/u', $data['name'])) {
            $this->error_message['name'] = '名前は日本語（漢字・ひらがな・カタカナ）のみで入力してください';
        }


        // ふりがな
        $kana = trim($data['kana'] ?? '');
        $noSpaceKana = preg_replace('/[\s　]+/u', '', $kana);
        if (empty($noSpaceKana)) {
            $this->error_message['kana'] = 'ふりがなが入力されていません';
        } elseif (preg_match('/^[\s　]|[\s　]$/u', $data['kana'])) {
            $this->error_message['kana'] = 'ふりがなの先頭または末尾にスペースを含めないでください';
        } elseif (mb_strlen($data['kana']) > 20) {
            $this->error_message['kana'] = 'ふりがなは20文字以内で入力してください';
        } elseif (!preg_match('/^[ぁ-んー\s　]+$/u', $data['kana'])) {
            $this->error_message['kana'] = 'ひらがなで入力してください';
        }

        // 生年月日
        if ($form !== "edit") {
            if (empty($data['birth_year']) || empty($data['birth_month']) || empty($data['birth_day'])) {
                $this->error_message['birth_date'] = '生年月日が入力されていません';
            } else {
                $year = (int)$data['birth_year'];
                $month = (int)$data['birth_month'];
                $day = (int)$data['birth_day'];

                // 年の範囲チェック（例：1900年～現在の年）
                $currentYear = (int)date('Y');
                if ($year < 1900 || $year > $currentYear) {
                    $this->error_message['birth_date'] = '生年月日の「年」が不正です（1900年〜' . $currentYear . 'の間で入力してください）';
                } elseif (!checkdate($month, $day, $year)) {
                    $this->error_message['birth_date'] = '生年月日が正しくありません';
                } else {
                    $birthDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    if ($birthDate > date('Y-m-d')) {
                        $this->error_message['birth_date'] = '生年月日に未来日は指定できません';
                    }
                }
            }
        }


        // 郵便番号
        if (empty($data['postal_code'])) {
            $this->error_message['postal_code'] = '郵便番号が入力されていません';
        } elseif (!preg_match('/^[0-9]{3}-[0-9]{4}$/', $data['postal_code'])) {
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
            // DBで郵便番号と住所の整合性チェック

        }

        // 電話番号
        if (empty($data['tel'])) {
            $this->error_message['tel'] = '電話番号が入力されていません';
        } elseif (
            !preg_match('/^0\d{1,4}-\d{1,4}-\d{3,4}$/', $data['tel']) ||
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

    public function validateFiles(array $files): bool
    {
        $this->error_message_files = [];

        foreach (['document1', 'document2'] as $doc) {
            if (!isset($files[$doc])) continue;
            if ($files[$doc]['error'] !== UPLOAD_ERR_NO_FILE) {
                if (!isValidFilesize($files[$doc])) {
                    $this->error_message_files[$doc] = '2MB以上はアップロードできません';
                } elseif (!isValidFileExtension($files[$doc])) {
                    $this->error_message_files[$doc] = 'ファイル形式は PNG, JPEG, JPG のいずれかのみ許可されています';
                }
            }
        }

        return empty($this->error_message_files);
    }

    public function getErrors(): array
    {
        return $this->error_message;
    }

    public function getErrorsFiles(): array
    {
        return $this->error_message_files;
    }

    private function isValidDate($year, $month, $day): bool
    {
        return checkdate((int)$month, (int)$day, (int)$year);
    }
}

// ↓ ファイルバリデーション関数（グローバル関数として残す）

function isValidFilesize(array $file): bool
{
    return $file['size'] <= 2 * 1024 * 1024; // 2MB
}

function isValidFileExtension(array $file): bool
{
    $allowed = ['jpeg', 'jpg', 'png'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    return in_array($ext, $allowed);
}
