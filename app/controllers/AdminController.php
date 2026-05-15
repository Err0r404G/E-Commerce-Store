<?php

require_once __DIR__ . '/../models/AdminModel.php';

class AdminController
{
    private AdminModel $adminModel;

    public function __construct(mysqli $conn)
    {
        $this->adminModel = new AdminModel($conn);
    }

    public function showDashboard(): void
    {
        include __DIR__ . '/../views/admin/AdminDashboard.php';
    }

    public function showVendorApprovals(): void
    {
        [$vendors, $counts] = $this->adminModel->getVendorApprovalData();
        include __DIR__ . '/../views/admin/VendorApproval.php';
    }

    public function vendorApprovalAction(): void
    {
        $this->jsonHeader();

        if (!$this->isAdmin()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized request.'], 403);
        }

        $vendorId = (int) ($_POST['vendor_id'] ?? 0);
        $action = $_POST['action'] ?? '';

        if ($vendorId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid approval request.'], 422);
        }

        $result = $this->adminModel->setVendorApproval($vendorId, $action);
        $this->jsonResponse($result, (int) ($result['status'] ?? 200));
    }

    public function showCategoryManagement(): void
    {
        [$categories, $categoryTree, $categoryStats, $categoryProducts] = $this->adminModel->getCategoryManagementData();
        include __DIR__ . '/../views/admin/AdminCatagory.php';
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

    public function showDisputes(): void
    {
        [$disputes, $disputeCounts] = $this->adminModel->getDisputeManagementData();
        include __DIR__ . '/../views/admin/AdminDispute.php';
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
