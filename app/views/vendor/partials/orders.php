<section class="vendor-orders-page">
    <div class="page-header">
        <h1>Orders</h1>
        <p>View incoming orders for your products and filter by item status.</p>
    </div>

    <section class="vendor-profile-panel vendor-orders-panel">
        <div class="vendor-panel-heading">
            <h2>Incoming Order Items</h2>

            <div class="vendor-order-filter">
                <label for="vendorOrderStatusFilter">Status</label>
                <select id="vendorOrderStatusFilter">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                </select>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="category-table vendor-product-table vendor-orders-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Product</th>
                        <th>Customer</th>
                        <th>Qty</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="empty-cell">No incoming order items found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($orders as $order): ?>
                        <?php
                        $status = $order['item_status'] ?? 'pending';
                        $amount = (float) $order['unit_price'] * (int) $order['quantity'];
                        ?>
                        <tr data-vendor-order-row data-status="<?= htmlspecialchars($status) ?>">
                            <td>
                                <strong>#<?= (int) $order['order_id'] ?></strong>
                                <small>Item #<?= (int) $order['order_item_id'] ?></small>
                            </td>
                            <td><?= htmlspecialchars($order['product_name'] ?? 'Unknown product') ?></td>
                            <td>
                                <strong><?= htmlspecialchars($order['customer_name'] ?? 'Customer') ?></strong>
                                <small><?= htmlspecialchars($order['customer_email'] ?? '') ?></small>
                            </td>
                            <td><?= (int) $order['quantity'] ?></td>
                            <td>$<?= number_format($amount, 2) ?></td>
                            <td><?= htmlspecialchars(strtoupper($order['payment_method'] ?? 'COD')) ?></td>
                            <td>
                                <span class="status-badge <?= htmlspecialchars($status) ?>">
                                    <?= htmlspecialchars(ucwords($status)) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars(date('M d, Y', strtotime($order['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
