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

    public function getDashboardData(): array
    {
        $this->ensureDeliveryFailureColumns();

        [$readyOrders, $dispatchStats] = $this->getReadyDispatchData();
        [$activeDeliveries, $activeStats] = $this->getActiveDeliveriesData();

        $deliveredToday = 0;
        $deliveredResult = $this->conn->query(
            "SELECT COUNT(*) AS total
             FROM delivery_assignments
             WHERE status = 'delivered'
               AND (
                    DATE(completed_at) = CURDATE()
                    OR (completed_at IS NULL AND DATE(assigned_at) = CURDATE())
               )"
        );

        if ($deliveredResult) {
            $row = $deliveredResult->fetch_assoc();
            $deliveredToday = (int) ($row['total'] ?? 0);
        }

        return [
            'pending_dispatch' => (int) ($dispatchStats['ready'] ?? count($readyOrders)),
            'active_deliveries' => (int) ($activeStats['active'] ?? count($activeDeliveries)),
            'delivered_today' => $deliveredToday,
            'assigned' => (int) ($activeStats['assigned'] ?? 0),
            'picked_up' => (int) ($activeStats['picked_up'] ?? 0),
            'in_transit' => (int) ($activeStats['in_transit'] ?? 0),
            'ready_items' => (int) ($dispatchStats['items'] ?? 0),
            'ready_value' => (float) ($dispatchStats['value'] ?? 0),
            'oldest_pending_days' => (int) ($dispatchStats['oldest_days'] ?? 0),
            'recent_active' => array_slice($activeDeliveries, 0, 5),
        ];
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

    public function getActiveDeliveriesData(): array
    {
        $this->ensureDeliveryAgentColumns();

        $deliveries = [];
        $result = $this->conn->query(
            "SELECT da.id AS assignment_id, da.order_id, da.agent_id, da.assigned_at, da.status, da.delivery_zone,
                    TIMESTAMPDIFF(MINUTE, da.assigned_at, NOW()) AS minutes_since_assignment,
                    o.shipping_address, o.payment_method, o.total_amount,
                    customer.name AS customer_name,
                    customer.email AS customer_email,
                    agent.name AS agent_name,
                    agent.phone AS agent_phone,
                    agent.vehicle_type,
                    COUNT(oi.id) AS item_count,
                    COALESCE(SUM(oi.quantity), 0) AS total_quantity
             FROM delivery_assignments da
             INNER JOIN orders o ON o.id = da.order_id
             LEFT JOIN users customer ON customer.id = o.customer_id
             LEFT JOIN delivery_agents agent ON agent.id = da.agent_id
             LEFT JOIN order_items oi ON oi.order_id = o.id
             WHERE da.status IN ('assigned', 'picked_up', 'in_transit')
             GROUP BY da.id, da.order_id, da.agent_id, da.assigned_at, da.status, da.delivery_zone,
                      o.shipping_address, o.payment_method, o.total_amount,
                      customer.name, customer.email, agent.name, agent.phone, agent.vehicle_type
             ORDER BY da.assigned_at ASC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $deliveries[] = $row;
            }
        }

        $stats = [
            'active' => count($deliveries),
            'assigned' => 0,
            'picked_up' => 0,
            'in_transit' => 0,
        ];

        foreach ($deliveries as $delivery) {
            $status = (string) ($delivery['status'] ?? '');

            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }

        return [$deliveries, $stats];
    }

    public function getFailedDeliveriesData(): array
    {
        $this->ensureDeliveryAgentColumns();
        $this->ensureDeliveryFailureColumns();

        $deliveries = [];
        $result = $this->conn->query(
            "SELECT da.id AS assignment_id, da.order_id, da.agent_id, da.assigned_at, da.status, da.delivery_zone,
                    da.failed_reason, da.failed_at, da.failure_resolution, da.customer_notified_at, da.customer_notification_note,
                    (SELECT COUNT(*) FROM delivery_assignments retry WHERE retry.retry_of_assignment_id = da.id) AS retry_count,
                    TIMESTAMPDIFF(MINUTE, COALESCE(da.failed_at, da.assigned_at), NOW()) AS minutes_since_failed,
                    o.shipping_address, o.payment_method, o.total_amount,
                    customer.name AS customer_name,
                    customer.email AS customer_email,
                    agent.name AS agent_name,
                    agent.phone AS agent_phone,
                    agent.vehicle_type,
                    COUNT(oi.id) AS item_count,
                    COALESCE(SUM(oi.quantity), 0) AS total_quantity
             FROM delivery_assignments da
             INNER JOIN orders o ON o.id = da.order_id
             LEFT JOIN users customer ON customer.id = o.customer_id
             LEFT JOIN delivery_agents agent ON agent.id = da.agent_id
             LEFT JOIN order_items oi ON oi.order_id = o.id
             WHERE da.status = 'failed'
             GROUP BY da.id, da.order_id, da.agent_id, da.assigned_at, da.status, da.delivery_zone,
                      da.failed_reason, da.failed_at, da.failure_resolution, da.customer_notified_at, da.customer_notification_note,
                      o.shipping_address, o.payment_method, o.total_amount,
                      customer.name, customer.email, agent.name, agent.phone, agent.vehicle_type
             ORDER BY COALESCE(da.failed_at, da.assigned_at) DESC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $deliveries[] = $row;
            }
        }

        $stats = [
            'failed' => count($deliveries),
            'open' => 0,
            'reassigned' => 0,
            'customer_notified' => 0,
        ];

        foreach ($deliveries as $delivery) {
            $resolution = (string) ($delivery['failure_resolution'] ?? 'open');

            if (isset($stats[$resolution])) {
                $stats[$resolution]++;
            }
        }

        return [
            'deliveries' => $deliveries,
            'agents' => $this->getAvailableAgents(),
            'stats' => $stats,
        ];
    }

    public function getDeliveryHistoryData(): array
    {
        $this->ensureDeliveryAgentColumns();
        $this->ensureDeliveryFailureColumns();

        $deliveries = [];
        $result = $this->conn->query(
            "SELECT da.id AS assignment_id, da.order_id, da.agent_id, da.assigned_at, da.status, da.delivery_zone,
                    da.completed_at, da.failed_reason, da.failed_at, da.failure_resolution,
                    da.customer_notified_at, da.customer_notification_note, da.retry_of_assignment_id,
                    TIMESTAMPDIFF(MINUTE, da.assigned_at, COALESCE(da.completed_at, da.failed_at, NOW())) AS handling_minutes,
                    o.shipping_address, o.payment_method, o.subtotal, o.discount_amount, o.total_amount, o.created_at AS order_created_at,
                    customer.name AS customer_name,
                    customer.email AS customer_email,
                    customer.phone AS customer_phone,
                    agent.name AS agent_name,
                    agent.phone AS agent_phone,
                    agent.vehicle_type,
                    COUNT(oi.id) AS item_count,
                    COALESCE(SUM(oi.quantity), 0) AS total_quantity,
                    GROUP_CONCAT(DISTINCT COALESCE(s.shop_name, seller_user.name, 'Unknown seller') ORDER BY s.shop_name SEPARATOR ', ') AS seller_names,
                    GROUP_CONCAT(DISTINCT NULLIF(oi.tracking_note, '') ORDER BY oi.id SEPARATOR ' | ') AS tracking_notes
             FROM delivery_assignments da
             INNER JOIN orders o ON o.id = da.order_id
             LEFT JOIN users customer ON customer.id = o.customer_id
             LEFT JOIN delivery_agents agent ON agent.id = da.agent_id
             LEFT JOIN order_items oi ON oi.order_id = o.id
             LEFT JOIN sellers s ON s.id = oi.seller_id
             LEFT JOIN users seller_user ON seller_user.id = s.user_id
             WHERE da.status IN ('delivered', 'failed')
             GROUP BY da.id, da.order_id, da.agent_id, da.assigned_at, da.status, da.delivery_zone,
                      da.completed_at, da.failed_reason, da.failed_at, da.failure_resolution,
                      da.customer_notified_at, da.customer_notification_note, da.retry_of_assignment_id,
                      o.shipping_address, o.payment_method, o.subtotal, o.discount_amount, o.total_amount, o.created_at,
                      customer.name, customer.email, customer.phone, agent.name, agent.phone, agent.vehicle_type
             ORDER BY COALESCE(da.completed_at, da.failed_at, da.assigned_at) DESC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $deliveries[] = $row;
            }
        }

        $stats = [
            'total' => count($deliveries),
            'delivered' => 0,
            'failed' => 0,
            'notified' => 0,
        ];

        foreach ($deliveries as $delivery) {
            $status = (string) ($delivery['status'] ?? '');

            if (isset($stats[$status])) {
                $stats[$status]++;
            }

            if (!empty($delivery['customer_notified_at'])) {
                $stats['notified']++;
            }
        }

        return [$deliveries, $stats];
    }

    public function getAgentReportData(): array
    {
        $this->ensureDeliveryAgentColumns();
        $this->ensureDeliveryFailureColumns();

        $agents = [];
        $result = $this->conn->query(
            "SELECT da.id, da.name, da.phone, da.vehicle_type, da.is_active,
                    COUNT(assignments.id) AS total_assignments,
                    SUM(CASE WHEN assignments.status = 'delivered' THEN 1 ELSE 0 END) AS completed_deliveries,
                    SUM(CASE WHEN assignments.status = 'failed' THEN 1 ELSE 0 END) AS failed_deliveries,
                    SUM(CASE WHEN assignments.status IN ('assigned', 'picked_up', 'in_transit') THEN 1 ELSE 0 END) AS active_deliveries,
                    AVG(
                        CASE
                            WHEN assignments.status = 'delivered'
                            THEN TIMESTAMPDIFF(MINUTE, assignments.assigned_at, COALESCE(assignments.completed_at, assignments.assigned_at))
                            ELSE NULL
                        END
                    ) AS average_delivery_minutes,
                    MAX(assignments.assigned_at) AS last_assignment_at,
                    MAX(COALESCE(assignments.completed_at, assignments.failed_at, assignments.assigned_at)) AS last_activity_at
             FROM delivery_agents da
             LEFT JOIN delivery_assignments assignments ON assignments.agent_id = da.id
             GROUP BY da.id, da.name, da.phone, da.vehicle_type, da.is_active
             ORDER BY completed_deliveries DESC, failed_deliveries ASC, da.name ASC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $total = (int) ($row['total_assignments'] ?? 0);
                $failed = (int) ($row['failed_deliveries'] ?? 0);
                $completed = (int) ($row['completed_deliveries'] ?? 0);

                $row['total_assignments'] = $total;
                $row['completed_deliveries'] = $completed;
                $row['failed_deliveries'] = $failed;
                $row['active_deliveries'] = (int) ($row['active_deliveries'] ?? 0);
                $row['average_delivery_minutes'] = $row['average_delivery_minutes'] !== null ? (float) $row['average_delivery_minutes'] : null;
                $row['failed_delivery_rate'] = $total > 0 ? round(($failed / $total) * 100, 2) : 0.0;

                $agents[] = $row;
            }
        }

        $stats = [
            'agents' => count($agents),
            'completed' => 0,
            'failed' => 0,
            'average_delivery_minutes' => null,
            'failed_delivery_rate' => 0.0,
        ];

        $totalAssignments = 0;
        $averageMinutesTotal = 0.0;
        $averageMinutesCount = 0;

        foreach ($agents as $agent) {
            $stats['completed'] += (int) $agent['completed_deliveries'];
            $stats['failed'] += (int) $agent['failed_deliveries'];
            $totalAssignments += (int) $agent['total_assignments'];

            if ($agent['average_delivery_minutes'] !== null) {
                $averageMinutesTotal += (float) $agent['average_delivery_minutes'];
                $averageMinutesCount++;
            }
        }

        if ($averageMinutesCount > 0) {
            $stats['average_delivery_minutes'] = $averageMinutesTotal / $averageMinutesCount;
        }

        if ($totalAssignments > 0) {
            $stats['failed_delivery_rate'] = round(($stats['failed'] / $totalAssignments) * 100, 2);
        }

        return [$agents, $stats];
    }

    public function getZoneReportData(): array
    {
        $this->ensureDeliveryFailureColumns();

        $zones = [];
        $result = $this->conn->query(
            "SELECT COALESCE(NULLIF(assignments.delivery_zone, ''), 'No zone selected') AS zone_name,
                    COUNT(assignments.id) AS total_deliveries,
                    SUM(CASE WHEN assignments.status = 'delivered' THEN 1 ELSE 0 END) AS completed_deliveries,
                    SUM(CASE WHEN assignments.status = 'failed' THEN 1 ELSE 0 END) AS failed_deliveries,
                    SUM(CASE WHEN assignments.status IN ('assigned', 'picked_up', 'in_transit') THEN 1 ELSE 0 END) AS active_deliveries,
                    AVG(
                        CASE
                            WHEN assignments.status = 'delivered'
                            THEN TIMESTAMPDIFF(MINUTE, assignments.assigned_at, COALESCE(assignments.completed_at, assignments.assigned_at))
                            ELSE NULL
                        END
                    ) AS average_delivery_minutes,
                    MAX(COALESCE(assignments.completed_at, assignments.failed_at, assignments.assigned_at)) AS last_activity_at
             FROM delivery_assignments assignments
             GROUP BY COALESCE(NULLIF(assignments.delivery_zone, ''), 'No zone selected')
             ORDER BY total_deliveries DESC, completed_deliveries DESC, zone_name ASC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $total = (int) ($row['total_deliveries'] ?? 0);
                $completed = (int) ($row['completed_deliveries'] ?? 0);
                $failed = (int) ($row['failed_deliveries'] ?? 0);

                $row['total_deliveries'] = $total;
                $row['completed_deliveries'] = $completed;
                $row['failed_deliveries'] = $failed;
                $row['active_deliveries'] = (int) ($row['active_deliveries'] ?? 0);
                $row['average_delivery_minutes'] = $row['average_delivery_minutes'] !== null ? (float) $row['average_delivery_minutes'] : null;
                $row['completed_rate'] = $total > 0 ? round(($completed / $total) * 100, 2) : 0.0;

                $zones[] = $row;
            }
        }

        $stats = [
            'zones' => count($zones),
            'total_deliveries' => 0,
            'completed' => 0,
            'average_delivery_minutes' => null,
        ];

        $averageMinutesTotal = 0.0;
        $averageMinutesCount = 0;

        foreach ($zones as $zone) {
            $stats['total_deliveries'] += (int) $zone['total_deliveries'];
            $stats['completed'] += (int) $zone['completed_deliveries'];

            if ($zone['average_delivery_minutes'] !== null) {
                $averageMinutesTotal += (float) $zone['average_delivery_minutes'];
                $averageMinutesCount++;
            }
        }

        if ($averageMinutesCount > 0) {
            $stats['average_delivery_minutes'] = $averageMinutesTotal / $averageMinutesCount;
        }

        return [$zones, $stats];
    }

    public function getDeliverySummaryData(): array
    {
        $this->ensureDeliveryFailureColumns();

        $dailyRows = $this->buildEmptyDailySummary();
        $weeklyRows = $this->buildEmptyWeeklySummary();

        $dailyResult = $this->conn->query(
            "SELECT period_date, metric, COUNT(*) AS total
             FROM (
                SELECT DATE(completed_at) AS period_date, 'delivered' AS metric
                FROM delivery_assignments
                WHERE status = 'delivered' AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                UNION ALL
                SELECT DATE(failed_at) AS period_date, 'failed' AS metric
                FROM delivery_assignments
                WHERE status = 'failed' AND failed_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                UNION ALL
                SELECT DATE(assigned_at) AS period_date, 'in_transit' AS metric
                FROM delivery_assignments
                WHERE status = 'in_transit' AND assigned_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             ) summary
             WHERE period_date IS NOT NULL
             GROUP BY period_date, metric"
        );

        if ($dailyResult) {
            while ($row = $dailyResult->fetch_assoc()) {
                $date = (string) ($row['period_date'] ?? '');
                $metric = (string) ($row['metric'] ?? '');

                if (isset($dailyRows[$date], $dailyRows[$date][$metric])) {
                    $dailyRows[$date][$metric] = (int) ($row['total'] ?? 0);
                }
            }
        }

        $weeklyResult = $this->conn->query(
            "SELECT week_start, metric, COUNT(*) AS total
             FROM (
                SELECT DATE_SUB(DATE(completed_at), INTERVAL WEEKDAY(completed_at) DAY) AS week_start, 'delivered' AS metric
                FROM delivery_assignments
                WHERE status = 'delivered'
                  AND completed_at >= DATE_SUB(CURDATE(), INTERVAL (WEEKDAY(CURDATE()) + 35) DAY)
                UNION ALL
                SELECT DATE_SUB(DATE(failed_at), INTERVAL WEEKDAY(failed_at) DAY) AS week_start, 'failed' AS metric
                FROM delivery_assignments
                WHERE status = 'failed'
                  AND failed_at >= DATE_SUB(CURDATE(), INTERVAL (WEEKDAY(CURDATE()) + 35) DAY)
                UNION ALL
                SELECT DATE_SUB(DATE(assigned_at), INTERVAL WEEKDAY(assigned_at) DAY) AS week_start, 'in_transit' AS metric
                FROM delivery_assignments
                WHERE status = 'in_transit'
                  AND assigned_at >= DATE_SUB(CURDATE(), INTERVAL (WEEKDAY(CURDATE()) + 35) DAY)
             ) summary
             WHERE week_start IS NOT NULL
             GROUP BY week_start, metric"
        );

        if ($weeklyResult) {
            while ($row = $weeklyResult->fetch_assoc()) {
                $weekStart = (string) ($row['week_start'] ?? '');
                $metric = (string) ($row['metric'] ?? '');

                if (isset($weeklyRows[$weekStart], $weeklyRows[$weekStart][$metric])) {
                    $weeklyRows[$weekStart][$metric] = (int) ($row['total'] ?? 0);
                }
            }
        }

        $dailyRows = array_values(array_reverse($dailyRows));
        $weeklyRows = array_values(array_reverse($weeklyRows));
        $today = $dailyRows[0] ?? ['delivered' => 0, 'failed' => 0, 'in_transit' => 0];
        $thisWeek = $weeklyRows[0] ?? ['delivered' => 0, 'failed' => 0, 'in_transit' => 0];

        return [
            'today' => $today,
            'this_week' => $thisWeek,
            'daily' => $dailyRows,
            'weekly' => $weeklyRows,
        ];
    }

    public function updateDeliveryStatus(int $assignmentId, string $nextStatus, ?string $failedReason = null): array
    {
        $this->ensureDeliveryFailureColumns();

        $allowedStatuses = ['picked_up', 'in_transit', 'delivered', 'failed'];

        if ($assignmentId <= 0 || !in_array($nextStatus, $allowedStatuses, true)) {
            return ['success' => false, 'status' => 422, 'message' => 'Invalid delivery status update.'];
        }

        $stmt = $this->conn->prepare(
            "SELECT da.id, da.order_id, da.status
             FROM delivery_assignments da
             WHERE da.id = ?
             LIMIT 1"
        );
        $stmt->bind_param('i', $assignmentId);
        $stmt->execute();
        $assignment = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$assignment) {
            return ['success' => false, 'status' => 404, 'message' => 'Delivery assignment not found.'];
        }

        $currentStatus = (string) ($assignment['status'] ?? '');
        $validTransitions = [
            'assigned' => ['picked_up'],
            'picked_up' => ['in_transit'],
            'in_transit' => ['delivered', 'failed'],
        ];

        if (!in_array($nextStatus, $validTransitions[$currentStatus] ?? [], true)) {
            return ['success' => false, 'status' => 422, 'message' => 'Delivery status cannot move to the selected step.'];
        }

        $failedReason = trim((string) $failedReason);

        if ($nextStatus === 'failed' && $failedReason === '') {
            return ['success' => false, 'status' => 422, 'message' => 'Please provide a failure reason.'];
        }

        $this->conn->begin_transaction();

        try {
            if ($nextStatus === 'failed') {
                $resolution = 'open';
                $statusStmt = $this->conn->prepare(
                    "UPDATE delivery_assignments
                     SET status = ?, failed_reason = ?, failed_at = NOW(), failure_resolution = ?
                     WHERE id = ?"
                );
                $statusStmt->bind_param('sssi', $nextStatus, $failedReason, $resolution, $assignmentId);
            } elseif ($nextStatus === 'delivered') {
                $statusStmt = $this->conn->prepare("UPDATE delivery_assignments SET status = ?, completed_at = NOW() WHERE id = ?");
                $statusStmt->bind_param('si', $nextStatus, $assignmentId);
            } else {
                $statusStmt = $this->conn->prepare("UPDATE delivery_assignments SET status = ? WHERE id = ?");
                $statusStmt->bind_param('si', $nextStatus, $assignmentId);
            }

            $statusStmt->execute();
            $success = $statusStmt->affected_rows >= 0;
            $statusStmt->close();

            if ($nextStatus === 'delivered') {
                $orderId = (int) $assignment['order_id'];
                $deliveredStatus = 'delivered';

                $orderStmt = $this->conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $orderStmt->bind_param('si', $deliveredStatus, $orderId);
                $orderStmt->execute();
                $orderStmt->close();

                $itemStmt = $this->conn->prepare("UPDATE order_items SET item_status = ? WHERE order_id = ?");
                $itemStmt->bind_param('si', $deliveredStatus, $orderId);
                $itemStmt->execute();
                $itemStmt->close();
            }

            $this->conn->commit();
        } catch (mysqli_sql_exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'status' => 422, 'message' => 'Delivery status could not be updated.'];
        }

        return [
            'success' => $success,
            'message' => 'Delivery status updated.',
        ];
    }

    public function reassignFailedDelivery(int $assignmentId, int $agentId): array
    {
        $this->ensureDeliveryFailureColumns();

        if ($assignmentId <= 0 || $agentId <= 0) {
            return ['success' => false, 'status' => 422, 'message' => 'Select a failed delivery and a new agent.'];
        }

        $stmt = $this->conn->prepare(
            "SELECT id, order_id, agent_id, delivery_zone, failure_resolution
             FROM delivery_assignments
             WHERE id = ? AND status = 'failed'
             LIMIT 1"
        );
        $stmt->bind_param('i', $assignmentId);
        $stmt->execute();
        $assignment = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$assignment) {
            return ['success' => false, 'status' => 404, 'message' => 'Failed delivery not found.'];
        }

        if ((string) ($assignment['failure_resolution'] ?? 'open') === 'reassigned' || $this->failedDeliveryHasRetry($assignmentId)) {
            return ['success' => false, 'status' => 422, 'message' => 'This failed delivery has already been reassigned.'];
        }

        if ((int) $assignment['agent_id'] === $agentId) {
            return ['success' => false, 'status' => 422, 'message' => 'Choose a different delivery agent.'];
        }

        if (!$this->isAgentAvailable($agentId)) {
            return ['success' => false, 'status' => 422, 'message' => 'Selected agent is not available.'];
        }

        $this->conn->begin_transaction();

        try {
            $orderId = (int) $assignment['order_id'];
            $deliveryZone = $assignment['delivery_zone'] !== null ? (string) $assignment['delivery_zone'] : null;
            $status = 'assigned';

            $insert = $this->conn->prepare(
                "INSERT INTO delivery_assignments (order_id, agent_id, status, delivery_zone, retry_of_assignment_id)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $insert->bind_param('iissi', $orderId, $agentId, $status, $deliveryZone, $assignmentId);
            $insert->execute();
            $success = $insert->affected_rows === 1;
            $insert->close();

            if ($success) {
                $resolution = 'reassigned';
                $update = $this->conn->prepare(
                    "UPDATE delivery_assignments
                     SET failure_resolution = ?
                     WHERE id = ?"
                );
                $update->bind_param('si', $resolution, $assignmentId);
                $update->execute();
                $update->close();

                $orderStatus = 'shipped';
                $orderStmt = $this->conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $orderStmt->bind_param('si', $orderStatus, $orderId);
                $orderStmt->execute();
                $orderStmt->close();
            }

            $this->conn->commit();
        } catch (mysqli_sql_exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'status' => 422, 'message' => 'Could not reassign this failed delivery.'];
        }

        return [
            'success' => $success,
            'message' => $success ? 'Failed delivery reassigned to a different agent.' : 'Failed delivery reassignment failed.',
        ];
    }

    public function notifyFailedDeliveryCustomer(int $assignmentId, string $note): array
    {
        $this->ensureDeliveryFailureColumns();

        $note = trim($note);

        if ($assignmentId <= 0 || $note === '') {
            return ['success' => false, 'status' => 422, 'message' => 'Write a customer notification note.'];
        }

        $stmt = $this->conn->prepare(
            "UPDATE delivery_assignments
             SET customer_notification_note = ?,
                 customer_notified_at = NOW(),
                 failure_resolution = CASE
                    WHEN failure_resolution = 'reassigned' THEN 'reassigned'
                    ELSE 'customer_notified'
                 END
             WHERE id = ? AND status = 'failed'"
        );
        $stmt->bind_param('si', $note, $assignmentId);
        $stmt->execute();
        $success = $stmt->affected_rows > 0;
        $stmt->close();

        return [
            'success' => $success,
            'status' => $success ? 200 : 404,
            'message' => $success ? 'Customer notification recorded.' : 'Customer notification could not be recorded.',
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

    private function failedDeliveryHasRetry(int $assignmentId): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT id
             FROM delivery_assignments
             WHERE retry_of_assignment_id = ?
             LIMIT 1"
        );
        $stmt->bind_param('i', $assignmentId);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $exists;
    }

    private function buildEmptyDailySummary(): array
    {
        $rows = [];

        for ($offset = 6; $offset >= 0; $offset--) {
            $timestamp = strtotime('-' . $offset . ' days');
            $date = date('Y-m-d', $timestamp);

            $rows[$date] = [
                'period' => $date,
                'label' => date('M j, Y', $timestamp),
                'delivered' => 0,
                'failed' => 0,
                'in_transit' => 0,
            ];
        }

        return $rows;
    }

    private function buildEmptyWeeklySummary(): array
    {
        $rows = [];
        $currentWeekStart = strtotime('monday this week');

        for ($offset = 5; $offset >= 0; $offset--) {
            $weekStartTimestamp = strtotime('-' . $offset . ' weeks', $currentWeekStart);
            $weekEndTimestamp = strtotime('+6 days', $weekStartTimestamp);
            $weekStart = date('Y-m-d', $weekStartTimestamp);

            $rows[$weekStart] = [
                'period' => $weekStart,
                'label' => date('M j', $weekStartTimestamp) . ' - ' . date('M j, Y', $weekEndTimestamp),
                'delivered' => 0,
                'failed' => 0,
                'in_transit' => 0,
            ];
        }

        return $rows;
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

    private function ensureDeliveryFailureColumns(): void
    {
        $columns = [
            'failed_reason' => "ALTER TABLE delivery_assignments ADD failed_reason text DEFAULT NULL AFTER delivery_zone",
            'failed_at' => "ALTER TABLE delivery_assignments ADD failed_at datetime DEFAULT NULL AFTER failed_reason",
            'completed_at' => "ALTER TABLE delivery_assignments ADD completed_at datetime DEFAULT NULL AFTER failed_at",
            'failure_resolution' => "ALTER TABLE delivery_assignments ADD failure_resolution enum('open','reassigned','customer_notified') NOT NULL DEFAULT 'open' AFTER completed_at",
            'customer_notified_at' => "ALTER TABLE delivery_assignments ADD customer_notified_at datetime DEFAULT NULL AFTER failure_resolution",
            'customer_notification_note' => "ALTER TABLE delivery_assignments ADD customer_notification_note text DEFAULT NULL AFTER customer_notified_at",
            'retry_of_assignment_id' => "ALTER TABLE delivery_assignments ADD retry_of_assignment_id int(11) DEFAULT NULL AFTER customer_notification_note",
        ];

        foreach ($columns as $column => $sql) {
            $result = $this->conn->query("SHOW COLUMNS FROM delivery_assignments LIKE '" . $this->conn->real_escape_string($column) . "'");

            if (!$result || $result->num_rows === 0) {
                $this->conn->query($sql);
            }
        }
    }
}
