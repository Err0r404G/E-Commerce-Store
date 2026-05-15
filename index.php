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

$page = $_GET['page'] ?? 'home';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$auth = new AuthController($conn);
$admin = new AdminController($conn);
$vendor = new VendorController($conn);

function dashboardUrlForRole(string $role): string
{
    if ($role === 'admin') {
        return '/E-Commerce-Store/index.php?page=adminDashboard';
    }

    if ($role === 'vendor') {
        return '/E-Commerce-Store/index.php?page=vendorDashboard';
    }

    if ($role === 'customer') {
        return '/E-Commerce-Store/customer.php';
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

elseif ($page === 'disputeAction') {

    $admin->disputeAction();
}

elseif ($page === 'customerDashboard') {

    includeViewOrShowMissing(__DIR__ . '/app/views/customer/customerDashboard.php', 'Customer Dashboard');
    exit;
}

elseif ($page === 'vendorDashboard') {

    requireRole('vendor');
    includeViewOrShowMissing(__DIR__ . '/app/views/vendor/view/vendor_home_page_screen.php', 'Vendor Dashboard');
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

    includeViewOrShowMissing(__DIR__ . '/app/views/delivery_manager/deliveryDashboard.php', 'Delivery Dashboard');
    exit;
}

/*
|--------------------------------------------------------------------------
| HOME PAGE
|--------------------------------------------------------------------------
*/

include __DIR__ . '/app/views/layouts/header.php';
?>

<main>

    <section class="hero">

        <h1>Welcome to NexusCommerce</h1>

        <p>
            Discover premium products with modern shopping experience.
        </p>

    </section>

</main>

<?php include __DIR__ . '/app/views/layouts/footer.php'; ?>
