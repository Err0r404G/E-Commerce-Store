<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: /E-Commerce-Store/index.php?page=login');
    exit;
}

if (($_SESSION['user']['role'] ?? null) !== 'vendor') {
    header('Location: /E-Commerce-Store/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard</title>
    <link rel="stylesheet" href="/E-Commerce-Store/public/css/layouts.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include __DIR__ . '/../layouts/vendor_side_menu_bar.php'; ?>

<div class="main-layout">
    <?php include __DIR__ . '/../layouts/vendor_header.php'; ?>
    
    <div class="main-content">
        <!-- STATS CARDS -->
        <div class="stats-grid">
            <?php 
            include __DIR__ . '/widgets/home_page_top_stats_card.php';
            
            // StatsCard($icon, $label, $value, $percentage, $isPositive)
            $card1 = new StatsCard('fas fa-money-bill-wave', 'Total Sales', '$42,980.50', 12.5, true);
            $card1->render();
            
            $card2 = new StatsCard('fas fa-percent', 'Commission Paid', '$4,298.05', 0, true);
            $card2->render();
            
            $card3 = new StatsCard('fas fa-wallet', 'Net Earnings', '$38,682.45', 0, true);
            $card3->render();
            
            $card4 = new StatsCard('fas fa-shopping-cart', 'Total Orders', '1,248', 8.2, true);
            $card4->render();
            ?>
        </div>

        <!-- STATS AND POPULAR PRODUCTS ROW -->
        <div class="dashboard-row">
            <!-- SALES CHART - 2/3 Width -->
            <div class="dashboard-col-2-3">
                <?php 
                include __DIR__ . '/widgets/vendor_homepage_stats.php';
                
                $salesCard = new SalesPerformanceCard(
                    'Sales Performance',
                    'Last 30 days performance trend',
                    [
                        'labels' => ['01 Oct', '08 Oct', '15 Oct', '22 Oct', '30 Oct'],
                        'values' => [2000, 2500, 3500, 2800, 4200]
                    ],
                    [
                        'labels' => ['01 Oct', '15 Oct', '30 Oct', '15 Nov', '30 Nov'],
                        'values' => [2000, 2800, 3500, 3200, 4200]
                    ]
                );
                $salesCard->render();
                ?>
            </div>

            <!-- POPULAR PRODUCTS - 1/3 Width -->
            <div class="dashboard-col-1-3">
                <?php 
                include __DIR__ . '/widgets/homepage_popular_product_card.php';
                
                $products = [
                    [
                        'image' => 'https://picsum.photos/60/60?random=1',
                        'name' => 'Minimalist Watch',
                        'sales' => '328 Sales',
                        'price' => '$12,840'
                    ],
                    [
                        'image' => 'https://picsum.photos/60/60?random=2',
                        'name' => 'Pro Headphones',
                        'sales' => '312 Sales',
                        'price' => '$15,600'
                    ],
                    [
                        'image' => 'https://picsum.photos/60/60?random=3',
                        'name' => 'Sport Runners',
                        'sales' => '256 Sales',
                        'price' => '$8,960'
                    ],
                     [
                        'image' => 'https://picsum.photos/60/60?random=3',
                        'name' => 'Sport Runners',
                        'sales' => '256 Sales',
                        'price' => '$8,960'
                    ]
                ];
                
                $popularCard = new PopularProductCard('Popular Products', '#', $products);
                $popularCard->render();
                ?>
            </div>
        </div>

        <!-- RECENT ORDERS TABLE -->
        <div style="padding: 0 30px; margin-bottom: 30px;">
            <?php 
            include __DIR__ . '/widgets/home_page_bottom_table.php';
            
            $orders = [
                [
                    'orderId' => '#VC-0842',
                    'customer' => 'Alex Thompson',
                    'date' => 'Oct 24, 2024',
                    'amount' => '$128.50',
                    'status' => 'shipped',
                    'actionLink' => '#'
                ],
                [
                    'orderId' => '#VC-0841',
                    'customer' => 'Sarah Jenkins',
                    'date' => 'Oct 24, 2024',
                    'amount' => '$342.00',
                    'status' => 'processing',
                    'actionLink' => '#'
                ],
                [
                    'orderId' => '#VC-0840',
                    'customer' => 'Michael Chen',
                    'date' => 'Oct 23, 2024',
                    'amount' => '$89.99',
                    'status' => 'pending',
                    'actionLink' => '#'
                ],
                [
                    'orderId' => '#VC-0839',
                    'customer' => 'Emma Rodriguez',
                    'date' => 'Oct 23, 2024',
                    'amount' => '$1,240.00',
                    'status' => 'shipped',
                    'actionLink' => '#'
                ]
            ];
            
            $ordersTable = new RecentOrdersTable('Recent Orders', '#', $orders);
            $ordersTable->render();
            ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/vendor_footer.php'; ?>

</body>
</html>

