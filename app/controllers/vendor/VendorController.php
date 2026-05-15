<?php

require_once __DIR__ . '/../../views/auth/model/UserModel.php';

class VendorController
{
    private UserModel $users;

    public function __construct(mysqli $conn)
    {
        $this->users = new UserModel($conn);
    }

    public function showProfile(array $errors = [], array $success = [], ?array $profileOverride = null): void
    {
        $vendorId = (int) ($_SESSION['user']['id'] ?? 0);
        $profile = $profileOverride ?? $this->users->findVendorProfile($vendorId);

        if (!$profile) {
            header('Location: /E-Commerce-Store/index.php?page=login');
            exit;
        }

        require __DIR__ . '/../../views/vendor/view/vendor_profile.php';
    }

    public function updateProfile(): void
    {
        $vendorId = (int) ($_SESSION['user']['id'] ?? 0);
        $profile = $this->users->findVendorProfile($vendorId);

        if (!$profile) {
            header('Location: /E-Commerce-Store/index.php?page=login');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $shopName = trim($_POST['shop_name'] ?? '');
        $shopDescription = trim($_POST['shop_description'] ?? '');
        $shopAddress = trim($_POST['shop_address'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [];

        if ($name === '' || $email === '' || $shopName === '' || $shopDescription === '' || $shopAddress === '') {
            $errors[] = 'Please fill in all required profile and shop fields.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if ($this->users->emailExistsForAnotherUser($email, $vendorId)) {
            $errors[] = 'Another account already uses this email.';
        }

        $passwordHash = null;
        $wantsPasswordChange = $currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '';

        if ($wantsPasswordChange) {
            if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                $errors[] = 'Fill in current password, new password, and confirm password to change password.';
            } elseif (!password_verify($currentPassword, $profile['password_hash'])) {
                $errors[] = 'Current password is incorrect.';
            } elseif (strlen($newPassword) < 6) {
                $errors[] = 'New password must be at least 6 characters.';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'New password and confirm password do not match.';
            } else {
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            }
        }

        $imagePath = null;

        if (!$errors) {
            $imagePath = $this->uploadVendorImage($errors);
        }

        if ($errors) {
            $profile = array_merge($profile, [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'shop_name' => $shopName,
                'shop_description' => $shopDescription,
                'address' => $shopAddress,
            ]);
            $this->showProfile($errors, [], $profile);
            return;
        }

        $updated = $this->users->updateVendorProfile($vendorId, [
            'name' => $name,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'profile_pic' => $imagePath,
            'password_hash' => $passwordHash,
            'shop_name' => $shopName,
            'shop_description' => $shopDescription,
            'shop_address' => $shopAddress,
        ]);

        if (!$updated) {
            $this->showProfile(['Could not update profile. Please try again.']);
            return;
        }

        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['phone'] = $phone !== '' ? $phone : null;

        if ($imagePath !== null) {
            $_SESSION['user']['profile_pic'] = $imagePath;
        }

        $this->showProfile([], ['Profile updated successfully.']);
    }

    private function uploadVendorImage(array &$errors): ?string
    {
        if (empty($_FILES['profile_pic']['name'])) {
            return null;
        }

        if ($_FILES['profile_pic']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload failed.';
            return null;
        }

        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mimeType = mime_content_type($_FILES['profile_pic']['tmp_name']);

        if (!isset($allowedTypes[$mimeType])) {
            $errors[] = 'Image must be JPG, PNG, or WEBP.';
            return null;
        }

        $uploadDir = __DIR__ . '/../../../public/uploads/profiles';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = uniqid('profile_', true) . '.' . $allowedTypes[$mimeType];
        $targetPath = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetPath)) {
            $errors[] = 'Could not save uploaded image.';
            return null;
        }

        return 'public/uploads/profiles/' . $fileName;
    }
}
