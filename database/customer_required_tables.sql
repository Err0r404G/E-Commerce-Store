-- Customer module helper tables.
-- Run this after importing database/ecommerce_store.sql.

CREATE TABLE IF NOT EXISTS `customer_addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `label` varchar(80) NOT NULL DEFAULT 'Home',
  `recipient_name` varchar(120) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address_line` text NOT NULL,
  `city` varchar(80) NOT NULL,
  `postal_code` varchar(30) DEFAULT NULL,
  `delivery_zone_id` int(11) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_customer_addresses_customer` (`customer_id`),
  KEY `fk_customer_address_zone` (`delivery_zone_id`),
  CONSTRAINT `fk_customer_address_user` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_customer_address_zone` FOREIGN KEY (`delivery_zone_id`) REFERENCES `delivery_zones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- The shared `orders` table stores the selected shipping address as text.
-- The checkout page still lets customers choose a delivery zone for fee and ETA calculation.
