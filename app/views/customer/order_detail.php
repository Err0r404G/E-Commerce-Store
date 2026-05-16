<main class="customer-shell checkout-grid">
    <section class="panel">
        <div class="section-title">
            <div>
                <p class="eyebrow">Order Tracking</p>
                <h1>Order #<?= (int) $order['id'] ?></h1>
            </div>
            <span class="status-pill live-order-status" data-order-id="<?= (int) $order['id'] ?>"><?= e(ucwords(str_replace('_', ' ', $order['status']))) ?></span>
        </div>
        <p>Placed on <?= e(date('M d, Y h:i A', strtotime($order['created_at']))) ?></p>
        <div class="timeline order-timeline">
            <?php foreach (['pending', 'confirmed', 'processing', 'shipped', 'delivered'] as $step): ?>
                <span class="<?= array_search($step, ['pending', 'confirmed', 'processing', 'shipped', 'delivered'], true) <= array_search($order['status'], ['pending', 'confirmed', 'processing', 'shipped', 'delivered'], true) ? 'done' : '' ?>"><?= e(ucwords($step)) ?></span>
            <?php endforeach; ?>
        </div>
        <h2>Items</h2>
        <div class="cart-list">
            <?php foreach ($items as $item): ?>
                <div class="cart-item">
                    <img src="<?= e(productImage($item['primary_image_path'])) ?>" alt="<?= e($item['name']) ?>">
                    <div>
                        <h3><?= e($item['name']) ?></h3>
                        <p><?= e($item['shop_name']) ?> · Qty <?= (int) $item['quantity'] ?> · <?= e(ucwords($item['item_status'])) ?></p>
                        <?php if ($order['status'] === 'delivered'): ?>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="customer_action" value="save_review">
                                <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                                <select name="rating">
                                    <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                                        <option value="<?= $rating ?>" <?= (int) ($item['own_rating'] ?? 5) === $rating ? 'selected' : '' ?>><?= $rating ?> star<?= $rating === 1 ? '' : 's' ?></option>
                                    <?php endfor; ?>
                                </select>
                                <input name="review_text" value="<?= e($item['own_review_text'] ?? '') ?>" placeholder="Write a review">
                                <button class="ghost-button dark" type="submit"><?= $item['own_review_text'] ? 'Update Review' : 'Save Review' ?></button>
                            </form>
                            <?php if (!empty($item['own_review_text'])): ?>
                                <form method="post" class="review-delete-form">
                                    <input type="hidden" name="customer_action" value="delete_review">
                                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                    <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                                    <button class="danger-button" type="submit">Delete Review</button>
                                </form>
                            <?php endif; ?>
                            <?php if (!empty($item['return_status'])): ?>
                                <p class="helper-text">
                                    Return: <?= e(ucwords(str_replace('_', ' ', $item['return_status']))) ?>
                                    <?= $item['return_reason'] ? ' - ' . e($item['return_reason']) : '' ?>
                                    <?php if (!empty($item['return_vendor_reason'])): ?>
                                        <br>Vendor response: <?= e($item['return_vendor_reason']) ?>
                                    <?php endif; ?>
                                </p>
                            <?php else: ?>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="customer_action" value="request_return">
                                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                    <input type="hidden" name="order_item_id" value="<?= (int) $item['id'] ?>">
                                    <input name="reason" placeholder="Return reason">
                                    <button class="ghost-button dark" type="submit">Request Return</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <strong><?= money((float) $item['unit_price'] * (int) $item['quantity']) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (in_array($order['status'], ['pending', 'confirmed', 'processing'], true)): ?>
            <form method="post">
                <input type="hidden" name="customer_action" value="cancel_order">
                <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                <button class="danger-button" type="submit">Cancel Order</button>
            </form>
        <?php endif; ?>
    </section>
    <aside class="panel summary-card">
        <h2>Summary</h2>
        <div class="summary-row"><span>Subtotal</span><strong><?= money((float) $order['subtotal']) ?></strong></div>
        <div class="summary-row"><span>Discount</span><strong><?= money((float) $order['discount_amount']) ?></strong></div>
        <div class="summary-row total"><span>Total</span><strong><?= money((float) $order['total_amount']) ?></strong></div>
        <h3>Ship To</h3>
        <p><?= nl2br(e($order['shipping_address'])) ?></p>
    </aside>
</main>
