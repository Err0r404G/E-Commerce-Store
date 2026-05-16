<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/E-Commerce-Store',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/app/views/auth/control/AuthController.php';
require_once __DIR__ . '/app/controllers/admin/AdminController.php';
require_once __DIR__ . '/app/controllers/vendor/VendorController.php';
require_once __DIR__ . '/app/controllers/deliveryManager/DeliveryManagerController.php';
require_once __DIR__ . '/app/controllers/CustomerAreaController.php';

$page = $_GET['page'] ?? 'home';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$auth = new AuthController($conn);
$admin = new AdminController($conn);
$vendor = new VendorController($conn);
$deliveryManager = new DeliveryManagerController($conn);
$customerArea = new CustomerAreaController($conn);

function dashboardUrlForRole(string $role): string
{
    if ($role === 'admin') {
        return '/E-Commerce-Store/index.php?page=adminDashboard';
    }

    if ($role === 'vendor') {
        return '/E-Commerce-Store/index.php?page=vendorDashboard';
    }

    if ($role === 'delivery_manager') {
        return '/E-Commerce-Store/index.php?page=deliveryDashboard';
    }

    if ($role === 'customer') {
        return '/E-Commerce-Store/index.php?page=customerDashboard';
    }

    return '/E-Commerce-Store/index.php';
}

function requireRole(string $role): void
{
    $currentRole = $_SESSION['user']['role'] ?? null;

    if ($currentRole !== $role) {
        header('Location: /E-Commerce-Store/index.php?page=login');
        exit;
    }
}

function includeViewOrShowMissing(string $path, string $title): void
{
    if (is_file($path)) {
        include $path;
        return;
    }

    include __DIR__ . '/app/views/layouts/header.php';
    ?>
    <main>
        <section class="hero">
            <h1><?= htmlspecialchars($title) ?></h1>
            <p>This view has not been moved into the new MVC structure yet.</p>
        </section>
    </main>
    <?php
    include __DIR__ . '/app/views/layouts/footer.php';
}

if ($page === 'home' && !empty($_SESSION['user']['role'])) {
    header('Location: ' . dashboardUrlForRole($_SESSION['user']['role']));
    exit;
}

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

if ($page === 'signup') {

    if ($requestMethod === 'POST') {
        $auth->signup();
    } else {
        $auth->showSignup();
    }

    exit;
}

elseif ($page === 'login') {

    if ($requestMethod === 'POST') {
        $auth->login();
    } else {
        $auth->showLogin();
    }

    exit;
}

elseif ($page === 'logout') {

    $auth->logout();
    exit;
}

/*
|--------------------------------------------------------------------------
| DASHBOARD ROUTES
|--------------------------------------------------------------------------
*/

elseif ($page === 'adminDashboard') {

    requireRole('admin');
    $admin->showDashboard();
    exit;
}

elseif ($page === 'vendorApprovalsAjax') {

    requireRole('admin');
    $admin->showVendorApprovals();
    exit;
}

elseif ($page === 'adminDashboardAjax') {

    requireRole('admin');
    $admin->showDashboardHome();
    exit;
}

elseif ($page === 'vendorApprovalAction') {

    $admin->vendorApprovalAction();
}

elseif ($page === 'categoryManagementAjax') {

    requireRole('admin');
    $admin->showCategoryManagement();
    exit;
}

elseif ($page === 'categoryAction') {

    $admin->categoryAction();
}

elseif ($page === 'productManagementAjax') {

    requireRole('admin');
    $admin->showProductManagement();
    exit;
}

elseif ($page === 'adminProductAction') {

    $admin->productAction();
}

elseif ($page === 'orderManagementAjax') {

    requireRole('admin');
    $admin->showOrderManagement();
    exit;
}

elseif ($page === 'adminCustomersAjax') {

    requireRole('admin');
    $admin->showCustomerAccounts();
    exit;
}

elseif ($page === 'adminDeliveryManagersAjax') {

    requireRole('admin');
    $admin->showDeliveryManagerAccounts();
    exit;
}

