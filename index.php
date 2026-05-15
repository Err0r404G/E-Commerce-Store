<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/E-Commerce-Store',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/app/controllers/AuthController.php';
require_once __DIR__ . '/app/controllers/AdminController.php';

$page = $_GET['page'] ?? 'home';

$auth = new AuthController($conn);
$admin = new AdminController($conn);

function dashboardUrlForRole(string $role): string
{
    if ($role === 'admin') {
        return '/E-Commerce-Store/index.php?page=adminDashboard';
    }

    if ($role === 'vendor') {
        return '/E-Commerce-Store/index.php?page=vendorDashboard';
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

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $auth->signup();
    } else {
        $auth->showSignup();
    }

    exit;
}

elseif ($page === 'login') {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

elseif ($page === 'adminDisputesAjax') {

    requireRole('admin');
    $admin->showDisputes();
    exit;
}

elseif ($page === 'disputeAction') {

    $admin->disputeAction();
}

elseif ($page === 'customerDashboard') {

    include __DIR__ . '/app/views/customer/customerDashboard.php';
    exit;
}

elseif ($page === 'vendorDashboard') {

    requireRole('vendor');
    include __DIR__ . '/app/views/vendor/vendor_home_page_screen.php';
    exit;
}

elseif ($page === 'deliveryDashboard') {

    include __DIR__ . '/app/views/delivery_manager/deliveryDashboard.php';
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
