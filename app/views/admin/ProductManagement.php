<?php
$products = $products ?? [];
$categories = $categories ?? [];
$sellers = $sellers ?? [];
$productStats = $productStats ?? ['total' => 0, 'active' => 0, 'removed' => 0];
?>

<section class="page-section product-management-page">

    <div class="page-header">
        <div>
            <h1>Manage Product</h1>
            <p>View products across the platform, filter listings, and remove policy-violating items.</p>
        </div>
    </div>

    <div class="stats-row account-stats-row">
        <div class="stat-card">
            <div class="stat-top">
                <span>Total Products</span>
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <h2><?= number_format((int) $productStats['total']) ?></h2>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span class="approved-stat-label">Active Listings</span>
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h2><?= number_format((int) $productStats['active']) ?></h2>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span>Removed Listings</span>
                <i class="fa-solid fa-ban"></i>
            </div>
            <h2><?= number_format((int) $productStats['removed']) ?></h2>
        </div>
    </div>

    <div class="approval-card">
        <div class="approval-header">
            <div>
                <h2>Platform Product Directory</h2>
                <p class="account-subtitle">Search by product, category, or seller.</p>
            </div>

            <div class="approval-tools product-tools">
                <div class="search-box-small">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="productSearch" placeholder="Search product...">
                </div>

                <select id="productCategoryFilter" class="admin-filter-select">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="productSellerFilter" class="admin-filter-select">
                    <option value="">All sellers</option>
                    <?php foreach ($sellers as $seller): ?>
                        <option value="<?= (int) $seller['id'] ?>">
                            <?= htmlspecialchars($seller['shop_name'] ?: ($seller['seller_name'] ?? 'Seller')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <p class="category-feedback" id="productFeedback" hidden></p>

        <table class="approval-table product-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Seller</th>
                    <th>Price / Stock</th>
                    <th>Sold</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" class="empty-cell">No products found.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($products as $product): ?>
                    <?php
                    $isAvailable = (int) ($product['is_available'] ?? 0) === 1;
                    $categoryName = $product['category_name'] ?: 'Uncategorized';
                    $sellerName = $product['shop_name'] ?: ($product['seller_name'] ?: 'No seller');
                    $searchText = strtolower(($product['name'] ?? '') . ' ' . $categoryName . ' ' . $sellerName . ' ' . ($product['description'] ?? ''));
                    ?>
                    <tr data-product-row
                        data-search="<?= htmlspecialchars($searchText) ?>"
                        data-category-id="<?= (int) ($product['category_id'] ?? 0) ?>"
                        data-seller-id="<?= (int) ($product['seller_id'] ?? 0) ?>">
                        <td>
                            <div class="business-info">
                                <div class="product-thumb">
                                    <?php if (!empty($product['primary_image_path'])): ?>
                                        <img src="/E-Commerce-Store/<?= htmlspecialchars($product['primary_image_path']) ?>" alt="">
                                    <?php else: ?>
                                        <i class="fa-solid fa-box"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h4><?= htmlspecialchars($product['name'] ?? 'Unnamed product') ?></h4>
                                    <p>ID #<?= (int) $product['id'] ?></p>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($categoryName) ?></td>
                        <td><?= htmlspecialchars($sellerName) ?></td>
                        <td>
                            <?= number_format((float) $product['price'], 2) ?>
                            <small><?= (int) $product['stock_qty'] ?> in stock</small>
                        </td>
                        <td><?= number_format((int) $product['sold_qty']) ?></td>
                        <td>
                            <span class="approval-status <?= $isAvailable ? 'approved' : 'suspended' ?>">
                                <?= $isAvailable ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($isAvailable): ?>
                                <button class="reject-btn suspend-btn" type="button"
                                    data-product-status-action="deactivate"
                                    data-product-id="<?= (int) $product['id'] ?>">
                                    Inactive
                                </button>
                            <?php else: ?>
                                <button class="approve-btn" type="button"
                                    data-product-status-action="activate"
                                    data-product-id="<?= (int) $product['id'] ?>">
                                    Active
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="table-footer">
            <p id="productCountText">Showing <?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?></p>
        </div>
    </div>

</section>
