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

$page = $_GET['page'] ?? 'home';

$auth = new AuthController($conn);

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

function fetchVendorApprovalData(mysqli $conn): array
{
    $vendors = [];
    $result = $conn->query(
        "SELECT u.id, u.name, u.email, u.phone, u.is_active, u.created_at,
                s.shop_name, s.is_approved
         FROM users u
         LEFT JOIN sellers s ON s.user_id = u.id
         WHERE u.role = 'vendor'
         ORDER BY u.is_active ASC, u.created_at DESC"
    );

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $vendors[] = $row;
        }
    }

    $counts = [
        'pending' => 0,
        'approved' => 0,
        'total' => count($vendors),
    ];

    foreach ($vendors as $vendor) {
        if ((int) $vendor['is_active'] === 1) {
            $counts['approved']++;
        } else {
            $counts['pending']++;
        }
    }

    return [$vendors, $counts];
}

function handleVendorApprovalAction(mysqli $conn): void
{
    header('Content-Type: application/json');

    if (($_SESSION['user']['role'] ?? null) !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized request.']);
        exit;
    }

    $vendorId = (int) ($_POST['vendor_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($vendorId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid approval request.']);
        exit;
    }

    $isActive = $action === 'approve' ? 1 : 0;
    $isApproved = $action === 'approve' ? 1 : 0;

    $stmt = $conn->prepare("SELECT is_active FROM users WHERE id = ? AND role = 'vendor' LIMIT 1");
    $stmt->bind_param('i', $vendorId);
    $stmt->execute();
    $vendor = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$vendor) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Vendor not found.']);
        exit;
    }

    if ((int) $vendor['is_active'] === $isActive) {
        echo json_encode([
            'success' => true,
            'message' => $action === 'approve' ? 'Vendor is already approved.' : 'Vendor is already rejected.',
        ]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'vendor'");
    $stmt->bind_param('ii', $isActive, $vendorId);
    $stmt->execute();
    $updated = $stmt->affected_rows >= 0;
    $stmt->close();

    $stmt = $conn->prepare("UPDATE sellers SET is_approved = ? WHERE user_id = ?");
    $stmt->bind_param('ii', $isApproved, $vendorId);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success' => $updated,
        'message' => $action === 'approve' ? 'Vendor approved.' : 'Vendor rejected.',
    ]);
    exit;
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
    include __DIR__ . '/app/views/admin/AdminDashboard.php';
    exit;
}

elseif ($page === 'vendorApprovalsAjax') {

    requireRole('admin');
    [$vendors, $counts] = fetchVendorApprovalData($conn);
    include __DIR__ . '/app/views/admin/VendorApproval.php';
    exit;
}

elseif ($page === 'vendorApprovalAction') {

    handleVendorApprovalAction($conn);
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
