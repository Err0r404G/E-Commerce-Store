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
}
