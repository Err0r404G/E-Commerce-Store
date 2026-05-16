<?php

class CustomerAreaModel
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function categories(): array
    {
        $result = $this->conn->query("SELECT id, name FROM categories ORDER BY name");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function deliveryZones(): array
    {
        $result = $this->conn->query("SELECT id, zone_name, delivery_fee, estimated_days FROM delivery_zones ORDER BY delivery_fee, zone_name");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function deliveryZone(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT id, zone_name, delivery_fee, estimated_days FROM delivery_zones WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $zone = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $zone ?: null;
    }

    public function customerProfile(int $customerId): ?array
    {
        $stmt = $this->conn->prepare("SELECT id, name, email, password_hash, phone, profile_pic FROM users WHERE id = ? AND role = 'customer' LIMIT 1");
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $profile = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $profile ?: null;
    }

    public function updateCustomerProfile(int $customerId, string $name, string $phone): bool
    {
        $stmt = $this->conn->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ? AND role = 'customer'");
        $stmt->bind_param('ssi', $name, $phone, $customerId);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function updateCustomerPassword(int $customerId, string $passwordHash): bool
    {
        $stmt = $this->conn->prepare("UPDATE users SET password_hash = ? WHERE id = ? AND role = 'customer'");
        $stmt->bind_param('si', $passwordHash, $customerId);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function updateCustomerProfilePicture(int $customerId, string $path): bool
    {
        $stmt = $this->conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ? AND role = 'customer'");
        $stmt->bind_param('si', $path, $customerId);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function products(array $filters = []): array
    {
        $sql = "SELECT p.*, c.name AS category_name, s.shop_name,
                       COALESCE(AVG(r.rating), 0) AS avg_rating,
                       COUNT(r.id) AS review_count
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                INNER JOIN sellers s ON s.id = p.seller_id
                LEFT JOIN reviews r ON r.product_id = p.id
                WHERE p.is_available = 1";
        $types = '';
        $params = [];

        if (($filters['keyword'] ?? '') !== '') {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR s.shop_name LIKE ?)";
            $keyword = '%' . $filters['keyword'] . '%';
            array_push($params, $keyword, $keyword, $keyword);
            $types .= 'sss';
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = (int) $filters['category_id'];
            $types .= 'i';
        }

        if (($filters['min_price'] ?? '') !== '') {
            $sql .= " AND p.price >= ?";
            $params[] = (float) $filters['min_price'];
            $types .= 'd';
        }

        if (($filters['max_price'] ?? '') !== '') {
            $sql .= " AND p.price <= ?";
            $params[] = (float) $filters['max_price'];
            $types .= 'd';
        }

        if (!empty($filters['in_stock'])) {
            $sql .= " AND p.stock_qty > 0";
        }

        $sql .= " GROUP BY p.id";

        if (($filters['min_rating'] ?? '') !== '') {
            $sql .= " HAVING avg_rating >= ?";
            $params[] = (float) $filters['min_rating'];
            $types .= 'd';
        }

        $sort = $filters['sort'] ?? 'newest';
        $sql .= match ($sort) {
            'price_low' => " ORDER BY p.price ASC",
            'price_high' => " ORDER BY p.price DESC",
            'rating' => " ORDER BY avg_rating DESC, review_count DESC",
            default => " ORDER BY p.created_at DESC",
        };

        $stmt = $this->conn->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    public function product(int $id): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT p.*, c.name AS category_name, s.shop_name, s.id AS seller_id,
                    COALESCE(AVG(r.rating), 0) AS avg_rating, COUNT(r.id) AS review_count
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             INNER JOIN sellers s ON s.id = p.seller_id
             LEFT JOIN reviews r ON r.product_id = p.id
             WHERE p.id = ?
             GROUP BY p.id
             LIMIT 1"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $product ?: null;
    }

    public function productImages(int $productId): array
    {
        $stmt = $this->conn->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY display_order, id");
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    public function reviews(int $productId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT r.*, u.name AS customer_name
             FROM reviews r
             INNER JOIN users u ON u.id = r.customer_id
             WHERE r.product_id = ?
             ORDER BY r.created_at DESC"
        );
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    public function cartProducts(array $cart): array
    {
        if (!$cart) {
            return [];
        }

        $ids = array_map('intval', array_keys($cart));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $stmt = $this->conn->prepare(
            "SELECT p.id, p.seller_id, p.name, p.price, p.stock_qty, p.primary_image_path, s.shop_name
             FROM products p
             INNER JOIN sellers s ON s.id = p.seller_id
             WHERE p.id IN ($placeholders) AND p.is_available = 1"
        );
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($rows as &$row) {
            $row['quantity'] = min((int) ($cart[$row['id']] ?? 1), max(1, (int) $row['stock_qty']));
            $row['line_total'] = $row['quantity'] * (float) $row['price'];
        }

        return $rows;
    }

    public function coupon(string $code): ?array
    {
        $platformCoupon = $this->platformCoupon($code);

        if ($platformCoupon) {
            return $platformCoupon;
        }

        $stmt = $this->conn->prepare(
            "SELECT id, code, discount_pct, 'vendor' AS funding_source
             FROM coupons
             WHERE code = ? AND is_active = 1 AND valid_until >= CURDATE() AND uses_count < max_uses
             LIMIT 1"
        );
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $coupon = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $coupon ?: null;
    }

    private function platformCoupon(string $code): ?array
    {
        if (!$this->tableExists('platform_coupons')) {
            return null;
        }

        $stmt = $this->conn->prepare(
            "SELECT id, code, discount_pct, 'platform' AS funding_source
             FROM platform_coupons
             WHERE code = ? AND is_active = 1 AND valid_until >= CURDATE() AND uses_count < max_uses
             LIMIT 1"
        );
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $coupon = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $coupon ?: null;
    }

    public function addresses(int $customerId): array
    {
        if (!$this->tableExists('customer_addresses')) {
            return [];
        }

        $stmt = $this->conn->prepare(
            "SELECT a.*, z.zone_name, z.delivery_fee, z.estimated_days
             FROM customer_addresses a
             LEFT JOIN delivery_zones z ON z.id = a.delivery_zone_id
             WHERE a.customer_id = ?
             ORDER BY a.is_default DESC, a.created_at DESC"
        );
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    public function saveAddress(int $customerId, array $data): bool
    {
        if (!$this->tableExists('customer_addresses')) {
            return false;
        }

        if (!empty($data['is_default'])) {
            $clear = $this->conn->prepare("UPDATE customer_addresses SET is_default = 0 WHERE customer_id = ?");
            $clear->bind_param('i', $customerId);
            $clear->execute();
            $clear->close();
        }

        $isDefault = !empty($data['is_default']) ? 1 : 0;
        $zoneId = $data['delivery_zone_id'] !== '' ? (int) $data['delivery_zone_id'] : null;

        if (!empty($data['id'])) {
            $stmt = $this->conn->prepare(
                "UPDATE customer_addresses
                 SET label = ?, recipient_name = ?, phone = ?, address_line = ?, city = ?, postal_code = ?, delivery_zone_id = ?, is_default = ?
                 WHERE id = ? AND customer_id = ?"
            );
            $stmt->bind_param(
                'ssssssiiii',
                $data['label'],
                $data['recipient_name'],
                $data['phone'],
                $data['address_line'],
                $data['city'],
                $data['postal_code'],
                $zoneId,
                $isDefault,
                $data['id'],
                $customerId
            );
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO customer_addresses (customer_id, label, recipient_name, phone, address_line, city, postal_code, delivery_zone_id, is_default)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'issssssii',
            $customerId,
            $data['label'],
            $data['recipient_name'],
            $data['phone'],
            $data['address_line'],
            $data['city'],
            $data['postal_code'],
            $zoneId,
            $isDefault
        );
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function deleteAddress(int $customerId, int $addressId): bool
    {
        if (!$this->tableExists('customer_addresses')) {
            return false;
        }

        $stmt = $this->conn->prepare("DELETE FROM customer_addresses WHERE id = ? AND customer_id = ?");
        $stmt->bind_param('ii', $addressId, $customerId);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function setDefaultAddress(int $customerId, int $addressId): bool
    {
        if (!$this->tableExists('customer_addresses')) {
            return false;
        }

        $this->conn->begin_transaction();
        try {
            $clear = $this->conn->prepare("UPDATE customer_addresses SET is_default = 0 WHERE customer_id = ?");
            $clear->bind_param('i', $customerId);
            $clear->execute();
            $clear->close();

            $stmt = $this->conn->prepare("UPDATE customer_addresses SET is_default = 1 WHERE id = ? AND customer_id = ?");
            $stmt->bind_param('ii', $addressId, $customerId);
            $stmt->execute();
            $ok = $stmt->affected_rows > 0;
            $stmt->close();

            $this->conn->commit();
            return $ok;
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function orders(int $customerId): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC");
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    public function order(int $customerId, int $orderId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM orders WHERE customer_id = ? AND id = ? LIMIT 1");
        $stmt->bind_param('ii', $customerId, $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $order ?: null;
    }

    public function orderItems(int $orderId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT oi.*, p.name, p.primary_image_path, s.shop_name,
                    rv.rating AS own_rating, rv.review_text AS own_review_text,
                    rr.status AS return_status, rr.reason AS return_reason
             FROM order_items oi
             INNER JOIN products p ON p.id = oi.product_id
             INNER JOIN sellers s ON s.id = oi.seller_id
             LEFT JOIN reviews rv ON rv.product_id = oi.product_id AND rv.order_id = oi.order_id
             LEFT JOIN return_requests rr ON rr.order_item_id = oi.id
             WHERE oi.order_id = ?"
        );
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    public function createOrder(int $customerId, array $items, array $data): int
    {
        $this->conn->begin_transaction();

        try {
            $subtotal = array_sum(array_column($items, 'line_total'));
            $coupon = $data['coupon_code'] !== '' ? $this->coupon($data['coupon_code']) : null;
            $discount = $coupon ? round($subtotal * ((float) $coupon['discount_pct'] / 100), 2) : 0.0;
            $deliveryFee = (float) ($data['delivery_fee'] ?? 0);
            $total = max(0, $subtotal - $discount + $deliveryFee);
            $couponUsageId = $coupon ? (int) $coupon['id'] : null;
            $couponId = $coupon && ($coupon['funding_source'] ?? 'vendor') === 'vendor' ? (int) $coupon['id'] : null;

            $stmt = $this->conn->prepare(
                "INSERT INTO orders (customer_id, shipping_address, payment_method, subtotal, discount_amount, total_amount, coupon_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('issdddi', $customerId, $data['shipping_address'], $data['payment_method'], $subtotal, $discount, $total, $couponId);
            $stmt->execute();
            $orderId = (int) $stmt->insert_id;
            $stmt->close();

            $itemStmt = $this->conn->prepare(
                "INSERT INTO order_items (order_id, product_id, seller_id, quantity, unit_price)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stockStmt = $this->conn->prepare("UPDATE products SET stock_qty = GREATEST(stock_qty - ?, 0) WHERE id = ?");

            foreach ($items as $item) {
                $productId = (int) $item['id'];
                $sellerId = (int) $item['seller_id'];
                $quantity = (int) $item['quantity'];
                $unitPrice = (float) $item['price'];
                $itemStmt->bind_param('iiiid', $orderId, $productId, $sellerId, $quantity, $unitPrice);
                $itemStmt->execute();
                $stockStmt->bind_param('ii', $quantity, $productId);
                $stockStmt->execute();
            }

            $itemStmt->close();
            $stockStmt->close();

            if ($coupon) {
                $couponTable = ($coupon['funding_source'] ?? 'vendor') === 'platform' ? 'platform_coupons' : 'coupons';
                $couponStmt = $this->conn->prepare("UPDATE {$couponTable} SET uses_count = uses_count + 1 WHERE id = ?");
                $couponStmt->bind_param('i', $couponUsageId);
                $couponStmt->execute();
                $couponStmt->close();
            }

            $this->conn->commit();
            return $orderId;
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function wishlist(int $customerId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT w.id AS wishlist_id, p.*, s.shop_name
             FROM wishlists w
             INNER JOIN products p ON p.id = w.product_id
             INNER JOIN sellers s ON s.id = p.seller_id
             WHERE w.customer_id = ?
             ORDER BY w.added_at DESC"
        );
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    public function wishlistProductIds(int $customerId): array
    {
        if ($customerId <= 0) {
            return [];
        }

        $stmt = $this->conn->prepare("SELECT product_id FROM wishlists WHERE customer_id = ?");
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return array_map('intval', array_column($rows, 'product_id'));
    }

    public function toggleWishlist(int $customerId, int $productId): bool
    {
        $stmt = $this->conn->prepare("SELECT id FROM wishlists WHERE customer_id = ? AND product_id = ? LIMIT 1");
        $stmt->bind_param('ii', $customerId, $productId);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            $delete = $this->conn->prepare("DELETE FROM wishlists WHERE id = ?");
            $delete->bind_param('i', $existing['id']);
            $ok = $delete->execute();
            $delete->close();
            return $ok;
        }

        $insert = $this->conn->prepare("INSERT INTO wishlists (customer_id, product_id) VALUES (?, ?)");
        $insert->bind_param('ii', $customerId, $productId);
        $ok = $insert->execute();
        $insert->close();

        return $ok;
    }

    public function saveReview(int $customerId, array $data): bool
    {
        if (!$this->canReviewProduct($customerId, (int) $data['order_id'], (int) $data['product_id'])) {
            return false;
        }

        $existing = $this->conn->prepare(
            "SELECT id FROM reviews WHERE product_id = ? AND order_id = ? AND customer_id = ? LIMIT 1"
        );
        $existing->bind_param('iii', $data['product_id'], $data['order_id'], $customerId);
        $existing->execute();
        $review = $existing->get_result()->fetch_assoc();
        $existing->close();

        if ($review) {
            $stmt = $this->conn->prepare("UPDATE reviews SET rating = ?, review_text = ? WHERE id = ? AND customer_id = ?");
            $stmt->bind_param('isii', $data['rating'], $data['review_text'], $review['id'], $customerId);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO reviews (product_id, order_id, customer_id, rating, review_text)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('iiiis', $data['product_id'], $data['order_id'], $customerId, $data['rating'], $data['review_text']);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function deleteReview(int $customerId, int $productId, int $orderId): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM reviews WHERE customer_id = ? AND product_id = ? AND order_id = ?");
        $stmt->bind_param('iii', $customerId, $productId, $orderId);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function requestReturn(int $customerId, int $orderId, int $orderItemId, string $reason): bool
    {
        if (!$this->canRequestReturn($customerId, $orderId, $orderItemId)) {
            return false;
        }

        $existing = $this->conn->prepare("SELECT id FROM return_requests WHERE order_item_id = ? AND customer_id = ? LIMIT 1");
        $existing->bind_param('ii', $orderItemId, $customerId);
        $existing->execute();
        $request = $existing->get_result()->fetch_assoc();
        $existing->close();

        if ($request) {
            $stmt = $this->conn->prepare("UPDATE return_requests SET reason = ?, status = 'pending' WHERE id = ? AND customer_id = ?");
            $stmt->bind_param('sii', $reason, $request['id'], $customerId);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO return_requests (order_id, order_item_id, customer_id, reason)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('iiis', $orderId, $orderItemId, $customerId, $reason);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function cancelOrder(int $customerId, int $orderId): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE orders SET status = 'cancelled'
             WHERE id = ? AND customer_id = ? AND status IN ('pending', 'confirmed', 'processing')"
        );
        $stmt->bind_param('ii', $orderId, $customerId);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();

        return $ok;
    }

    public function disputes(int $customerId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT d.*, s.shop_name
             FROM disputes d
             INNER JOIN sellers s ON s.id = d.seller_id
             WHERE d.customer_id = ?
             ORDER BY d.created_at DESC"
        );
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    public function disputeTargets(int $customerId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT o.id AS order_id, o.status, o.created_at, s.id AS seller_id, s.shop_name,
                    COUNT(oi.id) AS item_count
             FROM orders o
             INNER JOIN order_items oi ON oi.order_id = o.id
             INNER JOIN sellers s ON s.id = oi.seller_id
             WHERE o.customer_id = ?
             GROUP BY o.id, o.status, o.created_at, s.id, s.shop_name
             ORDER BY o.created_at DESC, s.shop_name ASC"
        );
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    public function createDispute(int $customerId, int $orderId, int $sellerId, string $description): bool
    {
        if (!$this->customerOrderHasSeller($customerId, $orderId, $sellerId)) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO disputes (customer_id, seller_id, order_id, description, status)
             VALUES (?, ?, ?, ?, 'open')"
        );
        $stmt->bind_param('iiis', $customerId, $sellerId, $orderId, $description);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT 1
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?
             LIMIT 1"
        );
        $stmt->bind_param('s', $table);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_row();
        $stmt->close();

        return $exists;
    }

    private function canReviewProduct(int $customerId, int $orderId, int $productId): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT 1
             FROM orders o
             INNER JOIN order_items oi ON oi.order_id = o.id
             WHERE o.id = ? AND o.customer_id = ? AND o.status = 'delivered' AND oi.product_id = ?
             LIMIT 1"
        );
        $stmt->bind_param('iii', $orderId, $customerId, $productId);
        $stmt->execute();
        $ok = (bool) $stmt->get_result()->fetch_row();
        $stmt->close();

        return $ok;
    }

    private function canRequestReturn(int $customerId, int $orderId, int $orderItemId): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT 1
             FROM orders o
             INNER JOIN order_items oi ON oi.order_id = o.id
             WHERE o.id = ? AND o.customer_id = ? AND o.status = 'delivered' AND oi.id = ?
             LIMIT 1"
        );
        $stmt->bind_param('iii', $orderId, $customerId, $orderItemId);
        $stmt->execute();
        $ok = (bool) $stmt->get_result()->fetch_row();
        $stmt->close();

        return $ok;
    }

    private function customerOrderHasSeller(int $customerId, int $orderId, int $sellerId): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT 1
             FROM orders o
             INNER JOIN order_items oi ON oi.order_id = o.id
             WHERE o.id = ? AND o.customer_id = ? AND oi.seller_id = ?
             LIMIT 1"
        );
        $stmt->bind_param('iii', $orderId, $customerId, $sellerId);
        $stmt->execute();
        $ok = (bool) $stmt->get_result()->fetch_row();
        $stmt->close();

        return $ok;
    }
}
