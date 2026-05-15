<section class="category-page">

    <div class="category-header">
        <h1>Category Management</h1>

        <button class="add-category-btn" id="showCategoryForm" type="button">
            <i class="fa-solid fa-plus"></i>
            Add Category
        </button>
    </div>

    <div class="category-stats">

        <div class="category-stat-card">
            <div class="category-stat-icon blue">
                <i class="fa-solid fa-border-all"></i>
            </div>
            <div>
                <p>Total Categories</p>
                <h2><?= number_format((int) ($categoryStats['total_categories'] ?? 0)) ?></h2>
            </div>
        </div>

        <div class="category-stat-card">
            <div class="category-stat-icon purple">
                <i class="fa-solid fa-box-archive"></i>
            </div>
            <div>
                <p>Total Products</p>
                <h2><?= number_format((int) ($categoryStats['total_products'] ?? 0)) ?></h2>
            </div>
        </div>

        <div class="category-stat-card">
            <div class="category-stat-icon yellow">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <p>Active Sellers</p>
                <h2><?= number_format((int) ($categoryStats['active_sellers'] ?? 0)) ?></h2>
            </div>
        </div>

    </div>

    <div class="category-summary-card">

        <div class="category-summary-header">
            <h2>Summary</h2>

            <div class="category-tools">
                <div class="category-search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="categorySearch" placeholder="Search category...">
                </div>

                <select id="categoryProductFilter" class="category-product-filter">
                    <option value="">Select category products</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>">
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <table class="category-table">
            <thead>
                <tr>
                    <th>Category Name</th>
                    <th>Total Products</th>
                    <th>Sold Product</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($categoryTree)): ?>
                    <tr>
                        <td colspan="4" class="empty-cell">No categories found.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($categoryTree as $category): ?>
                    <?php
                    $categoryId = (int) $category['id'];
                    $categoryName = (string) $category['name'];
                    $categoryDescription = (string) ($category['description'] ?? '');
                    $children = $category['children'] ?? [];
                    ?>
                    <tr class="main-category" data-category-row data-category-id="<?= $categoryId ?>" data-search="<?= htmlspecialchars(strtolower($categoryName . ' ' . $categoryDescription)) ?>">
                        <td>
                            <i class="fa-solid <?= empty($children) ? 'fa-chevron-right' : 'fa-chevron-down' ?>"></i>
                            <strong><?= htmlspecialchars($categoryName) ?></strong>
                            <span class="primary-badge">Primary</span>
                        </td>
                        <td><?= number_format((int) $category['total_products']) ?></td>
                        <td><?= number_format((int) $category['sold_products']) ?></td>
                        <td>
                            <button class="edit-category" type="button"
                                data-category-edit
                                data-category-id="<?= $categoryId ?>"
                                data-category-name="<?= htmlspecialchars($categoryName) ?>"
                                data-category-description="<?= htmlspecialchars($categoryDescription) ?>"
                                data-parent-id="">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button class="delete-category" type="button" data-category-delete data-category-id="<?= $categoryId ?>">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </td>
                    </tr>

                    <?php foreach ($children as $child): ?>
                        <?php
                        $childId = (int) $child['id'];
                        $childName = (string) $child['name'];
                        $childDescription = (string) ($child['description'] ?? '');
                        ?>
                        <tr class="sub-category" data-category-row data-category-id="<?= $childId ?>" data-parent-category-id="<?= $categoryId ?>" data-search="<?= htmlspecialchars(strtolower($childName . ' ' . $childDescription . ' ' . $categoryName)) ?>">
                            <td>
                                <span class="tree-line"></span>
                                <i class="fa-solid fa-arrow-turn-up fa-rotate-90"></i>
                                <?= htmlspecialchars($childName) ?>
                            </td>
                            <td><?= number_format((int) $child['total_products']) ?></td>
                            <td><?= number_format((int) $child['sold_products']) ?></td>
                            <td>
                                <button class="edit-category" type="button"
                                    data-category-edit
                                    data-category-id="<?= $childId ?>"
                                    data-category-name="<?= htmlspecialchars($childName) ?>"
                                    data-category-description="<?= htmlspecialchars($childDescription) ?>"
                                    data-parent-id="<?= $categoryId ?>">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button class="delete-category" type="button" data-category-delete data-category-id="<?= $childId ?>">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="table-footer">
            <p id="categoryPaginationInfo">Showing <?= min(count($categories), 5) ?> of <?= count($categories) ?> categor<?= count($categories) === 1 ? 'y' : 'ies' ?></p>

            <div class="category-pagination" id="categoryPagination" aria-label="Category pagination">
                <button type="button" data-category-page-prev aria-label="Previous page">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>

                <div id="categoryPageNumbers"></div>

                <button type="button" data-category-page-next aria-label="Next page">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>

    </div>

    <div class="category-products-card" id="categoryProductsCard" hidden>
        <div class="category-products-header">
            <h2>Available Products</h2>
            <p id="categoryProductsMeta">Select a category to view products.</p>
        </div>

        <div class="category-products-list" id="categoryProductsList">
            <?php foreach ($categoryProducts as $categoryId => $products): ?>
                <?php foreach ($products as $product): ?>
                    <?php $isAvailable = (int) $product['is_available'] === 1; ?>
                    <div class="category-product-item" data-product-category="<?= (int) $categoryId ?>" hidden>
                        <div>
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p><?= htmlspecialchars($product['shop_name'] ?: 'No seller assigned') ?></p>
                        </div>
                        <div class="category-product-details">
                            <span><?= number_format((float) $product['price'], 2) ?></span>
                            <span><?= (int) $product['stock_qty'] ?> in stock</span>
                            <strong class="<?= $isAvailable ? 'available' : 'unavailable' ?>">
                                <?= $isAvailable ? 'Available' : 'Unavailable' ?>
                            </strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>

        <p class="empty-cell" id="categoryProductsEmpty" hidden>No products found in this category.</p>
    </div>

    <div class="category-modal-backdrop" id="categoryModal" hidden>
        <div class="category-modal" role="dialog" aria-modal="true" aria-labelledby="categoryModalTitle">
            <div class="category-modal-heading">
                <h2 id="categoryModalTitle">Add New Category</h2>
                <p>Define a new product classification for your store's hierarchy.</p>
            </div>

            <form class="category-form category-modal-form" id="categoryForm">
                <input type="hidden" name="category_action" id="categoryAction" value="add">
                <input type="hidden" name="category_id" id="categoryId" value="">
                <input type="hidden" name="parent_id" id="categoryParent" value="">

                <div class="category-modal-grid">
                    <div class="category-form-panel category-info-panel">
                        <h3><i class="fa-solid fa-circle-info"></i> General Information</h3>

                        <label>
                            Category Name
                            <input type="text" name="name" id="categoryName" placeholder="e.g. Premium Electronics" required>
                        </label>

                        <label>
                            Category Description
                            <textarea name="description" id="categoryDescription" placeholder="Provide a detailed description for customers and SEO purposes..."></textarea>
                        </label>
                    </div>

                    <div class="category-form-panel category-assets-panel">
                        <h3><i class="fa-regular fa-image"></i> Category Assets</h3>

                        <label class="thumbnail-drop">
                            <input type="file" name="thumbnail" accept="image/png,image/jpeg,image/gif">
                            <span class="upload-icon"><i class="fa-regular fa-file-arrow-up"></i></span>
                            <strong>Upload thumbnail</strong>
                            <small>PNG, JPG up to 2MB (800x800px)</small>
                            <em>Select File</em>
                        </label>

                        <p class="icon-preview-label">Icon Preview</p>
                        <div class="icon-preview-row">
                            <button type="button"><i class="fa-solid fa-shapes"></i></button>
                            <button type="button"><i class="fa-solid fa-computer"></i></button>
                            <button type="button"><i class="fa-solid fa-couch"></i></button>
                            <button type="button" class="add-icon"><i class="fa-solid fa-plus"></i></button>
                        </div>
                    </div>
                </div>

                <div class="category-modal-actions">
                    <button class="modal-cancel-btn" id="cancelCategoryForm" type="button">Cancel</button>
                    <button class="modal-create-btn" type="submit">
                        <i class="fa-regular fa-circle-plus"></i>
                        Create Category
                    </button>
                </div>
            </form>
        </div>
    </div>

</section>
