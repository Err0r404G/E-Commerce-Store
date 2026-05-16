<main class="customer-shell checkout-grid">
    <section class="panel">
        <div class="section-title">
            <h1>Cart</h1>
            <a href="<?= customerUrl('marketplace') ?>">Continue shopping</a>
        </div>
        <?php if ($items): ?>
            <form method="post">
                <input type="hidden" name="customer_action" value="update_cart">
                <div class="cart-list">
                    <?php foreach ($items as $item): ?>
                        <div class="cart-item">
                            <img src="<?= e(productImage($item['primary_image_path'])) ?>" alt="<?= e($item['name']) ?>">
                            <div>
                                <h2><?= e($item['name']) ?></h2>
                                <p><?= e($item['shop_name']) ?></p>
                                <input type="number" min="0" max="<?= (int) $item['stock_qty'] ?>" name="quantities[<?= (int) $item['id'] ?>]" value="<?= (int) $item['quantity'] ?>">
                            </div>
                            <strong><?= money((float) $item['line_total']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="ghost-button dark" type="submit">Update Cart</button>
            </form>
        <?php else: ?>
            <p>Your cart is empty.</p>
        <?php endif; ?>
    </section>
    <aside class="panel summary-card">
        <h2>Cart Summary</h2>
        <div class="summary-row"><span>Items</span><strong><?= (int) $summary['count'] ?></strong></div>
        <div class="summary-row"><span>Subtotal</span><strong><?= money((float) $summary['subtotal']) ?></strong></div>
        <a class="primary-button full" href="<?= customerUrl('checkout') ?>">Proceed to Checkout</a>
    </aside>
</main>
