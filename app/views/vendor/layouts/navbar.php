<aside class="admin-sidebar vendor-sidebar">

    <nav class="admin-menu vendor-menu">
        <a href="/E-Commerce-Store/index.php?page=vendorDashboard" class="active">
            <i class="fa-solid fa-table-cells-large"></i>
            <span>Dashboard</span>
        </a>

        <a href="#">
            <i class="fa-solid fa-box-archive"></i>
            <span>Inventory</span>
        </a>

        <a href="#">
            <i class="fa-regular fa-clipboard"></i>
            <span>Orders</span>
        </a>

        <a href="#">
            <i class="fa-regular fa-chart-bar"></i>
            <span>Analytics</span>
        </a>

        <a href="#">
            <i class="fa-regular fa-credit-card"></i>
            <span>Payments</span>
        </a>

        <a href="#">
            <i class="fa-solid fa-gear"></i>
            <span>Settings</span>
        </a>
    </nav>

    <div class="admin-user-box vendor-user-box">
        <div class="user-info">
            <div class="user-icon">
                <?php if (!empty($vendorAvatar)): ?>
                    <img src="/E-Commerce-Store/<?= htmlspecialchars($vendorAvatar) ?>" alt="">
                <?php else: ?>
                    <i class="fa-regular fa-user"></i>
                <?php endif; ?>
            </div>

            <div>
                <h4><?= htmlspecialchars($vendorName ?? 'Vendor') ?></h4>
                <p><?= htmlspecialchars($vendorRole ?? 'VENDOR') ?></p>
            </div>
        </div>

        <a href="/E-Commerce-Store/index.php?page=logout" class="logout">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <span>Log Out</span>
        </a>
    </div>

</aside>
