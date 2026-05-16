<aside class="admin-sidebar vendor-sidebar">
    <?php $vendorNotifications = $vendorNotifications ?? ['orders' => 0, 'returns' => 0, 'reviews' => 0, 'total' => 0]; ?>

    <nav class="admin-menu vendor-menu">
        <a href="/E-Commerce-Store/index.php?page=vendorDashboard" class="<?= ($activeVendorPage ?? 'dashboard') === 'dashboard' ? 'active' : '' ?>">
            <i class="fa-solid fa-table-cells-large"></i>
            <span>Dashboard</span>
        </a>

        <a href="#" data-vendor-page="/E-Commerce-Store/index.php?page=vendorInventoryAjax">
            <i class="fa-solid fa-box-archive"></i>
            <span>Inventory</span>
        </a>

        <a href="#" data-vendor-page="/E-Commerce-Store/index.php?page=vendorOrdersAjax">
            <span class="vendor-menu-icon-wrap">
                <i class="fa-regular fa-clipboard"></i>
                <strong class="vendor-menu-alert" data-vendor-alert="orders" <?= (int) ($vendorNotifications['orders'] ?? 0) > 0 ? '' : 'hidden' ?>>!</strong>
            </span>
            <span>Orders</span>
        </a>

        <a href="#" data-vendor-page="/E-Commerce-Store/index.php?page=vendorReturnsAjax">
            <span class="vendor-menu-icon-wrap">
                <i class="fa-solid fa-rotate-left"></i>
                <strong class="vendor-menu-alert" data-vendor-alert="returns" <?= (int) ($vendorNotifications['returns'] ?? 0) > 0 ? '' : 'hidden' ?>>!</strong>
            </span>
            <span>Returns</span>
        </a>

        <a href="#" data-vendor-page="/E-Commerce-Store/index.php?page=vendorCouponsAjax">
            <i class="fa-solid fa-ticket"></i>
            <span>Promo Codes</span>
        </a>

        <a href="#" data-vendor-page="/E-Commerce-Store/index.php?page=vendorReviewsAjax">
            <span class="vendor-menu-icon-wrap">
                <i class="fa-regular fa-star"></i>
                <strong class="vendor-menu-alert" data-vendor-alert="reviews" <?= (int) ($vendorNotifications['reviews'] ?? 0) > 0 ? '' : 'hidden' ?>>!</strong>
            </span>
            <span>Reviews</span>
        </a>

        <a href="#" data-vendor-page="/E-Commerce-Store/index.php?page=vendorAnalyticsAjax">
            <i class="fa-regular fa-chart-bar"></i>
            <span>Analytics</span>
        </a>

        <a href="#" data-vendor-page="/E-Commerce-Store/index.php?page=vendorSettingsAjax" class="<?= ($activeVendorPage ?? '') === 'profile' ? 'active' : '' ?>">
            <i class="fa-solid fa-gear"></i>
            <span>Settings</span>
        </a>
    </nav>

    <div class="admin-user-box vendor-user-box">
        <div class="user-info">
            <div class="user-icon" id="vendorSidebarLogo">
                <?php if (!empty($vendorAvatar)): ?>
                    <img src="/E-Commerce-Store/<?= htmlspecialchars($vendorAvatar) ?>" alt="">
                <?php else: ?>
                    <i class="fa-regular fa-user"></i>
                <?php endif; ?>
            </div>

            <div>
                <h4 id="vendorSidebarName"><?= htmlspecialchars($vendorName ?? 'Vendor') ?></h4>
                <p><?= htmlspecialchars($vendorRole ?? 'VENDOR') ?></p>
            </div>
        </div>

        <a href="/E-Commerce-Store/index.php?page=logout" class="logout">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <span>Log Out</span>
        </a>
    </div>

</aside>
