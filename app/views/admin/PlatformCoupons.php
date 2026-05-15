<section class="vendor-coupons-page platform-coupons-page">
    <div class="page-header">
        <h1>Platform Coupons</h1>
        <p>Manage platform-funded coupon campaigns applied at checkout.</p>
    </div>

    <div class="vendor-inventory-grid">
        <form class="vendor-product-form vendor-profile-panel" id="platformCouponForm">
            <input type="hidden" name="coupon_action" value="save">
            <input type="hidden" name="coupon_id" id="platformCouponId">

            <h2>Coupon Details</h2>

            <div class="vendor-profile-grid">
                <label>
                    Code
                    <input type="text" name="code" id="platformCouponCode" placeholder="PLATFORM20" required>
                </label>

                <label>
                    Discount Percentage
                    <input type="number" name="discount_pct" id="platformCouponDiscount" min="1" max="100" step="0.01" required>
                </label>

                <label>
                    Maximum Uses
                    <input type="number" name="max_uses" id="platformCouponMaxUses" min="1" step="1" required>
                </label>

                <label>
                    Valid Until
                    <input type="date" name="valid_until" id="platformCouponValidUntil" required>
                </label>

                <label class="vendor-check-label">
                    <input type="checkbox" name="is_active" id="platformCouponActive" checked>
                    Active
                </label>
            </div>

            <p class="category-feedback" id="platformCouponFeedback" hidden></p>

            <div class="vendor-profile-actions vendor-product-actions">
                <button type="button" class="vendor-secondary-btn" id="platformCouponReset">Clear</button>
                <button type="submit">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Coupon
                </button>
            </div>
        </form>

        <section class="vendor-profile-panel">
            <div class="vendor-panel-heading">
                <h2>Campaigns</h2>
            </div>

            <div class="table-wrapper">
                <table class="category-table vendor-product-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Funding</th>
                            <th>Discount</th>
                            <th>Uses</th>
                            <th>Valid Until</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($coupons)): ?>
                            <tr>
                                <td colspan="7" class="empty-cell">No platform coupons found. Create your first campaign above.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($coupons as $coupon): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($coupon['code']) ?></strong></td>
                                <td>Platform-funded</td>
                                <td><?= number_format((float) $coupon['discount_pct'], 2) ?>%</td>
                                <td><?= (int) $coupon['uses_count'] ?> / <?= (int) $coupon['max_uses'] ?></td>
                                <td><?= htmlspecialchars(date('M d, Y', strtotime($coupon['valid_until']))) ?></td>
                                <td>
                                    <span class="approval-status <?= (int) $coupon['is_active'] === 1 ? 'approved' : 'pending' ?>">
                                        <?= (int) $coupon['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        class="edit-category"
                                        data-platform-coupon-edit
                                        data-coupon-id="<?= (int) $coupon['id'] ?>"
                                        data-code="<?= htmlspecialchars($coupon['code']) ?>"
                                        data-discount="<?= htmlspecialchars((string) $coupon['discount_pct']) ?>"
                                        data-max-uses="<?= (int) $coupon['max_uses'] ?>"
                                        data-valid-until="<?= htmlspecialchars($coupon['valid_until']) ?>"
                                        data-active="<?= (int) $coupon['is_active'] ?>"
                                    >
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                    <button type="button" class="edit-category vendor-toggle-coupon" data-platform-coupon-toggle data-coupon-id="<?= (int) $coupon['id'] ?>">
                                        <i class="fa-solid fa-power-off"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>
