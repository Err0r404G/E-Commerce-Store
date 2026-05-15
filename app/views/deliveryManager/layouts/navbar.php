<aside class="admin-sidebar delivery-sidebar">

    <nav class="admin-menu delivery-menu">
        <a href="/E-Commerce-Store/index.php?page=deliveryDashboard" class="<?= ($activeDeliveryPage ?? 'dashboard') === 'dashboard' ? 'active' : '' ?>">
            <i class="fa-solid fa-table-cells-large"></i>
            <span>Dashboard</span>
        </a>

        <a href="#">
            <i class="fa-solid fa-route"></i>
            <span>Assignments</span>
        </a>

        <a href="#">
            <i class="fa-solid fa-users-gear"></i>
            <span>Agents</span>
        </a>

        <a href="#">
            <i class="fa-solid fa-map-location-dot"></i>
            <span>Zones</span>
        </a>

        <a href="#">
            <i class="fa-regular fa-clock"></i>
            <span>Tracking</span>
        </a>
    </nav>

    <div class="admin-user-box delivery-user-box">
        <div class="user-info">
            <div class="user-icon">
                <?php if (!empty($deliveryAvatar)): ?>
                    <img src="/E-Commerce-Store/<?= htmlspecialchars($deliveryAvatar) ?>" alt="">
                <?php else: ?>
                    <i class="fa-regular fa-user"></i>
                <?php endif; ?>
            </div>

            <div>
                <h4><?= htmlspecialchars($deliveryName ?? 'Delivery Manager') ?></h4>
                <p><?= htmlspecialchars($deliveryRole ?? 'DELIVERY MANAGER') ?></p>
            </div>
        </div>

        <a href="/E-Commerce-Store/index.php?page=logout" class="logout">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <span>Log Out</span>
        </a>
    </div>

</aside>
