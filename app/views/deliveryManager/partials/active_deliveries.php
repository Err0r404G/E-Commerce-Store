<?php
$deliveries = $deliveries ?? [];
$deliveryStats = $deliveryStats ?? ['active' => 0, 'assigned' => 0, 'picked_up' => 0, 'in_transit' => 0];

function deliveryElapsedLabel(int $minutes): string
{
    if ($minutes < 60) {
        return $minutes . ' min';
    }

    $hours = intdiv($minutes, 60);
    $remainingMinutes = $minutes % 60;

    if ($hours < 24) {
        return $hours . 'h' . ($remainingMinutes > 0 ? ' ' . $remainingMinutes . 'm' : '');
    }

    $days = intdiv($hours, 24);
    $remainingHours = $hours % 24;

    return $days . 'd' . ($remainingHours > 0 ? ' ' . $remainingHours . 'h' : '');
}

function deliveryStatusLabel(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}
?>

<section class="vendor-profile-page delivery-manager-active-page">
    <div class="page-header">
        <h1>Active Deliveries</h1>
        <p>Track assigned deliveries, their current status, and update progress when needed.</p>
    </div>

    <div class="delivery-manager-stats-grid">
        <article class="category-stat-card">
            <div class="category-stat-icon blue">
                <i class="fa-solid fa-truck-fast"></i>
            </div>
            <div>
                <p>ACTIVE</p>
                <h2><?= number_format((int) $deliveryStats['active']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon purple">
                <i class="fa-solid fa-clipboard-check"></i>
            </div>
            <div>
                <p>ASSIGNED</p>
                <h2><?= number_format((int) $deliveryStats['assigned']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon yellow">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <div>
                <p>PICKED UP</p>
                <h2><?= number_format((int) $deliveryStats['picked_up']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon green">
                <i class="fa-solid fa-route"></i>
            </div>
            <div>
                <p>IN TRANSIT</p>
                <h2><?= number_format((int) $deliveryStats['in_transit']) ?></h2>
            </div>
        </article>
    </div>

    <div class="auth-message vendor-profile-message" id="deliveryStatusMessage" hidden></div>

    <section class="vendor-profile-panel">
        <div class="category-summary-header">
            <div>
                <h2>Delivery Queue</h2>
                <p>Move deliveries forward through picked up, in transit, delivered, or failed.</p>
            </div>

            <div class="category-search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="activeDeliverySearch" placeholder="Search deliveries...">
            </div>
        </div>

        <div class="table-wrapper">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Delivery Zone</th>
                        <th>Assigned Agent</th>
                        <th>Status</th>
                        <th>Since Assignment</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($deliveries)): ?>
                        <tr>
                            <td colspan="8">No active deliveries found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($deliveries as $delivery): ?>
                        <?php
                        $status = (string) ($delivery['status'] ?? 'assigned');
                        $zone = $delivery['delivery_zone'] ?: 'No zone selected';
                        $agentName = $delivery['agent_name'] ?: 'Delivery Agent';
                        $customerName = $delivery['customer_name'] ?: 'Customer';
                        $minutesSince = max(0, (int) ($delivery['minutes_since_assignment'] ?? 0));
                        $searchText = strtolower('#' . ($delivery['order_id'] ?? '') . ' ' . $zone . ' ' . $agentName . ' ' . ($delivery['agent_phone'] ?? '') . ' ' . $status . ' ' . $customerName . ' ' . ($delivery['customer_email'] ?? '') . ' ' . ($delivery['shipping_address'] ?? ''));
                        ?>
                        <tr data-active-delivery-row data-search="<?= htmlspecialchars($searchText) ?>">
                            <td>
                                <strong>#<?= (int) $delivery['order_id'] ?></strong>
                                <small><?= number_format((int) $delivery['total_quantity']) ?> units</small>
                            </td>
                            <td>
                                <?= htmlspecialchars($zone) ?>
                                <small><?= htmlspecialchars($delivery['shipping_address'] ?? 'No address') ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($agentName) ?></strong>
                                <small><?= htmlspecialchars(($delivery['vehicle_type'] ?? 'Vehicle') . ' - ' . ($delivery['agent_phone'] ?? 'No phone')) ?></small>
                            </td>
                            <td>
                                <span class="status status-processing">
                                    <?= htmlspecialchars(deliveryStatusLabel($status)) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars(deliveryElapsedLabel($minutesSince)) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($customerName) ?></strong>
                                <small><?= htmlspecialchars($delivery['customer_email'] ?? '') ?></small>
                            </td>
                            <td>$<?= number_format((float) ($delivery['total_amount'] ?? 0), 2) ?></td>
                            <td>
                                <div class="delivery-status-actions">
                                    <?php if ($status === 'assigned'): ?>
                                        <button type="button" class="approve-btn" data-delivery-status-action data-assignment-id="<?= (int) $delivery['assignment_id'] ?>" data-next-status="picked_up">
                                            Picked Up
                                        </button>
                                    <?php elseif ($status === 'picked_up'): ?>
                                        <button type="button" class="approve-btn" data-delivery-status-action data-assignment-id="<?= (int) $delivery['assignment_id'] ?>" data-next-status="in_transit">
                                            In Transit
                                        </button>
                                    <?php elseif ($status === 'in_transit'): ?>
                                        <button type="button" class="approve-btn" data-delivery-status-action data-assignment-id="<?= (int) $delivery['assignment_id'] ?>" data-next-status="delivered">
                                            Delivered
                                        </button>
                                        <button type="button" class="reject-btn suspend-btn" data-delivery-status-action data-assignment-id="<?= (int) $delivery['assignment_id'] ?>" data-next-status="failed">
                                            Failed
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <p id="activeDeliveryCountText">Showing <?= count($deliveries) ?> deliver<?= count($deliveries) === 1 ? 'y' : 'ies' ?></p>
        </div>
    </section>
</section>
