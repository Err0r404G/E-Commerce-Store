<?php

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../models/UserModel.php';

class AuthController
{
    private UserModel $users;

    public function __construct(mysqli $conn)
    {
        $this->users = new UserModel($conn);
    }

    public function showSignup(array $errors = [], array $old = []): void
    {
        require __DIR__ . '/../views/auth/signup.php';
    }

    public function signup(): void
    {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? 'customer';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [];
        $old = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'role' => $role,
        ];

        if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
            $errors[] = 'Please fill in all required fields.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (!in_array($role, ['customer', 'seller'], true)) {
            $errors[] = 'Please choose a valid account type.';
        }

        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Password and confirm password do not match.';
        }

        if ($this->users->findByEmail($email)) {
            $errors[] = 'An account already exists with this email.';
        }

        $profilePath = $this->uploadProfileImage($errors);

        if ($errors) {
            $this->showSignup($errors, $old);
            return;
        }

        $created = $this->users->create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'role' => $role,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'profile_pic' => $profilePath,
        ]);

        if (!$created) {
            $this->showSignup(['Signup failed. Please try again.'], $old);
            return;
        }

        $_SESSION['success'] = 'Account created successfully. Please log in.';
        header('Location: /E-Commerce-Store/index.php?page=login');
        exit;
    }

    public function showLogin(array $errors = [], array $old = []): void
    {
        require __DIR__ . '/../views/auth/login.php';
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $old = ['email' => $email];
        $errors = [];

        if ($email === '' || $password === '') {
            $errors[] = 'Please enter email and password.';
        }

        $user = $email !== '' ? $this->users->findByEmail($email) : null;

        if (!$errors && (!$user || !password_verify($password, $user['password_hash']))) {
            $errors[] = 'Invalid email or password.';
        }

        if (!$errors && (int) $user['is_active'] !== 1) {
            $errors[] = 'This account is not active.';
        }

        if ($errors) {
            $this->showLogin($errors, $old);
            return;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'role' => $user['role'],
            'profile_pic' => $user['profile_pic'],
        ];

        header('Location: ' . $this->dashboardUrl($user['role']));
        exit;
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();

        header('Location: /E-Commerce-Store/index.php');
        exit;
    }

    private function uploadProfileImage(array &$errors): ?string
    {
        if (empty($_FILES['profile_pic']['name'])) {
            return null;
        }

        if ($_FILES['profile_pic']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Profile picture upload failed.';
            return null;
        }

        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mimeType = mime_content_type($_FILES['profile_pic']['tmp_name']);

        if (!isset($allowedTypes[$mimeType])) {
            $errors[] = 'Profile picture must be JPG, PNG, or WEBP.';
            return null;
        }

        $uploadDir = __DIR__ . '/../../public/uploads/profiles';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = uniqid('profile_', true) . '.' . $allowedTypes[$mimeType];
        $targetPath = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetPath)) {
            $errors[] = 'Could not save profile picture.';
            return null;
        }

        return 'public/uploads/profiles/' . $fileName;
    }

    private function dashboardUrl(string $role): string
    {
        if ($role === 'admin') {
            return '/E-Commerce-Store/app/views/admin/AdminDashboard.php';
        }

        return '/E-Commerce-Store/index.php';
    }
}
