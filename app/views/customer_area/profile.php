<main class="customer-shell">
    <section class="profile-grid">
        <div class="panel profile-card">
            <div class="avatar"><?= e(strtoupper(substr($_SESSION['user']['name'] ?? 'C', 0, 1))) ?></div>
            <h1><?= e($_SESSION['user']['name'] ?? 'Customer') ?></h1>
            <p><?= e($_SESSION['user']['email'] ?? '') ?></p>
            <p><?= e($_SESSION['user']['phone'] ?? '') ?></p>
            <p class="helper-text">Profile update and password change belong here. The existing shared auth files were not edited.</p>
        </div>
        <div class="panel">
            <h2>Add Shipping Address</h2>
            <form method="post" class="form-grid">
                <input type="hidden" name="customer_action" value="save_address">
                <input name="label" placeholder="Label, e.g. Home" required>
                <input name="recipient_name" placeholder="Recipient name" required>
                <input name="phone" placeholder="Phone">
                <input name="city" placeholder="City" required>
                <input name="postal_code" placeholder="Postal code">
                <select name="delivery_zone_id">
                    <option value="">Delivery zone</option>
                    <?php foreach ($zones as $zone): ?><option value="<?= (int) $zone['id'] ?>"><?= e($zone['zone_name']) ?></option><?php endforeach; ?>
                </select>
                <textarea name="address_line" placeholder="Street address" required></textarea>
                <label class="check-row"><input type="checkbox" name="is_default" value="1"> Set as default</label>
                <button class="primary-button" type="submit">Save Address</button>
            </form>
        </div>
    </section>
    <section class="panel">
        <div class="section-title"><h2>Saved Addresses</h2></div>
        <div class="address-grid">
            <?php foreach ($addresses as $address): ?>
                <article class="address-card <?= $address['is_default'] ? 'default' : '' ?>">
                    <strong><?= e($address['label']) ?><?= $address['is_default'] ? ' · Default' : '' ?></strong>
                    <p><?= e($address['recipient_name']) ?><br><?= e($address['address_line']) ?><br><?= e($address['city']) ?> <?= e($address['postal_code']) ?></p>
                    <small><?= e($address['zone_name'] ?? 'No zone selected') ?></small>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if (!$addresses): ?><p>No saved addresses yet. Run `database/customer_required_tables.sql` if saves do not persist.</p><?php endif; ?>
    </section>
</main>
