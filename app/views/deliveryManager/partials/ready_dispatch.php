<?php
$orders = $orders ?? [];
$dispatchStats = $dispatchStats ?? ['ready' => 0, 'items' => 0, 'value' => 0, 'oldest_days' => 0];
?>

<section class="vendor-profile-page delivery-manager-ready-dispatch-page">
    <div class="page-header">
        <h1>Ready Dispatch</h1>
        <p>View orders fully shipped by sellers and waiting for delivery agent assignment.</p>
    </div>

    <div class="delivery-manager-stats-grid">
        <article class="category-stat-card">
            <div class="category-stat-icon blue">
                <i class="fa-solid fa-truck-ramp-box"></i>
            </div>
            <div>
                <p>READY ORDERS</p>
                <h2><?= number_format((int) $dispatchStats['ready']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon purple">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <div>
                <p>TOTAL UNITS</p>
                <h2><?= number_format((int) $dispatchStats['items']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon green">
                <i class="fa-solid fa-sack-dollar"></i>
            </div>
            <div>
                <p>QUEUE VALUE</p>
                <h2>$<?= number_format((float) $dispatchStats['value'], 2) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon yellow">
                <i class="fa-regular fa-clock"></i>
            </div>
            <div>
                <p>OLDEST WAIT</p>
                <h2><?= number_format((int) $dispatchStats['oldest_days']) ?>d</h2>
            </div>
        </article>
    </div>

    <section class="vendor-profile-panel">
        <div class="category-summary-header">
            <div>
                <h2>Unassigned Dispatch Queue</h2>
                <p>Only orders with every item marked shipped and no assigned agent appear here.</p>
            </div>

            <div class="category-search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="deliveryReadyDispatchSearch" placeholder="Search queue...">
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
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="9">No orders are ready for dispatch.</td>
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
                        <tr data-delivery-ready-dispatch-row data-search="<?= htmlspecialchars($searchText) ?>">
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
                            <td>
                                $<?= number_format((float) $order['total_amount'], 2) ?>
                                <small>Discount $<?= number_format((float) $order['discount_amount'], 2) ?></small>
                            </td>
                            <td><?= htmlspecialchars(strtoupper($order['payment_method'] ?? 'COD')) ?></td>
                            <td><small><?= htmlspecialchars($trackingNotes) ?></small></td>
                            <td>
                                <span class="status status-shipped">Ready</span>
                            </td>
                            <td><?= htmlspecialchars($displayDate) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <p id="deliveryReadyDispatchCountText">Showing <?= count($orders) ?> order<?= count($orders) === 1 ? '' : 's' ?></p>
        </div>
    </section>
</section>
