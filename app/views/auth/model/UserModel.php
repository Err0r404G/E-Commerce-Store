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

    public function findSellerByUserId(int $userId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM sellers WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $seller = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $seller ?: null;
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

    public function getVendorOrderItems(int $sellerId): array
    {
        $this->ensureOrderItemTrackingNoteColumn();

        $orders = [];
        $stmt = $this->conn->prepare(
            "SELECT oi.id AS order_item_id, oi.order_id, oi.product_id, oi.quantity, oi.unit_price, oi.item_status, oi.tracking_note,
                    p.name AS product_name, o.shipping_address, o.payment_method, o.created_at,
                    u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
             FROM order_items oi
             INNER JOIN products p ON p.id = oi.product_id
             INNER JOIN orders o ON o.id = oi.order_id
             INNER JOIN users u ON u.id = o.customer_id
             WHERE oi.seller_id = ?
             ORDER BY o.created_at DESC, oi.id DESC"
        );
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();

        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }

        $stmt->close();
        return $orders;
    }

    public function updateVendorOrderItemStatus(int $sellerId, int $orderItemId, string $status, ?string $trackingNote = null): bool
    {
        $this->ensureOrderItemTrackingNoteColumn();

        $stmt = $this->conn->prepare(
            "UPDATE order_items
             SET item_status = ?, tracking_note = ?
             WHERE id = ? AND seller_id = ?"
        );
        $stmt->bind_param("ssii", $status, $trackingNote, $orderItemId, $sellerId);
        $updated = $stmt->execute();
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
            $sellerStmt = $this->conn->prepare(
                "INSERT INTO sellers (user_id, shop_name, shop_description, shop_logo_path, address, is_approved)
                 VALUES (?, ?, ?, ?, ?, 0)"
            );

            $sellerStmt->bind_param(
                "issss",
                $userId,
                $data['shop_name'],
                $data['shop_description'],
                $data['shop_logo_path'],
                $data['shop_address']
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
