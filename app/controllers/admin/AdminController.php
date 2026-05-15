<?php

require_once __DIR__ . '/../../models/admin/AdminModel.php';

class AdminController
{
    private AdminModel $adminModel;

    public function __construct(mysqli $conn)
    {
        $this->adminModel = new AdminModel($conn);
    }

    public function showDashboard(): void
    {
        $dashboardMetrics = $this->adminModel->getDashboardMetrics();
        include __DIR__ . '/../../views/admin/AdminDashboard.php';
    }

    public function showDashboardHome(): void
    {
        $dashboardMetrics = $this->adminModel->getDashboardMetrics();
        include __DIR__ . '/../../views/admin/AdminHome.php';
    }

    public function showVendorApprovals(): void
    {
        [$vendors, $counts] = $this->adminModel->getVendorApprovalData();
        include __DIR__ . '/../../views/admin/VendorApproval.php';
    }

    public function vendorApprovalAction(): void
    {
        $this->jsonHeader();

        if (!$this->isAdmin()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized request.'], 403);
        }

        $vendorId = (int) ($_POST['vendor_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $reason = trim($_POST['reason'] ?? '');
        $reason = $reason !== '' ? $reason : null;

        if ($vendorId <= 0 || !in_array($action, ['approve', 'reject', 'suspend', 'reactivate'], true)) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid approval request.'], 422);
        }

        if (in_array($action, ['reject', 'suspend'], true) && $reason === null) {
            $this->jsonResponse(['success' => false, 'message' => 'Please provide a reason for this action.'], 422);
        }

        $result = $this->adminModel->setVendorApproval($vendorId, $action, $reason);
        $this->jsonResponse($result, (int) ($result['status'] ?? 200));
    }

    public function showCategoryManagement(): void
    {
        [$categories, $categoryTree, $categoryStats] = $this->adminModel->getCategoryManagementData();
        include __DIR__ . '/../../views/admin/AdminCatagory.php';
    }

    public function categoryAction(): void
    {
        $this->jsonHeader();

        if (!$this->isAdmin()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized request.'], 403);
        }

        $action = $_POST['category_action'] ?? '';
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $parentId = (int) ($_POST['parent_id'] ?? 0);
        $parentId = $parentId > 0 ? $parentId : null;
        $description = $description !== '' ? $description : null;

        if ($action === 'delete') {
            if ($categoryId <= 0) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid category.'], 422);
            }

            $result = $this->adminModel->deleteCategory($categoryId);
            $this->jsonResponse($result, (int) ($result['status'] ?? 200));
        }

        if ($name === '') {
            $this->jsonResponse(['success' => false, 'message' => 'Category name is required.'], 422);
        }

        if ($action === 'add') {
            $result = $this->adminModel->createCategory($name, $description, $parentId);
            $this->jsonResponse($result, (int) ($result['status'] ?? 200));
        }

        if ($action === 'update') {
            if ($categoryId <= 0) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid category.'], 422);
            }

            $result = $this->adminModel->updateCategory($categoryId, $name, $description, $parentId);
            $this->jsonResponse($result, (int) ($result['status'] ?? 200));
        }

