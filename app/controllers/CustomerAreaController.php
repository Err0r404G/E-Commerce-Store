<?php

require_once __DIR__ . '/../models/CustomerAreaModel.php';

class CustomerAreaController
{
    private CustomerAreaModel $model;

    public function __construct(mysqli $conn)
    {
        $this->model = new CustomerAreaModel($conn);
    }

    public function dispatch(string $page): void
    {
        $action = $_POST['customer_action'] ?? null;
        if ($action) {
            $this->handleAction($action);
            return;
        }

        match ($page) {
            'marketplace' => $this->marketplace(),
            'product' => $this->product(),
            'cart' => $this->cart(),
            'checkout' => $this->checkout(),
            'confirmation' => $this->confirmation(),
            'orders' => $this->orders(),
            'order' => $this->orderDetail(),
            'wishlist' => $this->wishlist(),
            'profile' => $this->profile(),
            'disputes' => $this->disputes(),
            default => $this->dashboard(),
        };
    }

    private function dashboard(): void
    {
        $this->requireCustomer();
        $customerId = $this->customerId();
        $orders = $this->model->orders($customerId);
        $wishlist = $this->model->wishlist($customerId);
        $cartCount = $this->cartCount();
        $activeOrder = $orders[0] ?? null;
        $this->render('dashboard', compact('orders', 'wishlist', 'cartCount', 'activeOrder'));
    }

    private function marketplace(): void
    {
        $filters = [
            'keyword' => trim($_GET['keyword'] ?? ''),
            'category_id' => $_GET['category_id'] ?? '',
            'min_price' => $_GET['min_price'] ?? '',
            'max_price' => $_GET['max_price'] ?? '',
            'min_rating' => $_GET['min_rating'] ?? '',
            'in_stock' => $_GET['in_stock'] ?? '',
            'sort' => $_GET['sort'] ?? 'newest',
        ];
        $products = $this->model->products($filters);
        $categories = $this->model->categories();
        $cartCount = $this->cartCount();
        $this->render('marketplace', compact('filters', 'products', 'categories', 'cartCount'));
    }

    private function product(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $product = $this->model->product($id);
        if (!$product) {
            http_response_code(404);
            $this->render('message', ['title' => 'Product not found', 'message' => 'The product is unavailable or no longer listed.']);
            return;
        }
        $images = $this->model->productImages($id);
        $reviews = $this->model->reviews($id);
        $cartCount = $this->cartCount();
        $this->render('product', compact('product', 'images', 'reviews', 'cartCount'));
    }

    private function cart(): void
    {
        $items = $this->cartItems();
        $summary = $this->summary($items);
        $cartCount = $this->cartCount();
        $this->render('cart', compact('items', 'summary', 'cartCount'));
    }

    private function checkout(): void
    {
        $this->requireCustomer();
        $items = $this->cartItems();
        if (!$items) {
            $this->flash('Please add at least one product before checkout.');
            $this->redirect('marketplace');
        }

        $customerId = $this->customerId();
        $addresses = $this->model->addresses($customerId);
        $zones = $this->model->deliveryZones();
        $summary = $this->summary($items);
        $cartCount = $this->cartCount();
        $this->render('checkout', compact('items', 'addresses', 'zones', 'summary', 'cartCount'));
    }

    private function orders(): void
    {
        $this->requireCustomer();
        $orders = $this->model->orders($this->customerId());
        $cartCount = $this->cartCount();
        $this->render('orders', compact('orders', 'cartCount'));
    }

    private function orderDetail(): void
    {
        $this->requireCustomer();
        $order = $this->model->order($this->customerId(), (int) ($_GET['id'] ?? 0));
        if (!$order) {
            http_response_code(404);
            $this->render('message', ['title' => 'Order not found', 'message' => 'This order is not available for your account.']);
            return;
        }
        $items = $this->model->orderItems((int) $order['id']);
        $cartCount = $this->cartCount();
        $this->render('order_detail', compact('order', 'items', 'cartCount'));
    }

