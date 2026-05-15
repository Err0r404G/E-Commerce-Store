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

include __DIR__ . '/../layouts/adminHeader.php';
?>

<section class="admin-layout">

    <aside class="admin-sidebar">

        <!-- MENU -->
        <nav class="admin-menu">

            <a href="#" class="active">
                <i class="fa-solid fa-table-cells-large"></i>
                <span>Dashboard</span>
            </a>

            <a href="#" data-page="/E-Commerce-Store/index.php?page=vendorApprovalsAjax">
                <i class="fa-solid fa-user-check"></i>
                <span>Vendor Approvals</span>
            </a>

            <a href="#" data-page="/E-Commerce-Store/index.php?page=categoryManagementAjax">
                <i class="fa-solid fa-shapes"></i>
                <span>Category Management</span>
            </a>

            <a href="#">
                <i class="fa-solid fa-gavel"></i>
                <span>Disputes</span>
            </a>

            <a href="#">
                <i class="fa-solid fa-chart-column"></i>
                <span>Platform Reports</span>
            </a>

        </nav>

        <!-- USER -->
        <div class="admin-user-box">

            <div class="user-info">

                <div class="user-icon">
                    <i class="fa-regular fa-user"></i>
                </div>

                <div>
                    <h4><?= htmlspecialchars($adminName) ?></h4>
                    <p><?= htmlspecialchars(strtoupper(str_replace('_', ' ', $adminRole))) ?></p>
                </div>

            </div>

            <a href="/E-Commerce-Store/index.php?page=logout" class="logout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span>Log Out</span>
            </a>

        </div>

    </aside>

<main class="admin-content" id="adminContent">

</main>

</section>
<script src="/E-Commerce-Store/public/js/adminAjax.js?v=category-pagination-1"></script>
</body>
</html>
