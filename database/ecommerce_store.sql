-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2026 at 07:19 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecommerce_store`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `name`, `description`) VALUES
(1, NULL, 'Electronics', 'Electronic gadgets and devices'),
(2, NULL, 'Clothing', 'Fashion and clothing products'),
(3, NULL, 'Books', 'Books and educational materials'),
(4, NULL, 'Home & Garden', 'Home improvement and garden products'),
(5, NULL, 'Mobile', 'Iphone, Samsung'),
(6, NULL, 'Others', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_pct` decimal(5,2) NOT NULL,
  `max_uses` int(11) DEFAULT 100,
  `uses_count` int(11) DEFAULT 0,
  `valid_until` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_agents`
--

CREATE TABLE `delivery_agents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL DEFAULT 'Delivery Agent',
  `vehicle_type` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_agents`
--

INSERT INTO `delivery_agents` (`id`, `user_id`, `name`, `vehicle_type`, `phone`, `is_active`, `created_at`) VALUES
(1, NULL, 'KUDDUS', 'Bike', '2646', 1, '2026-05-16 02:14:25'),
(2, NULL, 'MOFIS', 'Car', '1231', 1, '2026-05-16 02:14:40'),
(3, NULL, 'KAMAL', 'Van', '6545', 1, '2026-05-16 02:14:49'),
(4, NULL, 'ROFIQUE', 'Bicycle', '9878', 1, '2026-05-16 02:15:02');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_assignments`
--

CREATE TABLE `delivery_assignments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `assigned_at` datetime DEFAULT current_timestamp(),
  `status` enum('assigned','picked_up','in_transit','delivered','failed') DEFAULT 'assigned',
  `delivery_zone` varchar(100) DEFAULT NULL,
  `failed_reason` text DEFAULT NULL,
  `failed_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `failure_resolution` enum('open','reassigned','customer_notified') NOT NULL DEFAULT 'open',
  `customer_notified_at` datetime DEFAULT NULL,
  `customer_notification_note` text DEFAULT NULL,
  `retry_of_assignment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_zones`
--

CREATE TABLE `delivery_zones` (
  `id` int(11) NOT NULL,
  `zone_name` varchar(100) NOT NULL,
  `delivery_fee` decimal(8,2) DEFAULT 0.00,
  `estimated_days` int(11) DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_zones`
--

INSERT INTO `delivery_zones` (`id`, `zone_name`, `delivery_fee`, `estimated_days`) VALUES
(1, 'Dhaka City', 60.00, 2),
(2, 'Outside Dhaka', 120.00, 5);

-- --------------------------------------------------------

--
-- Table structure for table `disputes`
--

CREATE TABLE `disputes` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('open','resolved') DEFAULT 'open',
  `admin_note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `shipping_address` text NOT NULL,
  `payment_method` enum('cod','card') DEFAULT 'cod',
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled','return_requested','returned') DEFAULT 'pending',
  `coupon_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `shipping_address`, `payment_method`, `subtotal`, `discount_amount`, `total_amount`, `status`, `coupon_id`, `created_at`) VALUES
(1, 9, 's\nDelivery zone: Dhaka City (2 day estimate)', 'cod', 211.00, 0.00, 271.00, 'pending', NULL, '2026-05-16 01:13:52');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `item_status` enum('pending','confirmed','shipped','delivered') DEFAULT 'pending',
  `tracking_note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `seller_id`, `quantity`, `unit_price`, `item_status`, `tracking_note`) VALUES
(1, 1, 1, 1, 1, 100.00, 'confirmed', NULL),
(2, 1, 2, 1, 1, 111.00, 'shipped', 'working');

-- --------------------------------------------------------

--
-- Table structure for table `platform_coupons`
--

