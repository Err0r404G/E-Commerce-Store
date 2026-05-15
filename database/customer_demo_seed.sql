-- Optional demo data for the Customer role.
-- Password for demo users below is: password

INSERT INTO users (name, email, password_hash, phone, role, is_active)
VALUES
('Customer Demo', 'customer@store.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01700000001', 'customer', 1),
('Customer Demo Seller', 'customer-seller@store.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01700000002', 'vendor', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), phone = VALUES(phone), is_active = 1;

SET @demo_seller_user_id := (SELECT id FROM users WHERE email = 'customer-seller@store.com' LIMIT 1);

INSERT INTO sellers (user_id, shop_name, shop_description, address, is_approved, commission_rate)
VALUES (@demo_seller_user_id, 'Nexus Demo Goods', 'Demo seller products for customer role testing.', 'Dhaka, Bangladesh', 1, 10.00)
ON DUPLICATE KEY UPDATE shop_name = VALUES(shop_name), is_approved = 1;

SET @demo_seller_id := (SELECT id FROM sellers WHERE user_id = @demo_seller_user_id LIMIT 1);
SET @electronics_id := (SELECT id FROM categories WHERE name = 'Electronics' LIMIT 1);
SET @clothing_id := (SELECT id FROM categories WHERE name = 'Clothing' LIMIT 1);

INSERT INTO products (seller_id, category_id, name, description, price, stock_qty, primary_image_path, is_available)
VALUES
(@demo_seller_id, @electronics_id, 'Nexus Audio H1', 'Wireless noise-cancelling headphones with premium comfort and long battery life.', 149.00, 24, NULL, 1),
(@demo_seller_id, @electronics_id, 'Nexus Slate Mini', 'Compact productivity tablet for study, browsing, and entertainment.', 329.00, 12, NULL, 1),
(@demo_seller_id, @clothing_id, 'Velocity Runner V1', 'Lightweight everyday sneakers with breathable textile upper.', 89.00, 35, NULL, 1)
ON DUPLICATE KEY UPDATE stock_qty = VALUES(stock_qty), is_available = 1;

INSERT INTO coupons (seller_id, code, discount_pct, max_uses, uses_count, valid_until, is_active)
VALUES (@demo_seller_id, 'CUSTOMER10', 10.00, 100, 0, DATE_ADD(CURDATE(), INTERVAL 90 DAY), 1)
ON DUPLICATE KEY UPDATE discount_pct = 10.00, valid_until = DATE_ADD(CURDATE(), INTERVAL 90 DAY), is_active = 1;

SET @customer_id := (SELECT id FROM users WHERE email = 'customer@store.com' LIMIT 1);
SET @zone_id := (SELECT id FROM delivery_zones ORDER BY id LIMIT 1);

INSERT INTO customer_addresses (customer_id, label, recipient_name, phone, address_line, city, postal_code, delivery_zone_id, is_default)
SELECT @customer_id, 'Home', 'Customer Demo', '01700000001', 'House 12, Road 8, Dhanmondi', 'Dhaka', '1209', @zone_id, 1
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'customer_addresses')
ON DUPLICATE KEY UPDATE recipient_name = VALUES(recipient_name);