    private function wishlist(): void
    {
        $this->requireCustomer();
        $items = $this->model->wishlist($this->customerId());
        $cartCount = $this->cartCount();
        $this->render('wishlist', compact('items', 'cartCount'));
    }

    private function profile(): void
    {
        $this->requireCustomer();
        $profile = $this->model->customerProfile($this->customerId());
        $addresses = $this->model->addresses($this->customerId());
        $zones = $this->model->deliveryZones();
        $cartCount = $this->cartCount();
        $this->render('profile', compact('profile', 'addresses', 'zones', 'cartCount'));
    }

    private function disputes(): void
    {
        $this->requireCustomer();
        $customerId = $this->customerId();
        $disputes = $this->model->disputes($customerId);
        $disputeTargets = $this->model->disputeTargets($customerId);
        $cartCount = $this->cartCount();
        $this->render('disputes', compact('disputes', 'disputeTargets', 'cartCount'));
    }

    private function handleAction(string $action): void
    {
        match ($action) {
            'add_to_cart' => $this->addToCart(),
            'update_cart' => $this->updateCart(),
            'toggle_wishlist' => $this->toggleWishlist(),
            'update_profile' => $this->updateProfile(),
            'change_password' => $this->changePassword(),
            'upload_profile_picture' => $this->uploadProfilePicture(),
            'save_address' => $this->saveAddress(),
            'delete_address' => $this->deleteAddress(),
            'set_default_address' => $this->setDefaultAddress(),
            'place_order' => $this->placeOrder(),
            'cancel_order' => $this->cancelOrder(),
            'request_return' => $this->requestReturn(),
            'save_review' => $this->saveReview(),
            'delete_review' => $this->deleteReview(),
            'submit_dispute' => $this->submitDispute(),
            default => $this->redirect('dashboard'),
        };
    }

    private function addToCart(): void
    {
        $productId = max(0, (int) ($_POST['product_id'] ?? 0));
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        if ($productId > 0) {
            $_SESSION['customer_cart'][$productId] = min(99, ($_SESSION['customer_cart'][$productId] ?? 0) + $quantity);
            $this->flash('Product added to cart.');
        }
        $this->redirect($_POST['return_to'] ?? 'cart');
    }

    private function updateCart(): void
    {
        foreach ($_POST['quantities'] ?? [] as $productId => $quantity) {
            $productId = (int) $productId;
            $quantity = (int) $quantity;
            if ($quantity <= 0) {
                unset($_SESSION['customer_cart'][$productId]);
            } else {
                $_SESSION['customer_cart'][$productId] = min(99, $quantity);
            }
        }
        $this->flash('Cart updated.');
        $this->redirect('cart');
    }

    private function toggleWishlist(): void
    {
        $this->requireCustomer();
        $productId = (int) ($_POST['product_id'] ?? 0);
        if ($productId > 0) {
            $this->model->toggleWishlist($this->customerId(), $productId);
            $this->flash('Wishlist updated.');
        }
        $this->redirect($_POST['return_to'] ?? 'wishlist');
    }

    private function updateProfile(): void
    {
        $this->requireCustomer();
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if ($name === '') {
            $this->flash('Name is required.');
            $this->redirect('profile');
        }

        $this->model->updateCustomerProfile($this->customerId(), $name, $phone);
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['phone'] = $phone;
        $this->flash('Profile updated.');
        $this->redirect('profile');
    }

    private function changePassword(): void
    {
        $this->requireCustomer();
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $profile = $this->model->customerProfile($this->customerId());

        if (!$profile || !password_verify($current, $profile['password_hash'])) {
            $this->flash('Current password is incorrect.');
            $this->redirect('profile');
        }

        if (strlen($new) < 6 || $new !== $confirm) {
            $this->flash('New password must be at least 6 characters and match confirmation.');
            $this->redirect('profile');
        }

        $this->model->updateCustomerPassword($this->customerId(), password_hash($new, PASSWORD_DEFAULT));
        $this->flash('Password changed.');
        $this->redirect('profile');
    }

