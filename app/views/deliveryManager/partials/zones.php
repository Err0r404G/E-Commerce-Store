<section class="vendor-profile-page delivery-manager-zones-page">
    <div class="page-header">
        <h1>Manage Zones</h1>
        <p>Add delivery zones, edit fees, and set estimated delivery days.</p>
    </div>

    <div class="auth-message vendor-profile-message" id="deliveryZoneMessage" hidden></div>

    <form class="vendor-product-form vendor-profile-panel" id="deliveryZoneForm">
        <input type="hidden" name="zone_id" id="deliveryZoneId">

        <div class="vendor-profile-grid">
            <label>
                Zone Name
                <input type="text" name="zone_name" id="deliveryZoneName" required>
            </label>

            <label>
                Delivery Fee
                <input type="number" name="delivery_fee" id="deliveryZoneFee" min="0" step="0.01" required>
            </label>

            <label>
                Estimated Days
                <input type="number" name="estimated_days" id="deliveryZoneDays" min="1" step="1" required>
            </label>
        </div>

        <div class="vendor-profile-actions vendor-product-actions">
            <button type="submit">
                <i class="fa-solid fa-floppy-disk"></i>
                Save Zone
            </button>
            <button type="button" id="deliveryZoneReset">
                <i class="fa-solid fa-rotate-left"></i>
                Clear
            </button>
        </div>
    </form>

    <section class="vendor-profile-panel">
        <div class="category-summary-header">
            <div>
                <h2>Delivery Zones</h2>
                <p>Set the delivery fee and estimated delivery days for each zone.</p>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Zone</th>
                        <th>Delivery Fee</th>
                        <th>Estimated Days</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($zones)): ?>
                        <tr>
                            <td colspan="4">No delivery zones added yet.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($zones as $zone): ?>
                        <tr>
                            <td><?= htmlspecialchars($zone['zone_name'] ?? '') ?></td>
                            <td>$<?= number_format((float) ($zone['delivery_fee'] ?? 0), 2) ?></td>
                            <td><?= (int) ($zone['estimated_days'] ?? 0) ?> days</td>
                            <td class="category-actions-cell">
                                <button
                                    type="button"
                                    class="edit-category"
                                    data-zone-edit
                                    data-zone-id="<?= (int) $zone['id'] ?>"
                                    data-zone-name="<?= htmlspecialchars($zone['zone_name'] ?? '', ENT_QUOTES) ?>"
                                    data-delivery-fee="<?= htmlspecialchars((string) ($zone['delivery_fee'] ?? '0'), ENT_QUOTES) ?>"
                                    data-estimated-days="<?= (int) ($zone['estimated_days'] ?? 0) ?>"
                                    title="Edit zone"
                                >
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <button
                                    type="button"
                                    class="delete-category"
                                    data-zone-delete
                                    data-zone-id="<?= (int) $zone['id'] ?>"
                                    title="Delete zone"
                                >
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
