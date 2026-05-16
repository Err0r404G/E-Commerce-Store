<?php
$deliverySummaryData = $deliverySummaryData ?? [
    'today' => ['delivered' => 0, 'failed' => 0, 'in_transit' => 0],
    'this_week' => ['delivered' => 0, 'failed' => 0, 'in_transit' => 0],
    'daily' => [],
    'weekly' => [],
];

$today = $deliverySummaryData['today'] ?? ['delivered' => 0, 'failed' => 0, 'in_transit' => 0];
$thisWeek = $deliverySummaryData['this_week'] ?? ['delivered' => 0, 'failed' => 0, 'in_transit' => 0];
$dailyRows = $deliverySummaryData['daily'] ?? [];
$weeklyRows = $deliverySummaryData['weekly'] ?? [];
?>

<section class="vendor-profile-page delivery-manager-summary-page">
    <div class="page-header">
        <h1>Daily Weekly Summary</h1>
        <p>Track total delivered, failed, and in-transit deliveries by day and by week.</p>
    </div>

    <div class="delivery-manager-stats-grid">
        <article class="category-stat-card">
            <div class="category-stat-icon green">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <p>TODAY DELIVERED</p>
                <h2><?= number_format((int) $today['delivered']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon yellow">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <p>TODAY FAILED</p>
                <h2><?= number_format((int) $today['failed']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon blue">
                <i class="fa-solid fa-route"></i>
            </div>
            <div>
                <p>TODAY IN TRANSIT</p>
                <h2><?= number_format((int) $today['in_transit']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon purple">
                <i class="fa-regular fa-calendar"></i>
            </div>
            <div>
                <p>WEEK TOTAL</p>
                <h2><?= number_format((int) $thisWeek['delivered'] + (int) $thisWeek['failed'] + (int) $thisWeek['in_transit']) ?></h2>
            </div>
        </article>
    </div>

    <section class="vendor-profile-panel">
        <div class="category-summary-header">
            <div>
                <h2>Daily Summary</h2>
                <p>Last seven days of delivered, failed, and currently in-transit delivery activity.</p>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="orders-table delivery-summary-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total Delivered</th>
                        <th>Total Failed</th>
                        <th>Total In Transit</th>
                        <th>Total Activity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dailyRows)): ?>
                        <tr>
                            <td colspan="5">No daily delivery summary data found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($dailyRows as $row): ?>
                        <?php $total = (int) $row['delivered'] + (int) $row['failed'] + (int) $row['in_transit']; ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['label'] ?? 'N/A') ?></strong></td>
                            <td><?= number_format((int) $row['delivered']) ?></td>
                            <td><?= number_format((int) $row['failed']) ?></td>
                            <td><?= number_format((int) $row['in_transit']) ?></td>
                            <td><?= number_format($total) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="vendor-profile-panel">
        <div class="category-summary-header">
            <div>
                <h2>Weekly Summary</h2>
                <p>Last six weeks of delivered, failed, and currently in-transit delivery activity.</p>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="orders-table delivery-summary-table">
                <thead>
                    <tr>
                        <th>Week</th>
                        <th>Total Delivered</th>
                        <th>Total Failed</th>
                        <th>Total In Transit</th>
                        <th>Total Activity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($weeklyRows)): ?>
                        <tr>
                            <td colspan="5">No weekly delivery summary data found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($weeklyRows as $row): ?>
                        <?php $total = (int) $row['delivered'] + (int) $row['failed'] + (int) $row['in_transit']; ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['label'] ?? 'N/A') ?></strong></td>
                            <td><?= number_format((int) $row['delivered']) ?></td>
                            <td><?= number_format((int) $row['failed']) ?></td>
                            <td><?= number_format((int) $row['in_transit']) ?></td>
                            <td><?= number_format($total) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
