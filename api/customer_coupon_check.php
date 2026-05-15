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
require_once __DIR__ . '/../app/models/CustomerAreaModel.php';

$code = strtoupper(trim($_POST['code'] ?? ''));

if ($code === '') {
    echo json_encode(['valid' => false, 'message' => 'Coupon code is required.']);
    exit;
}

$model = new CustomerAreaModel($conn);
$coupon = $model->coupon($code);

if (!$coupon) {
    echo json_encode(['valid' => false, 'message' => 'Coupon is inactive, expired, or fully used.']);
    exit;
}

echo json_encode([
    'valid' => true,
    'message' => $coupon['code'] . ' applied: ' . (float) $coupon['discount_pct'] . '% ' . (($coupon['funding_source'] ?? 'vendor') === 'platform' ? 'platform discount.' : 'discount.'),
    'coupon_id' => (int) $coupon['id'],
    'discount_pct' => (float) $coupon['discount_pct'],
    'funding_source' => $coupon['funding_source'] ?? 'vendor',
]);