elseif ($page === 'adminAccountAction') {

    $admin->accountAction();
}

elseif ($page === 'createDeliveryManagerAction') {

    $admin->createDeliveryManagerAction();
}

elseif ($page === 'adminDisputesAjax') {

    requireRole('admin');
    $admin->showDisputes();
    exit;
}

elseif ($page === 'platformCouponsAjax') {

    requireRole('admin');
    $admin->showPlatformCoupons();
    exit;
}

elseif ($page === 'platformCouponAction') {

    requireRole('admin');
    $admin->platformCouponAction();
    exit;
}

elseif ($page === 'adminSettingsAjax') {

    requireRole('admin');
    $admin->showSettings();
    exit;
}

elseif ($page === 'platformReportsAjax') {

    requireRole('admin');
    $admin->showPlatformReports();
    exit;
}

elseif ($page === 'adminSettingsAction') {

    requireRole('admin');
    $admin->settingsAction();
    exit;
}

elseif ($page === 'disputeAction') {

    $admin->disputeAction();
}

elseif ($page === 'customerDashboard') {

    $customerArea->dispatch('dashboard');
    exit;
}

elseif ($page === 'customerMarketplace') {

    $customerArea->dispatch('marketplace');
    exit;
}

elseif ($page === 'customerProduct') {

    $customerArea->dispatch('product');
    exit;
}

elseif ($page === 'customerCart') {

    $customerArea->dispatch('cart');
    exit;
}

elseif ($page === 'customerCheckout') {

    $customerArea->dispatch('checkout');
    exit;
}

elseif ($page === 'customerConfirmation') {

    $customerArea->dispatch('confirmation');
    exit;
}

elseif ($page === 'customerOrders') {

    $customerArea->dispatch('orders');
    exit;
}

elseif ($page === 'customerOrder') {

    $customerArea->dispatch('order');
    exit;
}

elseif ($page === 'customerWishlist') {

    $customerArea->dispatch('wishlist');
    exit;
}

elseif ($page === 'customerProfile') {

    $customerArea->dispatch('profile');
    exit;
}

elseif ($page === 'customerDisputes') {

    $customerArea->dispatch('disputes');
    exit;
}

elseif ($page === 'vendorDashboard') {

    requireRole('vendor');
    $vendor->showDashboard();
    exit;
}

elseif ($page === 'vendorProfile') {

    requireRole('vendor');

    if ($requestMethod === 'POST') {
        $vendor->updateProfile();
    } else {
        $vendor->showProfile();
    }

    exit;
}

elseif ($page === 'vendorInventoryAjax') {

    requireRole('vendor');
    $vendor->showInventoryAjax();
    exit;
}

elseif ($page === 'vendorSettingsAjax') {

    requireRole('vendor');
    $vendor->showSettingsAjax();
    exit;
}

elseif ($page === 'vendorNotificationsAjax') {

    requireRole('vendor');
    $vendor->showNotificationCountsAjax();
    exit;
}

elseif ($page === 'vendorCouponsAjax') {

    requireRole('vendor');
    $vendor->showCouponsAjax();
    exit;
}

elseif ($page === 'vendorOrdersAjax') {

    requireRole('vendor');
    $vendor->showOrdersAjax();
    exit;
}

elseif ($page === 'vendorReturnsAjax') {

    requireRole('vendor');
    $vendor->showReturnsAjax();
    exit;
}

elseif ($page === 'vendorReviewsAjax') {

    requireRole('vendor');
    $vendor->showReviewsAjax();
    exit;
}

elseif ($page === 'vendorAnalyticsAjax') {

    requireRole('vendor');
    $vendor->showAnalyticsAjax();
    exit;
}

elseif ($page === 'vendorEarningsAjax') {

    requireRole('vendor');
    $vendor->showEarningsAjax();
    exit;
}

elseif ($page === 'vendorProductAction') {

    requireRole('vendor');
    $vendor->productAction();
    exit;
}

