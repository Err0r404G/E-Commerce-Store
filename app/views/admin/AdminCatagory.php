<section class="category-page">

    <div class="category-header">
        <h1>Category Management</h1>

        <button class="add-category-btn" id="showCategoryForm" type="button">
            <i class="fa-solid fa-plus"></i>
            Add Category
        </button>
    </div>

    <form class="category-form" id="categoryForm" hidden>
        <input type="hidden" name="category_action" id="categoryAction" value="add">
        <input type="hidden" name="category_id" id="categoryId" value="">

        <div class="category-form-grid">
            <label>
                Category Name
                <input type="text" name="name" id="categoryName" placeholder="Enter category name" required>
            </label>

            <label>
                Parent Category
                <select name="parent_id" id="categoryParent">
                    <option value="">Primary category</option>
                    <?php foreach ($categories as $category): ?>
                        <?php if ($category['parent_id'] === null): ?>
                            <option value="<?= (int) $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Description
                <input type="text" name="description" id="categoryDescription" placeholder="Short description">
            </label>
        </div>

        <div class="category-form-actions">
            <button class="approve-btn" type="submit">Save Category</button>
            <button class="reject-btn" id="cancelCategoryForm" type="button">Cancel</button>
        </div>
    </form>

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

            <div class="summary-actions">
                <button type="button" title="Collapse"><i class="fa-solid fa-compress"></i></button>
                <button type="button" title="Sort"><i class="fa-solid fa-up-down"></i></button>
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
                    <tr class="main-category" data-category-row data-search="<?= htmlspecialchars(strtolower($categoryName . ' ' . $categoryDescription)) ?>">
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
                        <tr class="sub-category" data-category-row data-search="<?= htmlspecialchars(strtolower($childName . ' ' . $childDescription . ' ' . $categoryName)) ?>">
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
            <p>Showing <?= count($categories) ?> categor<?= count($categories) === 1 ? 'y' : 'ies' ?></p>
        </div>

    </div>

</section>