CREATE TABLE `platform_coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_pct` decimal(5,2) NOT NULL,
  `max_uses` int(11) DEFAULT 100,
  `uses_count` int(11) DEFAULT 0,
  `valid_until` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_qty` int(11) DEFAULT 0,
  `primary_image_path` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `seller_id`, `category_id`, `name`, `description`, `price`, `stock_qty`, `primary_image_path`, `is_available`, `created_at`) VALUES
(1, 1, 6, 'KOLOM', 'WRITING INSTUMENT', 100.00, 99, 'public/uploads/products/product_6a076ffa28a8f1.80492478.jpg', 1, '2026-05-16 01:11:54'),
(2, 1, 3, 'KHATA', 'aaa', 111.00, 21, 'public/uploads/products/product_6a07703b25d7d3.38440774.png', 1, '2026-05-16 01:12:59'),
(3, 1, 2, 'random', 'aaa', 55.00, 32, 'public/uploads/products/product_6a07704a2e2211.88191982.png', 1, '2026-05-16 01:13:14');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `return_requests`
--

CREATE TABLE `return_requests` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `vendor_response_reason` text DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `review_text` text DEFAULT NULL,
  `seller_reply` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sellers`
--

CREATE TABLE `sellers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shop_name` varchar(150) NOT NULL,
  `shop_description` text DEFAULT NULL,
  `shop_logo_path` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `account_status` enum('pending','approved','rejected','suspended') NOT NULL DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT 10.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sellers`
--

INSERT INTO `sellers` (`id`, `user_id`, `shop_name`, `shop_description`, `shop_logo_path`, `address`, `is_approved`, `account_status`, `admin_note`, `commission_rate`, `created_at`) VALUES
(1, 8, 'SELLER\'s Store', 'Vendor storefront profile.', NULL, 'Not provided', 1, 'pending', NULL, 10.00, '2026-05-16 01:10:56');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('customer','vendor','delivery_manager','admin') NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `phone`, `role`, `profile_pic`, `is_active`, `created_at`) VALUES
(1, 'Sadman Sakib', 'admin@store.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'admin', NULL, 1, '2026-05-14 15:12:12'),
(2, 'SAKIB SADMAN', 'sakib09837@gmail.com', '$2y$10$WifIFTP8qjrCImdYJNE0iOyKMf5s8jnyXxcJvak7VHY0OiKbWjpoW', '+8801788960006', 'customer', NULL, 1, '2026-05-14 21:52:20'),
(3, 'SAKIB SADMAN', 'sakib@gmail.com', '$2y$10$rBAfxO8qMrtstaCI2NxCMeSkhistd47fY13qlwEzLGFJpf.LdQ7ey', '+8801788960006', 'customer', NULL, 1, '2026-05-14 21:52:41'),
(4, 'SAKIB SADMAN', 'sakib1112@gmail.com', '$2y$10$xFSpmW2U066X4invVRiXXuJKwefbr5IOfjDlV2.boQQbhbAtayQeG', '+8801788960006', 'customer', NULL, 1, '2026-05-14 23:52:24'),
(5, 'SAKIB SADMAN', 's7@gmail.com', '$2y$10$h7bzuVfvkXEThbjcxOYVF.kKotPQwgyOGqIHvcg6.OakymJNyKnF6', '+8801788960006', 'vendor', NULL, 1, '2026-05-15 00:06:00'),
(6, 'HAMJA@GMAIL.COM', 'HAMJA@GMAIL.COM', '$2y$10$YhvHyuT5k5dYwDaEUXNTLO9fzOcC2LvOCIONX5QAFoGoSiaEVg70u', '123', 'customer', NULL, 1, '2026-05-15 00:53:05'),
(7, 'SAKIB SADMAN', 's7222@gmail.com', '$2y$10$i7uv512zgpvPY8zVL8kXme/CMACYaPM8u.FkpiO7416JQAHn2Qq.C', '+8801788960006', 'customer', NULL, 1, '2026-05-15 02:30:56'),
(8, 'SELLER', 'seller@store.com', '$2y$10$BXixd2MHi6OiBvPqoATRe./2QpNMPrAi6MzbJpMS7UUw7Qx.ykvfm', '123', 'vendor', NULL, 1, '2026-05-15 03:42:06'),
(9, 'CUSTOMER', 'customer@store.com', '$2y$10$JVB6dnbUEVs5qv6JR3bw8uBvZZrd86.E9YY8mApuDf3YbK8yeMqki', '12345', 'customer', NULL, 1, '2026-05-15 04:00:10'),
(10, 'FRY', 'fry@store.com', '$2y$10$9VZb3ZY6ZFP9M5k5p7JGQuVyFZhZv6YJOdpQfR/JrtHBmIgtM2jI6', '016017012', 'vendor', 'public/uploads/profiles/profile_6a065007632267.83091479.png', 1, '2026-05-15 04:43:19'),
(11, 'Digu Vai', 'manager@store.com', '$2y$10$XEnqKTWLpCpFDcasPLFdHekB1CffdidxScnAlIq/nQWIHFhpba412', '123', 'delivery_manager', 'public/uploads/profiles/profile_6a0777298dee20.29897176.jpg', 1, '2026-05-15 22:27:50');

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `added_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_category_parent` (`parent_id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `fk_coupon_seller` (`seller_id`);

