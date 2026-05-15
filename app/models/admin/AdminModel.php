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
        $this->ensureSellerAccountColumns();
        $vendors = [];
        $result = $this->conn->query(
            "SELECT u.id, u.name, u.email, u.phone, u.is_active, u.created_at,
                    s.shop_name, s.is_approved, s.account_status, s.admin_note
             FROM users u
             LEFT JOIN sellers s ON s.user_id = u.id
             WHERE u.role = 'vendor'
             ORDER BY FIELD(COALESCE(s.account_status, 'pending'), 'pending', 'approved', 'suspended', 'rejected'),
                      u.created_at DESC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $vendors[] = $row;
            }
        }

        $counts = [
            'pending' => 0,
            'approved' => 0,
            'suspended' => 0,
            'rejected' => 0,
            'total' => count($vendors),
        ];

        foreach ($vendors as $vendor) {
            $status = $vendor['account_status'] ?: ((int) $vendor['is_active'] === 1 ? 'approved' : 'pending');

            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }

        return [$vendors, $counts];
    }

    public function getDashboardMetrics(): array
    {
        $usersByRole = [
            'admin' => 0,
            'customer' => 0,
            'vendor' => 0,
            'delivery_manager' => 0,
        ];

        $result = $this->conn->query("SELECT role, COUNT(*) AS total FROM users GROUP BY role");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $usersByRole[$row['role']] = (int) $row['total'];
            }
        }

        $activeSellersResult = $this->conn->query(
            "SELECT COUNT(*) AS total
             FROM sellers s
             INNER JOIN users u ON u.id = s.user_id
             WHERE s.is_approved = 1 AND u.is_active = 1"
        );
        $activeSellers = (int) (($activeSellersResult ? $activeSellersResult->fetch_assoc() : null)['total'] ?? 0);

        $ordersTodayResult = $this->conn->query(
            "SELECT COUNT(*) AS total
             FROM orders
             WHERE DATE(created_at) = CURDATE()"
        );
        $ordersToday = (int) (($ordersTodayResult ? $ordersTodayResult->fetch_assoc() : null)['total'] ?? 0);

        $monthlyRevenueResult = $this->conn->query(
            "SELECT COALESCE(SUM(total_amount), 0) AS total
             FROM orders
             WHERE YEAR(created_at) = YEAR(CURDATE())
               AND MONTH(created_at) = MONTH(CURDATE())"
        );
        $monthlyRevenue = (float) (($monthlyRevenueResult ? $monthlyRevenueResult->fetch_assoc() : null)['total'] ?? 0);

        return [
            'users_by_role' => $usersByRole,
            'total_users' => array_sum($usersByRole),
            'active_sellers' => $activeSellers,
            'orders_today' => $ordersToday,
            'monthly_revenue' => $monthlyRevenue,
        ];
    }

    public function setVendorApproval(int $vendorId, string $action, ?string $reason = null): array
    {
        $this->ensureSellerAccountColumns();

        $statusMap = [
            'approve' => ['approved', 1, 1, 'Seller approved.'],
            'reject' => ['rejected', 0, 0, 'Seller rejected.'],
            'suspend' => ['suspended', 0, 0, 'Seller suspended.'],
            'reactivate' => ['approved', 1, 1, 'Seller reactivated.'],
        ];

        if (!isset($statusMap[$action])) {
            return ['success' => false, 'status' => 422, 'message' => 'Invalid seller action.'];
        }

        [$accountStatus, $isActive, $isApproved, $message] = $statusMap[$action];

        $stmt = $this->conn->prepare("SELECT id, name, is_active FROM users WHERE id = ? AND role = 'vendor' LIMIT 1");
        $stmt->bind_param('i', $vendorId);
        $stmt->execute();
        $vendor = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$vendor) {
            return ['success' => false, 'status' => 404, 'message' => 'Vendor not found.'];
        }

        $stmt = $this->conn->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'vendor'");
        $stmt->bind_param('ii', $isActive, $vendorId);
        $stmt->execute();
        $updated = $stmt->affected_rows >= 0;
        $stmt->close();

        $shopName = trim((string) ($vendor['name'] ?? 'Vendor')) . "'s Store";
        $shopDescription = 'Vendor storefront profile.';
        $address = 'Not provided';

        $stmt = $this->conn->prepare(
            "INSERT INTO sellers (user_id, shop_name, shop_description, address, is_approved, account_status, admin_note)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                is_approved = VALUES(is_approved),
                account_status = VALUES(account_status),
                admin_note = VALUES(admin_note)"
        );
        $stmt->bind_param('isssiss', $vendorId, $shopName, $shopDescription, $address, $isApproved, $accountStatus, $reason);
        $stmt->execute();
        $stmt->close();

        return [
            'success' => $updated,
            'message' => $message,
        ];
    }

    public function getAccountManagementData(string $role): array
    {
        if (!in_array($role, ['customer', 'delivery_manager'], true)) {
            return [[], ['active' => 0, 'inactive' => 0, 'total' => 0]];
        }

        $accounts = [];
        $stmt = $this->conn->prepare(
            "SELECT id, name, email, phone, is_active, created_at
             FROM users
             WHERE role = ?
             ORDER BY is_active DESC, created_at DESC"
        );
        $stmt->bind_param('s', $role);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $accounts[] = $row;
        }

        $stmt->close();

        $counts = [
            'active' => 0,
            'inactive' => 0,
            'total' => count($accounts),
        ];

        foreach ($accounts as $account) {
            if ((int) $account['is_active'] === 1) {
                $counts['active']++;
            } else {
                $counts['inactive']++;
            }
        }

        return [$accounts, $counts];
    }

    public function setAccountStatus(int $userId, string $role, string $action): array
    {
        if (!in_array($role, ['customer', 'delivery_manager'], true)) {
            return ['success' => false, 'status' => 422, 'message' => 'Invalid account type.'];
        }

        if (!in_array($action, ['deactivate', 'reactivate'], true)) {
            return ['success' => false, 'status' => 422, 'message' => 'Invalid account action.'];
        }

        $stmt = $this->conn->prepare("SELECT id FROM users WHERE id = ? AND role = ? LIMIT 1");
        $stmt->bind_param('is', $userId, $role);
        $stmt->execute();
        $account = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$account) {
            return ['success' => false, 'status' => 404, 'message' => 'Account not found.'];
        }

        $isActive = $action === 'reactivate' ? 1 : 0;
        $stmt = $this->conn->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = ?");
        $stmt->bind_param('iis', $isActive, $userId, $role);
        $stmt->execute();
        $success = $stmt->affected_rows >= 0;
        $stmt->close();

        return [
            'success' => $success,
            'message' => $action === 'reactivate' ? 'Account reactivated.' : 'Account deactivated.',
        ];
    }

    public function createDeliveryManager(string $name, string $email, ?string $phone, string $password): array
    {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            return ['success' => false, 'status' => 422, 'message' => 'An account already exists with this email.'];
        }

        $role = 'delivery_manager';
        $profilePic = null;
        $isActive = 1;
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare(
            "INSERT INTO users (name, email, password_hash, phone, role, profile_pic, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('ssssssi', $name, $email, $passwordHash, $phone, $role, $profilePic, $isActive);
        $stmt->execute();
        $success = $stmt->affected_rows === 1;
        $stmt->close();

        return [
            'success' => $success,
            'message' => $success ? 'Delivery manager created.' : 'Delivery manager could not be created.',
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

    public function getProductManagementData(): array
    {
        $products = [];
        $result = $this->conn->query(
            "SELECT p.id, p.name, p.description, p.price, p.stock_qty, p.primary_image_path,
                    p.is_available, p.created_at,
                    c.id AS category_id, c.name AS category_name,
                    s.id AS seller_id, s.shop_name,
                    u.name AS seller_name,
                    COALESCE(SUM(oi.quantity), 0) AS sold_qty
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN sellers s ON s.id = p.seller_id
             LEFT JOIN users u ON u.id = s.user_id
             LEFT JOIN order_items oi ON oi.product_id = p.id
             GROUP BY p.id, p.name, p.description, p.price, p.stock_qty, p.primary_image_path,
                      p.is_available, p.created_at, c.id, c.name, s.id, s.shop_name, u.name
             ORDER BY p.created_at DESC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }

        $categories = [];
        $categoryResult = $this->conn->query("SELECT id, name FROM categories ORDER BY name ASC");
        if ($categoryResult) {
            while ($row = $categoryResult->fetch_assoc()) {
                $categories[] = $row;
            }
        }

        $sellers = [];
        $sellerResult = $this->conn->query(
            "SELECT s.id, s.shop_name, u.name AS seller_name
             FROM sellers s
             LEFT JOIN users u ON u.id = s.user_id
             ORDER BY s.shop_name ASC"
        );
        if ($sellerResult) {
            while ($row = $sellerResult->fetch_assoc()) {
                $sellers[] = $row;
            }
        }

        $stats = [
            'total' => count($products),
            'active' => 0,
            'removed' => 0,
        ];

        foreach ($products as $product) {
            if ((int) $product['is_available'] === 1) {
                $stats['active']++;
            } else {
                $stats['removed']++;
            }
        }

        return [$products, $categories, $sellers, $stats];
    }

    public function setProductAvailability(int $productId, bool $isAvailable): array
    {
        $stmt = $this->conn->prepare("SELECT id FROM products WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$product) {
            return ['success' => false, 'status' => 404, 'message' => 'Product not found.'];
        }

        $availableValue = $isAvailable ? 1 : 0;
        $stmt = $this->conn->prepare("UPDATE products SET is_available = ? WHERE id = ?");
        $stmt->bind_param('ii', $availableValue, $productId);
        $stmt->execute();
        $success = $stmt->affected_rows >= 0;
        $stmt->close();

        return [
            'success' => $success,
            'message' => $isAvailable
                ? 'Product listing activated.'
                : 'Product listing marked inactive.',
        ];
    }

    public function getOrderManagementData(): array
    {
        $orders = [];
        $result = $this->conn->query(
            "SELECT o.id, o.customer_id, o.shipping_address, o.payment_method, o.subtotal,
                    o.discount_amount, o.total_amount, o.status, o.created_at,
                    customer.name AS customer_name,
                    customer.email AS customer_email,
                    COUNT(oi.id) AS item_count,
                    COALESCE(SUM(oi.quantity), 0) AS total_quantity,
                    GROUP_CONCAT(DISTINCT s.id ORDER BY s.shop_name SEPARATOR ',') AS seller_ids,
                    GROUP_CONCAT(DISTINCT COALESCE(s.shop_name, seller_user.name, 'Unknown seller') ORDER BY s.shop_name SEPARATOR ', ') AS seller_names
             FROM orders o
             LEFT JOIN users customer ON customer.id = o.customer_id
             LEFT JOIN order_items oi ON oi.order_id = o.id
             LEFT JOIN sellers s ON s.id = oi.seller_id
             LEFT JOIN users seller_user ON seller_user.id = s.user_id
             GROUP BY o.id, o.customer_id, o.shipping_address, o.payment_method, o.subtotal,
                      o.discount_amount, o.total_amount, o.status, o.created_at,
                      customer.name, customer.email
             ORDER BY o.created_at DESC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }

        $sellers = [];
        $sellerResult = $this->conn->query(
            "SELECT DISTINCT s.id, s.shop_name, u.name AS seller_name
             FROM sellers s
             INNER JOIN order_items oi ON oi.seller_id = s.id
             LEFT JOIN users u ON u.id = s.user_id
             ORDER BY s.shop_name ASC"
        );
        if ($sellerResult) {
            while ($row = $sellerResult->fetch_assoc()) {
                $sellers[] = $row;
            }
        }

        $customers = [];
        $customerResult = $this->conn->query(
            "SELECT DISTINCT u.id, u.name, u.email
             FROM users u
             INNER JOIN orders o ON o.customer_id = u.id
             ORDER BY u.name ASC"
        );
        if ($customerResult) {
            while ($row = $customerResult->fetch_assoc()) {
                $customers[] = $row;
            }
        }

        $stats = [
            'total' => count($orders),
            'pending' => 0,
            'processing' => 0,
            'delivered' => 0,
            'cancelled' => 0,
            'revenue' => 0.0,
        ];

        foreach ($orders as $order) {
            $status = (string) ($order['status'] ?? '');
            if (in_array($status, ['pending', 'confirmed', 'processing', 'shipped'], true)) {
                $stats['processing']++;
            } elseif (isset($stats[$status])) {
                $stats[$status]++;
            }
            $stats['revenue'] += (float) ($order['total_amount'] ?? 0);
        }

        return [$orders, $sellers, $customers, $stats];
    }

    public function createCategory(string $name, ?string $description, ?int $parentId): array
    {
        if ($parentId !== null && !$this->isRootCategory($parentId)) {
            return ['success' => false, 'status' => 422, 'message' => 'Subcategories can only be added under a main category.'];
        }

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
        if (!$this->categoryExists($categoryId)) {
            return ['success' => false, 'status' => 404, 'message' => 'Category not found.'];
        }

        if ($categoryId === $parentId) {
            return ['success' => false, 'status' => 422, 'message' => 'A category cannot be its own parent.'];
        }

        if ($parentId !== null && !$this->isRootCategory($parentId)) {
            return ['success' => false, 'status' => 422, 'message' => 'Subcategories can only be placed under a main category.'];
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
        if (!$this->categoryExists($categoryId)) {
            return ['success' => false, 'status' => 404, 'message' => 'Category not found.'];
        }

        $productCount = $this->countProductsInCategory($categoryId);
        if ($productCount > 0) {
            return [
                'success' => false,
                'status' => 409,
                'message' => 'Delete blocked: this category has products assigned to it.',
            ];
        }

        $childCount = $this->countChildCategories($categoryId);
        if ($childCount > 0) {
            return [
                'success' => false,
                'status' => 409,
                'message' => 'Delete blocked: remove or rename its subcategories first.',
            ];
        }

        $stmt = $this->conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();
        $success = $stmt->affected_rows === 1;
        $stmt->close();

        return ['success' => $success, 'message' => $success ? 'Category deleted.' : 'Category not found.'];
    }

    public function getDisputeManagementData(): array
    {
        $disputes = [];
        $result = $this->conn->query(
            "SELECT d.id, d.order_id, d.description, d.status, d.admin_note, d.created_at,
                    customer.name AS customer_name,
                    customer.email AS customer_email,
                    seller_user.name AS seller_name,
                    s.shop_name,
                    o.total_amount
             FROM disputes d
             LEFT JOIN users customer ON customer.id = d.customer_id
             LEFT JOIN sellers s ON s.id = d.seller_id
             LEFT JOIN users seller_user ON seller_user.id = s.user_id
             LEFT JOIN orders o ON o.id = d.order_id
             ORDER BY d.status ASC, d.created_at DESC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $disputes[] = $row;
            }
        }

        $counts = [
            'urgent' => 0,
            'progress' => 0,
            'resolved' => 0,
            'total' => count($disputes),
        ];

        foreach ($disputes as $dispute) {
            if ($dispute['status'] === 'resolved') {
                $counts['resolved']++;
            } elseif (!empty($dispute['admin_note'])) {
                $counts['progress']++;
            } else {
                $counts['urgent']++;
            }
        }

        return [$disputes, $counts];
    }

    public function setDisputeStatus(int $disputeId, string $status, ?string $adminNote): array
    {
        $stmt = $this->conn->prepare("SELECT id FROM disputes WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $disputeId);
        $stmt->execute();
        $dispute = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$dispute) {
            return ['success' => false, 'status' => 404, 'message' => 'Dispute not found.'];
        }

        $stmt = $this->conn->prepare("UPDATE disputes SET status = ?, admin_note = ? WHERE id = ?");
        $stmt->bind_param('ssi', $status, $adminNote, $disputeId);
        $stmt->execute();
        $success = $stmt->affected_rows >= 0;
        $stmt->close();

        return ['success' => $success, 'message' => $status === 'resolved' ? 'Dispute resolved.' : 'Dispute reopened.'];
    }

    public function getPlatformCoupons(): array
    {
        $this->ensurePlatformCouponsTable();
        $coupons = [];
        $result = $this->conn->query(
            "SELECT id, code, discount_pct, max_uses, uses_count, valid_until, is_active, created_at
             FROM platform_coupons
             ORDER BY is_active DESC, valid_until DESC, id DESC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $coupons[] = $row;
            }
        }

        return $coupons;
    }

    public function savePlatformCoupon(array $data): array
    {
        $this->ensurePlatformCouponsTable();
        $couponId = (int) ($data['coupon_id'] ?? 0);

        if ($couponId > 0) {
            $stmt = $this->conn->prepare(
                "UPDATE platform_coupons
                 SET code = ?, discount_pct = ?, max_uses = ?, valid_until = ?, is_active = ?
                 WHERE id = ?"
            );
            $stmt->bind_param(
                'sdisii',
                $data['code'],
                $data['discount_pct'],
                $data['max_uses'],
                $data['valid_until'],
                $data['is_active'],
                $couponId
            );
        } else {
            $stmt = $this->conn->prepare(
                "INSERT INTO platform_coupons (code, discount_pct, max_uses, valid_until, is_active)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                'sdisi',
                $data['code'],
                $data['discount_pct'],
                $data['max_uses'],
                $data['valid_until'],
                $data['is_active']
            );
        }

        try {
            $success = $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            $stmt->close();
            return ['success' => false, 'status' => 422, 'message' => 'Coupon code already exists.'];
        }

        $stmt->close();

        return ['success' => $success, 'message' => $success ? 'Platform coupon saved.' : 'Platform coupon could not be saved.'];
    }

    public function togglePlatformCoupon(int $couponId): array
    {
        $this->ensurePlatformCouponsTable();
        $stmt = $this->conn->prepare(
            "UPDATE platform_coupons
             SET is_active = IF(is_active = 1, 0, 1)
             WHERE id = ?"
        );
        $stmt->bind_param('i', $couponId);
        $stmt->execute();
        $success = $stmt->affected_rows > 0;
        $stmt->close();

        return ['success' => $success, 'message' => $success ? 'Platform coupon status updated.' : 'Platform coupon not found.'];
    }

    public function getAdminProfile(int $adminId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, name, email, phone, password_hash, role, profile_pic
             FROM users
             WHERE id = ? AND role = 'admin'
             LIMIT 1"
        );
        $stmt->bind_param('i', $adminId);
        $stmt->execute();
        $profile = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $profile ?: null;
    }

    public function updateAdminProfile(int $adminId, array $data): array
    {
        $profilePic = $data['profile_pic'] ?? null;
        $passwordHash = $data['password_hash'] ?? null;

        if ($profilePic !== null && $passwordHash !== null) {
            $stmt = $this->conn->prepare(
                "UPDATE users
                 SET name = ?, phone = ?, profile_pic = ?, password_hash = ?
                 WHERE id = ? AND role = 'admin'"
            );
            $stmt->bind_param('ssssi', $data['name'], $data['phone'], $profilePic, $passwordHash, $adminId);
        } elseif ($profilePic !== null) {
            $stmt = $this->conn->prepare(
                "UPDATE users
                 SET name = ?, phone = ?, profile_pic = ?
                 WHERE id = ? AND role = 'admin'"
            );
            $stmt->bind_param('sssi', $data['name'], $data['phone'], $profilePic, $adminId);
        } elseif ($passwordHash !== null) {
            $stmt = $this->conn->prepare(
                "UPDATE users
                 SET name = ?, phone = ?, password_hash = ?
                 WHERE id = ? AND role = 'admin'"
            );
            $stmt->bind_param('sssi', $data['name'], $data['phone'], $passwordHash, $adminId);
        } else {
            $stmt = $this->conn->prepare(
                "UPDATE users
                 SET name = ?, phone = ?
                 WHERE id = ? AND role = 'admin'"
            );
            $stmt->bind_param('ssi', $data['name'], $data['phone'], $adminId);
        }

        $success = $stmt->execute();
        $stmt->close();

        return ['success' => $success, 'message' => $success ? 'Settings updated successfully.' : 'Settings update failed.'];
    }

    public function getPlatformReportData(string $month): array
    {
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-d', strtotime($monthStart . ' +1 month'));

        return [
            'selected_month' => $month,
            'month_label' => date('F Y', strtotime($monthStart)),
            'all_time' => $this->getReportSalesSummary(),
            'monthly' => $this->getReportSalesSummary($monthStart, $monthEnd),
            'top_sellers' => $this->getTopPerformingSellers($monthStart, $monthEnd),
            'top_categories' => $this->getTopSellingCategories($monthStart, $monthEnd),
            'delivery' => $this->getDeliveryPerformanceOverview($monthStart, $monthEnd),
            'generated_at' => date('M d, Y h:i A'),
        ];
    }

    private function getReportSalesSummary(?string $startDate = null, ?string $endDate = null): array
    {
        $where = "WHERE o.status NOT IN ('cancelled', 'returned')";
        $params = [];

        if ($startDate !== null && $endDate !== null) {
            $where .= " AND o.created_at >= ? AND o.created_at < ?";
            $params = [$startDate, $endDate];
        }

        $orderStmt = $this->conn->prepare(
            "SELECT COUNT(*) AS orders_count,
                    COUNT(DISTINCT o.customer_id) AS customers_count,
                    COALESCE(SUM(o.discount_amount), 0) AS discounts_given,
                    COALESCE(SUM(o.total_amount), 0) AS order_total
             FROM orders o
             {$where}"
        );

        if ($params) {
            $orderStmt->bind_param('ss', $params[0], $params[1]);
        }

        $orderStmt->execute();
        $orderSummary = $orderStmt->get_result()->fetch_assoc() ?: [];
        $orderStmt->close();

        $itemStmt = $this->conn->prepare(
            "SELECT COUNT(DISTINCT o.id) AS orders_count,
                    COALESCE(SUM(oi.quantity), 0) AS units_sold,
                    COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS gmv,
                    COALESCE(SUM((oi.quantity * oi.unit_price) * (COALESCE(s.commission_rate, 10) / 100)), 0) AS commission_earned
             FROM orders o
             INNER JOIN order_items oi ON oi.order_id = o.id
             LEFT JOIN sellers s ON s.id = oi.seller_id
             {$where}"
        );

        if ($params) {
            $itemStmt->bind_param('ss', $params[0], $params[1]);
        }

        $itemStmt->execute();
        $itemSummary = $itemStmt->get_result()->fetch_assoc() ?: [];
        $itemStmt->close();

        return [
            'orders_count' => (int) ($orderSummary['orders_count'] ?? 0),
            'customers_count' => (int) ($orderSummary['customers_count'] ?? 0),
            'units_sold' => (int) ($itemSummary['units_sold'] ?? 0),
            'gmv' => (float) ($itemSummary['gmv'] ?? 0),
            'commission_earned' => (float) ($itemSummary['commission_earned'] ?? 0),
            'discounts_given' => (float) ($orderSummary['discounts_given'] ?? 0),
            'order_total' => (float) ($orderSummary['order_total'] ?? 0),
        ];
    }

    private function getTopPerformingSellers(string $startDate, string $endDate): array
    {
        $stmt = $this->conn->prepare(
            "SELECT s.id, COALESCE(s.shop_name, u.name, 'Unknown seller') AS seller_name,
                    COUNT(DISTINCT o.id) AS orders_count,
                    COALESCE(SUM(oi.quantity), 0) AS units_sold,
                    COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS gmv,
                    COALESCE(SUM((oi.quantity * oi.unit_price) * (COALESCE(s.commission_rate, 10) / 100)), 0) AS commission_earned
             FROM order_items oi
             INNER JOIN orders o ON o.id = oi.order_id
             LEFT JOIN sellers s ON s.id = oi.seller_id
             LEFT JOIN users u ON u.id = s.user_id
             WHERE o.status NOT IN ('cancelled', 'returned')
               AND o.created_at >= ? AND o.created_at < ?
             GROUP BY s.id, seller_name
             ORDER BY gmv DESC
             LIMIT 8"
        );
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    private function getTopSellingCategories(string $startDate, string $endDate): array
    {
        $stmt = $this->conn->prepare(
            "SELECT COALESCE(c.name, 'Uncategorized') AS category_name,
                    COUNT(DISTINCT o.id) AS orders_count,
                    COALESCE(SUM(oi.quantity), 0) AS units_sold,
                    COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS gmv
             FROM order_items oi
             INNER JOIN orders o ON o.id = oi.order_id
             LEFT JOIN products p ON p.id = oi.product_id
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE o.status NOT IN ('cancelled', 'returned')
               AND o.created_at >= ? AND o.created_at < ?
             GROUP BY category_name
             ORDER BY gmv DESC
             LIMIT 8"
        );
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    private function getDeliveryPerformanceOverview(string $startDate, string $endDate): array
    {
        $statusCounts = [
            'assigned' => 0,
            'picked_up' => 0,
            'in_transit' => 0,
            'delivered' => 0,
            'failed' => 0,
        ];

        $stmt = $this->conn->prepare(
            "SELECT status, COUNT(*) AS total
             FROM delivery_assignments
             WHERE assigned_at >= ? AND assigned_at < ?
             GROUP BY status"
        );
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $statusCounts[$row['status']] = (int) $row['total'];
        }

        $stmt->close();

        $agentStmt = $this->conn->prepare(
            "SELECT COALESCE(u.name, 'Delivery agent') AS agent_name,
                    da.vehicle_type,
                    COUNT(d.id) AS assignments,
                    SUM(CASE WHEN d.status = 'delivered' THEN 1 ELSE 0 END) AS delivered,
                    SUM(CASE WHEN d.status = 'failed' THEN 1 ELSE 0 END) AS failed
             FROM delivery_assignments d
             LEFT JOIN delivery_agents da ON da.id = d.agent_id
             LEFT JOIN users u ON u.id = da.user_id
             WHERE d.assigned_at >= ? AND d.assigned_at < ?
             GROUP BY d.agent_id, agent_name, da.vehicle_type
             ORDER BY assignments DESC
             LIMIT 8"
        );
        $agentStmt->bind_param('ss', $startDate, $endDate);
        $agentStmt->execute();
        $agents = $agentStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $agentStmt->close();

        $totalAssignments = array_sum($statusCounts);
        $delivered = $statusCounts['delivered'];
        $failed = $statusCounts['failed'];

        return [
            'status_counts' => $statusCounts,
            'agents' => $agents,
            'total_assignments' => $totalAssignments,
            'delivery_success_rate' => $totalAssignments > 0 ? round(($delivered / $totalAssignments) * 100, 2) : 0,
            'failure_rate' => $totalAssignments > 0 ? round(($failed / $totalAssignments) * 100, 2) : 0,
        ];
    }

    private function countSellersWithProducts(): int
    {
        $result = $this->conn->query("SELECT COUNT(DISTINCT seller_id) AS total FROM products");
        $row = $result ? $result->fetch_assoc() : null;

        return (int) ($row['total'] ?? 0);
    }

    private function ensurePlatformCouponsTable(): void
    {
        $this->conn->query(
            "CREATE TABLE IF NOT EXISTS platform_coupons (
                id int(11) NOT NULL AUTO_INCREMENT,
                code varchar(50) NOT NULL,
                discount_pct decimal(5,2) NOT NULL,
                max_uses int(11) DEFAULT 100,
                uses_count int(11) DEFAULT 0,
                valid_until date NOT NULL,
                is_active tinyint(1) DEFAULT 1,
                created_at datetime DEFAULT current_timestamp(),
                PRIMARY KEY (id),
                UNIQUE KEY code (code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }

    private function ensureSellerAccountColumns(): void
    {
        $statusColumn = $this->conn->query("SHOW COLUMNS FROM sellers LIKE 'account_status'");
        if (!$statusColumn || $statusColumn->num_rows === 0) {
            $this->conn->query(
                "ALTER TABLE sellers
                 ADD account_status ENUM('pending','approved','rejected','suspended') NOT NULL DEFAULT 'pending' AFTER is_approved"
            );
            $this->conn->query(
                "UPDATE sellers
                 SET account_status = CASE
                    WHEN is_approved = 1 THEN 'approved'
                    ELSE 'pending'
                 END"
            );
        }

        $noteColumn = $this->conn->query("SHOW COLUMNS FROM sellers LIKE 'admin_note'");
        if (!$noteColumn || $noteColumn->num_rows === 0) {
            $this->conn->query("ALTER TABLE sellers ADD admin_note TEXT DEFAULT NULL AFTER account_status");
        }
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

    private function categoryExists(int $categoryId): bool
    {
        $stmt = $this->conn->prepare("SELECT id FROM categories WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $exists;
    }

    private function isRootCategory(int $categoryId): bool
    {
        $stmt = $this->conn->prepare("SELECT id FROM categories WHERE id = ? AND parent_id IS NULL LIMIT 1");
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $exists;
    }

    private function countProductsInCategory(int $categoryId): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM products WHERE category_id = ?");
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row['total'] ?? 0);
    }

    private function countChildCategories(int $categoryId): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM categories WHERE parent_id = ?");
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row['total'] ?? 0);
    }
}