elseif ($page === 'vendorProfileAction') {

    requireRole('vendor');
    $vendor->profileAction();
    exit;
}

elseif ($page === 'vendorCouponAction') {

    requireRole('vendor');
    $vendor->couponAction();
    exit;
}

elseif ($page === 'vendorOrderAction') {

    requireRole('vendor');
    $vendor->orderAction();
    exit;
}

elseif ($page === 'vendorReturnAction') {

    requireRole('vendor');
    $vendor->returnAction();
    exit;
}

elseif ($page === 'vendorReviewAction') {

    requireRole('vendor');
    $vendor->reviewAction();
    exit;
}

elseif ($page === 'deliveryDashboard') {

    requireRole('delivery_manager');
    $deliveryManager->showDashboard();
    exit;
}

elseif ($page === 'deliverySettingsAjax') {

    requireRole('delivery_manager');
    $deliveryManager->showSettingsAjax();
    exit;
}

elseif ($page === 'deliveryAgentsAjax') {

    requireRole('delivery_manager');
    $deliveryManager->showAgentsAjax();
    exit;
}

elseif ($page === 'deliveryZonesAjax') {

    requireRole('delivery_manager');
    $deliveryManager->showZonesAjax();
    exit;
}

elseif ($page === 'deliveryReadyDispatchAjax') {

    requireRole('delivery_manager');
    $deliveryManager->showReadyDispatchAjax();
    exit;
}

elseif ($page === 'deliveryAssignAgentAjax') {

    requireRole('delivery_manager');
    $deliveryManager->showAssignAgentAjax();
    exit;
}

elseif ($page === 'deliveryActiveDeliveriesAjax') {

    requireRole('delivery_manager');
    $deliveryManager->showActiveDeliveriesAjax();
    exit;
}

elseif ($page === 'deliveryFailedDeliveriesAjax') {

    requireRole('delivery_manager');
    $deliveryManager->showFailedDeliveriesAjax();
    exit;
}

elseif ($page === 'deliveryHistoryAjax') {

    requireRole('delivery_manager');
    $deliveryManager->showDeliveryHistoryAjax();
    exit;
}

elseif ($page === 'deliveryAgentReportAjax') {

    requireRole('delivery_manager');
    $deliveryManager->showAgentReportAjax();
    exit;
}

elseif ($page === 'deliveryZoneReportAjax') {

    requireRole('delivery_manager');
    $deliveryManager->showZoneReportAjax();
    exit;
}

elseif ($page === 'deliverySummaryAjax') {

    requireRole('delivery_manager');
    $deliveryManager->showDeliverySummaryAjax();
    exit;
}

elseif ($page === 'deliveryProfileAction') {

    requireRole('delivery_manager');
    $deliveryManager->profileAction();
    exit;
}

elseif ($page === 'deliveryAgentAction') {

    requireRole('delivery_manager');
    $deliveryManager->agentAction();
    exit;
}

elseif ($page === 'deliveryZoneAction') {

    requireRole('delivery_manager');
    $deliveryManager->zoneAction();
    exit;
}

elseif ($page === 'deliveryAssignAgentAction') {

    requireRole('delivery_manager');
    $deliveryManager->assignAgentAction();
    exit;
}

elseif ($page === 'deliveryStatusAction') {

    requireRole('delivery_manager');
    $deliveryManager->deliveryStatusAction();
    exit;
}

elseif ($page === 'deliveryFailedAction') {

    requireRole('delivery_manager');
    $deliveryManager->failedDeliveryAction();
    exit;
}

/*
|--------------------------------------------------------------------------
| HOME PAGE
|--------------------------------------------------------------------------
*/

$homeCategories = [];
$homeProducts = [];

$categoryResult = $conn->query(
    "SELECT c.id, c.name, COUNT(p.id) AS product_count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id AND p.is_available = 1
     GROUP BY c.id, c.name
     HAVING product_count > 0
     ORDER BY c.name
     LIMIT 6"
);

if ($categoryResult) {
    $homeCategories = $categoryResult->fetch_all(MYSQLI_ASSOC);
}

