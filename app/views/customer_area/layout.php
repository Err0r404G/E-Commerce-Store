<?php
$base = '/E-Commerce-Store/customer.php';
$assetBase = '/E-Commerce-Store/';
$user = $_SESSION['user'] ?? null;
$flash = $_SESSION['customer_flash'] ?? null;
unset($_SESSION['customer_flash']);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money(float $value): string
{
    return '$' . number_format($value, 2);
}

function productImage(?string $path): string
{
    if ($path) {
        return '/E-Commerce-Store/' . ltrim($path, '/');
    }

    return 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=900&q=80';
}
?>
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
    <a class="customer-brand" href="<?= $base ?>?page=marketplace">NexusCommerce</a>
    <nav class="customer-nav">
        <a href="<?= $base ?>?page=marketplace">Marketplace</a>
        <a href="<?= $base ?>?page=orders">Orders</a>
        <a href="<?= $base ?>?page=wishlist">Wishlist</a>
        <a href="<?= $base ?>?page=profile">Profile</a>
        <a href="<?= $base ?>?page=disputes">Disputes</a>
    </nav>
    <div class="customer-actions">
        <a class="icon-link" href="<?= $base ?>?page=cart" aria-label="Cart">
            <span class="material-symbols-outlined">shopping_cart</span>
            <?php if (!empty($cartCount)): ?><span class="badge"><?= (int) $cartCount ?></span><?php endif; ?>
        </a>
        <?php if ($user): ?>
            <span class="customer-name"><?= e($user['name']) ?></span>
            <a class="ghost-button" href="/E-Commerce-Store/index.php?page=logout">Logout</a>
        <?php else: ?>
            <a class="ghost-button" href="/E-Commerce-Store/index.php?page=login">Login</a>
        <?php endif; ?>
    </div>
</header>

<?php if ($flash): ?>
    <div class="flash-message"><?= e($flash) ?></div>
<?php endif; ?>

<?php require __DIR__ . '/' . $view . '.php'; ?>

<footer class="customer-footer">
    <strong>NexusCommerce</strong>
    <span>Customer marketplace, cart, checkout, tracking, reviews, wishlist, returns, and disputes.</span>
</footer>
<script src="/E-Commerce-Store/public/js/customer.js"></script>
</body>
</html>
