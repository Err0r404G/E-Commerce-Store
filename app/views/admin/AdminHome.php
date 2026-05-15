<?php
$dashboardMetrics = $dashboardMetrics ?? [
    'users_by_role' => [],
    'total_users' => 0,
    'active_sellers' => 0,
    'orders_today' => 0,
    'monthly_revenue' => 0,
];

$usersByRole = $dashboardMetrics['users_by_role'] ?? [];
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
        <p>Monitor platform users, sellers, orders, and revenue from one workspace.</p>
    </div>

    <div class="admin-home-stats">
        <article class="category-stat-card">
            <div class="category-stat-icon blue">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <p>Total Registered Users</p>
                <h2><?= number_format((int) ($dashboardMetrics['total_users'] ?? 0)) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon purple">
                <i class="fa-solid fa-store"></i>
            </div>
            <div>
                <p>Total Active Sellers</p>
                <h2><?= number_format((int) ($dashboardMetrics['active_sellers'] ?? 0)) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon yellow">
                <i class="fa-regular fa-clipboard"></i>
            </div>
            <div>
                <p>Total Orders Today</p>
                <h2><?= number_format((int) ($dashboardMetrics['orders_today'] ?? 0)) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon green">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <div>
                <p>Platform Revenue This Month</p>
                <h2>$<?= number_format((float) ($dashboardMetrics['monthly_revenue'] ?? 0), 2) ?></h2>
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
    </div>
</section>
