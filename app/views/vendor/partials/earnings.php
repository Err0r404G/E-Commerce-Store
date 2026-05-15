<section class="vendor-earnings-page">
    <div class="page-header">
        <h1>Earnings</h1>
        <p>Review gross earnings, platform commission deductions, and net payout by period.</p>
    </div>

    <?php
    $summary = $earnings['summary'];
    $periods = $earnings['periods'];
    ?>

    <div class="vendor-stats-grid">
        <article class="category-stat-card">
            <div class="category-stat-icon blue">
                <i class="fa-solid fa-sack-dollar"></i>
            </div>
            <div>
                <p>TOTAL EARNED</p>
                <h2>$<?= number_format((float) $summary['total_earned'], 2) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon purple">
                <i class="fa-solid fa-percent"></i>
            </div>
            <div>
                <p>COMMISSION DEDUCTED</p>
                <h2>$<?= number_format((float) $summary['commission_deducted'], 2) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon yellow">
                <i class="fa-regular fa-credit-card"></i>
            </div>
            <div>
                <p>NET PAYOUT</p>
                <h2>$<?= number_format((float) $summary['net_payout'], 2) ?></h2>
            </div>
        </article>
    </div>

    <section class="vendor-profile-panel vendor-earnings-panel">
        <div class="vendor-panel-heading">
            <div>
                <h2>Payout Summary</h2>
                <p class="vendor-panel-subtitle">Platform commission rate: <?= number_format((float) $summary['commission_rate'], 2) ?>%</p>
            </div>

            <div class="vendor-order-filter">
                <label for="vendorEarningsPeriod">Period</label>
                <select id="vendorEarningsPeriod">
                    <option value="day">Day</option>
                    <option value="week">Week</option>
                    <option value="month">Month</option>
                </select>
            </div>
        </div>

        <?php foreach ($periods as $period => $rows): ?>
            <div class="table-wrapper vendor-earnings-period" data-earnings-period="<?= htmlspecialchars($period) ?>" <?= $period === 'day' ? '' : 'hidden' ?>>
                <table class="category-table vendor-product-table">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Total Earned</th>
                            <th>Commission Deducted</th>
                            <th>Net Payout</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="4" class="empty-cell">No earnings data found.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['period_label']) ?></td>
                                <td>$<?= number_format((float) $row['total_earned'], 2) ?></td>
                                <td class="vendor-commission-cell">-$<?= number_format((float) $row['commission_deducted'], 2) ?></td>
                                <td><strong>$<?= number_format((float) $row['net_payout'], 2) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </section>
</section>
