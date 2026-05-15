<?php
$reportData = $reportData ?? [];
$allTime = $reportData['all_time'] ?? [];
$monthly = $reportData['monthly'] ?? [];
$delivery = $reportData['delivery'] ?? [];
$topSellers = $reportData['top_sellers'] ?? [];
$topCategories = $reportData['top_categories'] ?? [];
$money = static fn ($value): string => '$' . number_format((float) $value, 2);
$number = static fn ($value): string => number_format((float) $value);
$monthLabel = $reportData['month_label'] ?? date('F Y');
$generatedAt = $reportData['generated_at'] ?? date('M d, Y h:i A');
?>

<section class="platform-reports-page">
    <div class="page-header platform-report-header">
        <div>
            <h1>Platform Reports</h1>
            <p>Marketplace revenue, seller/category performance, delivery overview, and monthly reporting.</p>
        </div>

        <button type="button" class="add-category-btn" data-report-print="platformReportFull">
            <i class="fa-solid fa-print"></i>
            Print All Summary
        </button>
    </div>

    <form class="platform-report-toolbar" id="platformReportMonthForm">
        <label for="platformReportMonth">Monthly report</label>
        <input type="month" id="platformReportMonth" name="month" value="<?= htmlspecialchars($reportData['selected_month'] ?? date('Y-m')) ?>">
    </form>

    <div id="platformReportFull" data-report-title="Marketplace Report - <?= htmlspecialchars($monthLabel) ?>">
        <section class="platform-report-section" id="platformRevenueReport" data-report-title="Platform Revenue Analytics">
            <div class="platform-section-heading">
                <div>
                    <h2>Platform-Wide Revenue Analytics</h2>
                    <p>All active marketplace orders, excluding cancelled and returned orders.</p>
                </div>
            </div>

            <div class="platform-report-grid">
                <div class="platform-report-card">
                    <p>All-Time GMV</p>
                    <h3><?= $money($allTime['gmv'] ?? 0) ?></h3>
                    <span><?= $number($allTime['orders_count'] ?? 0) ?> orders</span>
                </div>
                <div class="platform-report-card">
                    <p>Commission Earned</p>
                    <h3><?= $money($allTime['commission_earned'] ?? 0) ?></h3>
                    <span>Across seller commission rates</span>
                </div>
                <div class="platform-report-card">
                    <p>Units Sold</p>
                    <h3><?= $number($allTime['units_sold'] ?? 0) ?></h3>
                    <span><?= $number($allTime['customers_count'] ?? 0) ?> customers</span>
                </div>
                <div class="platform-report-card">
                    <p>Total Discounts</p>
                    <h3><?= $money($allTime['discounts_given'] ?? 0) ?></h3>
                    <span>Vendor and platform coupons</span>
                </div>
            </div>
        </section>

        <section class="platform-report-section" id="platformMonthlyReport" data-report-title="Monthly Marketplace Report - <?= htmlspecialchars($monthLabel) ?>">
            <div class="platform-section-heading">
                <div>
                    <h2>Comprehensive Monthly Report</h2>
                    <p><?= htmlspecialchars($monthLabel) ?> marketplace performance generated <?= htmlspecialchars($generatedAt) ?>.</p>
                </div>
            </div>

            <div class="platform-report-grid">
                <div class="platform-report-card">
                    <p>Monthly GMV</p>
                    <h3><?= $money($monthly['gmv'] ?? 0) ?></h3>
                    <span><?= $number($monthly['orders_count'] ?? 0) ?> orders</span>
                </div>
                <div class="platform-report-card">
                    <p>Commission</p>
                    <h3><?= $money($monthly['commission_earned'] ?? 0) ?></h3>
                    <span>Platform revenue</span>
                </div>
                <div class="platform-report-card">
                    <p>Units Sold</p>
                    <h3><?= $number($monthly['units_sold'] ?? 0) ?></h3>
                    <span><?= $number($monthly['customers_count'] ?? 0) ?> customers</span>
                </div>
                <div class="platform-report-card">
                    <p>Delivery Success</p>
                    <h3><?= number_format((float) ($delivery['delivery_success_rate'] ?? 0), 2) ?>%</h3>
                    <span><?= $number($delivery['total_assignments'] ?? 0) ?> assignments</span>
                </div>
            </div>
        </section>

        <div class="platform-report-columns">
            <section class="platform-report-section" id="platformSellersReport" data-report-title="Top-Performing Sellers - <?= htmlspecialchars($monthLabel) ?>">
                <div class="platform-section-heading">
                    <div>
                        <h2>Top-Performing Sellers</h2>
                        <p>Ranked by monthly GMV.</p>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="category-table vendor-product-table">
                        <thead>
                            <tr>
                                <th>Seller</th>
                                <th>Orders</th>
                                <th>Units</th>
                                <th>GMV</th>
                                <th>Commission</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topSellers)): ?>
                                <tr><td colspan="5" class="empty-cell">No seller performance data for this month.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($topSellers as $seller): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($seller['seller_name'] ?? 'Unknown seller') ?></strong></td>
                                    <td><?= $number($seller['orders_count'] ?? 0) ?></td>
                                    <td><?= $number($seller['units_sold'] ?? 0) ?></td>
                                    <td><?= $money($seller['gmv'] ?? 0) ?></td>
                                    <td><?= $money($seller['commission_earned'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="platform-report-section" id="platformCategoriesReport" data-report-title="Top-Selling Categories - <?= htmlspecialchars($monthLabel) ?>">
                <div class="platform-section-heading">
                    <div>
                        <h2>Top-Selling Categories</h2>
                        <p>Ranked by monthly GMV.</p>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="category-table vendor-product-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Orders</th>
                                <th>Units</th>
                                <th>GMV</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topCategories)): ?>
                                <tr><td colspan="4" class="empty-cell">No category sales data for this month.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($topCategories as $category): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($category['category_name'] ?? 'Uncategorized') ?></strong></td>
                                    <td><?= $number($category['orders_count'] ?? 0) ?></td>
                                    <td><?= $number($category['units_sold'] ?? 0) ?></td>
                                    <td><?= $money($category['gmv'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <section class="platform-report-section" id="platformDeliveryReport" data-report-title="Delivery Performance Overview - <?= htmlspecialchars($monthLabel) ?>">
            <div class="platform-section-heading">
                <div>
                    <h2>Delivery Performance Overview</h2>
                    <p>Summary pulled from delivery manager assignment data.</p>
                </div>
            </div>

            <div class="platform-delivery-grid">
                <div class="platform-report-card">
                    <p>Total Assignments</p>
                    <h3><?= $number($delivery['total_assignments'] ?? 0) ?></h3>
                    <span><?= number_format((float) ($delivery['failure_rate'] ?? 0), 2) ?>% failed</span>
                </div>
                <?php foreach (($delivery['status_counts'] ?? []) as $status => $count): ?>
                    <div class="platform-report-card compact">
                        <p><?= htmlspecialchars(ucwords(str_replace('_', ' ', $status))) ?></p>
                        <h3><?= $number($count) ?></h3>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="table-wrapper">
                <table class="category-table vendor-product-table">
                    <thead>
                        <tr>
                            <th>Delivery Agent</th>
                            <th>Vehicle</th>
                            <th>Assignments</th>
                            <th>Delivered</th>
                            <th>Failed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($delivery['agents'])): ?>
                            <tr><td colspan="5" class="empty-cell">No delivery assignment data for this month.</td></tr>
                        <?php endif; ?>
                        <?php foreach (($delivery['agents'] ?? []) as $agent): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($agent['agent_name'] ?? 'Delivery agent') ?></strong></td>
                                <td><?= htmlspecialchars($agent['vehicle_type'] ?? 'N/A') ?></td>
                                <td><?= $number($agent['assignments'] ?? 0) ?></td>
                                <td><?= $number($agent['delivered'] ?? 0) ?></td>
                                <td><?= $number($agent['failed'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>