--
-- Indexes for table `delivery_agents`
--
ALTER TABLE `delivery_agents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `delivery_assignments`
--
ALTER TABLE `delivery_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_delivery_assignment_order` (`order_id`),
  ADD KEY `fk_delivery_assignment_agent` (`agent_id`);

--
-- Indexes for table `delivery_zones`
--
ALTER TABLE `delivery_zones`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `disputes`
--
ALTER TABLE `disputes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dispute_customer` (`customer_id`),
  ADD KEY `fk_dispute_seller` (`seller_id`),
  ADD KEY `fk_dispute_order` (`order_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_coupon` (`coupon_id`),
  ADD KEY `idx_orders_customer` (`customer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_item_order` (`order_id`),
  ADD KEY `fk_order_item_product` (`product_id`),
  ADD KEY `fk_order_item_seller` (`seller_id`);

--
-- Indexes for table `platform_coupons`
--
ALTER TABLE `platform_coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_products_name` (`name`),
  ADD KEY `idx_products_category` (`category_id`),
  ADD KEY `idx_products_seller` (`seller_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_product_image_product` (`product_id`);

--
-- Indexes for table `return_requests`
--
ALTER TABLE `return_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_return_order` (`order_id`),
  ADD KEY `fk_return_order_item` (`order_item_id`),
  ADD KEY `fk_return_customer` (`customer_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_review_product` (`product_id`),
  ADD KEY `fk_review_order` (`order_id`),
  ADD KEY `fk_review_customer` (`customer_id`);

--
-- Indexes for table `sellers`
--
ALTER TABLE `sellers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_id` (`customer_id`,`product_id`),
  ADD KEY `fk_wishlist_product` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_agents`
--
ALTER TABLE `delivery_agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `delivery_assignments`
--
ALTER TABLE `delivery_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_zones`
--
ALTER TABLE `delivery_zones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `disputes`
--
ALTER TABLE `disputes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `platform_coupons`
--
ALTER TABLE `platform_coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `return_requests`
--
ALTER TABLE `return_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sellers`
--
ALTER TABLE `sellers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `coupons`
--
ALTER TABLE `coupons`
  ADD CONSTRAINT `fk_coupon_seller` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `delivery_agents`
--
ALTER TABLE `delivery_agents`
  ADD CONSTRAINT `fk_delivery_agent_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `delivery_assignments`
--
ALTER TABLE `delivery_assignments`
  ADD CONSTRAINT `fk_delivery_assignment_agent` FOREIGN KEY (`agent_id`) REFERENCES `delivery_agents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_delivery_assignment_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `disputes`
--
ALTER TABLE `disputes`
  ADD CONSTRAINT `fk_dispute_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dispute_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dispute_seller` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_item_seller` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_product_seller` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_product_image_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `return_requests`
--
ALTER TABLE `return_requests`
  ADD CONSTRAINT `fk_return_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_return_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_return_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_review_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sellers`
--
ALTER TABLE `sellers`
  ADD CONSTRAINT `fk_seller_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `fk_wishlist_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
