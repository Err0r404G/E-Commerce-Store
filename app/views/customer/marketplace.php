<main class="customer-shell marketplace-layout">
    <aside class="filter-panel">
        <form method="get">
            <input type="hidden" name="page" value="customerMarketplace">
            <label>Search</label>
            <input name="keyword" value="<?= e($filters['keyword']) ?>" placeholder="Search products, sellers...">
            <label>Category</label>
            <select name="category_id">
                <option value="">All categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= (string) $filters['category_id'] === (string) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Price Range</label>
            <div class="split-inputs">
                <input type="number" min="0" step="0.01" name="min_price" value="<?= e($filters['min_price']) ?>" placeholder="Min">
                <input type="number" min="0" step="0.01" name="max_price" value="<?= e($filters['max_price']) ?>" placeholder="Max">
            </div>
            <label>Minimum Rating</label>
            <select name="min_rating">
                <option value="">Any rating</option>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <option value="<?= $i ?>" <?= (string) $filters['min_rating'] === (string) $i ? 'selected' : '' ?>><?= $i ?> stars and up</option>
                <?php endfor; ?>
            </select>
            <label class="check-row"><input type="checkbox" name="in_stock" value="1" <?= $filters['in_stock'] ? 'checked' : '' ?>> In stock only</label>
            <button class="primary-button full" type="submit">Apply Filters</button>
        </form>
    </aside>

    <section class="product-results">
        <header class="page-heading">
            <div>
                <p class="eyebrow">Marketplace</p>
                <h1>Discover products across sellers</h1>
                <p><?= count($products) ?> available result<?= count($products) === 1 ? '' : 's' ?></p>
            </div>
            <form method="get" class="sort-form">
                <input type="hidden" name="page" value="customerMarketplace">
                <input type="hidden" name="keyword" value="<?= e($filters['keyword']) ?>">
                <input type="hidden" name="category_id" value="<?= e($filters['category_id']) ?>">
                <select name="sort" onchange="this.form.submit()">
                    <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>Newest</option>
                    <option value="price_low" <?= $filters['sort'] === 'price_low' ? 'selected' : '' ?>>Price: low to high</option>
                    <option value="price_high" <?= $filters['sort'] === 'price_high' ? 'selected' : '' ?>>Price: high to low</option>
                    <option value="rating" <?= $filters['sort'] === 'rating' ? 'selected' : '' ?>>Top rated</option>
                </select>
            </form>
        </header>

        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <?php $isWishlisted = in_array((int) $product['id'], $wishlistProductIds ?? [], true); ?>
                <article class="product-card">
                    <a class="product-image" href="<?= customerUrl('product', ['id' => (int) $product['id']]) ?>">
                        <img src="<?= e(productImage($product['primary_image_path'])) ?>" alt="<?= e($product['name']) ?>">
                    </a>
                    <div class="product-body">
                        <p class="seller-name"><?= e($product['shop_name']) ?></p>
                        <h2><?= e($product['name']) ?></h2>
                        <div class="rating-row">
                            <span class="stars"><?= str_repeat('★', (int) round((float) $product['avg_rating'])) ?></span>
                            <span><?= number_format((float) $product['avg_rating'], 1) ?> (<?= (int) $product['review_count'] ?>)</span>
                        </div>
                        <div class="product-footer">
                            <strong><?= money((float) $product['price']) ?></strong>
                            <span class="<?= (int) $product['stock_qty'] > 0 ? 'stock-ok' : 'stock-out' ?>"><?= (int) $product['stock_qty'] > 0 ? 'In Stock' : 'Out' ?></span>
                        </div>
                        <div class="button-row">
                            <form method="post">
                                <input type="hidden" name="customer_action" value="add_to_cart">
                                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                <input type="hidden" name="return_to" value="marketplace">
                                <button class="primary-button" type="submit">Add to Cart</button>
                            </form>
                            <form method="post">
                                <input type="hidden" name="customer_action" value="toggle_wishlist">
                                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                <input type="hidden" name="return_to" value="marketplace">
                                <button class="icon-button wishlist-button <?= $isWishlisted ? 'is-active' : '' ?>" type="submit" aria-label="<?= $isWishlisted ? 'Remove from wishlist' : 'Add to wishlist' ?>" title="<?= $isWishlisted ? 'Saved to wishlist' : 'Add to wishlist' ?>">
                                    <span class="material-symbols-outlined">favorite</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
