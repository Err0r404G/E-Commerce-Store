<main class="customer-shell narrow">
    <section class="panel center-panel">
        <span class="material-symbols-outlined large-icon">task_alt</span>
        <p class="eyebrow">Order Confirmed</p>
        <h1>Order #<?= (int) $order['id'] ?> is placed</h1>
        <p>Your order has been received and is waiting for seller confirmation.</p>
        <div class="summary-row"><span>Payment</span><strong><?= e(strtoupper($order['payment_method'])) ?></strong></div>
        <div class="summary-row"><span>Total</span><strong><?= money((float) $order['total_amount']) ?></strong></div>
        <div class="summary-row"><span>Status</span><strong><?= e(ucwords(str_replace('_', ' ', $order['status']))) ?></strong></div>
        <h2>Items</h2>
        <?php foreach ($items as $item): ?>
            <div class="summary-line"><span><?= e($item['name']) ?> x<?= (int) $item['quantity'] ?></span><strong><?= money((float) $item['unit_price'] * (int) $item['quantity']) ?></strong></div>
        <?php endforeach; ?>
        <p class="helper-text"><?= nl2br(e($order['shipping_address'])) ?></p>
        <div class="button-row confirmation-actions">
            <a class="primary-button" href="/E-Commerce-Store/customer.php?page=order&id=<?= (int) $order['id'] ?>">Track Order</a>
            <a class="ghost-button dark" href="/E-Commerce-Store/customer.php?page=marketplace">Continue Shopping</a>
        </div>
    </section>
</main>
