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
$dashboardMetrics = $dashboardMetrics ?? [];
$money = static fn ($value): string => '$' . number_format((float) $value, 2);
$number = static fn ($value): string => number_format((float) $value);
$statusLabel = static fn ($value): string => ucwords(str_replace('_', ' ', (string) $value));

include __DIR__ . '/../layouts/header.php';
?>

<section class="admin-layout vendor-layout">
    <?php include __DIR__ . '/../layouts/navbar.php'; ?>

    <main class="admin-content vendor-content" id="vendorContent">
        <section class="vendor-dashboard-page">
            <div class="page-header">
                <h1>Vendor Dashboard</h1>
                <p>Quick control room for your products, order flow, returns, reviews, and store earnings.</p>
            </div>

            <div class="admin-home-stats vendor-dashboard-stats">
                <article class="category-stat-card">
                    <div class="category-stat-icon blue"><i class="fa-solid fa-boxes-stacked"></i></div>
                    <div>
                        <p>Total Products</p>
                        <h2><?= $number($dashboardMetrics['total_products'] ?? 0) ?></h2>
                    </div>
                </article>

                <article class="category-stat-card">
                    <div class="category-stat-icon green"><i class="fa-solid fa-bag-shopping"></i></div>
                    <div>
                        <p>Active Products</p>
                        <h2><?= $number($dashboardMetrics['active_products'] ?? 0) ?></h2>
                    </div>
                </article>

                <article class="category-stat-card">
                    <div class="category-stat-icon yellow"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div>
                        <p>Low Stock</p>
                        <h2><?= $number($dashboardMetrics['low_stock_products'] ?? 0) ?></h2>
                    </div>
                </article>

                <article class="category-stat-card">
                    <div class="category-stat-icon purple"><i class="fa-regular fa-clipboard"></i></div>
                    <div>
                        <p>Order Items</p>
                        <h2><?= $number($dashboardMetrics['order_items'] ?? 0) ?></h2>
                    </div>
                </article>

                <article class="category-stat-card">
                    <div class="category-stat-icon blue"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div>
                        <p>Pending Items</p>
                        <h2><?= $number($dashboardMetrics['pending_items'] ?? 0) ?></h2>
                    </div>
                </article>

                <article class="category-stat-card">
                    <div class="category-stat-icon yellow"><i class="fa-solid fa-dollar-sign"></i></div>
                    <div>
                        <p>Total Revenue</p>
                        <h2><?= $money($dashboardMetrics['total_revenue'] ?? 0) ?></h2>
                    </div>
                </article>

                <article class="category-stat-card">
                    <div class="category-stat-icon green"><i class="fa-solid fa-sack-dollar"></i></div>
                    <div>
                        <p>Net Payout</p>
                        <h2><?= $money($dashboardMetrics['net_payout'] ?? 0) ?></h2>
                    </div>
                </article>

                <article class="category-stat-card">
                    <div class="category-stat-icon purple"><i class="fa-regular fa-star"></i></div>
                    <div>
                        <p>Avg Rating</p>
                        <h2><?= number_format((float) ($dashboardMetrics['average_rating'] ?? 0), 1) ?></h2>
                    </div>
                </article>
            </div>

            <div class="admin-home-grid vendor-dashboard-grid">
                <section class="category-summary-card admin-report-card">
                    <div class="category-summary-header">
                        <div>
                            <h2>Store Snapshot</h2>
                            <p>This month at a glance</p>
                        </div>
                        <span><?= htmlspecialchars(date('F Y')) ?></span>
                    </div>

                    <div class="admin-report-list">
                        <div class="admin-report-row"><span>Monthly Revenue</span><strong><?= $money($dashboardMetrics['monthly_revenue'] ?? 0) ?></strong></div>
                        <div class="admin-report-row"><span>Units Sold</span><strong><?= $number($dashboardMetrics['units_sold'] ?? 0) ?></strong></div>
                        <div class="admin-report-row"><span>Commission Deducted</span><strong><?= $money($dashboardMetrics['commission_deducted'] ?? 0) ?></strong></div>
                        <div class="admin-report-row"><span>Commission Rate</span><strong><?= number_format((float) ($dashboardMetrics['commission_rate'] ?? 0), 2) ?>%</strong></div>
                        <div class="admin-report-row"><span>Pending Returns</span><strong><?= $number($dashboardMetrics['pending_returns'] ?? 0) ?></strong></div>
                        <div class="admin-report-row"><span>Active Promo Codes</span><strong><?= $number($dashboardMetrics['active_coupons'] ?? 0) ?></strong></div>
                    </div>
                </section>

                <section class="category-summary-card admin-role-card vendor-order-status-card">
                    <div class="category-summary-header"><h2>Order Status</h2></div>

                    <div class="admin-role-list">
                        <div class="admin-role-row"><span>Pending</span><strong><?= $number($dashboardMetrics['pending_items'] ?? 0) ?></strong></div>
                        <div class="admin-role-row"><span>Processing</span><strong><?= $number($dashboardMetrics['confirmed_items'] ?? 0) ?></strong></div>
                        <div class="admin-role-row"><span>Shipped</span><strong><?= $number($dashboardMetrics['shipped_items'] ?? 0) ?></strong></div>
                        <div class="admin-role-row"><span>Delivered</span><strong><?= $number($dashboardMetrics['delivered_items'] ?? 0) ?></strong></div>
                        <div class="admin-role-row"><span>Reviews</span><strong><?= $number($dashboardMetrics['review_count'] ?? 0) ?></strong></div>
                    </div>
                </section>
            </div>

            <div class="vendor-dashboard-panels">
                <section class="category-summary-card admin-report-card">
                    <div class="category-summary-header">
                        <div>
                            <h2>Recent Order Items</h2>
                            <p>Latest customer activity</p>
                        </div>
                        <button class="add-category-btn" type="button" data-vendor-page="/E-Commerce-Store/index.php?page=vendorOrdersAjax">Open Orders</button>
                    </div>

                    <div class="vendor-dashboard-list">
                        <?php if (empty($dashboardMetrics['recent_orders'])): ?>
                            <p class="empty-cell">No order items yet.</p>
                        <?php endif; ?>

                        <?php foreach (($dashboardMetrics['recent_orders'] ?? []) as $order): ?>
                            <?php $amount = (float) $order['unit_price'] * (int) $order['quantity']; ?>
                            <div class="vendor-dashboard-row">
                                <div>
                                    <strong>#<?= (int) $order['order_id'] ?> - <?= htmlspecialchars($order['product_name'] ?? 'Product') ?></strong>
                                    <span><?= htmlspecialchars($order['customer_name'] ?? 'Customer') ?> - Qty <?= (int) $order['quantity'] ?></span>
                                </div>
                                <div>
                                    <strong><?= $money($amount) ?></strong>
                                    <span><?= htmlspecialchars($statusLabel($order['item_status'] ?? 'pending')) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="category-summary-card admin-report-card">
                    <div class="category-summary-header">
                        <div>
                            <h2>Low Stock Watch</h2>
                            <p>Products needing attention</p>
                        </div>
                        <button class="add-category-btn" type="button" data-vendor-page="/E-Commerce-Store/index.php?page=vendorInventoryAjax">Add Product</button>
                    </div>

                    <div class="vendor-dashboard-list">
                        <?php if (empty($dashboardMetrics['low_stock_items'])): ?>
                            <p class="empty-cell">No low stock products.</p>
                        <?php endif; ?>

                        <?php foreach (($dashboardMetrics['low_stock_items'] ?? []) as $product): ?>
                            <div class="vendor-dashboard-row">
                                <div>
                                    <strong><?= htmlspecialchars($product['name'] ?? 'Product') ?></strong>
                                    <span><?= (int) $product['is_available'] === 1 ? 'Active' : 'Unavailable' ?></span>
                                </div>
                                <div>
                                    <strong><?= $number($product['stock_qty'] ?? 0) ?></strong >
                                    <span>In stock</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <section class="category-summary-card admin-report-card vendor-top-products-card">
                <div class="category-summary-header">
                    <div>
                        <h2>Top Products</h2>
                        <p>Your strongest sellers</p>
                    </div>
                    <button class="add-category-btn" type="button" data-vendor-page="/E-Commerce-Store/index.php?page=vendorAnalyticsAjax">View Analytics</button>
                </div>

                <div class="admin-report-list">
                    <?php if (empty($dashboardMetrics['top_products'])): ?>
                        <p class="empty-cell">No product sales yet.</p>
                    <?php endif; ?>

                    <?php foreach (($dashboardMetrics['top_products'] ?? []) as $product): ?>
                        <div class="admin-report-row">
                            <span><?= htmlspecialchars($product['name'] ?? 'Product') ?></span>
                            <strong><?= $money($product['revenue'] ?? 0) ?></strong>
                            <small><?= $number($product['units_sold'] ?? 0) ?> units sold</small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </section>
    </main>
</section>

<script src="/E-Commerce-Store/public/js/vendorAjax.js?v=vendor-ajax-1"></script>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
