<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: /E-Commerce-Store/index.php?page=login');
    exit;
}

if (($_SESSION['user']['role'] ?? null) !== 'vendor') {
    header('Location: /E-Commerce-Store/index.php');
    exit;
}

$currentUser = $_SESSION['user'];
$vendorName = $currentUser['name'] ?? 'Vendor';
$vendorRole = strtoupper(str_replace('_', ' ', $currentUser['role'] ?? 'vendor'));
$vendorAvatar = $currentUser['profile_pic'] ?? null;
$activeVendorPage = 'dashboard';

include __DIR__ . '/../layouts/header.php';
?>

<section class="admin-layout vendor-layout">
    <?php include __DIR__ . '/../layouts/navbar.php'; ?>

    <main class="admin-content vendor-content" id="vendorContent">
        <section class="vendor-dashboard-page">
            <div class="page-header">
                <h1>Dashboard</h1>
                <p>Track store activity and manage daily vendor operations.</p>
            </div>

            <div class="vendor-stats-grid">
                <article class="category-stat-card">
                    <div class="category-stat-icon blue">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div>
                        <p>PRODUCTS</p>
                        <h2>0</h2>
                    </div>
                </article>

                <article class="category-stat-card">
                    <div class="category-stat-icon purple">
                        <i class="fa-regular fa-clipboard"></i>
                    </div>
                    <div>
                        <p>ORDERS</p>
                        <h2>0</h2>
                    </div>
                </article>

                <article class="category-stat-card">
                    <div class="category-stat-icon yellow">
                        <i class="fa-solid fa-dollar-sign"></i>
                    </div>
                    <div>
                        <p>REVENUE</p>
                        <h2>$0</h2>
                    </div>
                </article>
            </div>

            <div class="category-summary-card vendor-summary-card">
                <div class="category-summary-header">
                    <div>
                        <h2>Store Overview</h2>
                        <p>Your recent vendor activity will appear here.</p>
                    </div>
                    <button class="add-category-btn" type="button" data-vendor-page="/E-Commerce-Store/index.php?page=vendorInventoryAjax">
                        <i class="fa-solid fa-plus"></i>
                        Add Product
                    </button>
                </div>
            </div>
        </section>
    </main>
</section>

<script src="/E-Commerce-Store/public/js/vendorAjax.js?v=vendor-ajax-1"></script>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
