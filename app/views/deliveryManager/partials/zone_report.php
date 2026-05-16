<?php
$zoneReports = $zoneReports ?? [];
$zoneReportStats = $zoneReportStats ?? [
    'zones' => 0,
    'total_deliveries' => 0,
    'completed' => 0,
    'average_delivery_minutes' => null,
];

function zoneReportDuration(?float $minutes): string
{
    if ($minutes === null) {
        return 'N/A';
    }

    $roundedMinutes = max(0, (int) round($minutes));

    if ($roundedMinutes < 60) {
        return $roundedMinutes . ' min';
    }

    $hours = intdiv($roundedMinutes, 60);
    $remainingMinutes = $roundedMinutes % 60;

    if ($hours < 24) {
        return $hours . 'h' . ($remainingMinutes > 0 ? ' ' . $remainingMinutes . 'm' : '');
    }

    $days = intdiv($hours, 24);
    $remainingHours = $hours % 24;

    return $days . 'd' . ($remainingHours > 0 ? ' ' . $remainingHours . 'h' : '');
}

function zoneReportDate(?string $dateTime): string
{
    if (empty($dateTime)) {
        return 'N/A';
    }

    $timestamp = strtotime($dateTime);

    return $timestamp ? date('M j, Y g:i A', $timestamp) : 'N/A';
}
?>

<section class="vendor-profile-page delivery-manager-zone-report-page">
    <div class="page-header">
        <h1>Zone Report</h1>
        <p>Review delivery volume and average delivery time across every delivery zone.</p>
    </div>

    <div class="delivery-manager-stats-grid">
        <article class="category-stat-card">
            <div class="category-stat-icon blue">
                <i class="fa-solid fa-map-location-dot"></i>
            </div>
            <div>
                <p>ZONES</p>
                <h2><?= number_format((int) $zoneReportStats['zones']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon green">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <div>
                <p>VOLUME</p>
                <h2><?= number_format((int) $zoneReportStats['total_deliveries']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon purple">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <p>COMPLETED</p>
                <h2><?= number_format((int) $zoneReportStats['completed']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon yellow">
                <i class="fa-solid fa-stopwatch"></i>
            </div>
            <div>
                <p>AVG TIME</p>
                <h2><?= htmlspecialchars(zoneReportDuration($zoneReportStats['average_delivery_minutes'])) ?></h2>
            </div>
        </article>
    </div>

    <section class="vendor-profile-panel">
        <div class="category-summary-header">
            <div>
                <h2>Zone Performance</h2>
                <p>Volume includes all assignments; average delivery time uses completed deliveries.</p>
            </div>

            <div class="category-search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="zoneReportSearch" placeholder="Search zones...">
            </div>
        </div>

        <div class="table-wrapper">
            <table class="orders-table zone-report-table">
                <thead>
                    <tr>
                        <th>Zone</th>
                        <th>Delivery Volume</th>
                        <th>Average Delivery Time</th>
                        <th>Completed</th>
                        <th>Completed Rate</th>
                        <th>Failed</th>
                        <th>Active</th>
                        <th>Last Activity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($zoneReports)): ?>
                        <tr>
                            <td colspan="8">No delivery zone performance data found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($zoneReports as $zone): ?>
                        <?php
                        $zoneName = $zone['zone_name'] ?: 'No zone selected';
                        $completedRate = (float) ($zone['completed_rate'] ?? 0);
                        $searchText = strtolower($zoneName . ' ' . ($zone['total_deliveries'] ?? 0) . ' ' . $completedRate);
                        ?>
                        <tr data-zone-report-row data-search="<?= htmlspecialchars($searchText) ?>">
                            <td>
                                <strong><?= htmlspecialchars($zoneName) ?></strong>
                            </td>
                            <td><?= number_format((int) ($zone['total_deliveries'] ?? 0)) ?></td>
                            <td><?= htmlspecialchars(zoneReportDuration($zone['average_delivery_minutes'])) ?></td>
                            <td><?= number_format((int) ($zone['completed_deliveries'] ?? 0)) ?></td>
                            <td><?= number_format($completedRate, 2) ?>%</td>
                            <td><?= number_format((int) ($zone['failed_deliveries'] ?? 0)) ?></td>
                            <td><?= number_format((int) ($zone['active_deliveries'] ?? 0)) ?></td>
                            <td><?= htmlspecialchars(zoneReportDate($zone['last_activity_at'] ?? null)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <p id="zoneReportCountText">Showing <?= count($zoneReports) ?> zone<?= count($zoneReports) === 1 ? '' : 's' ?></p>
        </div>
    </section>
</section>
