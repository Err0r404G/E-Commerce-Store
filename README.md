# Project 04 - E-Commerce Store (NexusCommerce)

## Overview

A multi-role e-commerce marketplace built with **PHP**, **MySQL**, **mysqli**, and an MVC-style folder structure. The platform connects **customers**, **vendors**, **delivery managers**, and a **platform admin** in one store system. Customers browse products, manage carts, apply coupons, place orders, track order status, manage profiles, wishlists, returns, reviews, and disputes. Vendors manage inventory, coupons, orders, returns, reviews, analytics, earnings, and store settings. Delivery managers manage delivery agents, delivery zones, and their own profile. Platform admin oversees vendors, categories, products, orders, accounts, disputes, coupons, reports, and platform settings.

## Roles

| Role | Responsibility |
|------|----------------|
| Customer | Browse products, cart, checkout, coupons, orders, wishlist, profile, reviews, returns, disputes |
| Vendor | Product inventory, coupons, order processing, return requests, reviews, analytics, earnings, shop profile |
| Delivery Manager | Delivery agents, delivery zones, delivery settings |
| Platform Admin | Vendor approval, category management, product oversight, orders, accounts, disputes, coupons, reports, settings |

## Technical Requirements

- **MVC-style structure** with controllers, models, and views separated by responsibility
- **PHP** server-side application
- **MySQL** database using XAMPP/MariaDB
- **mysqli** database connection through `config/db.php`
- **PHP sessions** with role-based dashboard routing
- **Role-based access control** through `requireRole()` on protected routes
- **AJAX/fetch/XMLHttpRequest features** for admin, vendor, delivery manager, and customer workflows
- Runnable locally on **XAMPP Apache** from `htdocs/E-Commerce-Store`
- Main database import file: `database/ecommerce_store.sql`

## Separation of Concerns

- `index.php` works as the main front controller for auth, admin, vendor, delivery manager, customer, and home routes.
- Controllers handle request routing and business actions.
- Models handle data access and database operations.
- Views render the UI.
- Public CSS and JavaScript live under `public/`.
- AJAX endpoints and dashboard partial requests return JSON or partial HTML where needed.

## Folder Structure

```text
E-Commerce-Store/
├── api/                         # Customer AJAX API endpoints
│   ├── customer_coupon_check.php
│   └── customer_order_status.php
├── app/
│   ├── controllers/             # Route/business controllers
│   │   ├── admin/
│   │   ├── auth/
│   │   ├── deliveryManager/
│   │   ├── vendor/
│   │   ├── CustomerAreaController.php
│   │   └── SellerController.php
│   ├── models/                  # Data access models
│   │   ├── admin/
│   │   ├── auth/
│   │   ├── deliveryManager/
│   │   └── CustomerAreaModel.php
│   └── views/                   # UI templates and partials
│       ├── admin/
│       ├── auth/
│       ├── customer/
│       ├── deliveryManager/
│       ├── layouts/
│       └── vendor/
├── config/
│   └── db.php                   # Database connection
├── database/
│   ├── ecommerce_store.sql      # Main schema and seed data
│   └── customer_demo_seed.sql
├── public/
│   ├── css/                     # Stylesheets
│   ├── js/                      # AJAX and page scripts
│   └── uploads/                 # Uploaded profile/product images
└── index.php                    # Main front controller
```

## Shared Database Tables

`categories`, `coupons`, `customer_addresses`, `delivery_agents`, `delivery_assignments`, `delivery_zones`, `disputes`, `orders`, `platform_coupons`, `order_items`, `products`, `product_images`, `return_requests`, `reviews`, `sellers`, `users`, `wishlists`

See `database/ecommerce_store.sql`.

## AJAX Features

| Role | AJAX feature |
|------|--------------|
| Customer | Coupon validation through `api/customer_coupon_check.php` and live order status polling through `api/customer_order_status.php` |
| Vendor | Dashboard partial loading, product actions, coupon actions, order updates, return handling, review actions, profile update |
| Delivery Manager | Dashboard partial loading, profile update, delivery agent create/update/toggle, delivery zone create/update/delete |
| Platform Admin | Dashboard partial loading, vendor approval actions, category actions, product activation, account actions, delivery manager creation, disputes, coupons, reports/settings actions |

## Main Routes

| Route | Purpose |
|-------|---------|
| `/E-Commerce-Store/index.php` | Home page |
| `/E-Commerce-Store/index.php?page=signup` | User registration |
| `/E-Commerce-Store/index.php?page=login` | User login |
| `/E-Commerce-Store/index.php?page=adminDashboard` | Admin dashboard |
| `/E-Commerce-Store/index.php?page=vendorDashboard` | Vendor dashboard |
| `/E-Commerce-Store/index.php?page=deliveryDashboard` | Delivery manager dashboard |
| `/E-Commerce-Store/index.php?page=customerDashboard` | Customer dashboard |
| `/E-Commerce-Store/index.php?page=customerMarketplace` | Product marketplace |
| `/E-Commerce-Store/index.php?page=customerCart` | Customer cart |
| `/E-Commerce-Store/index.php?page=customerCheckout` | Checkout |
| `/E-Commerce-Store/index.php?page=customerOrders` | Customer orders |

## Local Setup

1. Copy or keep the project folder at:

```text
C:\xampp\htdocs\E-Commerce-Store
```

2. Start **Apache** and **MySQL** from the XAMPP Control Panel.

3. Create a MySQL database named:

```sql
CREATE DATABASE ecommerce_store;
```

4. Import the main SQL file:

```text
database/ecommerce_store.sql
```

5. Confirm database settings in `config/db.php`:

```php
define("DB_HOST", "localhost");
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_NAME", "ecommerce_store");
```

6. Open the project in the browser:

```text
http://localhost/E-Commerce-Store/index.php
```

## Demo Accounts

The SQL file includes seeded users such as:

| Role | Email |
|------|-------|
| Admin | `admin@store.com` |
| Vendor | `seller@store.com` |
| Customer | `customer@store.com` |
| Delivery Manager | `manager@store.com` |

Password hashes are stored in the database. Use the password assigned during setup or reset demo passwords locally if needed.

## Member Assignment

| Folder / Area | Owner |
|---------------|-------|
| `app/controllers/CustomerAreaController.php`, `app/models/CustomerAreaModel.php`, `app/views/customer/`, `api/customer_*`, `public/js/customer.js` | Customer role |
| `app/controllers/vendor/`, `app/views/vendor/`, `public/js/vendorAjax.js` | Vendor role |
| `app/controllers/deliveryManager/`, `app/views/deliveryManager/`, `public/js/deliveryManagerAjax.js` | Delivery manager role |
| `app/controllers/admin/`, `app/models/admin/`, `app/views/admin/`, `public/js/adminAjax.js` | Platform admin role |
| `config/`, `database/`, shared layouts, authentication, shared models | Shared project foundation |

