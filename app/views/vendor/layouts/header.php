<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard - NexusCommerce</title>

    <link rel="stylesheet" href="/E-Commerce-Store/public/css/layouts.css?v=vendor-layout-1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body class="vendor-body">

<header class="navbar admin-navbar vendor-navbar">
    <?php
    $vendorNotifications = $vendorNotifications ?? ['orders' => 0, 'returns' => 0, 'reviews' => 0, 'total' => 0];
    $vendorNotificationTotal = (int) ($vendorNotifications['total'] ?? 0);
    ?>

    <div class="vendor-brand-wrap">
        <a class="logo" href="/E-Commerce-Store/index.php?page=vendorDashboard">
            Nexus<span>Commerce</span>
        </a>

        <span
            class="vendor-notification-badge"
            id="vendorNotificationBadge"
            title="<?= htmlspecialchars((int) $vendorNotifications['orders'] . ' new orders, ' . (int) $vendorNotifications['returns'] . ' return requests, ' . (int) $vendorNotifications['reviews'] . ' new reviews') ?>"
            aria-label="<?= htmlspecialchars($vendorNotificationTotal . ' vendor notifications') ?>"
            <?= $vendorNotificationTotal > 0 ? '' : 'hidden' ?>
        >
            <i class="fa-solid fa-bell"></i>
            <strong id="vendorNotificationCount"><?= $vendorNotificationTotal > 99 ? '99+' : $vendorNotificationTotal ?></strong>
        </span>
    </div>

    <div class="vendor-top-actions">
        <span class="vendor-status-badge">
            <i class="fa-solid fa-circle-check"></i>
            Active Vendor
        </span>
    </div>
</header>
