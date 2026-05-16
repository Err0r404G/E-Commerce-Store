<?php
$agentReports = $agentReports ?? [];
$agentReportStats = $agentReportStats ?? [
    'agents' => 0,
    'completed' => 0,
    'failed' => 0,
    'average_delivery_minutes' => null,
    'failed_delivery_rate' => 0,
];

function agentReportDuration(?float $minutes): string
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

function agentReportDate(?string $dateTime): string
{
    if (empty($dateTime)) {
        return 'N/A';
    }

    $timestamp = strtotime($dateTime);

    return $timestamp ? date('M j, Y g:i A', $timestamp) : 'N/A';
}
?>

<section class="vendor-profile-page delivery-manager-agent-report-page">
    <div class="page-header">
        <h1>Agent Report</h1>
        <p>Compare delivery agents by completed deliveries, average delivery time, and failed delivery rate.</p>
    </div>

    <div class="delivery-manager-stats-grid">
        <article class="category-stat-card">
            <div class="category-stat-icon blue">
                <i class="fa-solid fa-people-carry-box"></i>
            </div>
            <div>
                <p>AGENTS</p>
                <h2><?= number_format((int) $agentReportStats['agents']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon green">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <p>COMPLETED</p>
                <h2><?= number_format((int) $agentReportStats['completed']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon purple">
                <i class="fa-solid fa-stopwatch"></i>
            </div>
            <div>
                <p>AVG TIME</p>
                <h2><?= htmlspecialchars(agentReportDuration($agentReportStats['average_delivery_minutes'])) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon yellow">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <p>FAILED RATE</p>
                <h2><?= number_format((float) $agentReportStats['failed_delivery_rate'], 2) ?>%</h2>
            </div>
        </article>
    </div>

    <section class="vendor-profile-panel">
        <div class="category-summary-header">
            <div>
                <h2>Agent Performance</h2>
                <p>Completed deliveries and averages are calculated from finished delivery assignments.</p>
            </div>

            <div class="category-search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="agentReportSearch" placeholder="Search agents...">
            </div>
        </div>

        <div class="table-wrapper">
            <table class="orders-table agent-report-table">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Status</th>
                        <th>Completed</th>
                        <th>Average Delivery Time</th>
                        <th>Failed Delivery Rate</th>
                        <th>Failed</th>
                        <th>Active</th>
                        <th>Total Assignments</th>
                        <th>Last Activity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($agentReports)): ?>
                        <tr>
                            <td colspan="9">No delivery agents found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($agentReports as $agent): ?>
                        <?php
                        $agentName = $agent['name'] ?: 'Delivery Agent';
                        $vehicleType = $agent['vehicle_type'] ?: 'Vehicle';
                        $phone = $agent['phone'] ?: 'No phone';
                        $isActive = (int) ($agent['is_active'] ?? 0) === 1;
                        $failureRate = (float) ($agent['failed_delivery_rate'] ?? 0);
                        $searchText = strtolower($agentName . ' ' . $vehicleType . ' ' . $phone . ' ' . ($isActive ? 'active' : 'inactive') . ' ' . $failureRate);
                        ?>
                        <tr data-agent-report-row data-search="<?= htmlspecialchars($searchText) ?>">
                            <td>
                                <strong><?= htmlspecialchars($agentName) ?></strong>
                                <small><?= htmlspecialchars($vehicleType . ' - ' . $phone) ?></small>
                            </td>
                            <td>
                                <span class="status <?= $isActive ? 'status-shipped' : 'status-processing' ?>">
                                    <?= $isActive ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td><?= number_format((int) ($agent['completed_deliveries'] ?? 0)) ?></td>
                            <td><?= htmlspecialchars(agentReportDuration($agent['average_delivery_minutes'])) ?></td>
                            <td><?= number_format($failureRate, 2) ?>%</td>
                            <td><?= number_format((int) ($agent['failed_deliveries'] ?? 0)) ?></td>
                            <td><?= number_format((int) ($agent['active_deliveries'] ?? 0)) ?></td>
                            <td><?= number_format((int) ($agent['total_assignments'] ?? 0)) ?></td>
                            <td>
                                <strong><?= htmlspecialchars(agentReportDate($agent['last_activity_at'] ?? null)) ?></strong>
                                <small>Last assigned: <?= htmlspecialchars(agentReportDate($agent['last_assignment_at'] ?? null)) ?></small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <p id="agentReportCountText">Showing <?= count($agentReports) ?> agent<?= count($agentReports) === 1 ? '' : 's' ?></p>
        </div>
    </section>
</section>
