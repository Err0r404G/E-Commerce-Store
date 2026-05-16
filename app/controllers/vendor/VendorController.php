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
        $this->users->ensureSellerForVendor($vendorId);
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
        $this->users->ensureSellerForVendor($vendorId);
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
        $this->users->ensureSellerForVendor($vendorId);
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

    public function showCouponsAjax(): void
    {
        $seller = $this->requireSeller();
        $coupons = $this->users->getVendorCoupons((int) $seller['id']);

        require __DIR__ . '/../../views/vendor/partials/coupons.php';
    }

    public function showOrdersAjax(): void
    {
        $seller = $this->requireSeller();
        $allowedStatuses = ['pending', 'confirmed', 'shipped', 'delivered'];
        $selectedStatus = strtolower(trim($_GET['status'] ?? ''));

        if (!in_array($selectedStatus, $allowedStatuses, true)) {
            $selectedStatus = '';
        }

        $orders = $this->users->getVendorOrderItems((int) $seller['id'], $selectedStatus);
        $orderItemsByOrder = $this->users->getVendorOrderItemsGroupedByOrderIds(
            (int) $seller['id'],
            array_column($orders, 'order_id')
        );

        require __DIR__ . '/../../views/vendor/partials/orders.php';
    }

    public function showReturnsAjax(): void
    {
        $seller = $this->requireSeller();
        $returnRequests = $this->users->getVendorReturnRequests((int) $seller['id']);

        require __DIR__ . '/../../views/vendor/partials/returns.php';
    }

    public function showReviewsAjax(): void
    {
        $seller = $this->requireSeller();
        $reviews = $this->users->getVendorReviews((int) $seller['id']);

        require __DIR__ . '/../../views/vendor/partials/reviews.php';
    }

    public function showAnalyticsAjax(): void
    {
        $seller = $this->requireSeller();
        $analytics = $this->users->getVendorAnalytics((int) $seller['id']);
        $earnings = $this->users->getVendorEarnings((int) $seller['id']);

        require __DIR__ . '/../../views/vendor/partials/analytics.php';
    }

    public function showEarningsAjax(): void
    {
        $seller = $this->requireSeller();
        $earnings = $this->users->getVendorEarnings((int) $seller['id']);

        require __DIR__ . '/../../views/vendor/partials/earnings.php';
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
        $additionalImages = [];
        if (!$errors) {
            $imagePath = $this->uploadImage('product_image', 'products', $errors);
            $additionalImages = $this->uploadMultipleImages('additional_images', 'products', 4, $errors);
        }

        if ($errors) {
            $this->jsonResponse(['success' => false, 'message' => implode(' ', $errors)], 422);
            return;
        }

        $productId = $this->users->saveVendorProduct((int) $seller['id'], [
            'product_id' => (int) ($_POST['product_id'] ?? 0),
            'category_id' => $categoryId,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'stock_qty' => $stockQty,
            'primary_image_path' => $imagePath,
            'is_available' => isset($_POST['is_available']) ? 1 : 0,
        ]);

        if (!$productId) {
            $this->jsonResponse(['success' => false, 'message' => 'Product save failed.'], 422);
            return;
        }

        if ($additionalImages) {
            $imagesSaved = $this->users->replaceProductImages((int) $seller['id'], $productId, $additionalImages);

            if (!$imagesSaved) {
                $this->jsonResponse(['success' => false, 'message' => 'Product saved, but additional images failed.'], 422);
                return;
            }
        }

        $this->jsonResponse(['success' => true, 'message' => 'Product saved.']);
    }

    public function couponAction(): void
    {
        $seller = $this->requireSeller();
        $action = $_POST['coupon_action'] ?? 'save';

        if ($action === 'toggle') {
            $couponId = (int) ($_POST['coupon_id'] ?? 0);

            if ($couponId <= 0) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid coupon.'], 422);
                return;
            }

            $updated = $this->users->toggleVendorCoupon((int) $seller['id'], $couponId);
            $this->jsonResponse(['success' => $updated, 'message' => $updated ? 'Coupon status updated.' : 'Coupon update failed.'], $updated ? 200 : 422);
            return;
        }

        $code = strtoupper(trim($_POST['code'] ?? ''));
        $discountPct = (float) ($_POST['discount_pct'] ?? 0);
        $maxUses = (int) ($_POST['max_uses'] ?? 0);
        $validUntil = trim($_POST['valid_until'] ?? '');
        $errors = [];

        if ($code === '' || !preg_match('/^[A-Z0-9_-]{3,50}$/', $code)) {
            $errors[] = 'Coupon code must be 3-50 characters using letters, numbers, dashes, or underscores.';
        }

        if ($discountPct <= 0 || $discountPct > 100) {
            $errors[] = 'Discount percentage must be between 1 and 100.';
        }

        if ($maxUses <= 0) {
            $errors[] = 'Maximum uses must be greater than 0.';
        }

        if ($validUntil === '' || !DateTime::createFromFormat('Y-m-d', $validUntil)) {
            $errors[] = 'Please choose a valid date.';
        }

        if ($errors) {
            $this->jsonResponse(['success' => false, 'message' => implode(' ', $errors)], 422);
            return;
        }

        $saved = $this->users->saveVendorCoupon((int) $seller['id'], [
            'coupon_id' => (int) ($_POST['coupon_id'] ?? 0),
            'code' => $code,
            'discount_pct' => $discountPct,
            'max_uses' => $maxUses,
            'valid_until' => $validUntil,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ]);

        $this->jsonResponse(['success' => $saved, 'message' => $saved ? 'Coupon saved.' : 'Coupon save failed.'], $saved ? 200 : 422);
    }

    public function orderAction(): void
    {
        $seller = $this->requireSeller();
        $action = $_POST['order_action'] ?? '';
        $orderItemId = (int) ($_POST['order_item_id'] ?? 0);
        $trackingNote = trim($_POST['tracking_note'] ?? '');

        if ($orderItemId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid order item.'], 422);
            return;
        }

        if ($action === 'confirm') {
            $updated = $this->users->updateVendorOrderItemStatus((int) $seller['id'], $orderItemId, 'confirmed', null);
            $this->jsonResponse(['success' => $updated, 'message' => $updated ? 'Order item marked as processing.' : 'Order item update failed.'], $updated ? 200 : 422);
            return;
        }

        if ($action === 'ship') {
            if ($trackingNote === '') {
                $this->jsonResponse(['success' => false, 'message' => 'Please add a tracking note before marking as shipped.'], 422);
                return;
            }

            $updated = $this->users->updateVendorOrderItemStatus((int) $seller['id'], $orderItemId, 'shipped', $trackingNote);
            $this->jsonResponse(['success' => $updated, 'message' => $updated ? 'Order item marked as shipped.' : 'Order item update failed.'], $updated ? 200 : 422);
            return;
        }

        $this->jsonResponse(['success' => false, 'message' => 'Invalid order action.'], 422);
    }

    public function returnAction(): void
    {
        $seller = $this->requireSeller();
        $action = $_POST['return_action'] ?? '';
        $returnRequestId = (int) ($_POST['return_request_id'] ?? 0);
        $reason = trim($_POST['vendor_response_reason'] ?? '');

        if ($returnRequestId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid return request.'], 422);
            return;
        }

        if ($reason === '') {
            $this->jsonResponse(['success' => false, 'message' => 'Please add a reason for this decision.'], 422);
            return;
        }

        $status = $action === 'approve' ? 'approved' : 'rejected';
        $updated = $this->users->updateVendorReturnRequest((int) $seller['id'], $returnRequestId, $status, $reason);
        $message = $updated
            ? 'Return request ' . $status . '.'
            : 'Return request could not be updated.';

        $this->jsonResponse(['success' => $updated, 'message' => $message], $updated ? 200 : 422);
    }

    public function reviewAction(): void
    {
        $seller = $this->requireSeller();
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        $reply = trim($_POST['seller_reply'] ?? '');

        if ($reviewId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid review.'], 422);
            return;
        }

        if ($reply === '') {
            $this->jsonResponse(['success' => false, 'message' => 'Please write a reply before saving.'], 422);
            return;
        }

        $updated = $this->users->replyToVendorReview((int) $seller['id'], $reviewId, $reply);
        $this->jsonResponse(['success' => $updated, 'message' => $updated ? 'Reply saved.' : 'Reply save failed.'], $updated ? 200 : 422);
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

    private function uploadMultipleImages(string $fieldName, string $folder, int $maxFiles, array &$errors): array
    {
        if (empty($_FILES[$fieldName]['name']) || !is_array($_FILES[$fieldName]['name'])) {
            return [];
        }

        $names = array_filter($_FILES[$fieldName]['name'], static fn ($name) => $name !== '');

        if (count($names) > $maxFiles) {
            $errors[] = 'You can upload up to ' . $maxFiles . ' additional images.';
            return [];
        }

        $paths = [];

        foreach ($_FILES[$fieldName]['name'] as $index => $name) {
            if ($name === '') {
                continue;
            }

            $singleFile = [
                'name' => $_FILES[$fieldName]['name'][$index],
                'type' => $_FILES[$fieldName]['type'][$index],
                'tmp_name' => $_FILES[$fieldName]['tmp_name'][$index],
                'error' => $_FILES[$fieldName]['error'][$index],
                'size' => $_FILES[$fieldName]['size'][$index],
            ];

            $paths[] = $this->saveUploadedFile($singleFile, $folder, $errors);
        }

        return array_values(array_filter($paths));
    }

    private function saveUploadedFile(array $file, string $folder, array &$errors): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload failed.';
            return null;
        }

        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mimeType = mime_content_type($file['tmp_name']);

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

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $errors[] = 'Could not save uploaded image.';
            return null;
        }

        return 'public/uploads/' . $folder . '/' . $fileName;
    }

    private function saveProfileFromRequest(): array
    {
        $vendorId = (int) ($_SESSION['user']['id'] ?? 0);
        $this->users->ensureSellerForVendor($vendorId);
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
        $seller = $this->users->ensureSellerForVendor($vendorId);

        if (!$seller) {
            http_response_code(403);
            exit('<p class="admin-error">Seller profile not found. Please contact admin support.</p>');
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
