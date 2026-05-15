<section class="vendor-analytics-page">
    <div class="page-header">
        <h1>Analytics</h1>
        <p>Track revenue, top products, order volume, and average order value.</p>
    </div>

    <?php
    $summary = $analytics['summary'];
    $periods = $analytics['periods'];
    ?>

    <div class="vendor-stats-grid">
        <article class="category-stat-card">
            <div class="category-stat-icon blue">
                <i class="fa-solid fa-dollar-sign"></i>
            </div>
            <div>
                <p>TOTAL REVENUE</p>
                <h2>$<?= number_format((float) $summary['total_revenue'], 2) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon purple">
                <i class="fa-regular fa-clipboard"></i>
            </div>
            <div>
                <p>ORDER VOLUME</p>
                <h2><?= number_format((int) $summary['order_volume']) ?></h2>
            </div>
        </article>

        <article class="category-stat-card">
            <div class="category-stat-icon yellow">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <div>
                <p>AVG ORDER VALUE</p>
                <h2>$<?= number_format((float) $summary['average_order_value'], 2) ?></h2>
            </div>
        </article>
    </div>

    <section class="vendor-profile-panel vendor-analytics-panel">
        <div class="vendor-panel-heading">
            <h2>Revenue Per Period</h2>

            <div class="vendor-order-filter">
                <label for="vendorAnalyticsPeriod">Period</label>
                <select id="vendorAnalyticsPeriod">
                    <option value="day">Day</option>
                    <option value="week">Week</option>
                    <option value="month">Month</option>
                </select>
            </div>
        </div>

        <?php foreach ($periods as $period => $rows): ?>
            <div class="table-wrapper vendor-analytics-period" data-analytics-period="<?= htmlspecialchars($period) ?>" <?= $period === 'day' ? '' : 'hidden' ?>>
                <table class="category-table vendor-product-table">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Revenue</th>
                            <th>Order Volume</th>
                            <th>Average Order Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="4" class="empty-cell">No sales data found.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['period_label']) ?></td>
                                <td>$<?= number_format((float) $row['revenue'], 2) ?></td>
                                <td><?= number_format((int) $row['order_volume']) ?></td>
                                <td>$<?= number_format((float) $row['average_order_value'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="vendor-profile-panel vendor-analytics-panel">
        <div class="vendor-panel-heading">
            <h2>Top-Selling Products</h2>
        </div>

        <div class="table-wrapper">
            <table class="category-table vendor-product-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Units Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($analytics['top_products'])): ?>
                        <tr>
                            <td colspan="3" class="empty-cell">No top-selling products yet.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($analytics['top_products'] as $product): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($product['name']) ?></strong></td>
                            <td><?= number_format((int) $product['units_sold']) ?></td>
                            <td>$<?= number_format((float) $product['revenue'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
