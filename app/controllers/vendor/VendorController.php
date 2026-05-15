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

    public function showInventoryAjax(): void
    {
        $seller = $this->requireSeller();
        $categories = $this->users->getPlatformCategories();
        $products = $this->users->getVendorProducts((int) $seller['id']);

        require __DIR__ . '/../../views/vendor/partials/inventory.php';
    }

    public function showSettingsAjax(): void
    {
        $vendorId = (int) ($_SESSION['user']['id'] ?? 0);
        $profile = $this->users->findVendorProfile($vendorId);
        $errors = [];
        $success = [];

        if (!$profile) {
            http_response_code(404);
            echo '<p class="admin-error">Vendor profile not found.</p>';
            return;
        }

        require __DIR__ . '/../../views/vendor/partials/settings.php';
    }

    public function profileAction(): void
    {
        $result = $this->saveProfileFromRequest();
        $this->jsonResponse($result, $result['success'] ? 200 : 422);
    }

    public function productAction(): void
    {
        $seller = $this->requireSeller();
        $action = $_POST['product_action'] ?? 'save';

        if ($action === 'delete') {
            $productId = (int) ($_POST['product_id'] ?? 0);

            if ($productId <= 0) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid product.'], 422);
                return;
            }

            $deleted = $this->users->deleteVendorProduct((int) $seller['id'], $productId);
            $this->jsonResponse(['success' => $deleted, 'message' => $deleted ? 'Product deleted.' : 'Delete failed.'], $deleted ? 200 : 422);
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $price = (float) ($_POST['price'] ?? 0);
        $stockQty = (int) ($_POST['stock_qty'] ?? 0);
        $errors = [];

        if ($name === '' || $description === '' || $categoryId <= 0 || $price <= 0 || $stockQty < 0) {
            $errors[] = 'Please fill in valid product details.';
        }

        $imagePath = null;
        if (!$errors) {
            $imagePath = $this->uploadImage('product_image', 'products', $errors);
        }

        if ($errors) {
            $this->jsonResponse(['success' => false, 'message' => implode(' ', $errors)], 422);
            return;
        }

        $saved = $this->users->saveVendorProduct((int) $seller['id'], [
            'product_id' => (int) ($_POST['product_id'] ?? 0),
            'category_id' => $categoryId,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'stock_qty' => $stockQty,
            'primary_image_path' => $imagePath,
            'is_available' => isset($_POST['is_available']) ? 1 : 0,
        ]);

        $this->jsonResponse(['success' => $saved, 'message' => $saved ? 'Product saved.' : 'Product save failed.'], $saved ? 200 : 422);
    }

    private function uploadVendorImage(array &$errors): ?string
    {
        return $this->uploadImage('profile_pic', 'profiles', $errors);
    }

    private function uploadImage(string $fieldName, string $folder, array &$errors): ?string
    {
        if (empty($_FILES[$fieldName]['name'])) {
            return null;
        }

        if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload failed.';
            return null;
        }

        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mimeType = mime_content_type($_FILES[$fieldName]['tmp_name']);

        if (!isset($allowedTypes[$mimeType])) {
            $errors[] = 'Image must be JPG, PNG, or WEBP.';
            return null;
        }

        $uploadDir = __DIR__ . '/../../../public/uploads/' . $folder;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = uniqid(rtrim($folder, 's') . '_', true) . '.' . $allowedTypes[$mimeType];
        $targetPath = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetPath)) {
            $errors[] = 'Could not save uploaded image.';
            return null;
        }

        return 'public/uploads/' . $folder . '/' . $fileName;
    }

    private function saveProfileFromRequest(): array
    {
        $vendorId = (int) ($_SESSION['user']['id'] ?? 0);
        $profile = $this->users->findVendorProfile($vendorId);

        if (!$profile) {
            return ['success' => false, 'message' => 'Vendor profile not found.'];
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
            return ['success' => false, 'message' => implode(' ', $errors)];
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
            return ['success' => false, 'message' => 'Could not update profile. Please try again.'];
        }

        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['phone'] = $phone !== '' ? $phone : null;

        if ($imagePath !== null) {
            $_SESSION['user']['profile_pic'] = $imagePath;
        }

        return [
            'success' => true,
            'message' => 'Profile updated successfully.',
            'name' => $name,
            'role' => 'VENDOR',
            'profile_pic' => $imagePath ?? ($_SESSION['user']['profile_pic'] ?? null),
        ];
    }

    private function requireSeller(): array
    {
        $vendorId = (int) ($_SESSION['user']['id'] ?? 0);
        $seller = $this->users->findSellerByUserId($vendorId);

        if (!$seller) {
            http_response_code(403);
            exit('Seller profile not found.');
        }

        return $seller;
    }

    private function jsonResponse(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload);
    }
}
