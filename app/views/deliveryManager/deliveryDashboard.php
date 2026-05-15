<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: /E-Commerce-Store/index.php?page=login');
    exit;
}

if (($_SESSION['user']['role'] ?? null) !== 'delivery_manager') {
    header('Location: /E-Commerce-Store/index.php');
    exit;
}

$currentUser = $_SESSION['user'];
$deliveryName = $currentUser['name'] ?? 'Delivery Manager';
$deliveryRole = strtoupper(str_replace('_', ' ', $currentUser['role'] ?? 'delivery_manager'));
$deliveryAvatar = $currentUser['profile_pic'] ?? null;
$activeDeliveryPage = 'dashboard';

include __DIR__ . '/layouts/header.php';
?>

<section class="admin-layout delivery-layout">
    <?php include __DIR__ . '/layouts/navbar.php'; ?>

    <main class="admin-content delivery-content" id="deliveryContent">
        <section class="delivery-dashboard-page">
            <div class="page-header">
                <h1>Delivery Dashboard</h1>
                <p>Track delivery operations and manage fulfillment teams.</p>
            </div>

            <div class="delivery-stats-grid">
                <article class="category-stat-card">
                    <div class="category-stat-icon blue">
                        <i class="fa-solid fa-truck-fast"></i>
                    </div>
                    <div>
                        <p>ACTIVE DELIVERIES</p>
                        <h2>0</h2>
                    </div>
                </article>

                <article class="category-stat-card">
                    <div class="category-stat-icon purple">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <p>AVAILABLE AGENTS</p>
                        <h2>0</h2>
                    </div>
                </article>

                <article class="category-stat-card">
                    <div class="category-stat-icon yellow">
                        <i class="fa-solid fa-map-location-dot"></i>
                    </div>
                    <div>
                        <p>DELIVERY ZONES</p>
                        <h2>0</h2>
                    </div>
                </article>
            </div>

            <div class="category-summary-card delivery-summary-card">
                <div class="category-summary-header">
                    <div>
                        <h2>Operations Overview</h2>
                        <p>Delivery assignments, routes, and status updates will appear here.</p>
                    </div>
                    <button class="add-category-btn" type="button">
                        <i class="fa-solid fa-plus"></i>
                        Assign Delivery
                    </button>
                </div>
            </div>
        </section>
    </main>
</section>

<?php include __DIR__ . '/layouts/footer.php'; ?>
