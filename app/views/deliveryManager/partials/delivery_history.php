<?php
$historyDeliveries = $historyDeliveries ?? [];
$historyStats = $historyStats ?? ['total' => 0, 'delivered' => 0, 'failed' => 0, 'notified' => 0];

function deliveryHistoryDate(?string $dateTime): string
{
    if (empty($dateTime)) {
        return 'N/A';
    }

    $timestamp = strtotime($dateTime);

    return $timestamp ? date('M j, Y g:i A', $timestamp) : 'N/A';
}

function deliveryHistoryDuration(int $minutes): string
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

function deliveryHistoryLabel(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}
?>

<section class="vendor-profile-page delivery-manager-history-page">
    <div class="page-header">
        <h1>Delivery History</h1>
        <p>View completed and failed deliveries with order details, assignment details, outcomes, and timestamps.</p>
    </div>

    <div class="delivery-manager-stats-grid">
        <article class="category-stat-card">
            <div class="category-stat-icon blue">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div>
                <p>HISTORY</p>
                <h2><?= number_format((int) $historyStats['total']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon green">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <p>COMPLETED</p>
                <h2><?= number_format((int) $historyStats['delivered']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon yellow">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <p>FAILED</p>
                <h2><?= number_format((int) $historyStats['failed']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon purple">
                <i class="fa-regular fa-envelope"></i>
            </div>
            <div>
                <p>NOTIFIED</p>
                <h2><?= number_format((int) $historyStats['notified']) ?></h2>
            </div>
        </article>
    </div>

    <section class="vendor-profile-panel">
        <div class="category-summary-header">
            <div>
                <h2>Completed and Failed Deliveries</h2>
                <p>Search by order, customer, agent, zone, seller, status, reason, or tracking note.</p>
            </div>

            <div class="category-search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="deliveryHistorySearch" placeholder="Search history...">
            </div>
        </div>

        <div class="table-wrapper">
            <table class="orders-table delivery-history-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Outcome</th>
                        <th>Timeline</th>
                        <th>Customer</th>
                        <th>Agent</th>
                        <th>Delivery Details</th>
                        <th>Order Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historyDeliveries)): ?>
                        <tr>
                            <td colspan="7">No completed or failed deliveries found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($historyDeliveries as $delivery): ?>
                        <?php
                        $status = (string) ($delivery['status'] ?? '');
                        $zone = $delivery['delivery_zone'] ?: 'No zone selected';
                        $agentName = $delivery['agent_name'] ?: 'Delivery Agent';
                        $customerName = $delivery['customer_name'] ?: 'Customer';
                        $customerEmail = $delivery['customer_email'] ?: 'No email';
                        $customerPhone = $delivery['customer_phone'] ?: 'No phone';
                        $sellerNames = $delivery['seller_names'] ?: 'No seller assigned';
                        $trackingNotes = $delivery['tracking_notes'] ?: 'No tracking note';
                        $failedReason = $delivery['failed_reason'] ?: 'No failure reason';
                        $notificationNote = $delivery['customer_notification_note'] ?: 'No notification note';
                        $endedAt = $status === 'delivered' ? ($delivery['completed_at'] ?? null) : ($delivery['failed_at'] ?? null);
                        $searchText = strtolower('#' . ($delivery['order_id'] ?? '') . ' ' . $status . ' ' . $zone . ' ' . $agentName . ' ' . ($delivery['agent_phone'] ?? '') . ' ' . $customerName . ' ' . $customerEmail . ' ' . $customerPhone . ' ' . ($delivery['shipping_address'] ?? '') . ' ' . $sellerNames . ' ' . $trackingNotes . ' ' . $failedReason . ' ' . $notificationNote);
                        ?>
                        <tr data-delivery-history-row data-search="<?= htmlspecialchars($searchText) ?>">
                            <td>
                                <strong>#<?= (int) $delivery['order_id'] ?></strong>
                                <small>Assignment #<?= (int) $delivery['assignment_id'] ?></small>
                                <?php if (!empty($delivery['retry_of_assignment_id'])): ?>
                                    <small>Retry of #<?= (int) $delivery['retry_of_assignment_id'] ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status <?= $status === 'delivered' ? 'status-shipped' : 'status-processing' ?>">
                                    <?= htmlspecialchars(deliveryHistoryLabel($status)) ?>
                                </span>
                                <?php if ($status === 'failed'): ?>
                                    <small><?= htmlspecialchars($failedReason) ?></small>
                                    <small><?= htmlspecialchars(deliveryHistoryLabel((string) ($delivery['failure_resolution'] ?? 'open'))) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong>Assigned</strong>
                                <small><?= htmlspecialchars(deliveryHistoryDate($delivery['assigned_at'] ?? null)) ?></small>
                                <strong><?= $status === 'delivered' ? 'Completed' : 'Failed' ?></strong>
                                <small><?= htmlspecialchars(deliveryHistoryDate($endedAt)) ?></small>
                                <strong>Handled In</strong>
                                <small><?= htmlspecialchars(deliveryHistoryDuration(max(0, (int) ($delivery['handling_minutes'] ?? 0)))) ?></small>
                                <?php if (!empty($delivery['customer_notified_at'])): ?>
                                    <strong>Customer Notified</strong>
                                    <small><?= htmlspecialchars(deliveryHistoryDate($delivery['customer_notified_at'])) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($customerName) ?></strong>
                                <small><?= htmlspecialchars($customerEmail) ?></small>
                                <small><?= htmlspecialchars($customerPhone) ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($agentName) ?></strong>
                                <small><?= htmlspecialchars(($delivery['vehicle_type'] ?? 'Vehicle') . ' - ' . ($delivery['agent_phone'] ?? 'No phone')) ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($zone) ?></strong>
                                <small><?= htmlspecialchars($delivery['shipping_address'] ?? 'No address') ?></small>
                                <?php if (!empty($delivery['customer_notification_note'])): ?>
                                    <small><?= htmlspecialchars($notificationNote) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong>$<?= number_format((float) ($delivery['total_amount'] ?? 0), 2) ?></strong>
                                <small><?= number_format((int) ($delivery['item_count'] ?? 0)) ?> item<?= (int) ($delivery['item_count'] ?? 0) === 1 ? '' : 's' ?>, <?= number_format((int) ($delivery['total_quantity'] ?? 0)) ?> units</small>
                                <small><?= htmlspecialchars(strtoupper($delivery['payment_method'] ?? 'COD')) ?></small>
                                <small><?= htmlspecialchars($sellerNames) ?></small>
                                <small><?= htmlspecialchars($trackingNotes) ?></small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <p id="deliveryHistoryCountText">Showing <?= count($historyDeliveries) ?> deliver<?= count($historyDeliveries) === 1 ? 'y' : 'ies' ?></p>
        </div>
    </section>
</section>
