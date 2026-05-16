<main class="customer-shell">
    <section class="profile-grid">
        <div class="panel profile-card">
            <?php if (!empty($profile['profile_pic'])): ?>
                <img class="avatar image-avatar" src="/E-Commerce-Store/<?= e($profile['profile_pic']) ?>" alt="<?= e($profile['name']) ?>">
            <?php else: ?>
                <div class="avatar"><?= e(strtoupper(substr($profile['name'] ?? 'C', 0, 1))) ?></div>
            <?php endif; ?>
            <h1><?= e($profile['name'] ?? 'Customer') ?></h1>
            <p><?= e($profile['email'] ?? '') ?></p>
            <p><?= e($profile['phone'] ?? '') ?></p>
            <form method="post" enctype="multipart/form-data" class="form-grid compact-form">
                <input type="hidden" name="customer_action" value="upload_profile_picture">
                <input type="file" name="profile_pic" accept="image/jpeg,image/png,image/webp" required>
                <button class="ghost-button dark" type="submit">Upload Photo</button>
            </form>
        </div>
        <div class="panel">
            <h2>Update Profile</h2>
            <form method="post" class="form-grid">
                <input type="hidden" name="customer_action" value="update_profile">
                <input name="name" value="<?= e($profile['name'] ?? '') ?>" placeholder="Full name" required>
                <input name="phone" value="<?= e($profile['phone'] ?? '') ?>" placeholder="Phone">
                <button class="primary-button" type="submit">Save Profile</button>
            </form>
            <h2>Change Password</h2>
            <form method="post" class="form-grid">
                <input type="hidden" name="customer_action" value="change_password">
                <input type="password" name="current_password" placeholder="Current password" required>
                <input type="password" name="new_password" placeholder="New password" required>
                <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                <button class="ghost-button dark" type="submit">Change Password</button>
            </form>
        </div>
    </section>

    <section class="panel">
        <div class="section-title"><h2>Add Shipping Address</h2></div>
        <form method="post" class="form-grid address-form">
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
    </section>

    <section class="panel">
        <div class="section-title"><h2>Saved Addresses</h2></div>
        <div class="address-grid">
            <?php foreach ($addresses as $address): ?>
                <article class="address-card <?= $address['is_default'] ? 'default' : '' ?>">
                    <strong><?= e($address['label']) ?><?= $address['is_default'] ? ' - Default' : '' ?></strong>
                    <p><?= e($address['recipient_name']) ?><br><?= e($address['address_line']) ?><br><?= e($address['city']) ?> <?= e($address['postal_code']) ?></p>
                    <small><?= e($address['zone_name'] ?? 'No zone selected') ?></small>
                    <details>
                        <summary>Edit address</summary>
                        <form method="post" class="form-grid compact-form">
                            <input type="hidden" name="customer_action" value="save_address">
                            <input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>">
                            <input name="label" value="<?= e($address['label']) ?>" required>
                            <input name="recipient_name" value="<?= e($address['recipient_name']) ?>" required>
                            <input name="phone" value="<?= e($address['phone']) ?>">
                            <input name="city" value="<?= e($address['city']) ?>" required>
                            <input name="postal_code" value="<?= e($address['postal_code']) ?>">
                            <select name="delivery_zone_id">
                                <option value="">Delivery zone</option>
                                <?php foreach ($zones as $zone): ?>
                                    <option value="<?= (int) $zone['id'] ?>" <?= (int) ($address['delivery_zone_id'] ?? 0) === (int) $zone['id'] ? 'selected' : '' ?>><?= e($zone['zone_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <textarea name="address_line" required><?= e($address['address_line']) ?></textarea>
                            <label class="check-row"><input type="checkbox" name="is_default" value="1" <?= $address['is_default'] ? 'checked' : '' ?>> Set as default</label>
                            <button class="primary-button" type="submit">Update Address</button>
                        </form>
                    </details>
                    <div class="address-actions">
                        <?php if (!$address['is_default']): ?>
                            <form method="post">
                                <input type="hidden" name="customer_action" value="set_default_address">
                                <input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>">
                                <button class="ghost-button dark" type="submit">Set Default</button>
                            </form>
                        <?php endif; ?>
                        <form method="post">
                            <input type="hidden" name="customer_action" value="delete_address">
                            <input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>">
                            <button class="danger-button" type="submit">Delete</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if (!$addresses): ?><p>No saved addresses yet. Re-import `database/ecommerce_store.sql` if saves do not persist.</p><?php endif; ?>
    </section>
</main>
