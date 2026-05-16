<?php
$failedDeliveryData = $failedDeliveryData ?? ['deliveries' => [], 'agents' => [], 'stats' => []];
$deliveries = $failedDeliveryData['deliveries'] ?? [];
$agents = $failedDeliveryData['agents'] ?? [];
$stats = $failedDeliveryData['stats'] ?? ['failed' => 0, 'open' => 0, 'reassigned' => 0, 'customer_notified' => 0];

function failedDeliveryElapsedLabel(int $minutes): string
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

function failedDeliveryResolutionLabel(string $resolution): string
{
    return ucwords(str_replace('_', ' ', $resolution));
}
?>

<section class="vendor-profile-page delivery-manager-failed-page">
    <div class="page-header">
        <h1>Failed Deliveries</h1>
        <p>Review failed attempts, capture the reason, reassign to another agent, or record the customer notification.</p>
    </div>

    <div class="delivery-manager-stats-grid">
        <article class="category-stat-card">
            <div class="category-stat-icon yellow">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <p>FAILED</p>
                <h2><?= number_format((int) $stats['failed']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon blue">
                <i class="fa-regular fa-folder-open"></i>
            </div>
            <div>
                <p>OPEN</p>
                <h2><?= number_format((int) $stats['open']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon green">
                <i class="fa-solid fa-route"></i>
            </div>
            <div>
                <p>REASSIGNED</p>
                <h2><?= number_format((int) $stats['reassigned']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon purple">
                <i class="fa-regular fa-envelope"></i>
            </div>
            <div>
                <p>NOTIFIED</p>
                <h2><?= number_format((int) $stats['customer_notified']) ?></h2>
            </div>
        </article>
    </div>

    <div class="auth-message vendor-profile-message" id="failedDeliveryMessage" hidden></div>

    <section class="vendor-profile-panel">
        <div class="category-summary-header">
            <div>
                <h2>Failure Queue</h2>
                <p>Open failures need either a new agent assignment or a customer notification record.</p>
            </div>

            <div class="category-search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="failedDeliverySearch" placeholder="Search failures...">
            </div>
        </div>

        <div class="table-wrapper">
            <table class="orders-table failed-delivery-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Failed Reason</th>
                        <th>Agent</th>
                        <th>Resolution</th>
                        <th>Customer</th>
                        <th>Reassign</th>
                        <th>Notify</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($deliveries)): ?>
                        <tr>
                            <td colspan="7">No failed deliveries found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($deliveries as $delivery): ?>
                        <?php
                        $assignmentId = (int) ($delivery['assignment_id'] ?? 0);
                        $agentId = (int) ($delivery['agent_id'] ?? 0);
                        $resolution = (string) ($delivery['failure_resolution'] ?? 'open');
                        $retryCount = (int) ($delivery['retry_count'] ?? 0);
                        $zone = $delivery['delivery_zone'] ?: 'No zone selected';
                        $agentName = $delivery['agent_name'] ?: 'Delivery Agent';
                        $customerName = $delivery['customer_name'] ?: 'Customer';
                        $customerEmail = $delivery['customer_email'] ?: 'No email';
                        $failedReason = $delivery['failed_reason'] ?: 'No reason recorded';
                        $notificationNote = $delivery['customer_notification_note'] ?: '';
                        $minutesSince = max(0, (int) ($delivery['minutes_since_failed'] ?? 0));
                        $searchText = strtolower('#' . ($delivery['order_id'] ?? '') . ' ' . $zone . ' ' . $agentName . ' ' . ($delivery['agent_phone'] ?? '') . ' ' . $resolution . ' ' . $customerName . ' ' . $customerEmail . ' ' . ($delivery['shipping_address'] ?? '') . ' ' . $failedReason . ' ' . $notificationNote);
                        ?>
                        <tr data-failed-delivery-row data-search="<?= htmlspecialchars($searchText) ?>">
                            <td>
                                <strong>#<?= (int) $delivery['order_id'] ?></strong>
                                <small><?= htmlspecialchars($zone) ?></small>
                                <small><?= htmlspecialchars(failedDeliveryElapsedLabel($minutesSince)) ?> ago</small>
                            </td>
                            <td>
                                <?= htmlspecialchars($failedReason) ?>
                                <small><?= htmlspecialchars($delivery['shipping_address'] ?? 'No address') ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($agentName) ?></strong>
                                <small><?= htmlspecialchars(($delivery['vehicle_type'] ?? 'Vehicle') . ' - ' . ($delivery['agent_phone'] ?? 'No phone')) ?></small>
                            </td>
                            <td>
                                <span class="status status-processing">
                                    <?= htmlspecialchars(failedDeliveryResolutionLabel($resolution)) ?>
                                </span>
                                <?php if ($notificationNote !== ''): ?>
                                    <small><?= htmlspecialchars($notificationNote) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($customerName) ?></strong>
                                <small><?= htmlspecialchars($customerEmail) ?></small>
                                <small>$<?= number_format((float) ($delivery['total_amount'] ?? 0), 2) ?></small>
                            </td>
                            <td>
                                <form class="failed-delivery-inline-form" data-failed-delivery-form>
                                    <input type="hidden" name="failed_delivery_action" value="reassign">
                                    <input type="hidden" name="assignment_id" value="<?= $assignmentId ?>">
                                    <select name="agent_id" required <?= $retryCount > 0 ? 'disabled' : '' ?>>
                                        <option value="">New agent</option>
                                        <?php foreach ($agents as $agent): ?>
                                            <?php if ((int) ($agent['id'] ?? 0) === $agentId) {
                                                continue;
                                            } ?>
                                            <option value="<?= (int) $agent['id'] ?>">
                                                <?= htmlspecialchars($agent['name'] ?? 'Delivery Agent') ?> - <?= (int) ($agent['active_deliveries_count'] ?? 0) ?> active
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="approve-btn" <?= $retryCount > 0 ? 'disabled' : '' ?>>
                                        Reassign
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form class="failed-delivery-inline-form" data-failed-delivery-form>
                                    <input type="hidden" name="failed_delivery_action" value="notify">
                                    <input type="hidden" name="assignment_id" value="<?= $assignmentId ?>">
                                    <textarea name="notification_note" required placeholder="Message summary"><?= htmlspecialchars($notificationNote) ?></textarea>
                                    <button type="submit" class="reject-btn">
                                        Notify
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <p id="failedDeliveryCountText">Showing <?= count($deliveries) ?> failed deliver<?= count($deliveries) === 1 ? 'y' : 'ies' ?></p>
        </div>
    </section>
</section>
