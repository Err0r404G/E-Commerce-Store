<main class="customer-app">
    <aside class="customer-sidebar">
        <h2>Customer Hub</h2>
        <a class="active" href="/E-Commerce-Store/customer.php"><span class="material-symbols-outlined">dashboard</span>Dashboard</a>
        <a href="/E-Commerce-Store/customer.php?page=orders"><span class="material-symbols-outlined">package_2</span>My Orders</a>
        <a href="/E-Commerce-Store/customer.php?page=wishlist"><span class="material-symbols-outlined">favorite</span>Wishlist</a>
        <a href="/E-Commerce-Store/customer.php?page=profile"><span class="material-symbols-outlined">person</span>Profile</a>
    </aside>
    <section class="customer-content">
        <header class="page-heading">
            <div>
                <p class="eyebrow">Premium Member</p>
                <h1>Good day, <?= e($_SESSION['user']['name'] ?? 'Customer') ?>.</h1>
            </div>
            <a class="primary-button" href="/E-Commerce-Store/customer.php?page=marketplace">Browse Products</a>
        </header>

        <div class="metric-grid">
            <div class="metric-card"><span><?= count($orders) ?></span><p>Total orders</p></div>
            <div class="metric-card"><span><?= count(array_filter($orders, fn($o) => !in_array($o['status'], ['delivered', 'cancelled', 'returned'], true))) ?></span><p>Active orders</p></div>
            <div class="metric-card"><span><?= count($wishlist) ?></span><p>Wishlist items</p></div>
            <div class="metric-card"><span><?= (int) $cartCount ?></span><p>Cart quantity</p></div>
        </div>

        <div class="two-column">
            <section class="panel">
                <div class="section-title">
                    <h2>Active Shipment</h2>
                    <?php if ($activeOrder): ?><a href="/E-Commerce-Store/customer.php?page=order&id=<?= (int) $activeOrder['id'] ?>">Track</a><?php endif; ?>
                </div>
                <?php if ($activeOrder): ?>
                    <div class="status-line" data-order-id="<?= (int) $activeOrder['id'] ?>">
                        <span class="status-dot"></span>
                        <strong id="live-status"><?= e(ucwords(str_replace('_', ' ', $activeOrder['status']))) ?></strong>
                    </div>
                    <div class="timeline">
                        <?php foreach (['pending', 'confirmed', 'processing', 'shipped', 'delivered'] as $step): ?>
                            <span class="<?= array_search($step, ['pending', 'confirmed', 'processing', 'shipped', 'delivered'], true) <= array_search($activeOrder['status'], ['pending', 'confirmed', 'processing', 'shipped', 'delivered'], true) ? 'done' : '' ?>"><?= e(ucwords($step)) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <p>Order #<?= (int) $activeOrder['id'] ?> total <?= money((float) $activeOrder['total_amount']) ?></p>
                <?php else: ?>
                    <p>No active orders yet. Your first order will appear here with live tracking.</p>
                <?php endif; ?>
            </section>

            <section class="panel dark-panel">
                <h2>Share a Review</h2>
                <p>After delivery, purchased products can be rated from the order detail page.</p>
                <a class="light-button" href="/E-Commerce-Store/customer.php?page=orders">Review purchases</a>
            </section>
        </div>
    </section>
</main>
