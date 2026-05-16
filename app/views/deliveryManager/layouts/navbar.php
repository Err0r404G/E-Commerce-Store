<aside class="admin-sidebar delivery-manager-sidebar">

    <nav class="admin-menu delivery-manager-menu">
        <a href="/E-Commerce-Store/index.php?page=deliveryDashboard" class="<?= ($activeDeliveryPage ?? 'dashboard') === 'dashboard' ? 'active' : '' ?>">
            <i class="fa-solid fa-table-cells-large"></i>
            <span>Dashboard</span>
        </a>

        <a href="#" data-delivery-page="/E-Commerce-Store/index.php?page=deliveryAgentsAjax" class="<?= ($activeDeliveryPage ?? '') === 'agents' ? 'active' : '' ?>">
            <i class="fa-solid fa-people-carry-box"></i>
            <span>Manage Agents</span>
        </a>

        <a href="#" data-delivery-page="/E-Commerce-Store/index.php?page=deliveryZonesAjax" class="<?= ($activeDeliveryPage ?? '') === 'zones' ? 'active' : '' ?>">
            <i class="fa-solid fa-map-location-dot"></i>
            <span>Manage Zones</span>
        </a>

        <a href="#" data-delivery-page="/E-Commerce-Store/index.php?page=deliveryReadyDispatchAjax" class="<?= ($activeDeliveryPage ?? '') === 'dispatch' ? 'active' : '' ?>">
            <i class="fa-solid fa-box-open"></i>
            <span>Ready Dispatch</span>
        </a>

        <a href="#" class="<?= ($activeDeliveryPage ?? '') === 'assignments' ? 'active' : '' ?>">
            <i class="fa-solid fa-route"></i>
            <span>Assign Agent</span>
        </a>

        <a href="#" class="<?= ($activeDeliveryPage ?? '') === 'active_deliveries' ? 'active' : '' ?>">
            <i class="fa-solid fa-truck-fast"></i>
            <span>Active Deliveries</span>
        </a>

        <a href="#" class="<?= ($activeDeliveryPage ?? '') === 'failed_deliveries' ? 'active' : '' ?>">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>Failed Deliveries</span>
        </a>

        <a href="#" class="<?= ($activeDeliveryPage ?? '') === 'history' ? 'active' : '' ?>">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Delivery History</span>
        </a>

        <a href="#" class="<?= ($activeDeliveryPage ?? '') === 'agent_reports' ? 'active' : '' ?>">
            <i class="fa-regular fa-chart-bar"></i>
            <span>Agent Report</span>
        </a>

        <a href="#" class="<?= ($activeDeliveryPage ?? '') === 'zone_reports' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-pie"></i>
            <span>Zone Report</span>
        </a>

        <a href="#" class="<?= ($activeDeliveryPage ?? '') === 'delivery_summary' ? 'active' : '' ?>">
            <i class="fa-regular fa-chart-bar"></i>
            <span>Daily Weekly Summary</span>
        </a>

        <a href="#" data-delivery-page="/E-Commerce-Store/index.php?page=deliverySettingsAjax" class="<?= ($activeDeliveryPage ?? '') === 'settings' ? 'active' : '' ?>">
            <i class="fa-solid fa-gear"></i>
            <span>Settings</span>
        </a>
    </nav>

    <div class="admin-user-box delivery-manager-user-box">
        <div class="user-info">
            <div class="user-icon" id="deliverySidebarLogo">
                <?php if (!empty($deliveryAvatar)): ?>
                    <img src="/E-Commerce-Store/<?= htmlspecialchars($deliveryAvatar) ?>" alt="">
                <?php else: ?>
                    <i class="fa-regular fa-user"></i>
                <?php endif; ?>
            </div>

            <div>
                <h4 id="deliverySidebarName"><?= htmlspecialchars($deliveryName ?? 'Delivery Manager') ?></h4>
                <p><?= htmlspecialchars($deliveryRole ?? 'DELIVERY MANAGER') ?></p>
            </div>
        </div>

        <a href="/E-Commerce-Store/index.php?page=logout" class="logout">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <span>Log Out</span>
        </a>
    </div>

</aside>
