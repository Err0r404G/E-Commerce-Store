<section class="vendor-orders-page">
    <div class="page-header">
        <h1>Orders</h1>
        <p>View incoming orders for your products and filter by item status.</p>
    </div>

    <section class="vendor-profile-panel vendor-orders-panel">
        <div class="vendor-panel-heading">
            <h2>Incoming Order Items</h2>

            <div class="vendor-order-filter">
                <div class="category-search-box vendor-order-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="vendorOrderSearch" placeholder="Search orders...">
                </div>

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
                        <th>Tracking</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="10" class="empty-cell">No incoming order items found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($orders as $order): ?>
                        <?php
                        $status = $order['item_status'] ?? 'pending';
                        $statusLabel = $status === 'confirmed' ? 'Processing' : ucwords($status);
                        $amount = (float) $order['unit_price'] * (int) $order['quantity'];
                        ?>
                        <tr
                            data-vendor-order-row
                            data-status="<?= htmlspecialchars($status) ?>"
                            data-search="<?= htmlspecialchars(strtolower('#' . $order['order_id'] . ' item ' . $order['order_item_id'] . ' ' . ($order['product_name'] ?? '') . ' ' . ($order['customer_name'] ?? '') . ' ' . ($order['customer_email'] ?? '') . ' ' . ($order['payment_method'] ?? ''))) ?>"
                        >
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
                                    <?= htmlspecialchars($statusLabel) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($order['tracking_note'])): ?>
                                    <small><?= htmlspecialchars($order['tracking_note']) ?></small>
                                <?php else: ?>
                                    <small>No tracking note</small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars(date('M d, Y', strtotime($order['created_at']))) ?></td>
                            <td>
                                <div class="vendor-order-actions">
                                    <button type="button" class="vendor-order-detail-btn" data-order-detail data-order-id="<?= (int) $order['order_id'] ?>">
                                        View
                                    </button>

                                    <?php if ($status === 'pending'): ?>
                                        <button type="button" class="vendor-order-action-btn" data-order-confirm data-order-item-id="<?= (int) $order['order_item_id'] ?>">
                                            Confirm
                                        </button>
                                    <?php elseif ($status === 'confirmed'): ?>
                                        <form class="vendor-ship-form" data-order-ship-form>
                                            <input type="hidden" name="order_item_id" value="<?= (int) $order['order_item_id'] ?>">
                                            <input type="text" name="tracking_note" placeholder="Tracking note" required>
                                            <button type="submit">Ship</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="vendor-muted-action">No action</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div class="vendor-order-modal-backdrop" id="vendorOrderDetailModal" hidden>
        <div class="vendor-order-modal" role="dialog" aria-modal="true" aria-labelledby="vendorOrderDetailTitle">
            <div class="vendor-order-modal-top">
                <h2 id="vendorOrderDetailTitle">Order Detail</h2>
                <button type="button" class="vendor-order-modal-close" data-order-detail-close aria-label="Close order detail">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div id="vendorOrderDetailBody" class="vendor-order-modal-body">
                <div class="admin-loading">Loading...</div>
            </div>
        </div>
    </div>
</section>
