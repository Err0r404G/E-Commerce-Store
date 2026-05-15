<?php

class AdminModel
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function getVendorApprovalData(): array
    {
        $vendors = [];
        $result = $this->conn->query(
            "SELECT u.id, u.name, u.email, u.phone, u.is_active, u.created_at,
                    s.shop_name, s.is_approved
             FROM users u
             LEFT JOIN sellers s ON s.user_id = u.id
             WHERE u.role = 'vendor'
             ORDER BY u.is_active ASC, u.created_at DESC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $vendors[] = $row;
            }
        }

        $counts = [
            'pending' => 0,
            'approved' => 0,
            'total' => count($vendors),
        ];

        foreach ($vendors as $vendor) {
            if ((int) $vendor['is_active'] === 1) {
                $counts['approved']++;
            } else {
                $counts['pending']++;
            }
        }

        return [$vendors, $counts];
    }

    public function setVendorApproval(int $vendorId, string $action): array
    {
        $isActive = $action === 'approve' ? 1 : 0;
        $isApproved = $action === 'approve' ? 1 : 0;

        $stmt = $this->conn->prepare("SELECT is_active FROM users WHERE id = ? AND role = 'vendor' LIMIT 1");
        $stmt->bind_param('i', $vendorId);
        $stmt->execute();
        $vendor = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$vendor) {
            return ['success' => false, 'status' => 404, 'message' => 'Vendor not found.'];
        }

        if ((int) $vendor['is_active'] === $isActive) {
            return [
                'success' => true,
                'message' => $action === 'approve' ? 'Vendor is already approved.' : 'Vendor is already rejected.',
            ];
        }

        $stmt = $this->conn->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'vendor'");
        $stmt->bind_param('ii', $isActive, $vendorId);
        $stmt->execute();
        $updated = $stmt->affected_rows >= 0;
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE sellers SET is_approved = ? WHERE user_id = ?");
        $stmt->bind_param('ii', $isApproved, $vendorId);
        $stmt->execute();
        $stmt->close();

        return [
            'success' => $updated,
            'message' => $action === 'approve' ? 'Vendor approved.' : 'Vendor rejected.',
        ];
    }

    public function getCategoryManagementData(): array
    {
        $categories = [];
        $result = $this->conn->query(
            "SELECT c.id, c.parent_id, c.name, c.description,
                    COUNT(DISTINCT p.id) AS total_products,
                    COALESCE(SUM(oi.quantity), 0) AS sold_products,
                    COUNT(DISTINCT p.seller_id) AS active_sellers
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id
             LEFT JOIN order_items oi ON oi.product_id = p.id
             GROUP BY c.id, c.parent_id, c.name, c.description
             ORDER BY c.parent_id IS NOT NULL, c.name ASC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }

        $categoryTree = [];
        foreach ($categories as $category) {
            if ($category['parent_id'] === null) {
                $category['children'] = [];
                $categoryTree[(int) $category['id']] = $category;
            }
        }

        foreach ($categories as $category) {
            $parentId = $category['parent_id'] === null ? null : (int) $category['parent_id'];
            if ($parentId !== null && isset($categoryTree[$parentId])) {
                $categoryTree[$parentId]['children'][] = $category;
            }
        }

        $stats = [
            'total_categories' => count($categories),
            'total_products' => array_sum(array_map(static fn ($category) => (int) $category['total_products'], $categories)),
            'active_sellers' => $this->countSellersWithProducts(),
        ];

        return [$categories, array_values($categoryTree), $stats];
    }

    public function createCategory(string $name, ?string $description, ?int $parentId): array
    {
        if ($this->categoryNameExists($name, $parentId)) {
            return ['success' => false, 'status' => 422, 'message' => 'Category already exists.'];
        }

        $stmt = $this->conn->prepare("INSERT INTO categories (parent_id, name, description) VALUES (?, ?, ?)");
        $stmt->bind_param('iss', $parentId, $name, $description);
        $stmt->execute();
        $success = $stmt->affected_rows === 1;
        $stmt->close();

        return ['success' => $success, 'message' => $success ? 'Category added.' : 'Category could not be added.'];
    }

    public function updateCategory(int $categoryId, string $name, ?string $description, ?int $parentId): array
    {
        if ($categoryId === $parentId) {
            return ['success' => false, 'status' => 422, 'message' => 'A category cannot be its own parent.'];
        }

        if ($this->categoryNameExists($name, $parentId, $categoryId)) {
            return ['success' => false, 'status' => 422, 'message' => 'Category already exists.'];
        }

        $stmt = $this->conn->prepare("UPDATE categories SET parent_id = ?, name = ?, description = ? WHERE id = ?");
        $stmt->bind_param('issi', $parentId, $name, $description, $categoryId);
        $stmt->execute();
        $success = $stmt->affected_rows >= 0;
        $stmt->close();

        return ['success' => $success, 'message' => $success ? 'Category updated.' : 'Category could not be updated.'];
    }

    public function deleteCategory(int $categoryId): array
    {
        $stmt = $this->conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();
        $success = $stmt->affected_rows === 1;
        $stmt->close();

        return ['success' => $success, 'message' => $success ? 'Category deleted.' : 'Category not found.'];
    }

    private function countSellersWithProducts(): int
    {
        $result = $this->conn->query("SELECT COUNT(DISTINCT seller_id) AS total FROM products");
        $row = $result ? $result->fetch_assoc() : null;

        return (int) ($row['total'] ?? 0);
    }

    private function categoryNameExists(string $name, ?int $parentId, ?int $excludeId = null): bool
    {
        if ($parentId === null) {
            $sql = "SELECT id FROM categories WHERE name = ? AND parent_id IS NULL";
            $types = 's';
            $params = [$name];
        } else {
            $sql = "SELECT id FROM categories WHERE name = ? AND parent_id = ?";
            $types = 'si';
            $params = [$name, $parentId];
        }

        if ($excludeId !== null) {
            $sql .= " AND id <> ?";
            $types .= 'i';
            $params[] = $excludeId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $exists;
    }
}
