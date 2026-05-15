<main class="customer-shell checkout-grid">
    <section class="panel">
        <h1>Secure Checkout</h1>
        <form method="post" id="checkout-form">
            <input type="hidden" name="customer_action" value="place_order">
            <input type="hidden" id="delivery-fee" name="delivery_fee" value="0">

            <h2>Shipping Address</h2>
            <?php if (!$addresses): ?>
                <p class="warning-text">No saved address found. You can paste a delivery address below or add saved addresses from Profile.</p>
            <?php endif; ?>
            <textarea name="shipping_address" required placeholder="Recipient name, phone, street, city, postal code"><?= $addresses ? e($addresses[0]['recipient_name'] . "\n" . $addresses[0]['phone'] . "\n" . $addresses[0]['address_line'] . "\n" . $addresses[0]['city'] . ' ' . $addresses[0]['postal_code']) : '' ?></textarea>

            <h2>Delivery Zone</h2>
            <select id="zone-select">
                <option data-fee="0" data-days="3">Select zone</option>
                <?php foreach ($zones as $zone): ?>
                    <option data-fee="<?= (float) $zone['delivery_fee'] ?>" data-days="<?= (int) $zone['estimated_days'] ?>"><?= e($zone['zone_name']) ?> - <?= money((float) $zone['delivery_fee']) ?>, <?= (int) $zone['estimated_days'] ?> days</option>
                <?php endforeach; ?>
            </select>

            <h2>Payment</h2>
            <div class="radio-row">
                <label><input type="radio" name="payment_method" value="cod" checked> Cash on Delivery</label>
                <label><input type="radio" name="payment_method" value="card"> Card</label>
            </div>

            <h2>Coupon</h2>
            <div class="coupon-row">
                <input id="coupon-code" name="coupon_code" placeholder="Enter coupon code">
                <button class="ghost-button dark" id="apply-coupon" type="button">Apply</button>
            </div>
            <p id="coupon-message" class="helper-text"></p>

            <button class="primary-button full" type="submit">Place Order</button>
        </form>
    </section>
    <aside class="panel summary-card">
        <h2>Order Summary</h2>
        <?php foreach ($items as $item): ?>
            <div class="summary-line"><span><?= e($item['name']) ?> x<?= (int) $item['quantity'] ?></span><strong><?= money((float) $item['line_total']) ?></strong></div>
        <?php endforeach; ?>
        <div class="summary-row"><span>Subtotal</span><strong id="subtotal" data-value="<?= (float) $summary['subtotal'] ?>"><?= money((float) $summary['subtotal']) ?></strong></div>
        <div class="summary-row"><span>Discount</span><strong id="discount">$0.00</strong></div>
        <div class="summary-row"><span>Delivery</span><strong id="delivery-display">$0.00</strong></div>
        <div class="summary-row total"><span>Total</span><strong id="checkout-total"><?= money((float) $summary['subtotal']) ?></strong></div>
        <p class="helper-text" id="delivery-window">Estimated delivery appears after selecting a zone.</p>
    </aside>
</main>
