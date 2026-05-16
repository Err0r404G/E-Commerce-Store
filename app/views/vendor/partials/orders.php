<section class="vendor-orders-page">
    <div class="page-header">
        <div>
            <h1>Orders</h1>
            <p>View incoming orders for your products and filter by item status.</p>
        </div>

        <button
            type="button"
            class="vendor-incoming-orders-btn"
            data-incoming-orders
        >
            <i class="fa-regular fa-clipboard"></i>
            <span>Incoming Orders</span>
        </button>
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
                    <option value="" <?= ($selectedStatus ?? '') === '' ? 'selected' : '' ?>>All statuses</option>
                    <option value="pending" <?= ($selectedStatus ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="confirmed" <?= ($selectedStatus ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="shipped" <?= ($selectedStatus ?? '') === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                    <option value="delivered" <?= ($selectedStatus ?? '') === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                </select>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="category-table vendor-product-table vendor-orders-table">
                <thead>
                    <tr>
                        <th></th>
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
                            <td colspan="11" class="empty-cell">No incoming order items found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($orders as $order): ?>
                        <?php
                        $status = $order['item_status'] ?? 'pending';
                        $statusLabel = $status === 'confirmed' ? 'Processing' : ucwords($status);
                        $amount = (float) $order['unit_price'] * (int) $order['quantity'];
                        $orderId = (int) $order['order_id'];
                        $detailKey = $orderId . '-' . (int) $order['order_item_id'];
                        $storeItems = $orderItemsByOrder[$orderId] ?? [$order];
                        ?>
                        <tr
                            data-vendor-order-row
                            data-order-id="<?= $orderId ?>"
                            data-order-detail-key="<?= htmlspecialchars($detailKey) ?>"
                            data-status="<?= htmlspecialchars($status) ?>"
                            data-search="<?= htmlspecialchars(strtolower('#' . $order['order_id'] . ' item ' . $order['order_item_id'] . ' ' . ($order['product_name'] ?? '') . ' ' . ($order['customer_name'] ?? '') . ' ' . ($order['customer_email'] ?? '') . ' ' . ($order['payment_method'] ?? ''))) ?>"
                        >
                            <td class="vendor-order-toggle-cell">
                                <button
                                    type="button"
                                    class="vendor-order-toggle"
                                    data-order-toggle
                                    data-order-detail-key="<?= htmlspecialchars($detailKey) ?>"
                                    aria-expanded="false"
                                    aria-label="Show order details"
                                >
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>
                            </td>
                            <td>
                                <strong>#<?= $orderId ?></strong>
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
                            </td>
                        </tr>
                        <tr class="vendor-order-detail-row" data-vendor-order-detail-row data-order-detail-key="<?= htmlspecialchars($detailKey) ?>" hidden>
                            <td colspan="11">
                                <div class="vendor-order-dropdown">
                                    <section>
                                        <h3>Customer Shipping Address</h3>
                                        <p><?= nl2br(htmlspecialchars($order['shipping_address'] ?? 'No shipping address available.')) ?></p>
                                    </section>

                                    <section>
                                        <h3>Payment Method</h3>
                                        <p><?= htmlspecialchars(strtoupper($order['payment_method'] ?? 'COD')) ?></p>
                                    </section>

                                    <section class="vendor-order-dropdown-items">
                                        <h3>Store Items In This Order</h3>
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Item</th>
                                                    <th>Qty</th>
                                                    <th>Unit Price</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($storeItems as $storeItem): ?>
                                                    <?php
                                                    $itemStatus = $storeItem['item_status'] ?? 'pending';
                                                    $itemStatusLabel = $itemStatus === 'confirmed' ? 'Processing' : ucwords($itemStatus);
                                                    $itemTotal = (float) $storeItem['unit_price'] * (int) $storeItem['quantity'];
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?= htmlspecialchars($storeItem['product_name'] ?? 'Unknown product') ?></strong>
                                                            <small>Item #<?= (int) $storeItem['order_item_id'] ?></small>
                                                        </td>
                                                        <td><?= (int) $storeItem['quantity'] ?></td>
                                                        <td>$<?= number_format((float) $storeItem['unit_price'], 2) ?></td>
                                                        <td>$<?= number_format($itemTotal, 2) ?></td>
                                                        <td>
                                                            <span class="status-badge <?= htmlspecialchars($itemStatus) ?>">
                                                                <?= htmlspecialchars($itemStatusLabel) ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </section>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
