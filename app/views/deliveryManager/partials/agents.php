<section class="vendor-profile-page delivery-manager-agents-page">
    <div class="page-header">
        <h1>Manage Agents</h1>
        <p>Add delivery agents, edit details, and control active status.</p>
    </div>

    <div class="auth-message vendor-profile-message" id="deliveryAgentMessage" hidden></div>

    <form class="vendor-product-form vendor-profile-panel" id="deliveryAgentForm">
        <input type="hidden" name="agent_id" id="deliveryAgentId">

        <div class="vendor-profile-grid">
            <label>
                Agent Name
                <input type="text" name="name" id="deliveryAgentName" required>
            </label>

            <label>
                Phone
                <input type="text" name="phone" id="deliveryAgentPhone" required>
            </label>

            <label>
                Vehicle Type
                <select name="vehicle_type" id="deliveryAgentVehicle" required>
                    <option value="">Select vehicle</option>
                    <option value="Bicycle">Bicycle</option>
                    <option value="Bike">Bike</option>
                    <option value="Motorcycle">Motorcycle</option>
                    <option value="Car">Car</option>
                    <option value="Van">Van</option>
                </select>
            </label>

            <label>
                Status
                <select name="is_active" id="deliveryAgentActive">
                    <option value="1">Active</option>
                    <option value="">Inactive</option>
                </select>
            </label>
        </div>

        <div class="vendor-profile-actions vendor-product-actions">
            <button type="submit">
                <i class="fa-solid fa-floppy-disk"></i>
                Save Agent
            </button>
            <button type="button" id="deliveryAgentReset">
                <i class="fa-solid fa-rotate-left"></i>
                Clear
            </button>
        </div>
    </form>

    <section class="vendor-profile-panel">
        <div class="category-summary-header">
            <div>
                <h2>Delivery Agents</h2>
                <p>View agents and their active deliveries count.</p>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Vehicle</th>
                        <th>Active Deliveries</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($agents)): ?>
                        <tr>
                            <td colspan="6">No delivery agents added yet.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($agents as $agent): ?>
                        <?php $isActive = (int) ($agent['is_active'] ?? 0) === 1; ?>
                        <tr>
                            <td><?= htmlspecialchars($agent['name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($agent['phone'] ?? '') ?></td>
                            <td><?= htmlspecialchars($agent['vehicle_type'] ?? '') ?></td>
                            <td><?= (int) ($agent['active_deliveries_count'] ?? 0) ?></td>
                            <td>
                                <span class="status <?= $isActive ? 'status-shipped' : 'status-cancelled' ?>">
                                    <?= $isActive ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="category-actions-cell">
                                <button
                                    type="button"
                                    class="edit-category"
                                    data-agent-edit
                                    data-agent-id="<?= (int) $agent['id'] ?>"
                                    data-name="<?= htmlspecialchars($agent['name'] ?? '', ENT_QUOTES) ?>"
                                    data-phone="<?= htmlspecialchars($agent['phone'] ?? '', ENT_QUOTES) ?>"
                                    data-vehicle-type="<?= htmlspecialchars($agent['vehicle_type'] ?? '', ENT_QUOTES) ?>"
                                    data-active="<?= $isActive ? '1' : '0' ?>"
                                    title="Edit agent"
                                >
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <button
                                    type="button"
                                    class="<?= $isActive ? 'delete-category' : 'add-subcategory' ?>"
                                    data-agent-toggle
                                    data-agent-id="<?= (int) $agent['id'] ?>"
                                    title="<?= $isActive ? 'Deactivate agent' : 'Activate agent' ?>"
                                >
                                    <i class="fa-solid <?= $isActive ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
