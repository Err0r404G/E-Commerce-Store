<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: /E-Commerce-Store/index.php?page=login');
    exit;
}

if (($_SESSION['user']['role'] ?? null) !== 'admin') {
    header('Location: /E-Commerce-Store/index.php');
    exit;
}

$currentUser = $_SESSION['user'];
$adminName = $currentUser['name'];
$adminRole = $currentUser['role'];

include __DIR__ . '/layouts/header.php';
?>

<section class="admin-layout">

    <?php include __DIR__ . '/layouts/navbar.php'; ?>

<main class="admin-content" id="adminContent">
    <?php include __DIR__ . '/AdminHome.php'; ?>

</main>
</section>
<script src="/E-Commerce-Store/public/js/adminAjax.js?v=platform-coupons-1"></script>
</body>
</html>