    private function uploadProfilePicture(): void
    {
        $this->requireCustomer();
        $file = $_FILES['profile_pic'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->flash('Choose an image to upload.');
            $this->redirect('profile');
        }

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($file['tmp_name']);
        if (!isset($allowed[$mime])) {
            $this->flash('Profile picture must be JPG, PNG, or WEBP.');
            $this->redirect('profile');
        }

        if ((int) $file['size'] > 2 * 1024 * 1024) {
            $this->flash('Profile picture must be under 2 MB.');
            $this->redirect('profile');
        }

        $dir = __DIR__ . '/../../public/uploads/profiles';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $name = 'customer_' . $this->customerId() . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
        $target = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            $this->flash('Could not save profile picture.');
            $this->redirect('profile');
        }

        $path = 'public/uploads/profiles/' . $name;
        $this->model->updateCustomerProfilePicture($this->customerId(), $path);
        $_SESSION['user']['profile_pic'] = $path;
        $this->flash('Profile picture updated.');
        $this->redirect('profile');
    }

    private function saveAddress(): void
    {
        $this->requireCustomer();
        $data = [
            'id' => (int) ($_POST['address_id'] ?? 0),
            'label' => trim($_POST['label'] ?? 'Home'),
            'recipient_name' => trim($_POST['recipient_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'address_line' => trim($_POST['address_line'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'postal_code' => trim($_POST['postal_code'] ?? ''),
            'delivery_zone_id' => $_POST['delivery_zone_id'] ?? '',
            'is_default' => isset($_POST['is_default']),
        ];

        if ($data['recipient_name'] === '' || $data['address_line'] === '' || $data['city'] === '') {
            $this->flash('Recipient, address, and city are required.');
            $this->redirect('profile');
        }

        $ok = $this->model->saveAddress($this->customerId(), $data);
        $this->flash($ok ? 'Address saved.' : 'Address table is missing. Run database/customer_required_tables.sql first.');
        $this->redirect('profile');
    }

    private function deleteAddress(): void
    {
        $this->requireCustomer();
        $this->model->deleteAddress($this->customerId(), (int) ($_POST['address_id'] ?? 0));
        $this->flash('Address deleted.');
        $this->redirect('profile');
    }

    private function setDefaultAddress(): void
    {
        $this->requireCustomer();
        $ok = $this->model->setDefaultAddress($this->customerId(), (int) ($_POST['address_id'] ?? 0));
        $this->flash($ok ? 'Default address updated.' : 'Address not found.');
        $this->redirect('profile');
    }

    private function placeOrder(): void
    {
        $this->requireCustomer();
        $items = $this->cartItems();
        if (!$items) {
            $this->flash('Your cart is empty.');
            $this->redirect('cart');
        }

        $address = trim($_POST['shipping_address'] ?? '');
        $payment = $_POST['payment_method'] ?? 'cod';
        $zone = $this->model->deliveryZone((int) ($_POST['delivery_zone_id'] ?? 0));
        if ($address === '' || !$zone || !in_array($payment, ['cod', 'card'], true)) {
            $this->flash('Shipping address, delivery zone, and payment method are required.');
            $this->redirect('checkout');
        }

        $addressWithZone = $address . "\nDelivery zone: " . $zone['zone_name'] . " (" . (int) $zone['estimated_days'] . " day estimate)";
        $orderId = $this->model->createOrder($this->customerId(), $items, [
            'shipping_address' => $addressWithZone,
            'payment_method' => $payment,
            'coupon_code' => trim($_POST['coupon_code'] ?? ''),
            'delivery_fee' => (float) $zone['delivery_fee'],
        ]);
        unset($_SESSION['customer_cart']);
        $this->redirect('confirmation&id=' . $orderId);
    }

    private function confirmation(): void
    {
        $this->requireCustomer();
        $order = $this->model->order($this->customerId(), (int) ($_GET['id'] ?? 0));
        if (!$order) {
            http_response_code(404);
            $this->render('message', ['title' => 'Order not found', 'message' => 'This order is not available for your account.']);
            return;
        }
        $items = $this->model->orderItems((int) $order['id']);
        $cartCount = $this->cartCount();
        $this->render('confirmation', compact('order', 'items', 'cartCount'));
    }

    private function cancelOrder(): void
    {
        $this->requireCustomer();
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $ok = $this->model->cancelOrder($this->customerId(), $orderId);
        $this->flash($ok ? 'Order cancelled.' : 'This order can no longer be cancelled.');
        $this->redirect('order&id=' . $orderId);
    }

    private function requestReturn(): void
    {
        $this->requireCustomer();
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $itemId = (int) ($_POST['order_item_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        if ($reason === '') {
            $this->flash('Please enter a reason for the return request.');
            $this->redirect('order&id=' . $orderId);
        }
        $ok = $this->model->requestReturn($this->customerId(), $orderId, $itemId, $reason);
        $this->flash($ok ? 'Return request submitted.' : 'Only delivered order items can be returned.');
        $this->redirect('order&id=' . $orderId);
    }

    private function saveReview(): void
    {
        $this->requireCustomer();
        $data = [
            'product_id' => (int) ($_POST['product_id'] ?? 0),
            'order_id' => (int) ($_POST['order_id'] ?? 0),
            'rating' => max(1, min(5, (int) ($_POST['rating'] ?? 5))),
            'review_text' => trim($_POST['review_text'] ?? ''),
        ];
        if ($data['review_text'] === '') {
            $this->flash('Review text is required.');
            $this->redirect('order&id=' . $data['order_id']);
        }

        $ok = $this->model->saveReview($this->customerId(), $data);
        $this->flash($ok ? 'Review saved.' : 'Only delivered purchased products can be reviewed.');
        $this->redirect('order&id=' . $data['order_id']);
    }

    private function deleteReview(): void
    {
        $this->requireCustomer();
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $this->model->deleteReview($this->customerId(), (int) ($_POST['product_id'] ?? 0), $orderId);
        $this->flash('Review deleted.');
        $this->redirect('order&id=' . $orderId);
    }

    private function submitDispute(): void
    {
        $this->requireCustomer();
        $target = $_POST['dispute_target'] ?? '';
        $description = trim($_POST['description'] ?? '');
        [$orderId, $sellerId] = array_pad(array_map('intval', explode(':', $target, 2)), 2, 0);

        if ($orderId <= 0 || $sellerId <= 0 || strlen($description) < 10) {
            $this->flash('Choose an order and write at least 10 characters for the dispute.');
            $this->redirect('disputes');
        }

        $ok = $this->model->createDispute($this->customerId(), $orderId, $sellerId, $description);
        $this->flash($ok ? 'Dispute submitted for admin review.' : 'That order and seller combination is not available for your account.');
        $this->redirect('disputes');
    }

    private function cartItems(): array
    {
        return $this->model->cartProducts($_SESSION['customer_cart'] ?? []);
    }

    private function summary(array $items): array
    {
        $subtotal = array_sum(array_column($items, 'line_total'));
        return [
            'subtotal' => $subtotal,
            'count' => array_sum(array_column($items, 'quantity')),
        ];
    }

    private function cartCount(): int
    {
        return array_sum($_SESSION['customer_cart'] ?? []);
    }

    private function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../views/customer_area/layout.php';
    }

    private function requireCustomer(): void
    {
        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'customer') {
            header('Location: /E-Commerce-Store/index.php?page=login');
            exit;
        }
    }

    private function customerId(): int
    {
        return (int) ($_SESSION['user']['id'] ?? 0);
    }

    private function redirect(string $page): void
    {
        header('Location: /E-Commerce-Store/customer.php?page=' . $page);
        exit;
    }

    private function flash(string $message): void
    {
        $_SESSION['customer_flash'] = $message;
    }
}
