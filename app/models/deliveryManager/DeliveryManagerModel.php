<?php

class DeliveryManagerModel
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function getReadyDispatchData(): array
    {
        $this->ensureOrderItemTrackingNoteColumn();

        $orders = [];
        $result = $this->conn->query(
            "SELECT o.id, o.customer_id, o.shipping_address, o.payment_method, o.subtotal,
                    o.discount_amount, o.total_amount, o.status, o.created_at,
                    customer.name AS customer_name,
                    customer.email AS customer_email,
                    customer.phone AS customer_phone,
                    COUNT(oi.id) AS item_count,
                    COALESCE(SUM(oi.quantity), 0) AS total_quantity,
                    GROUP_CONCAT(DISTINCT COALESCE(s.shop_name, seller_user.name, 'Unknown seller') ORDER BY s.shop_name SEPARATOR ', ') AS seller_names,
                    GROUP_CONCAT(DISTINCT NULLIF(oi.tracking_note, '') ORDER BY oi.id SEPARATOR ' | ') AS tracking_notes
             FROM orders o
             INNER JOIN order_items oi ON oi.order_id = o.id
             LEFT JOIN users customer ON customer.id = o.customer_id
             LEFT JOIN sellers s ON s.id = oi.seller_id
             LEFT JOIN users seller_user ON seller_user.id = s.user_id
             WHERE o.status NOT IN ('delivered', 'cancelled', 'return_requested', 'returned')
               AND NOT EXISTS (
                    SELECT 1
                    FROM delivery_assignments da
                    WHERE da.order_id = o.id
               )
             GROUP BY o.id, o.customer_id, o.shipping_address, o.payment_method, o.subtotal,
                      o.discount_amount, o.total_amount, o.status, o.created_at,
                      customer.name, customer.email, customer.phone
             HAVING COUNT(oi.id) > 0
                AND SUM(CASE WHEN oi.item_status = 'shipped' THEN 1 ELSE 0 END) = COUNT(oi.id)
             ORDER BY o.created_at ASC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }

        $stats = [
            'ready' => count($orders),
            'items' => 0,
            'value' => 0.0,
            'oldest_days' => 0,
        ];

        foreach ($orders as $order) {
            $stats['items'] += (int) ($order['total_quantity'] ?? 0);
            $stats['value'] += (float) ($order['total_amount'] ?? 0);

            if (!empty($order['created_at'])) {
                $ageDays = max(0, (int) floor((time() - strtotime((string) $order['created_at'])) / 86400));
                $stats['oldest_days'] = max($stats['oldest_days'], $ageDays);
            }
        }

        return [$orders, $stats];
    }

    private function ensureOrderItemTrackingNoteColumn(): void
    {
        $result = $this->conn->query("SHOW COLUMNS FROM order_items LIKE 'tracking_note'");

        if ($result && $result->num_rows > 0) {
            return;
        }

        $this->conn->query("ALTER TABLE order_items ADD tracking_note text DEFAULT NULL AFTER item_status");
    }
}
