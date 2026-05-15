<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/E-Commerce-Store',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

$user = $_SESSION['user'] ?? null;
if (!$user || ($user['role'] ?? '') !== 'customer') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Customer login required.']);
    exit;
}

$orderId = (int) ($_GET['order_id'] ?? 0);
$customerId = (int) $user['id'];

$stmt = $conn->prepare(
    "SELECT o.status, da.status AS delivery_status
     FROM orders o
     LEFT JOIN delivery_assignments da ON da.order_id = o.id
     WHERE o.id = ? AND o.customer_id = ?
     ORDER BY da.assigned_at DESC
     LIMIT 1"
);
$stmt->bind_param('ii', $orderId, $customerId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Order not found.']);
    exit;
}

$status = $row['delivery_status'] ?: $row['status'];
$label = ucwords(str_replace('_', ' ', $status));

echo json_encode([
    'ok' => true,
    'status' => $status,
    'status_label' => $label,
]);
