<?php

class UserModel
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        return $user ?: null;
    }

    public function create(array $data): bool
    {
        $this->conn->begin_transaction();

        $stmt = $this->conn->prepare(
            "INSERT INTO users (name, email, password_hash, phone, role, profile_pic, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $isActive = (int) ($data['is_active'] ?? 1);

        $stmt->bind_param(
            "ssssssi",
            $data['name'],
            $data['email'],
            $data['password_hash'],
            $data['phone'],
            $data['role'],
            $data['profile_pic'],
            $isActive
        );

        $created = $stmt->execute();
        $userId = (int) $this->conn->insert_id;
        $stmt->close();

        if (!$created) {
            $this->conn->rollback();
            return false;
        }

        if (($data['role'] ?? '') === 'vendor') {
            $sellerStmt = $this->conn->prepare(
                "INSERT INTO sellers (user_id, shop_name, shop_description, shop_logo_path, address, is_approved)
                 VALUES (?, ?, ?, ?, ?, 0)"
            );

            $sellerStmt->bind_param(
                "issss",
                $userId,
                $data['shop_name'],
                $data['shop_description'],
                $data['shop_logo_path'],
                $data['shop_address']
            );

            $sellerCreated = $sellerStmt->execute();
            $sellerStmt->close();

            if (!$sellerCreated) {
                $this->conn->rollback();
                return false;
            }
        }

        $this->conn->commit();
        return true;
    }
}
