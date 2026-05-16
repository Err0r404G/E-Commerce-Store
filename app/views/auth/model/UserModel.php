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

    public function getVendorOrderDetail(int $sellerId, int $orderId): ?array
    {
        $this->ensureOrderItemTrackingNoteColumn();

        $stmt = $this->conn->prepare(
            "SELECT o.id AS order_id, o.shipping_address, o.payment_method, o.created_at,
                    u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
             FROM orders o
             INNER JOIN users u ON u.id = o.customer_id
             WHERE o.id = ?
               AND EXISTS (
                   SELECT 1
                   FROM order_items oi
                   WHERE oi.order_id = o.id AND oi.seller_id = ?
               )
             LIMIT 1"
        );
        $stmt->bind_param("ii", $orderId, $sellerId);
        $stmt->execute();

        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$order) {
            return null;
        }

        $items = [];
        $itemsStmt = $this->conn->prepare(
            "SELECT oi.id AS order_item_id, oi.product_id, oi.quantity, oi.unit_price, oi.item_status, oi.tracking_note,
                    p.name AS product_name
             FROM order_items oi
             INNER JOIN products p ON p.id = oi.product_id
             WHERE oi.seller_id = ? AND oi.order_id = ?
             ORDER BY oi.id ASC"
        );
        $itemsStmt->bind_param("ii", $sellerId, $orderId);
        $itemsStmt->execute();

        $result = $itemsStmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        $itemsStmt->close();
        $order['items'] = $items;

        return $order;
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
