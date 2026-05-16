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

<section class="admin-layout delivery-manager-layout">
    <?php include __DIR__ . '/layouts/navbar.php'; ?>

    <main class="admin-content delivery-manager-content" id="deliveryManagerContent">
        <section class="delivery-manager-dashboard-page">
            <div class="page-header">
                <h1>Delivery Dashboard</h1>
                <p>Monitor daily fulfillment, delivery agents, and shipment progress.</p>
            </div>

            <div class="delivery-manager-stats-grid">
                <article class="category-stat-card">
                    <div class="category-stat-icon blue">
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <div>
                        <p>ASSIGNED</p>
                        <h2>0</h2>
                    </div>
                </article>

                <article class="category-stat-card">
                    <div class="category-stat-icon purple">
                        <i class="fa-solid fa-truck-fast"></i>
                    </div>
                    <div>
                        <p>IN TRANSIT</p>
                        <h2>0</h2>
                    </div>
                </article>

                <article class="category-stat-card">
                    <div class="category-stat-icon green">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div>
                        <p>DELIVERED</p>
                        <h2>0</h2>
                    </div>
                </article>
            </div>

            <div class="delivery-manager-dashboard-grid">
                <div class="category-summary-card delivery-manager-summary-card">
                    <div class="category-summary-header">
                        <div>
                            <h2>Delivery Queue</h2>
                            <p>New assignments and active delivery updates will appear here.</p>
                        </div>
                        <button class="add-category-btn" type="button">
                            <i class="fa-solid fa-plus"></i>
                            Assign Delivery
                        </button>
                    </div>
                </div>

                <div class="category-summary-card delivery-manager-side-card">
                    <div class="category-summary-header">
                        <div>
                            <h2>Zone Status</h2>
                            <p>Review coverage, estimated days, and delivery fees.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</section>

<script src="/E-Commerce-Store/public/js/deliveryManagerAjax.js?v=delivery-ready-dispatch-1"></script>
<?php include __DIR__ . '/layouts/footer.php'; ?>