$productResult = $conn->query(
    "SELECT p.id, p.name, p.price, p.stock_qty, p.primary_image_path,
            c.name AS category_name, s.shop_name,
            COALESCE(AVG(r.rating), 0) AS avg_rating,
            COUNT(r.id) AS review_count
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     INNER JOIN sellers s ON s.id = p.seller_id
     LEFT JOIN reviews r ON r.product_id = p.id
     WHERE p.is_available = 1
     GROUP BY p.id, p.name, p.price, p.stock_qty, p.primary_image_path, c.name, s.shop_name
     ORDER BY p.created_at DESC
     LIMIT 8"
);

if ($productResult) {
    $homeProducts = $productResult->fetch_all(MYSQLI_ASSOC);
}

function homeProductImage(?string $path): string
{
    if ($path) {
        return '/E-Commerce-Store/' . ltrim($path, '/');
    }

    return 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=900&q=80';
}

include __DIR__ . '/app/views/layouts/header.php';
?>

<main class="storefront-home">

    <section class="storefront-hero">
        <div>
            <p class="storefront-eyebrow">Fresh arrivals</p>
            <h1>Shop products from verified NexusCommerce sellers.</h1>
            <p>Browse available products, compare sellers, and check out from the customer marketplace.</p>
            <div class="storefront-actions">
                <a class="storefront-primary" href="/E-Commerce-Store/index.php?page=customerMarketplace">Shop Marketplace</a>
                <a class="storefront-secondary" href="/E-Commerce-Store/index.php?page=signup">Create Account</a>
            </div>
        </div>
        <div class="storefront-hero-panel">
            <strong><?= count($homeProducts) ?></strong>
            <span>featured products ready to browse</span>
        </div>
    </section>

    <?php if ($homeCategories): ?>
        <section class="storefront-section">
            <div class="storefront-section-heading">
                <h2>Shop By Category</h2>
                <a href="/E-Commerce-Store/index.php?page=customerMarketplace">View all</a>
            </div>
            <div class="storefront-category-grid">
                <?php foreach ($homeCategories as $category): ?>
                    <a class="storefront-category" href="/E-Commerce-Store/index.php?page=customerMarketplace&category_id=<?= (int) $category['id'] ?>">
                        <span><?= htmlspecialchars($category['name']) ?></span>
                        <small><?= (int) $category['product_count'] ?> product<?= (int) $category['product_count'] === 1 ? '' : 's' ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="storefront-section">
        <div class="storefront-section-heading">
            <h2>Featured Products</h2>
            <a href="/E-Commerce-Store/index.php?page=customerMarketplace">Browse marketplace</a>
        </div>

        <?php if ($homeProducts): ?>
            <div class="storefront-product-grid">
                <?php foreach ($homeProducts as $product): ?>
                    <article class="storefront-product-card">
                        <a class="storefront-product-image" href="/E-Commerce-Store/index.php?page=customerProduct&id=<?= (int) $product['id'] ?>">
                            <img src="<?= htmlspecialchars(homeProductImage($product['primary_image_path'])) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        </a>
                        <div class="storefront-product-body">
                            <p><?= htmlspecialchars($product['shop_name']) ?></p>
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <div class="storefront-product-meta">
                                <span><?= htmlspecialchars($product['category_name'] ?? 'Product') ?></span>
                                <span><?= number_format((float) $product['avg_rating'], 1) ?> (<?= (int) $product['review_count'] ?>)</span>
                            </div>
                            <div class="storefront-product-footer">
                                <strong>$<?= number_format((float) $product['price'], 2) ?></strong>
                                <a href="/E-Commerce-Store/index.php?page=customerProduct&id=<?= (int) $product['id'] ?>">View</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="storefront-empty">
                <h2>No products available yet.</h2>
                <p>Approved vendor products will appear here automatically.</p>
            </div>
        <?php endif; ?>
    </section>

</main>

<?php include __DIR__ . '/app/views/layouts/footer.php'; ?>
