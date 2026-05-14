<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: /E-Commerce-Store/index.php?page=login');
    exit;
}

if (($_SESSION['user']['role'] ?? null) !== 'seller') {
    header('Location: /E-Commerce-Store/index.php');
    exit;
}

$currentUser = $_SESSION['user'];
$sellerName = $currentUser['name'];
$sellerRole = $currentUser['role'];

include __DIR__ . '/../layouts/adminHeader.php';
?>

<section class="admin-layout">

    <aside class="admin-sidebar">

        <nav class="admin-menu">

            <a href="/E-Commerce-Store/index.php?page=sellerDashboard" class="active">
                <i class="fa-solid fa-table-cells-large"></i>
                <span>Dashboard</span>
            </a>

            <a href="#">
                <i class="fa-solid fa-box"></i>
                <span>Products</span>
            </a>

            <a href="#">
                <i class="fa-solid fa-receipt"></i>
                <span>Orders</span>
            </a>

            <a href="#">
                <i class="fa-solid fa-chart-line"></i>
                <span>Reports</span>
            </a>

        </nav>

        <div class="admin-user-box">

            <div class="user-info">

                <div class="user-icon">
                    <i class="fa-regular fa-user"></i>
                </div>

                <div>
                    <h4><?= htmlspecialchars($sellerName) ?></h4>
                    <p><?= htmlspecialchars(strtoupper(str_replace('_', ' ', $sellerRole))) ?></p>
                </div>

            </div>

            <a href="/E-Commerce-Store/index.php?page=logout" class="logout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span>Log Out</span>
            </a>

        </div>

    </aside>

    <main class="admin-content">

        <div class="dashboard-empty">
            <h1>Seller Dashboard</h1>
            <p>Manage products, orders, and sales reports from here.</p>
        </div>

    </main>

</section>

</body>
</html>
