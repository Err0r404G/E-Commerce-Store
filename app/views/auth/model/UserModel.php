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

    public function findVendorProfile(int $userId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT u.id, u.name, u.email, u.phone, u.password_hash, u.role, u.profile_pic,
                    s.shop_name, s.shop_description, s.shop_logo_path, s.address
             FROM users u
             LEFT JOIN sellers s ON s.user_id = u.id
             WHERE u.id = ? AND u.role = 'vendor'
             LIMIT 1"
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $result = $stmt->get_result();
        $profile = $result->fetch_assoc();
        $stmt->close();

        return $profile ?: null;
    }

    public function emailExistsForAnotherUser(string $email, int $userId): bool
    {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
        $stmt->bind_param("si", $email, $userId);
        $stmt->execute();

        $exists = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $exists;
    }

    public function updateVendorProfile(int $userId, array $data): bool
    {
        $this->conn->begin_transaction();

        $profilePic = $data['profile_pic'] ?? null;
        $passwordHash = $data['password_hash'] ?? null;

        if ($profilePic !== null && $passwordHash !== null) {
            $stmt = $this->conn->prepare(
                "UPDATE users
                 SET name = ?, email = ?, phone = ?, profile_pic = ?, password_hash = ?
                 WHERE id = ? AND role = 'vendor'"
            );
            $stmt->bind_param("sssssi", $data['name'], $data['email'], $data['phone'], $profilePic, $passwordHash, $userId);
        } elseif ($profilePic !== null) {
            $stmt = $this->conn->prepare(
                "UPDATE users
                 SET name = ?, email = ?, phone = ?, profile_pic = ?
                 WHERE id = ? AND role = 'vendor'"
            );
            $stmt->bind_param("ssssi", $data['name'], $data['email'], $data['phone'], $profilePic, $userId);
        } elseif ($passwordHash !== null) {
            $stmt = $this->conn->prepare(
                "UPDATE users
                 SET name = ?, email = ?, phone = ?, password_hash = ?
                 WHERE id = ? AND role = 'vendor'"
            );
            $stmt->bind_param("ssssi", $data['name'], $data['email'], $data['phone'], $passwordHash, $userId);
        } else {
            $stmt = $this->conn->prepare(
                "UPDATE users
                 SET name = ?, email = ?, phone = ?
                 WHERE id = ? AND role = 'vendor'"
            );
            $stmt->bind_param("sssi", $data['name'], $data['email'], $data['phone'], $userId);
        }

        $userUpdated = $stmt->execute();
        $stmt->close();

        if (!$userUpdated) {
            $this->conn->rollback();
            return false;
        }

        $sellerStmt = $this->conn->prepare(
            "INSERT INTO sellers (user_id, shop_name, shop_description, shop_logo_path, address, is_approved)
             VALUES (?, ?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE
                shop_name = VALUES(shop_name),
                shop_description = VALUES(shop_description),
                shop_logo_path = COALESCE(VALUES(shop_logo_path), shop_logo_path),
                address = VALUES(address)"
        );

        $sellerStmt->bind_param(
            "issss",
            $userId,
            $data['shop_name'],
            $data['shop_description'],
            $profilePic,
            $data['shop_address']
        );

        $sellerUpdated = $sellerStmt->execute();
        $sellerStmt->close();

        if (!$sellerUpdated) {
            $this->conn->rollback();
            return false;
        }

        $this->conn->commit();
        return true;
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
