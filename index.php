<?php
session_start();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/app/controllers/AuthController.php';

$page = $_GET['page'] ?? 'home';

$auth = new AuthController($conn);

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

    include __DIR__ . '/app/views/admin/adminDashboard.php';
    exit;
}

elseif ($page === 'customerDashboard') {

    include __DIR__ . '/app/views/customer/customerDashboard.php';
    exit;
}

elseif ($page === 'sellerDashboard') {

    include __DIR__ . '/app/views/seller/sellerDashboard.php';
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