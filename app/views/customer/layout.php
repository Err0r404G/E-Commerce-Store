<?php
$base = '/E-Commerce-Store/index.php';
$assetBase = '/E-Commerce-Store/';
$user = $_SESSION['user'] ?? null;
$flash = $_SESSION['customer_flash'] ?? null;
unset($_SESSION['customer_flash']);

if (!function_exists('customerUrl')) {
    function customerUrl(string $page = 'dashboard', array $params = []): string
    {
        $pageMap = [
            'dashboard' => 'customerDashboard',
            'marketplace' => 'customerMarketplace',
            'product' => 'customerProduct',
            'cart' => 'customerCart',
            'checkout' => 'customerCheckout',
            'confirmation' => 'customerConfirmation',
            'orders' => 'customerOrders',
            'order' => 'customerOrder',
            'wishlist' => 'customerWishlist',
            'profile' => 'customerProfile',
            'disputes' => 'customerDisputes',
        ];
        $query = array_merge(['page' => $pageMap[$page] ?? 'customerDashboard'], $params);

        return '/E-Commerce-Store/index.php?' . http_build_query($query);
    }
}

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
<?php require __DIR__ . '/layouts/header.php'; ?>
<?php require __DIR__ . '/' . $view . '.php'; ?>
<?php require __DIR__ . '/layouts/footer.php'; ?>
