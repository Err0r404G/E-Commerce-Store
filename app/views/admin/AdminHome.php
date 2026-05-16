<?php
$dashboardMetrics = $dashboardMetrics ?? [
    'users_by_role' => [],
    'total_users' => 0,
    'active_sellers' => 0,
    'orders_today' => 0,
    'monthly_revenue' => 0,
    'total_categories' => 0,
    'total_products' => 0,
    'active_products' => 0,
    'low_stock_products' => 0,
    'unresolved_disputes' => 0,
    'active_coupons' => 0,
    'report_summary' => [],
    'delivery_summary' => [],
];

$usersByRole = $dashboardMetrics['users_by_role'] ?? [];
$reportSummary = $dashboardMetrics['report_summary'] ?? [];
$deliverySummary = $dashboardMetrics['delivery_summary'] ?? [];
$money = static fn ($value): string => '$' . number_format((float) $value, 2);
$number = static fn ($value): string => number_format((float) $value);
$roleLabels = [
    'admin' => 'Admins',
    'customer' => 'Customers',
    'vendor' => 'Vendors',
    'delivery_manager' => 'Delivery Managers',
];
?>

<section class="admin-home-page">
    <div class="page-header">
        <h1>Admin Dashboard</h1>
        <p>Quick control room for catalog size, product health, unresolved disputes, active coupons, and platform performance.</p>
    </div>

    <div class="admin-home-stats">
        <article class="category-stat-card">
            <div class="category-stat-icon blue">
                <i class="fa-solid fa-shapes"></i>
            </div>
            <div>
                <p>Total Categories</p>
                <h2><?= $number($dashboardMetrics['total_categories'] ?? 0) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon purple">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <div>
                <p>Total Products</p>
                <h2><?= $number($dashboardMetrics['total_products'] ?? 0) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon yellow">
                <i class="fa-solid fa-gavel"></i>
            </div>
            <div>
                <p>Unresolved Disputes</p>
                <h2><?= $number($dashboardMetrics['unresolved_disputes'] ?? 0) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon green">
                <i class="fa-solid fa-ticket"></i>
            </div>
            <div>
                <p>Active Platform Coupons</p>
                <h2><?= $number($dashboardMetrics['active_coupons'] ?? 0) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon blue">
                <i class="fa-solid fa-bag-shopping"></i>
            </div>
            <div>
                <p>Active Products</p>
                <h2><?= $number($dashboardMetrics['active_products'] ?? 0) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon yellow">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <p>Low Stock Products</p>
                <h2><?= $number($dashboardMetrics['low_stock_products'] ?? 0) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon purple">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <p>Total Users</p>
                <h2><?= $number($dashboardMetrics['total_users'] ?? 0) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon green">
                <i class="fa-solid fa-store"></i>
            </div>
            <div>
                <p>Active Sellers</p>
                <h2><?= $number($dashboardMetrics['active_sellers'] ?? 0) ?></h2>
            </div>
        </article>
    </div>

    <div class="admin-home-grid">
        <section class="category-summary-card admin-role-card">
            <div class="category-summary-header">
                <h2>Users By Role</h2>
            </div>

            <div class="admin-role-list">
                <?php foreach ($roleLabels as $role => $label): ?>
                    <?php $count = (int) ($usersByRole[$role] ?? 0); ?>
                    <div class="admin-role-row">
                        <span><?= htmlspecialchars($label) ?></span>
                        <strong><?= number_format($count) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="category-summary-card admin-report-card">
            <div class="category-summary-header">
                <div>
                    <h2>Platform Report Snapshot</h2>
                    <p>This month at a glance</p>
                </div>
                <span><?= htmlspecialchars(date('F Y')) ?></span>
            </div>

            <div class="admin-report-list">
                <div class="admin-report-row">
                    <span>Monthly GMV</span>
                    <strong><?= $money($reportSummary['gmv'] ?? 0) ?></strong>
                </div>
                <div class="admin-report-row">
                    <span>Commission Earned</span>
                    <strong><?= $money($reportSummary['commission_earned'] ?? 0) ?></strong>
                </div>
                <div class="admin-report-row">
                    <span>Orders This Month</span>
                    <strong><?= $number($reportSummary['orders_count'] ?? 0) ?></strong>
                </div>
                <div class="admin-report-row">
                    <span>Units Sold</span>
                    <strong><?= $number($reportSummary['units_sold'] ?? 0) ?></strong>
                </div>
                <div class="admin-report-row">
                    <span>Delivery Success</span>
                    <strong><?= number_format((float) ($deliverySummary['delivery_success_rate'] ?? 0), 2) ?>%</strong>
                </div>
                <div class="admin-report-row">
                    <span>Orders Today</span>
                    <strong><?= $number($dashboardMetrics['orders_today'] ?? 0) ?></strong>
                </div>
            </div>
        </section>
    </div>
</section>
