<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../controllers/CustomerAreaController.php';

$controller = new CustomerAreaController($conn);
$controller->dispatch('dashboard');
?>
