<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/E-Commerce-Store',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/app/controllers/CustomerAreaController.php';

$controller = new CustomerAreaController($conn);
$controller->dispatch($_GET['page'] ?? 'dashboard');
