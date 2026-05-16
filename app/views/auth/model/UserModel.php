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

    public function findVendorProfile(int $userId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT u.id, u.name, u.email, u.phone, u.password_hash, u.role, u.profile_pic,
                    s.shop_name, s.shop_description, s.shop_logo_path, s.address
             FROM users u
             LEFT JOIN sellers s ON s.user_id = u.id
             WHERE u.id = ? AND u.role = 'vendor'
             LIMIT 1"
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $result = $stmt->get_result();
        $profile = $result->fetch_assoc();
        $stmt->close();

        return $profile ?: null;
    }

    public function findDeliveryManagerProfile(int $userId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, name, email, phone, password_hash, role, profile_pic, is_active, created_at
             FROM users
             WHERE id = ? AND role = 'delivery_manager'
             LIMIT 1"
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $profile = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $profile ?: null;
    }

    public function findSellerByUserId(int $userId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM sellers WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $seller = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $seller ?: null;
    }

    public function ensureSellerForVendor(int $userId): ?array
    {
        $seller = $this->findSellerByUserId($userId);

        if ($seller) {
            return $seller;
        }

        $stmt = $this->conn->prepare("SELECT id, name, is_active FROM users WHERE id = ? AND role = 'vendor' LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $vendor = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$vendor || (int) $vendor['is_active'] !== 1) {
            return null;
        }

        $shopName = trim((string) ($vendor['name'] ?? 'Vendor')) . "'s Store";
        $description = 'Vendor storefront profile.';
        $address = 'Not provided';
        $isApproved = 1;

        $stmt = $this->conn->prepare(
            "INSERT INTO sellers (user_id, shop_name, shop_description, address, is_approved)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("isssi", $userId, $shopName, $description, $address, $isApproved);
        $created = $stmt->execute();
        $stmt->close();

        return $created ? $this->findSellerByUserId($userId) : null;
    }

    public function getPlatformCategories(): array
    {
        $categories = [];
        $result = $this->conn->query("SELECT id, name FROM categories ORDER BY parent_id IS NOT NULL, name ASC");

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }

        return $categories;
    }

    public function getVendorProducts(int $sellerId): array
    {
        $products = [];
        $stmt = $this->conn->prepare(
            "SELECT p.id, p.category_id, p.name, p.description, p.price, p.stock_qty, p.primary_image_path,
                    p.is_available, p.created_at, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.seller_id = ?
             ORDER BY p.created_at DESC"
        );
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();

        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        $stmt->close();
        return $products;
    }

    public function getVendorDashboardMetrics(int $sellerId): array
    {
        $metrics = [
            'total_products' => 0,
            'active_products' => 0,
            'low_stock_products' => 0,
            'active_coupons' => 0,
            'order_items' => 0,
            'pending_items' => 0,
            'confirmed_items' => 0,
            'shipped_items' => 0,
            'delivered_items' => 0,
            'total_revenue' => 0,
            'monthly_revenue' => 0,
            'units_sold' => 0,
            'pending_returns' => 0,
            'review_count' => 0,
            'average_rating' => 0,
            'commission_rate' => 0,
            'commission_deducted' => 0,
            'net_payout' => 0,
            'recent_orders' => [],
            'low_stock_items' => [],
            'top_products' => [],
        ];

        $seller = $this->findSellerById($sellerId);
        $metrics['commission_rate'] = (float) ($seller['commission_rate'] ?? 10);

        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS total_products,
                    COALESCE(SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END), 0) AS active_products,
                    COALESCE(SUM(CASE WHEN stock_qty <= 5 THEN 1 ELSE 0 END), 0) AS low_stock_products
             FROM products
             WHERE seller_id = ?"
        );
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        $productRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($productRow) {
            $metrics['total_products'] = (int) $productRow['total_products'];
            $metrics['active_products'] = (int) $productRow['active_products'];
            $metrics['low_stock_products'] = (int) $productRow['low_stock_products'];
        }

        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS order_items,
                    COALESCE(SUM(CASE WHEN oi.item_status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_items,
                    COALESCE(SUM(CASE WHEN oi.item_status = 'confirmed' THEN 1 ELSE 0 END), 0) AS confirmed_items,
                    COALESCE(SUM(CASE WHEN oi.item_status = 'shipped' THEN 1 ELSE 0 END), 0) AS shipped_items,
                    COALESCE(SUM(CASE WHEN oi.item_status = 'delivered' THEN 1 ELSE 0 END), 0) AS delivered_items,
                    COALESCE(SUM(oi.quantity), 0) AS units_sold,
                    COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS total_revenue,
                    COALESCE(SUM(CASE WHEN DATE_FORMAT(o.created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN oi.quantity * oi.unit_price ELSE 0 END), 0) AS monthly_revenue
             FROM order_items oi
             INNER JOIN orders o ON o.id = oi.order_id
             WHERE oi.seller_id = ?"
        );
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        $orderRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($orderRow) {
            foreach (['order_items', 'pending_items', 'confirmed_items', 'shipped_items', 'delivered_items', 'units_sold'] as $key) {
                $metrics[$key] = (int) $orderRow[$key];
            }

            $metrics['total_revenue'] = (float) $orderRow['total_revenue'];
            $metrics['monthly_revenue'] = (float) $orderRow['monthly_revenue'];
        }

        $metrics['commission_deducted'] = $metrics['total_revenue'] * ($metrics['commission_rate'] / 100);
        $metrics['net_payout'] = $metrics['total_revenue'] - $metrics['commission_deducted'];

        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS active_coupons
             FROM coupons
             WHERE seller_id = ? AND is_active = 1"
        );
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        $couponRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $metrics['active_coupons'] = (int) ($couponRow['active_coupons'] ?? 0);

        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS pending_returns
             FROM return_requests rr
             INNER JOIN order_items oi ON oi.id = rr.order_item_id
             WHERE oi.seller_id = ? AND rr.status = 'pending'"
        );
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        $returnRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $metrics['pending_returns'] = (int) ($returnRow['pending_returns'] ?? 0);

        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS review_count,
                    COALESCE(AVG(r.rating), 0) AS average_rating
             FROM reviews r
             INNER JOIN products p ON p.id = r.product_id
             WHERE p.seller_id = ?"
        );
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        $reviewRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $metrics['review_count'] = (int) ($reviewRow['review_count'] ?? 0);
        $metrics['average_rating'] = (float) ($reviewRow['average_rating'] ?? 0);

        $stmt = $this->conn->prepare(
            "SELECT oi.id AS order_item_id, oi.order_id, oi.quantity, oi.unit_price, oi.item_status, o.created_at,
                    p.name AS product_name,
                    u.name AS customer_name
             FROM order_items oi
             INNER JOIN orders o ON o.id = oi.order_id
             INNER JOIN products p ON p.id = oi.product_id
             INNER JOIN users u ON u.id = o.customer_id
             WHERE oi.seller_id = ?
             ORDER BY o.created_at DESC, oi.id DESC
             LIMIT 5"
        );
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $metrics['recent_orders'][] = $row;
        }
        $stmt->close();

        $stmt = $this->conn->prepare(
            "SELECT id, name, stock_qty, is_available
             FROM products
             WHERE seller_id = ? AND stock_qty <= 5
             ORDER BY stock_qty ASC, name ASC
             LIMIT 6"
        );
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $metrics['low_stock_items'][] = $row;
        }
        $stmt->close();

        $stmt = $this->conn->prepare(
            "SELECT p.name,
                    COALESCE(SUM(oi.quantity), 0) AS units_sold,
                    COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS revenue
             FROM order_items oi
             INNER JOIN products p ON p.id = oi.product_id
             WHERE oi.seller_id = ?
             GROUP BY p.id, p.name
             ORDER BY units_sold DESC, revenue DESC
             LIMIT 5"
        );
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $metrics['top_products'][] = $row;
        }
        $stmt->close();

        return $metrics;
    }

    public function getVendorNotificationCounts(int $sellerId): array
    {
        $counts = [
            'orders' => 0,
            'returns' => 0,
            'reviews' => 0,
            'total' => 0,
        ];

        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS count
             FROM order_items
             WHERE seller_id = ? AND item_status = 'pending'"
        );
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $counts['orders'] = (int) ($row['count'] ?? 0);

        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS count
             FROM return_requests rr
             INNER JOIN order_items oi ON oi.id = rr.order_item_id
             WHERE oi.seller_id = ? AND rr.status = 'pending'"
        );
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $counts['returns'] = (int) ($row['count'] ?? 0);

        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS count
             FROM reviews r
             INNER JOIN products p ON p.id = r.product_id
             WHERE p.seller_id = ?
                AND (r.seller_reply IS NULL OR r.seller_reply = '')"
        );
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $counts['reviews'] = (int) ($row['count'] ?? 0);
        $counts['total'] = $counts['orders'] + $counts['returns'] + $counts['reviews'];

        return $counts;
    }

    public function saveVendorProduct(int $sellerId, array $data): ?int
    {
        $productId = (int) ($data['product_id'] ?? 0);
        $imagePath = $data['primary_image_path'] ?? null;

        if ($productId > 0) {
            $stmt = $this->conn->prepare(
                "UPDATE products
                 SET category_id = ?, name = ?, description = ?, price = ?, stock_qty = ?,
                     primary_image_path = COALESCE(?, primary_image_path), is_available = ?
                 WHERE id = ? AND seller_id = ?"
            );

            $stmt->bind_param(
                "issdisiii",
                $data['category_id'],
                $data['name'],
                $data['description'],
                $data['price'],
                $data['stock_qty'],
                $imagePath,
                $data['is_available'],
                $productId,
                $sellerId
            );
        } else {
            $stmt = $this->conn->prepare(
                "INSERT INTO products (seller_id, category_id, name, description, price, stock_qty, primary_image_path, is_available)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "iissdisi",
                $sellerId,
                $data['category_id'],
                $data['name'],
                $data['description'],
                $data['price'],
                $data['stock_qty'],
                $imagePath,
                $data['is_available']
            );
        }

        $saved = $stmt->execute();
        $savedProductId = $productId > 0 ? $productId : (int) $this->conn->insert_id;
        $stmt->close();

        return $saved ? $savedProductId : null;
    }

    public function replaceProductImages(int $sellerId, int $productId, array $imagePaths): bool
    {
        $this->conn->begin_transaction();

        $stmt = $this->conn->prepare(
            "DELETE pi FROM product_images pi
             INNER JOIN products p ON p.id = pi.product_id
             WHERE pi.product_id = ? AND p.seller_id = ?"
        );
        $stmt->bind_param("ii", $productId, $sellerId);
        $deleted = $stmt->execute();
        $stmt->close();

        if (!$deleted) {
            $this->conn->rollback();
            return false;
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO product_images (product_id, image_path, display_order)
             VALUES (?, ?, ?)"
        );

        foreach ($imagePaths as $displayOrder => $imagePath) {
            $order = $displayOrder + 1;
            $stmt->bind_param("isi", $productId, $imagePath, $order);

            if (!$stmt->execute()) {
                $stmt->close();
                $this->conn->rollback();
                return false;
            }
        }

        $stmt->close();
        $this->conn->commit();
        return true;
    }

    public function deleteVendorProduct(int $sellerId, int $productId): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
        $stmt->bind_param("ii", $productId, $sellerId);
        $deleted = $stmt->execute();
        $stmt->close();

        return $deleted;
    }

    public function getVendorCoupons(int $sellerId): array
    {
        $coupons = [];
        $stmt = $this->conn->prepare(
            "SELECT id, code, discount_pct, max_uses, uses_count, valid_until, is_active
             FROM coupons
             WHERE seller_id = ?
             ORDER BY valid_until DESC, id DESC"
        );
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();

        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $coupons[] = $row;
        }

        $stmt->close();
        return $coupons;
    }

    public function saveVendorCoupon(int $sellerId, array $data): bool
    {
        $couponId = (int) ($data['coupon_id'] ?? 0);

        if ($couponId > 0) {
            $stmt = $this->conn->prepare(
                "UPDATE coupons
                 SET code = ?, discount_pct = ?, max_uses = ?, valid_until = ?, is_active = ?
                 WHERE id = ? AND seller_id = ?"
            );
            $stmt->bind_param(
                "sdisiii",
                $data['code'],
                $data['discount_pct'],
                $data['max_uses'],
                $data['valid_until'],
                $data['is_active'],
                $couponId,
                $sellerId
            );
        } else {
            $stmt = $this->conn->prepare(
                "INSERT INTO coupons (seller_id, code, discount_pct, max_uses, valid_until, is_active)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "isdisi",
                $sellerId,
                $data['code'],
                $data['discount_pct'],
                $data['max_uses'],
                $data['valid_until'],
                $data['is_active']
            );
        }

        $saved = $stmt->execute();
        $stmt->close();

        return $saved;
    }

    public function toggleVendorCoupon(int $sellerId, int $couponId): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE coupons
             SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END
             WHERE id = ? AND seller_id = ?"
        );
        $stmt->bind_param("ii", $couponId, $sellerId);
        $updated = $stmt->execute();
        $stmt->close();

        return $updated;
    }

    public function getVendorOrderItems(int $sellerId, string $status = ''): array
    {
        $this->ensureOrderItemTrackingNoteColumn();

        $orders = [];
        $allowedStatuses = ['pending', 'confirmed', 'shipped', 'delivered'];
        $hasStatusFilter = in_array($status, $allowedStatuses, true);
        $sql = "SELECT oi.id AS order_item_id, oi.order_id, oi.product_id, oi.quantity, oi.unit_price, oi.item_status, oi.tracking_note,
                       p.name AS product_name, o.shipping_address, o.payment_method, o.created_at,
                       u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
                FROM order_items oi
                INNER JOIN products p ON p.id = oi.product_id
                INNER JOIN orders o ON o.id = oi.order_id
                INNER JOIN users u ON u.id = o.customer_id
                WHERE oi.seller_id = ?";

        if ($hasStatusFilter) {
            $sql .= " AND oi.item_status = ?";
        }

        $sql .= " ORDER BY o.created_at DESC, oi.id DESC";

        $stmt = $this->conn->prepare($sql);

        if ($hasStatusFilter) {
            $stmt->bind_param("is", $sellerId, $status);
        } else {
            $stmt->bind_param("i", $sellerId);
        }

        $stmt->execute();

        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }

        $stmt->close();
        return $orders;
    }

    public function getVendorOrderItemsGroupedByOrderIds(int $sellerId, array $orderIds): array
    {
        $this->ensureOrderItemTrackingNoteColumn();

        $orderIds = array_values(array_unique(array_filter(array_map('intval', $orderIds))));

        if (empty($orderIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $types = 'i' . str_repeat('i', count($orderIds));
        $params = array_merge([$sellerId], $orderIds);
        $bindParams = [$types];

        $stmt = $this->conn->prepare(
            "SELECT oi.id AS order_item_id, oi.order_id, oi.product_id, oi.quantity, oi.unit_price, oi.item_status, oi.tracking_note,
                    p.name AS product_name, o.shipping_address, o.payment_method, o.created_at,
                    u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
             FROM order_items oi
             INNER JOIN products p ON p.id = oi.product_id
             INNER JOIN orders o ON o.id = oi.order_id
             INNER JOIN users u ON u.id = o.customer_id
             WHERE oi.seller_id = ? AND oi.order_id IN ($placeholders)
             ORDER BY o.created_at DESC, oi.id DESC"
        );

        foreach ($params as $index => $value) {
            $bindParams[] = &$params[$index];
        }

        call_user_func_array([$stmt, 'bind_param'], $bindParams);
        $stmt->execute();

        $grouped = [];
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $grouped[(int) $row['order_id']][] = $row;
        }

        $stmt->close();
        return $grouped;
    }

    public function updateVendorOrderItemStatus(int $sellerId, int $orderItemId, string $status, ?string $trackingNote = null): bool
    {
        $this->ensureOrderItemTrackingNoteColumn();

        $orderStmt = $this->conn->prepare("SELECT order_id FROM order_items WHERE id = ? AND seller_id = ? LIMIT 1");
        $orderStmt->bind_param("ii", $orderItemId, $sellerId);
        $orderStmt->execute();
        $order = $orderStmt->get_result()->fetch_assoc();
        $orderStmt->close();

        if (!$order) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "UPDATE order_items
             SET item_status = ?, tracking_note = ?
             WHERE id = ? AND seller_id = ?"
        );
        $stmt->bind_param("ssii", $status, $trackingNote, $orderItemId, $sellerId);
        $updated = $stmt->execute();
        $stmt->close();

        if ($updated) {
            $this->syncOrderStatusFromItems((int) $order['order_id']);
        }

        return $updated;
    }

    private function syncOrderStatusFromItems(int $orderId): void
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS total_items,
                    SUM(CASE WHEN item_status = 'pending' THEN 1 ELSE 0 END) AS pending_items,
                    SUM(CASE WHEN item_status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_items,
                    SUM(CASE WHEN item_status = 'shipped' THEN 1 ELSE 0 END) AS shipped_items,
                    SUM(CASE WHEN item_status = 'delivered' THEN 1 ELSE 0 END) AS delivered_items
             FROM order_items
             WHERE order_id = ?"
        );
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $summary = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $total = (int) ($summary['total_items'] ?? 0);
        if ($total === 0) {
            return;
        }

        $pending = (int) ($summary['pending_items'] ?? 0);
        $confirmed = (int) ($summary['confirmed_items'] ?? 0);
        $shipped = (int) ($summary['shipped_items'] ?? 0);
        $delivered = (int) ($summary['delivered_items'] ?? 0);

        if ($delivered === $total) {
            $nextStatus = 'delivered';
        } elseif ($shipped === $total) {
            $nextStatus = 'shipped';
        } elseif ($shipped > 0 || ($confirmed > 0 && $pending > 0)) {
            $nextStatus = 'processing';
        } elseif ($confirmed === $total) {
            $nextStatus = 'confirmed';
        } elseif ($confirmed > 0) {
            $nextStatus = 'processing';
        } else {
            $nextStatus = 'pending';
        }

        $update = $this->conn->prepare(
            "UPDATE orders
             SET status = ?
             WHERE id = ?
               AND status NOT IN ('delivered', 'cancelled', 'return_requested', 'returned')"
        );
        $update->bind_param("si", $nextStatus, $orderId);
        $update->execute();
        $update->close();
    }

    public function getVendorReturnRequests(int $sellerId): array
    {
        $this->ensureReturnRequestResponseColumns();

        $requests = [];
        $stmt = $this->conn->prepare(
            "SELECT rr.id AS return_request_id, rr.order_id, rr.order_item_id, rr.customer_id,
                    rr.reason AS customer_reason, rr.status, rr.vendor_response_reason, rr.responded_at, rr.created_at,
                    oi.quantity, oi.unit_price, oi.item_status,
                    p.name AS product_name,
                    u.name AS customer_name, u.email AS customer_email
             FROM return_requests rr
             INNER JOIN order_items oi ON oi.id = rr.order_item_id
             INNER JOIN products p ON p.id = oi.product_id
             INNER JOIN users u ON u.id = rr.customer_id
             WHERE oi.seller_id = ?
             ORDER BY FIELD(rr.status, 'pending', 'approved', 'rejected', 'completed'), rr.created_at DESC, rr.id DESC"
        );
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();

        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }

        $stmt->close();
        return $requests;
    }

    public function updateVendorReturnRequest(int $sellerId, int $returnRequestId, string $status, string $vendorReason): bool
    {
        $this->ensureReturnRequestResponseColumns();

        if (!in_array($status, ['approved', 'rejected'], true)) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "UPDATE return_requests rr
             INNER JOIN order_items oi ON oi.id = rr.order_item_id
             SET rr.status = ?, rr.vendor_response_reason = ?, rr.responded_at = NOW()
             WHERE rr.id = ? AND oi.seller_id = ? AND rr.status = 'pending'"
        );
        $stmt->bind_param("ssii", $status, $vendorReason, $returnRequestId, $sellerId);
        $updated = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();

        return $updated;
    }

    public function getVendorReviews(int $sellerId): array
    {
        $reviews = [];
        $stmt = $this->conn->prepare(
            "SELECT r.id, r.product_id, r.order_id, r.customer_id, r.rating, r.review_text, r.seller_reply, r.created_at,
                    p.name AS product_name,
                    u.name AS customer_name, u.email AS customer_email
             FROM reviews r
             INNER JOIN products p ON p.id = r.product_id
             INNER JOIN users u ON u.id = r.customer_id
             WHERE p.seller_id = ?
             ORDER BY r.created_at DESC, r.id DESC"
        );
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();

        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }

        $stmt->close();
        return $reviews;
    }

    public function replyToVendorReview(int $sellerId, int $reviewId, string $reply): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE reviews r
             INNER JOIN products p ON p.id = r.product_id
             SET r.seller_reply = ?
             WHERE r.id = ? AND p.seller_id = ?"
        );
        $stmt->bind_param("sii", $reply, $reviewId, $sellerId);
        $updated = $stmt->execute();
        $stmt->close();

        return $updated;
    }

    public function getVendorAnalytics(int $sellerId): array
    {
        $summary = [
            'total_revenue' => 0,
            'order_volume' => 0,
            'average_order_value' => 0,
        ];

        $stmt = $this->conn->prepare(
            "SELECT COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS total_revenue,
                    COUNT(DISTINCT oi.order_id) AS order_volume
             FROM order_items oi
             WHERE oi.seller_id = ?"
        );
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $summary['total_revenue'] = (float) $row['total_revenue'];
            $summary['order_volume'] = (int) $row['order_volume'];
            $summary['average_order_value'] = $summary['order_volume'] > 0
                ? $summary['total_revenue'] / $summary['order_volume']
                : 0;
        }

        $topProducts = [];
        $stmt = $this->conn->prepare(
            "SELECT p.name, SUM(oi.quantity) AS units_sold,
                    COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS revenue
             FROM order_items oi
             INNER JOIN products p ON p.id = oi.product_id
             WHERE oi.seller_id = ?
             GROUP BY p.id, p.name
             ORDER BY units_sold DESC, revenue DESC
             LIMIT 5"
        );
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $topProducts[] = $row;
        }
        $stmt->close();

        return [
            'summary' => $summary,
            'top_products' => $topProducts,
            'periods' => [
                'day' => $this->getVendorAnalyticsPeriod($sellerId, 'day'),
                'week' => $this->getVendorAnalyticsPeriod($sellerId, 'week'),
                'month' => $this->getVendorAnalyticsPeriod($sellerId, 'month'),
            ],
        ];
    }

    private function getVendorAnalyticsPeriod(int $sellerId, string $period): array
    {
        $formats = [
            'day' => "%Y-%m-%d",
            'week' => "%x-W%v",
            'month' => "%Y-%m",
        ];
        $format = $formats[$period] ?? $formats['day'];
        $rows = [];

        $stmt = $this->conn->prepare(
            "SELECT DATE_FORMAT(o.created_at, '$format') AS period_label,
                    COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS revenue,
                    COUNT(DISTINCT oi.order_id) AS order_volume
             FROM order_items oi
             INNER JOIN orders o ON o.id = oi.order_id
             WHERE oi.seller_id = ?
             GROUP BY period_label
             ORDER BY period_label DESC
             LIMIT 12"
        );
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $orderVolume = (int) $row['order_volume'];
            $revenue = (float) $row['revenue'];
            $rows[] = [
                'period_label' => $row['period_label'],
                'revenue' => $revenue,
                'order_volume' => $orderVolume,
                'average_order_value' => $orderVolume > 0 ? $revenue / $orderVolume : 0,
            ];
        }

        $stmt->close();
        return $rows;
    }

    public function getVendorEarnings(int $sellerId): array
    {
        $seller = $this->findSellerById($sellerId);
        $commissionRate = (float) ($seller['commission_rate'] ?? 10);

        $summary = [
            'total_earned' => 0,
            'commission_rate' => $commissionRate,
            'commission_deducted' => 0,
            'net_payout' => 0,
        ];

        $stmt = $this->conn->prepare(
            "SELECT COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS total_earned
             FROM order_items oi
             WHERE oi.seller_id = ?"
        );
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $summary['total_earned'] = (float) $row['total_earned'];
            $summary['commission_deducted'] = $summary['total_earned'] * ($commissionRate / 100);
            $summary['net_payout'] = $summary['total_earned'] - $summary['commission_deducted'];
        }

        return [
            'summary' => $summary,
            'periods' => [
                'day' => $this->getVendorEarningsPeriod($sellerId, 'day', $commissionRate),
                'week' => $this->getVendorEarningsPeriod($sellerId, 'week', $commissionRate),
                'month' => $this->getVendorEarningsPeriod($sellerId, 'month', $commissionRate),
            ],
        ];
    }

    private function findSellerById(int $sellerId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM sellers WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();

        $seller = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $seller ?: null;
    }

    private function getVendorEarningsPeriod(int $sellerId, string $period, float $commissionRate): array
    {
        $formats = [
            'day' => "%Y-%m-%d",
            'week' => "%x-W%v",
            'month' => "%Y-%m",
        ];
        $format = $formats[$period] ?? $formats['day'];
        $rows = [];

        $stmt = $this->conn->prepare(
            "SELECT DATE_FORMAT(o.created_at, '$format') AS period_label,
                    COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS total_earned
             FROM order_items oi
             INNER JOIN orders o ON o.id = oi.order_id
             WHERE oi.seller_id = ?
             GROUP BY period_label
             ORDER BY period_label DESC
             LIMIT 12"
        );
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $earned = (float) $row['total_earned'];
            $commission = $earned * ($commissionRate / 100);

            $rows[] = [
                'period_label' => $row['period_label'],
                'total_earned' => $earned,
                'commission_deducted' => $commission,
                'net_payout' => $earned - $commission,
            ];
        }

        $stmt->close();
        return $rows;
    }

    private function ensureOrderItemTrackingNoteColumn(): void
    {
        $result = $this->conn->query("SHOW COLUMNS FROM order_items LIKE 'tracking_note'");

        if ($result && $result->num_rows > 0) {
            return;
        }

        $this->conn->query("ALTER TABLE order_items ADD tracking_note text DEFAULT NULL AFTER item_status");
    }

    private function ensureReturnRequestResponseColumns(): void
    {
        $reasonColumn = $this->conn->query("SHOW COLUMNS FROM return_requests LIKE 'vendor_response_reason'");

        if (!$reasonColumn || $reasonColumn->num_rows === 0) {
            $this->conn->query("ALTER TABLE return_requests ADD vendor_response_reason text DEFAULT NULL AFTER status");
        }

        $respondedAtColumn = $this->conn->query("SHOW COLUMNS FROM return_requests LIKE 'responded_at'");

        if (!$respondedAtColumn || $respondedAtColumn->num_rows === 0) {
            $this->conn->query("ALTER TABLE return_requests ADD responded_at datetime DEFAULT NULL AFTER vendor_response_reason");
        }
    }

    public function emailExistsForAnotherUser(string $email, int $userId): bool
    {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
        $stmt->bind_param("si", $email, $userId);
        $stmt->execute();

        $exists = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $exists;
    }

    public function updateVendorProfile(int $userId, array $data): bool
    {
        $this->conn->begin_transaction();

        $profilePic = $data['profile_pic'] ?? null;
        $passwordHash = $data['password_hash'] ?? null;

        if ($profilePic !== null && $passwordHash !== null) {
            $stmt = $this->conn->prepare(
                "UPDATE users
                 SET name = ?, email = ?, phone = ?, profile_pic = ?, password_hash = ?
                 WHERE id = ? AND role = 'vendor'"
            );
            $stmt->bind_param("sssssi", $data['name'], $data['email'], $data['phone'], $profilePic, $passwordHash, $userId);
        } elseif ($profilePic !== null) {
            $stmt = $this->conn->prepare(
                "UPDATE users
                 SET name = ?, email = ?, phone = ?, profile_pic = ?
                 WHERE id = ? AND role = 'vendor'"
            );
            $stmt->bind_param("ssssi", $data['name'], $data['email'], $data['phone'], $profilePic, $userId);
        } elseif ($passwordHash !== null) {
            $stmt = $this->conn->prepare(
                "UPDATE users
                 SET name = ?, email = ?, phone = ?, password_hash = ?
                 WHERE id = ? AND role = 'vendor'"
            );
            $stmt->bind_param("ssssi", $data['name'], $data['email'], $data['phone'], $passwordHash, $userId);
        } else {
            $stmt = $this->conn->prepare(
                "UPDATE users
                 SET name = ?, email = ?, phone = ?
                 WHERE id = ? AND role = 'vendor'"
            );
            $stmt->bind_param("sssi", $data['name'], $data['email'], $data['phone'], $userId);
        }

        $userUpdated = $stmt->execute();
        $stmt->close();

        if (!$userUpdated) {
            $this->conn->rollback();
            return false;
        }

        $sellerStmt = $this->conn->prepare(
            "INSERT INTO sellers (user_id, shop_name, shop_description, shop_logo_path, address, is_approved)
             VALUES (?, ?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE
                shop_name = VALUES(shop_name),
                shop_description = VALUES(shop_description),
                shop_logo_path = COALESCE(VALUES(shop_logo_path), shop_logo_path),
                address = VALUES(address)"
        );

        $sellerStmt->bind_param(
            "issss",
            $userId,
            $data['shop_name'],
            $data['shop_description'],
            $profilePic,
            $data['shop_address']
        );

        $sellerUpdated = $sellerStmt->execute();
        $sellerStmt->close();

        if (!$sellerUpdated) {
            $this->conn->rollback();
            return false;
        }

        $this->conn->commit();
        return true;
    }

    public function updateDeliveryManagerProfile(int $userId, array $data): bool
    {
        $profilePic = $data['profile_pic'] ?? null;
        $passwordHash = $data['password_hash'] ?? null;

        if ($profilePic !== null && $passwordHash !== null) {
            $stmt = $this->conn->prepare(
                "UPDATE users
                 SET name = ?, email = ?, phone = ?, profile_pic = ?, password_hash = ?
                 WHERE id = ? AND role = 'delivery_manager'"
            );
            $stmt->bind_param("sssssi", $data['name'], $data['email'], $data['phone'], $profilePic, $passwordHash, $userId);
        } elseif ($profilePic !== null) {
            $stmt = $this->conn->prepare(
                "UPDATE users
                 SET name = ?, email = ?, phone = ?, profile_pic = ?
                 WHERE id = ? AND role = 'delivery_manager'"
            );
            $stmt->bind_param("ssssi", $data['name'], $data['email'], $data['phone'], $profilePic, $userId);
        } elseif ($passwordHash !== null) {
            $stmt = $this->conn->prepare(
                "UPDATE users
                 SET name = ?, email = ?, phone = ?, password_hash = ?
                 WHERE id = ? AND role = 'delivery_manager'"
            );
            $stmt->bind_param("ssssi", $data['name'], $data['email'], $data['phone'], $passwordHash, $userId);
        } else {
            $stmt = $this->conn->prepare(
                "UPDATE users
                 SET name = ?, email = ?, phone = ?
                 WHERE id = ? AND role = 'delivery_manager'"
            );
            $stmt->bind_param("sssi", $data['name'], $data['email'], $data['phone'], $userId);
        }

        $updated = $stmt->execute();
        $stmt->close();

        return $updated;
    }

    public function getDeliveryAgents(): array
    {
        $this->ensureDeliveryAgentColumns();

        $agents = [];
        $result = $this->conn->query(
            "SELECT da.id, da.name, da.phone, da.vehicle_type, da.is_active, da.created_at,
                    COUNT(CASE WHEN d.status IN ('assigned', 'picked_up', 'in_transit') THEN 1 END) AS active_deliveries_count
             FROM delivery_agents da
             LEFT JOIN delivery_assignments d ON d.agent_id = da.id
             GROUP BY da.id, da.name, da.phone, da.vehicle_type, da.is_active, da.created_at
             ORDER BY da.is_active DESC, da.created_at DESC, da.id DESC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $agents[] = $row;
            }
        }

        return $agents;
    }

    public function saveDeliveryAgent(int $agentId, array $data): bool
    {
        $this->ensureDeliveryAgentColumns();

        if ($agentId > 0) {
            $stmt = $this->conn->prepare(
                "UPDATE delivery_agents
                 SET name = ?, phone = ?, vehicle_type = ?, is_active = ?
                 WHERE id = ?"
            );
            $stmt->bind_param(
                "sssii",
                $data['name'],
                $data['phone'],
                $data['vehicle_type'],
                $data['is_active'],
                $agentId
            );
        } else {
            $stmt = $this->conn->prepare(
                "INSERT INTO delivery_agents (user_id, name, phone, vehicle_type, is_active)
                 VALUES (NULL, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "sssi",
                $data['name'],
                $data['phone'],
                $data['vehicle_type'],
                $data['is_active']
            );
        }

        $saved = $stmt->execute();
        $stmt->close();

        return $saved;
    }

    public function toggleDeliveryAgent(int $agentId): bool
    {
        $this->ensureDeliveryAgentColumns();

        $stmt = $this->conn->prepare(
            "UPDATE delivery_agents
             SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END
             WHERE id = ?"
        );
        $stmt->bind_param("i", $agentId);
        $updated = $stmt->execute();
        $stmt->close();

        return $updated;
    }

    public function getDeliveryZones(): array
    {
        $zones = [];
        $result = $this->conn->query(
            "SELECT id, zone_name, delivery_fee, estimated_days
             FROM delivery_zones
             ORDER BY zone_name ASC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $zones[] = $row;
            }
        }

        return $zones;
    }

    public function saveDeliveryZone(int $zoneId, array $data): bool
    {
        if ($zoneId > 0) {
            $stmt = $this->conn->prepare(
                "UPDATE delivery_zones
                 SET zone_name = ?, delivery_fee = ?, estimated_days = ?
                 WHERE id = ?"
            );
            $stmt->bind_param(
                "sdii",
                $data['zone_name'],
                $data['delivery_fee'],
                $data['estimated_days'],
                $zoneId
            );
        } else {
            $stmt = $this->conn->prepare(
                "INSERT INTO delivery_zones (zone_name, delivery_fee, estimated_days)
                 VALUES (?, ?, ?)"
            );
            $stmt->bind_param(
                "sdi",
                $data['zone_name'],
                $data['delivery_fee'],
                $data['estimated_days']
            );
        }

        $saved = $stmt->execute();
        $stmt->close();

        return $saved;
    }

    public function deleteDeliveryZone(int $zoneId): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM delivery_zones WHERE id = ?");
        $stmt->bind_param("i", $zoneId);
        $deleted = $stmt->execute();
        $stmt->close();

        return $deleted;
    }

    private function ensureDeliveryAgentColumns(): void
    {
        $nameColumn = $this->conn->query("SHOW COLUMNS FROM delivery_agents LIKE 'name'");

        if (!$nameColumn || $nameColumn->num_rows === 0) {
            $this->conn->query("ALTER TABLE delivery_agents ADD name varchar(100) NOT NULL DEFAULT 'Delivery Agent' AFTER user_id");
        }

        $userIdColumn = $this->conn->query("SHOW COLUMNS FROM delivery_agents LIKE 'user_id'");
        $userId = $userIdColumn ? $userIdColumn->fetch_assoc() : null;

        if ($userId && strtoupper((string) ($userId['Null'] ?? '')) === 'NO') {
            $this->conn->query("ALTER TABLE delivery_agents MODIFY user_id int(11) DEFAULT NULL");
        }
    }

    public function create(array $data): bool
    {
        $this->conn->begin_transaction();

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
        $userId = (int) $this->conn->insert_id;
        $stmt->close();

        if (!$created) {
            $this->conn->rollback();
            return false;
        }

        if (($data['role'] ?? '') === 'vendor') {
            $shopName = trim((string) ($data['shop_name'] ?? ''));
            $shopName = $shopName !== '' ? $shopName : $data['name'] . "'s Store";
            $shopDescription = trim((string) ($data['shop_description'] ?? ''));
            $shopDescription = $shopDescription !== '' ? $shopDescription : 'Vendor storefront profile.';
            $shopLogoPath = $data['shop_logo_path'] ?? $data['profile_pic'] ?? null;
            $shopAddress = trim((string) ($data['shop_address'] ?? ''));
            $shopAddress = $shopAddress !== '' ? $shopAddress : 'Not provided';

            $sellerStmt = $this->conn->prepare(
                "INSERT INTO sellers (user_id, shop_name, shop_description, shop_logo_path, address, is_approved)
                 VALUES (?, ?, ?, ?, ?, 0)"
            );

            $sellerStmt->bind_param(
                "issss",
                $userId,
                $shopName,
                $shopDescription,
                $shopLogoPath,
                $shopAddress
            );

            $sellerCreated = $sellerStmt->execute();
            $sellerStmt->close();

            if (!$sellerCreated) {
                $this->conn->rollback();
                return false;
            }
        }

        $this->conn->commit();
        return true;
    }
}
