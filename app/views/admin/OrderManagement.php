<?php
$orders = $orders ?? [];
$sellers = $sellers ?? [];
$customers = $customers ?? [];
$orderStats = $orderStats ?? ['total' => 0, 'pending' => 0, 'processing' => 0, 'delivered' => 0, 'cancelled' => 0, 'revenue' => 0];
$orderStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'return_requested', 'returned'];
?>

<section class="page-section order-management-page">
    <div class="page-header">
        <div>
            <h1>Orders</h1>
            <p>View all orders across the platform with filters by status, date range, seller, and customer.</p>
        </div>
    </div>

    <div class="stats-row account-stats-row">
        <div class="stat-card">
            <div class="stat-top">
                <span>Total Orders</span>
                <i class="fa-solid fa-receipt"></i>
            </div>
            <h2><?= number_format((int) $orderStats['total']) ?></h2>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span>In Progress</span>
                <i class="fa-regular fa-clock"></i>
            </div>
            <h2><?= number_format((int) $orderStats['processing']) ?></h2>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span>Delivered</span>
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h2><?= number_format((int) $orderStats['delivered']) ?></h2>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span>Revenue</span>
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <h2>$<?= number_format((float) $orderStats['revenue'], 2) ?></h2>
        </div>
    </div>

    <div class="approval-card">
        <div class="approval-header">
            <div>
                <h2>Platform Order Directory</h2>
                <p class="account-subtitle">Search by order, customer, seller, payment method, or shipping address.</p>
            </div>

            <div class="approval-tools order-tools">
                <div class="search-box-small">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="orderSearch" placeholder="Search orders...">
                </div>

                <select id="orderStatusFilter" class="admin-filter-select">
                    <option value="">All statuses</option>
                    <?php foreach ($orderStatuses as $status): ?>
                        <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $status))) ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="orderSellerFilter" class="admin-filter-select">
                    <option value="">All sellers</option>
                    <?php foreach ($sellers as $seller): ?>
                        <?php $sellerName = $seller['shop_name'] ?: ($seller['seller_name'] ?? 'Seller'); ?>
                        <option value="<?= (int) $seller['id'] ?>"><?= htmlspecialchars($sellerName) ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="orderCustomerFilter" class="admin-filter-select">
                    <option value="">All customers</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?= (int) $customer['id'] ?>">
                            <?= htmlspecialchars(($customer['name'] ?? 'Customer') . ' - ' . ($customer['email'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            </div>
        </div>

        <table class="approval-table order-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Seller</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" class="empty-cell">No orders found.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($orders as $order): ?>
                    <?php
                    $status = $order['status'] ?? 'pending';
                    $createdAt = !empty($order['created_at']) ? strtotime((string) $order['created_at']) : null;
                    $orderDate = $createdAt ? date('Y-m-d', $createdAt) : '';
                    $displayDate = $createdAt ? date('M j, Y', $createdAt) : 'N/A';
                    $customerName = $order['customer_name'] ?: 'Customer';
                    $sellerNames = $order['seller_names'] ?: 'No seller assigned';
                    $searchText = strtolower('#' . ($order['id'] ?? '') . ' ' . $customerName . ' ' . ($order['customer_email'] ?? '') . ' ' . $sellerNames . ' ' . ($order['payment_method'] ?? '') . ' ' . ($order['shipping_address'] ?? ''));
                    ?>
                    <tr data-order-row
                        data-search="<?= htmlspecialchars($searchText) ?>"
                        data-status="<?= htmlspecialchars($status) ?>"
                        data-date="<?= htmlspecialchars($orderDate) ?>"
                        data-seller-ids=",<?= htmlspecialchars((string) ($order['seller_ids'] ?? '')) ?>,"
                        data-customer-id="<?= (int) ($order['customer_id'] ?? 0) ?>">
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
                        <td>
                            <span class="approval-status order-status <?= htmlspecialchars($status) ?>">
                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $status))) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($displayDate) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="table-footer">
            <p id="orderCountText">Showing <?= count($orders) ?> order<?= count($orders) === 1 ? '' : 's' ?></p>
        </div>
    </div>
</section>
