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
        $stmt->close();

        return $created;
    }
}
