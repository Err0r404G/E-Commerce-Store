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

    public function getAssignAgentData(): array
    {
        [$orders, $dispatchStats] = $this->getReadyDispatchData();
        $agents = $this->getAvailableAgents();

        return [
            'orders' => $orders,
            'agents' => $agents,
            'zones' => $this->getDeliveryZones(),
            'stats' => [
                'ready_orders' => $dispatchStats['ready'],
                'available_agents' => count($agents),
                'active_assignments' => $this->countActiveAssignments(),
            ],
        ];
    }

    public function assignAgentToOrder(int $orderId, int $agentId, ?string $deliveryZone = null): array
    {
        if ($orderId <= 0 || $agentId <= 0) {
            return ['success' => false, 'status' => 422, 'message' => 'Select an order and an available agent.'];
        }

        if (!$this->isOrderReadyForDispatch($orderId)) {
            return ['success' => false, 'status' => 422, 'message' => 'This order is no longer ready for assignment.'];
        }

        if (!$this->isAgentAvailable($agentId)) {
            return ['success' => false, 'status' => 422, 'message' => 'Selected agent is not available.'];
        }

        $this->conn->begin_transaction();

        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO delivery_assignments (order_id, agent_id, status, delivery_zone)
                 VALUES (?, ?, 'assigned', ?)"
            );
            $stmt->bind_param('iis', $orderId, $agentId, $deliveryZone);
            $stmt->execute();
            $success = $stmt->affected_rows === 1;
            $stmt->close();

            if ($success) {
                $orderStatus = 'shipped';
                $statusStmt = $this->conn->prepare(
                    "UPDATE orders
                     SET status = ?
                     WHERE id = ? AND status IN ('pending', 'confirmed', 'processing', 'shipped')"
                );
                $statusStmt->bind_param('si', $orderStatus, $orderId);
                $statusStmt->execute();
                $statusStmt->close();
            }

            $this->conn->commit();
        } catch (mysqli_sql_exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'status' => 422, 'message' => 'Could not assign this order.'];
        }

        return [
            'success' => $success,
            'message' => $success ? 'Delivery agent assigned successfully.' : 'Delivery agent assignment failed.',
        ];
    }

    private function getAvailableAgents(): array
    {
        $this->ensureDeliveryAgentColumns();

        $agents = [];
        $result = $this->conn->query(
            "SELECT da.id, da.name, da.phone, da.vehicle_type, da.is_active,
                    COUNT(CASE WHEN d.status IN ('assigned', 'picked_up', 'in_transit') THEN 1 END) AS active_deliveries_count
             FROM delivery_agents da
             LEFT JOIN delivery_assignments d ON d.agent_id = da.id
             WHERE da.is_active = 1
             GROUP BY da.id, da.name, da.phone, da.vehicle_type, da.is_active
             ORDER BY active_deliveries_count ASC, da.name ASC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $agents[] = $row;
            }
        }

        return $agents;
    }

    private function getDeliveryZones(): array
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

    private function countActiveAssignments(): int
    {
        $result = $this->conn->query(
            "SELECT COUNT(*) AS total
             FROM delivery_assignments
             WHERE status IN ('assigned', 'picked_up', 'in_transit')"
        );
        $row = $result ? $result->fetch_assoc() : null;

        return (int) ($row['total'] ?? 0);
    }

    private function isAgentAvailable(int $agentId): bool
    {
        $this->ensureDeliveryAgentColumns();

        $stmt = $this->conn->prepare("SELECT id FROM delivery_agents WHERE id = ? AND is_active = 1 LIMIT 1");
        $stmt->bind_param('i', $agentId);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $exists;
    }

    private function isOrderReadyForDispatch(int $orderId): bool
    {
        $this->ensureOrderItemTrackingNoteColumn();

        $stmt = $this->conn->prepare(
            "SELECT o.id
             FROM orders o
             INNER JOIN order_items oi ON oi.order_id = o.id
             WHERE o.id = ?
               AND o.status NOT IN ('delivered', 'cancelled', 'return_requested', 'returned')
               AND NOT EXISTS (
                    SELECT 1
                    FROM delivery_assignments da
                    WHERE da.order_id = o.id
               )
             GROUP BY o.id
             HAVING COUNT(oi.id) > 0
                AND SUM(CASE WHEN oi.item_status = 'shipped' THEN 1 ELSE 0 END) = COUNT(oi.id)
             LIMIT 1"
        );
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $exists;
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

    private function ensureOrderItemTrackingNoteColumn(): void
    {
        $result = $this->conn->query("SHOW COLUMNS FROM order_items LIKE 'tracking_note'");

        if ($result && $result->num_rows > 0) {
            return;
        }

        $this->conn->query("ALTER TABLE order_items ADD tracking_note text DEFAULT NULL AFTER item_status");
    }
}
