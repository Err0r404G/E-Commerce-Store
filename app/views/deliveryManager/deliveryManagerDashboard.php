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
$profile = $profile ?? $currentUser;
$dashboardMetrics = $dashboardMetrics ?? [
    'pending_dispatch' => 0,
    'active_deliveries' => 0,
    'delivered_today' => 0,
    'assigned' => 0,
    'picked_up' => 0,
    'in_transit' => 0,
    'ready_items' => 0,
    'ready_value' => 0,
    'oldest_pending_days' => 0,
    'recent_active' => [],
];
$deliveryName = $profile['name'] ?? ($currentUser['name'] ?? 'Delivery Manager');
$deliveryRole = strtoupper(str_replace('_', ' ', $profile['role'] ?? ($currentUser['role'] ?? 'delivery_manager')));
$deliveryAvatar = $profile['profile_pic'] ?? ($currentUser['profile_pic'] ?? null);
$activeDeliveryPage = 'dashboard';

$number = static fn($value): string => number_format((int) $value);
$money = static fn($value): string => '$' . number_format((float) $value, 2);

include __DIR__ . '/layouts/header.php';
?>

<section class="admin-layout delivery-manager-layout">
    <?php include __DIR__ . '/layouts/navbar.php'; ?>

    <main class="admin-content delivery-manager-content" id="deliveryManagerContent">
        <section class="delivery-manager-dashboard-page">
            <div class="page-header">
                <h1>Delivery Dashboard</h1>
                <p>Monitor pending dispatch, active deliveries, delivered orders, and profile access.</p>
            </div>

            <div class="delivery-manager-stats-grid">
                <article class="category-stat-card">
                    <div class="category-stat-icon blue">
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <div>
                        <p>PENDING DISPATCH</p>
                        <h2><?= $number($dashboardMetrics['pending_dispatch'] ?? 0) ?></h2>
                    </div>
                </article>

                <article class="category-stat-card">
                    <div class="category-stat-icon purple">
                        <i class="fa-solid fa-truck-fast"></i>
                    </div>
                    <div>
                        <p>ACTIVE DELIVERIES</p>
                        <h2><?= $number($dashboardMetrics['active_deliveries'] ?? 0) ?></h2>
                    </div>
                </article>

                <article class="category-stat-card">
                    <div class="category-stat-icon green">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div>
                        <p>DELIVERED TODAY</p>
                        <h2><?= $number($dashboardMetrics['delivered_today'] ?? 0) ?></h2>
                    </div>
                </article>
            </div>

            <div class="delivery-manager-dashboard-grid">
                <div class="category-summary-card delivery-manager-summary-card">
                    <div class="category-summary-header">
                        <div>
                            <h2>Logistics Snapshot</h2>
                            <p>Ready orders and active delivery stages update from current assignment data.</p>
                        </div>
                        <a class="add-category-btn" href="#" data-delivery-page="/E-Commerce-Store/index.php?page=deliveryAssignAgentAjax">
                            <i class="fa-solid fa-plus"></i>
                            Assign Delivery
                        </a>
                    </div>

                    <div class="admin-report-list">
                        <div class="admin-report-row">
                            <span>Ready Items</span>
                            <strong><?= $number($dashboardMetrics['ready_items'] ?? 0) ?></strong>
                        </div>
                        <div class="admin-report-row">
                            <span>Ready Value</span>
                            <strong><?= $money($dashboardMetrics['ready_value'] ?? 0) ?></strong>
                        </div>
                        <div class="admin-report-row">
                            <span>Assigned</span>
                            <strong><?= $number($dashboardMetrics['assigned'] ?? 0) ?></strong>
                        </div>
                        <div class="admin-report-row">
                            <span>Picked Up</span>
                            <strong><?= $number($dashboardMetrics['picked_up'] ?? 0) ?></strong>
                        </div>
                        <div class="admin-report-row">
                            <span>In Transit</span>
                            <strong><?= $number($dashboardMetrics['in_transit'] ?? 0) ?></strong>
                        </div>
                        <div class="admin-report-row">
                            <span>Oldest Pending</span>
                            <strong><?= $number($dashboardMetrics['oldest_pending_days'] ?? 0) ?>d</strong>
                        </div>
                    </div>
                </div>

                <div class="category-summary-card delivery-manager-side-card">
                    <div class="category-summary-header">
                        <div>
                            <h2>Profile Management</h2>
                            <p>Signed in as <?= htmlspecialchars($deliveryName) ?>. Manage contact details, profile photo, and password.</p>
                        </div>
                    </div>

                    <div class="delivery-manager-profile-card">
                        <div class="vendor-profile-logo">
                            <?php if (!empty($deliveryAvatar)): ?>
                                <img src="/E-Commerce-Store/<?= htmlspecialchars($deliveryAvatar) ?>" alt="">
                            <?php else: ?>
                                <i class="fa-regular fa-user"></i>
                            <?php endif; ?>
                        </div>

                        <div>
                            <strong><?= htmlspecialchars($deliveryName) ?></strong>
                            <span><?= htmlspecialchars($profile['email'] ?? ($currentUser['email'] ?? 'No email')) ?></span>
                            <span><?= htmlspecialchars($profile['phone'] ?? ($currentUser['phone'] ?? 'No phone')) ?></span>
                        </div>
                    </div>

                    <div class="delivery-manager-card-actions">
                        <a href="#" data-delivery-page="/E-Commerce-Store/index.php?page=deliverySettingsAjax">
                            <i class="fa-solid fa-user-gear"></i>
                            Manage Profile
                        </a>
                        <a href="#" data-delivery-page="/E-Commerce-Store/index.php?page=deliveryActiveDeliveriesAjax">
                            <i class="fa-solid fa-route"></i>
                            View Active
                        </a>
                    </div>
                </div>
            </div>

            <section class="category-summary-card delivery-manager-recent-card">
                <div class="category-summary-header">
                    <div>
                        <h2>Active Delivery Queue</h2>
                        <p>Latest active assignments by order, agent, status, and zone.</p>
                    </div>
                    <a class="add-category-btn" href="#" data-delivery-page="/E-Commerce-Store/index.php?page=deliveryActiveDeliveriesAjax">
                        <i class="fa-solid fa-truck-fast"></i>
                        Open Queue
                    </a>
                </div>

                <div class="table-wrapper">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Agent</th>
                                <th>Status</th>
                                <th>Zone</th>
                                <th>Customer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dashboardMetrics['recent_active'])): ?>
                                <tr>
                                    <td colspan="5">No active deliveries right now.</td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach (($dashboardMetrics['recent_active'] ?? []) as $delivery): ?>
                                <tr>
                                    <td><strong>#<?= (int) ($delivery['order_id'] ?? 0) ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($delivery['agent_name'] ?: 'Delivery Agent') ?></strong>
                                        <small><?= htmlspecialchars($delivery['agent_phone'] ?? 'No phone') ?></small>
                                    </td>
                                    <td>
                                        <span class="status status-processing">
                                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) ($delivery['status'] ?? 'assigned')))) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($delivery['delivery_zone'] ?: 'No zone selected') ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($delivery['customer_name'] ?: 'Customer') ?></strong>
                                        <small><?= htmlspecialchars($delivery['customer_email'] ?? '') ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </section>
    </main>
</section>

<script src="/E-Commerce-Store/public/js/deliveryManagerAjax.js?v=delivery-active-deliveries-1"></script>
<?php include __DIR__ . '/layouts/footer.php'; ?>
