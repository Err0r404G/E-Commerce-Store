<section class="vendor-inventory-page">
    <div class="page-header">
        <h1>Inventory</h1>
        <p>Add products to platform categories and manage only your shop inventory.</p>
    </div>

    <div class="vendor-inventory-grid">
        <form class="vendor-product-form vendor-profile-panel" id="vendorProductForm" enctype="multipart/form-data">
            <input type="hidden" name="product_action" value="save">
            <input type="hidden" name="product_id" id="vendorProductId">

            <h2>Product Details</h2>

            <div class="vendor-profile-grid">
                <label>
                    Product Name
                    <input type="text" name="name" id="vendorProductName" required>
                </label>

                <label>
                    Existing Category
                    <select name="category_id" id="vendorProductCategory" required>
                        <option value="">Select category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Price
                    <input type="number" name="price" id="vendorProductPrice" min="0.01" step="0.01" required>
                </label>

                <label>
                    Stock
                    <input type="number" name="stock_qty" id="vendorProductStock" min="0" step="1" required>
                </label>

                <label class="vendor-file-field">
                    Product Image
                    <input type="file" name="product_image" accept="image/jpeg,image/png,image/webp">
                </label>

                <label class="vendor-check-label">
                    <input type="checkbox" name="is_available" id="vendorProductAvailable" checked>
                    Available
                </label>
            </div>

            <label class="vendor-wide-label">
                Description
                <textarea name="description" id="vendorProductDescription" required></textarea>
            </label>

            <div class="vendor-profile-actions vendor-product-actions">
                <button type="button" class="vendor-secondary-btn" id="vendorProductReset">Clear</button>
                <button type="submit">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Product
                </button>
            </div>
        </form>

        <section class="vendor-profile-panel">
            <div class="vendor-panel-heading">
                <h2>Shop Products</h2>
                <div class="category-search-box vendor-product-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="vendorInventorySearch" placeholder="Search products...">
                </div>
            </div>

            <div class="table-wrapper">
                <table class="category-table vendor-product-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="6" class="empty-cell">No products found. Add your first product above.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($products as $product): ?>
                            <tr data-vendor-product-row data-search="<?= htmlspecialchars(strtolower($product['name'] . ' ' . ($product['category_name'] ?? ''))) ?>">
                                <td>
                                    <strong><?= htmlspecialchars($product['name']) ?></strong>
                                    <small><?= htmlspecialchars(strlen($product['description'] ?? '') > 64 ? substr($product['description'], 0, 64) . '...' : ($product['description'] ?? '')) ?></small>
                                </td>
                                <td><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></td>
                                <td>$<?= number_format((float) $product['price'], 2) ?></td>
                                <td><?= (int) $product['stock_qty'] ?></td>
                                <td>
                                    <span class="approval-status <?= (int) $product['is_available'] === 1 ? 'approved' : 'pending' ?>">
                                        <?= (int) $product['is_available'] === 1 ? 'Available' : 'Hidden' ?>
                                    </span>
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        class="edit-category"
                                        data-product-edit
                                        data-product-id="<?= (int) $product['id'] ?>"
                                        data-name="<?= htmlspecialchars($product['name']) ?>"
                                        data-description="<?= htmlspecialchars($product['description'] ?? '') ?>"
                                        data-category-id="<?= (int) $product['category_id'] ?>"
                                        data-price="<?= htmlspecialchars((string) $product['price']) ?>"
                                        data-stock="<?= (int) $product['stock_qty'] ?>"
                                        data-available="<?= (int) $product['is_available'] ?>"
                                    >
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                    <button type="button" class="delete-category" data-product-delete data-product-id="<?= (int) $product['id'] ?>">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>
