<?php
$assignmentData = $assignmentData ?? ['orders' => [], 'agents' => [], 'zones' => [], 'stats' => []];
$orders = $assignmentData['orders'] ?? [];
$agents = $assignmentData['agents'] ?? [];
$zones = $assignmentData['zones'] ?? [];
$stats = $assignmentData['stats'] ?? ['ready_orders' => 0, 'available_agents' => 0, 'active_assignments' => 0];
?>

<section class="vendor-profile-page delivery-manager-assign-page">
    <div class="page-header">
        <h1>Assign Agent</h1>
        <p>Select a ready dispatch order, choose an available delivery agent, and confirm the assignment.</p>
    </div>

    <div class="delivery-manager-stats-grid">
        <article class="category-stat-card">
            <div class="category-stat-icon blue">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <div>
                <p>READY ORDERS</p>
                <h2><?= number_format((int) $stats['ready_orders']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon green">
                <i class="fa-solid fa-user-check"></i>
            </div>
            <div>
                <p>AVAILABLE AGENTS</p>
                <h2><?= number_format((int) $stats['available_agents']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon purple">
                <i class="fa-solid fa-route"></i>
            </div>
            <div>
                <p>ACTIVE ASSIGNMENTS</p>
                <h2><?= number_format((int) $stats['active_assignments']) ?></h2>
            </div>
        </article>
    </div>

    <div class="auth-message vendor-profile-message" id="deliveryAssignAgentMessage" hidden></div>

    <form class="vendor-product-form vendor-profile-panel" id="deliveryAssignAgentForm">
        <div class="vendor-profile-grid">
            <label>
                Ready Order
                <select name="order_id" id="deliveryAssignOrder" required>
                    <option value="">Select order</option>
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $customerName = $order['customer_name'] ?: 'Customer';
                        $sellerNames = $order['seller_names'] ?: 'Seller';
                        ?>
                        <option value="<?= (int) $order['id'] ?>">
                            #<?= (int) $order['id'] ?> - <?= htmlspecialchars($customerName) ?> - <?= number_format((int) $order['total_quantity']) ?> units - <?= htmlspecialchars($sellerNames) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Available Agent
                <select name="agent_id" id="deliveryAssignAgent" required>
                    <option value="">Select agent</option>
                    <?php foreach ($agents as $agent): ?>
                        <option value="<?= (int) $agent['id'] ?>">
                            <?= htmlspecialchars($agent['name'] ?? 'Delivery Agent') ?> - <?= htmlspecialchars($agent['vehicle_type'] ?? 'Vehicle') ?> - <?= (int) ($agent['active_deliveries_count'] ?? 0) ?> active
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Delivery Zone
                <select name="delivery_zone" id="deliveryAssignZone">
                    <option value="">No zone selected</option>
                    <?php foreach ($zones as $zone): ?>
                        <option value="<?= htmlspecialchars($zone['zone_name'] ?? '') ?>">
                            <?= htmlspecialchars($zone['zone_name'] ?? '') ?> - $<?= number_format((float) ($zone['delivery_fee'] ?? 0), 2) ?> - <?= (int) ($zone['estimated_days'] ?? 0) ?> days
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="vendor-profile-actions vendor-product-actions">
            <button type="submit" <?= empty($orders) || empty($agents) ? 'disabled' : '' ?>>
                <i class="fa-solid fa-check"></i>
                Confirm Assignment
            </button>
        </div>
    </form>

    <section class="vendor-profile-panel">
        <div class="category-summary-header">
            <div>
                <h2>Ready Orders</h2>
                <p>Orders disappear from this list after an agent is assigned.</p>
            </div>

            <div class="category-search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="deliveryAssignSearch" placeholder="Search orders...">
            </div>
        </div>

        <div class="table-wrapper">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Seller</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Tracking</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8">No ready dispatch orders are waiting for assignment.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($orders as $order): ?>
                        <?php
                        $createdAt = !empty($order['created_at']) ? strtotime((string) $order['created_at']) : null;
                        $displayDate = $createdAt ? date('M j, Y', $createdAt) : 'N/A';
                        $customerName = $order['customer_name'] ?: 'Customer';
                        $sellerNames = $order['seller_names'] ?: 'No seller assigned';
                        $trackingNotes = $order['tracking_notes'] ?: 'No tracking note';
                        $searchText = strtolower('#' . ($order['id'] ?? '') . ' ' . $customerName . ' ' . ($order['customer_email'] ?? '') . ' ' . $sellerNames . ' ' . ($order['payment_method'] ?? '') . ' ' . ($order['shipping_address'] ?? '') . ' ' . $trackingNotes);
                        ?>
                        <tr data-delivery-assign-row data-search="<?= htmlspecialchars($searchText) ?>">
                            <td>
                                <strong>#<?= (int) $order['id'] ?></strong>
                                <small><?= htmlspecialchars($order['shipping_address'] ?? 'No address') ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($customerName) ?></strong>
                                <small><?= htmlspecialchars($order['customer_email'] ?? '') ?></small>
                            </td>
                            <td><?= htmlspecialchars($sellerNames) ?></td>
                            <td>
                                <?= number_format((int) $order['item_count']) ?> item<?= (int) $order['item_count'] === 1 ? '' : 's' ?>
                                <small><?= number_format((int) $order['total_quantity']) ?> units</small>
                            </td>
                            <td>$<?= number_format((float) $order['total_amount'], 2) ?></td>
                            <td><?= htmlspecialchars(strtoupper($order['payment_method'] ?? 'COD')) ?></td>
                            <td><small><?= htmlspecialchars($trackingNotes) ?></small></td>
                            <td><?= htmlspecialchars($displayDate) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <p id="deliveryAssignCountText">Showing <?= count($orders) ?> order<?= count($orders) === 1 ? '' : 's' ?></p>
        </div>
    </section>
</section>
