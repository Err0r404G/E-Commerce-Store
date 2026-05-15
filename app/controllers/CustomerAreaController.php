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
        $addresses = $this->model->addresses($this->customerId());
        $zones = $this->model->deliveryZones();
        $cartCount = $this->cartCount();
        $this->render('profile', compact('addresses', 'zones', 'cartCount'));
    }

    private function disputes(): void
    {
        $this->requireCustomer();
        $disputes = $this->model->disputes($this->customerId());
        $cartCount = $this->cartCount();
        $this->render('disputes', compact('disputes', 'cartCount'));
    }

    private function handleAction(string $action): void
    {
        match ($action) {
            'add_to_cart' => $this->addToCart(),
            'update_cart' => $this->updateCart(),
            'toggle_wishlist' => $this->toggleWishlist(),
            'save_address' => $this->saveAddress(),
            'place_order' => $this->placeOrder(),
            'cancel_order' => $this->cancelOrder(),
            'request_return' => $this->requestReturn(),
            'save_review' => $this->saveReview(),
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

    private function saveAddress(): void
    {
        $this->requireCustomer();
        $data = [
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
        if ($address === '' || !in_array($payment, ['cod', 'card'], true)) {
            $this->flash('Shipping address and payment method are required.');
            $this->redirect('checkout');
        }

        $zoneFee = (float) ($_POST['delivery_fee'] ?? 0);
        $orderId = $this->model->createOrder($this->customerId(), $items, [
            'shipping_address' => $address,
            'payment_method' => $payment,
            'coupon_code' => trim($_POST['coupon_code'] ?? ''),
            'delivery_fee' => $zoneFee,
        ]);
        unset($_SESSION['customer_cart']);
        $this->flash('Order placed successfully. Your order ID is #' . $orderId . '.');
        $this->redirect('order&id=' . $orderId);
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
        $this->model->requestReturn($this->customerId(), $orderId, $itemId, $reason);
        $this->flash('Return request submitted.');
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
        $this->model->saveReview($this->customerId(), $data);
        $this->flash('Review saved.');
        $this->redirect('order&id=' . $data['order_id']);
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