        $this->jsonResponse(['success' => false, 'message' => 'Invalid category action.'], 422);
    }

    public function showProductManagement(): void
    {
        [$products, $categories, $sellers, $productStats] = $this->adminModel->getProductManagementData();
        include __DIR__ . '/../../views/admin/ProductManagement.php';
    }

    public function productAction(): void
    {
        $this->jsonHeader();

        if (!$this->isAdmin()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized request.'], 403);
        }

        $productId = (int) ($_POST['product_id'] ?? 0);
        $action = $_POST['action'] ?? '';

        if ($productId <= 0 || !in_array($action, ['deactivate', 'activate'], true)) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid product request.'], 422);
        }

        $result = $this->adminModel->setProductAvailability($productId, $action === 'activate');
        $this->jsonResponse($result, (int) ($result['status'] ?? 200));
    }

    public function showOrderManagement(): void
    {
        [$orders, $sellers, $customers, $orderStats] = $this->adminModel->getOrderManagementData();
        include __DIR__ . '/../../views/admin/OrderManagement.php';
    }

    public function showCustomerAccounts(): void
    {
        $role = 'customer';
        $pageTitle = 'Manage Customers';
        $pageDescription = 'Search, view, deactivate, and reactivate customer accounts.';
        [$accounts, $accountCounts] = $this->adminModel->getAccountManagementData($role);
        include __DIR__ . '/../../views/admin/AccountManagement.php';
    }

    public function showDeliveryManagerAccounts(): void
    {
        $role = 'delivery_manager';
        $pageTitle = 'Manage Delivery Managers';
        $pageDescription = 'Search, view, deactivate, and reactivate delivery manager accounts.';
        [$accounts, $accountCounts] = $this->adminModel->getAccountManagementData($role);
        include __DIR__ . '/../../views/admin/AccountManagement.php';
    }

    public function accountAction(): void
    {
        $this->jsonHeader();

        if (!$this->isAdmin()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized request.'], 403);
        }

        $userId = (int) ($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? '';
        $action = $_POST['action'] ?? '';

        if ($userId <= 0 || !in_array($role, ['customer', 'delivery_manager'], true)) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid account request.'], 422);
        }

        $result = $this->adminModel->setAccountStatus($userId, $role, $action);
        $this->jsonResponse($result, (int) ($result['status'] ?? 200));
    }

    public function createDeliveryManagerAction(): void
    {
        $this->jsonHeader();

        if (!$this->isAdmin()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized request.'], 403);
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $phone = $phone !== '' ? $phone : null;

        if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
            $this->jsonResponse(['success' => false, 'message' => 'Please fill in all required fields.'], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(['success' => false, 'message' => 'Please enter a valid email address.'], 422);
        }

        if (strlen($password) < 6) {
            $this->jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters.'], 422);
        }

        if ($password !== $confirmPassword) {
            $this->jsonResponse(['success' => false, 'message' => 'Password and confirm password do not match.'], 422);
        }

        $result = $this->adminModel->createDeliveryManager($name, $email, $phone, $password);
        $this->jsonResponse($result, (int) ($result['status'] ?? 200));
    }

    public function showDisputes(): void
    {
        [$disputes, $disputeCounts] = $this->adminModel->getDisputeManagementData();
        include __DIR__ . '/../../views/admin/AdminDispute.php';
    }

    public function showPlatformCoupons(): void
    {
        $coupons = $this->adminModel->getPlatformCoupons();
        include __DIR__ . '/../../views/admin/PlatformCoupons.php';
    }

    public function showSettings(): void
    {
        $adminId = (int) ($_SESSION['user']['id'] ?? 0);
        $profile = $this->adminModel->getAdminProfile($adminId);

        if (!$profile) {
            http_response_code(404);
            echo '<p class="admin-error">Admin profile not found.</p>';
            return;
        }

        include __DIR__ . '/../../views/admin/AdminSettings.php';
    }

    public function settingsAction(): void
    {
        $this->jsonHeader();

        if (!$this->isAdmin()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized request.'], 403);
        }

        $adminId = (int) ($_SESSION['user']['id'] ?? 0);
        $profile = $this->adminModel->getAdminProfile($adminId);

        if (!$profile) {
            $this->jsonResponse(['success' => false, 'message' => 'Admin profile not found.'], 404);
        }

        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $errors = [];

        if ($name === '') {
            $errors[] = 'Full name is required.';
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
            $imagePath = $this->uploadAdminImage($errors);
        }

        if ($errors) {
            $this->jsonResponse(['success' => false, 'message' => implode(' ', $errors)], 422);
        }

        $result = $this->adminModel->updateAdminProfile($adminId, [
            'name' => $name,
            'phone' => $phone !== '' ? $phone : null,
            'profile_pic' => $imagePath,
            'password_hash' => $passwordHash,
        ]);

        if (!$result['success']) {
            $this->jsonResponse($result, (int) ($result['status'] ?? 422));
        }

        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['phone'] = $phone !== '' ? $phone : null;

        if ($imagePath !== null) {
            $_SESSION['user']['profile_pic'] = $imagePath;
        }

        $this->jsonResponse([
            'success' => true,
            'message' => 'Settings updated successfully.',
            'name' => $name,
            'phone' => $phone !== '' ? $phone : null,
            'profile_pic' => $imagePath ?? ($_SESSION['user']['profile_pic'] ?? null),
        ]);
    }

    public function platformCouponAction(): void
    {
        $this->jsonHeader();

        if (!$this->isAdmin()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized request.'], 403);
        }

        $action = $_POST['coupon_action'] ?? 'save';

        if ($action === 'toggle') {
            $couponId = (int) ($_POST['coupon_id'] ?? 0);

            if ($couponId <= 0) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid coupon.'], 422);
            }

            $result = $this->adminModel->togglePlatformCoupon($couponId);
            $this->jsonResponse($result, (int) ($result['status'] ?? 200));
        }

        $code = strtoupper(trim($_POST['code'] ?? ''));
        $discountPct = (float) ($_POST['discount_pct'] ?? 0);
        $maxUses = (int) ($_POST['max_uses'] ?? 0);
        $validUntil = trim($_POST['valid_until'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $errors = [];

        if (!preg_match('/^[A-Z0-9_-]{3,50}$/', $code)) {
            $errors[] = 'Coupon code must be 3-50 characters using letters, numbers, dashes, or underscores.';
        }

        if ($discountPct <= 0 || $discountPct > 100) {
            $errors[] = 'Discount percentage must be between 1 and 100.';
        }

        if ($maxUses <= 0) {
            $errors[] = 'Maximum uses must be at least 1.';
        }

        if ($validUntil === '' || strtotime($validUntil) === false) {
            $errors[] = 'Valid until date is required.';
        }

        if ($errors) {
            $this->jsonResponse(['success' => false, 'message' => implode(' ', $errors)], 422);
        }

        $result = $this->adminModel->savePlatformCoupon([
            'coupon_id' => (int) ($_POST['coupon_id'] ?? 0),
            'code' => $code,
            'discount_pct' => $discountPct,
            'max_uses' => $maxUses,
            'valid_until' => $validUntil,
            'is_active' => $isActive,
        ]);

        $this->jsonResponse($result, (int) ($result['status'] ?? 200));
    }

    public function disputeAction(): void
    {
        $this->jsonHeader();

        if (!$this->isAdmin()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized request.'], 403);
        }

        $disputeId = (int) ($_POST['dispute_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $adminNote = trim($_POST['admin_note'] ?? '');
        $adminNote = $adminNote !== '' ? $adminNote : null;

        if ($disputeId <= 0 || !in_array($action, ['resolve', 'reopen'], true)) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid dispute request.'], 422);
        }

        if ($action === 'resolve' && ($adminNote === null || strlen($adminNote) < 5)) {
            $this->jsonResponse(['success' => false, 'message' => 'Write a resolution note before closing the dispute.'], 422);
        }

        $status = $action === 'resolve' ? 'resolved' : 'open';
        $result = $this->adminModel->setDisputeStatus($disputeId, $status, $adminNote);
        $this->jsonResponse($result, (int) ($result['status'] ?? 200));
    }

    private function isAdmin(): bool
    {
        return ($_SESSION['user']['role'] ?? null) === 'admin';
    }

    private function jsonHeader(): void
    {
        header('Content-Type: application/json');
    }

    private function jsonResponse(array $payload, int $status = 200): void
    {
        http_response_code($status);
        unset($payload['status']);
        echo json_encode($payload);
        exit;
    }

    private function uploadAdminImage(array &$errors): ?string
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

        $fileName = uniqid('admin_', true) . '.' . $allowedTypes[$mimeType];
        $targetPath = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetPath)) {
            $errors[] = 'Could not save uploaded image.';
            return null;
        }

        return 'public/uploads/profiles/' . $fileName;
    }
}
