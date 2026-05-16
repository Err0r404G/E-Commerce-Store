<?php
$items = $order['items'] ?? [];
$vendorTotal = 0.0;
$paymentMethod = strtoupper($order['payment_method'] ?? 'COD');
?>

<div class="vendor-order-detail">
    <div class="vendor-order-detail-header">
        <div>
            <p>ORDER DETAIL</p>
            <h2>#<?= (int) $order['order_id'] ?></h2>
        </div>
        <span><?= htmlspecialchars(date('M d, Y', strtotime($order['created_at']))) ?></span>
    </div>

    <div class="vendor-order-detail-grid">
        <section>
            <h3>Customer</h3>
            <strong><?= htmlspecialchars($order['customer_name'] ?? 'Customer') ?></strong>
            <span><?= htmlspecialchars($order['customer_email'] ?? '') ?></span>
            <?php if (!empty($order['customer_phone'])): ?>
                <span><?= htmlspecialchars($order['customer_phone']) ?></span>
            <?php endif; ?>
        </section>

        <section>
            <h3>Shipping Address</h3>
            <address><?= nl2br(htmlspecialchars($order['shipping_address'] ?? 'No shipping address found.')) ?></address>
        </section>

        <section>
            <h3>Payment Method</h3>
            <strong><?= htmlspecialchars($paymentMethod === 'COD' ? 'Cash on Delivery' : $paymentMethod) ?></strong>
        </section>
    </div>

    <div class="vendor-order-items-box">
        <h3>Items From Your Store</h3>
        <div class="table-wrapper">
            <table class="category-table vendor-order-detail-table">
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
                    <?php foreach ($items as $item): ?>
                        <?php
                        $itemTotal = (float) $item['unit_price'] * (int) $item['quantity'];
                        $vendorTotal += $itemTotal;
                        $status = $item['item_status'] ?? 'pending';
                        $statusLabel = $status === 'confirmed' ? 'Processing' : ucwords($status);
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($item['product_name'] ?? 'Unknown product') ?></strong>
                                <small>Item #<?= (int) $item['order_item_id'] ?></small>
                            </td>
                            <td><?= (int) $item['quantity'] ?></td>
                            <td>$<?= number_format((float) $item['unit_price'], 2) ?></td>
                            <td>$<?= number_format($itemTotal, 2) ?></td>
                            <td>
                                <span class="status-badge <?= htmlspecialchars($status) ?>">
                                    <?= htmlspecialchars($statusLabel) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">Store Item Total</td>
                        <td colspan="2">$<?= number_format($vendorTotal, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
