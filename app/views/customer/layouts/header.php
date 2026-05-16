<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexusCommerce Customer</title>
    <link rel="stylesheet" href="/E-Commerce-Store/public/css/customer.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
</head>
<body>
<header class="customer-topbar">
    <a class="customer-brand" href="<?= customerUrl('marketplace') ?>">NexusCommerce</a>
    <nav class="customer-nav">
        <a href="<?= customerUrl('marketplace') ?>">Marketplace</a>
        <a href="<?= customerUrl('orders') ?>">Orders</a>
        <a href="<?= customerUrl('wishlist') ?>">Wishlist</a>
        <a href="<?= customerUrl('profile') ?>">Profile</a>
        <a href="<?= customerUrl('disputes') ?>">Disputes</a>
    </nav>
    <div class="customer-actions">
        <a class="icon-link" href="<?= customerUrl('cart') ?>" aria-label="Cart">
            <span class="material-symbols-outlined">shopping_cart</span>
            <?php if (!empty($cartCount)): ?><span class="badge"><?= (int) $cartCount ?></span><?php endif; ?>
        </a>
        <?php if ($user): ?>
            <span class="customer-name"><?= e($user['name']) ?></span>
            <a class="ghost-button customer-logout" href="/E-Commerce-Store/index.php?page=logout">
                <span class="material-symbols-outlined">logout</span>
                Logout
            </a>
        <?php else: ?>
            <a class="ghost-button" href="/E-Commerce-Store/index.php?page=login">Login</a>
        <?php endif; ?>
    </div>
</header>

<?php if ($flash): ?>
    <div class="flash-message"><?= e($flash) ?></div>
<?php endif; ?>
