<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!class_exists('UserAddress')) {
    class UserAddress
    {
        private $pdo;

        public function __construct($pdo)
        {
            $this->pdo = $pdo;
        }

        // 住所登録
        public function create(array $data): bool
        {
            $sql = "INSERT INTO user_addresses (user_id, postal_code, prefecture, city_town, building, created_at)
                VALUES (:user_id, :postal_code, :prefecture, :city_town, :building, NOW())";

            try {
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([
                    ':user_id'     => $data['user_id'],
                    ':postal_code' => $data['postal_code'],
                    ':prefecture'  => $data['prefecture'],
                    ':city_town'   => $data['city_town'],
                    ':building'    => $data['building'],
                ]);
            } catch (PDOException $e) {
                return false;
            }
        }

        // ユーザーIDから住所取得
        public function findByUserId(int $userId): ?array
        {
            $sql = "SELECT * FROM user_addresses WHERE user_id = :user_id LIMIT 1";

            try {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':user_id' => $userId]);
                $address = $stmt->fetch(PDO::FETCH_ASSOC);
                return $address ?: null;
            } catch (PDOException $e) {
                return null;
            }
        }

        // ユーザーIDで住所更新
        public function updateByUserId(array $data): bool
        {
            $sql = "UPDATE user_addresses
                SET postal_code = :postal_code,
                    prefecture  = :prefecture,
                    city_town   = :city_town,
                    building    = :building,
                    created_at  = NOW()
                WHERE user_id = :user_id";

            try {
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([
                    ':postal_code' => $data['postal_code'],
                    ':prefecture'  => $data['prefecture'],
                    ':city_town'   => $data['city_town'],
                    ':building'    => $data['building'],
                    ':user_id'     => $data['user_id']
                ]);
            } catch (PDOException $e) {
                return false;
            }
        }
    }
}
