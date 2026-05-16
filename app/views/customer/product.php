<main class="customer-shell">
    <nav class="breadcrumbs"><a href="<?= customerUrl('marketplace') ?>">Marketplace</a><span>/</span><span><?= e($product['name']) ?></span></nav>
    <section class="product-detail">
        <div>
            <div class="hero-product-image">
                <img src="<?= e(productImage($product['primary_image_path'])) ?>" alt="<?= e($product['name']) ?>">
            </div>
            <?php if ($images): ?>
                <div class="thumbnail-row">
                    <?php foreach ($images as $image): ?>
                        <img src="<?= e(productImage($image['image_path'])) ?>" alt="">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="product-info-panel">
            <p class="eyebrow"><?= e($product['shop_name']) ?></p>
            <h1><?= e($product['name']) ?></h1>
            <div class="rating-row"><span class="stars"><?= str_repeat('★', (int) round((float) $product['avg_rating'])) ?></span><span><?= number_format((float) $product['avg_rating'], 1) ?> from <?= (int) $product['review_count'] ?> reviews</span></div>
            <strong class="detail-price"><?= money((float) $product['price']) ?></strong>
            <p><?= nl2br(e($product['description'])) ?></p>
            <p class="<?= (int) $product['stock_qty'] > 0 ? 'stock-ok' : 'stock-out' ?>"><?= (int) $product['stock_qty'] ?> units available</p>
            <form method="post" class="purchase-form">
                <input type="hidden" name="customer_action" value="add_to_cart">
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <input type="hidden" name="return_to" value="product&id=<?= (int) $product['id'] ?>">
                <label>Quantity</label>
                <input type="number" name="quantity" min="1" max="<?= max(1, (int) $product['stock_qty']) ?>" value="1">
                <button class="primary-button" type="submit">Add to Cart</button>
            </form>
            <form method="post">
                <input type="hidden" name="customer_action" value="toggle_wishlist">
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <input type="hidden" name="return_to" value="product&id=<?= (int) $product['id'] ?>">
                <button class="ghost-button dark wishlist-text-button <?= !empty($isWishlisted) ? 'is-active' : '' ?>" type="submit">
                    <span class="material-symbols-outlined">favorite</span>
                    <?= !empty($isWishlisted) ? 'Saved to Wishlist' : 'Save to Wishlist' ?>
                </button>
            </form>
        </div>
    </section>

    <section class="panel reviews-panel">
        <div class="section-title"><h2>Customer Reviews</h2></div>
        <?php if ($reviews): ?>
            <div class="review-grid">
                <?php foreach ($reviews as $review): ?>
                    <article class="review-card">
                        <strong><?= e($review['customer_name']) ?></strong>
                        <span class="stars"><?= str_repeat('★', (int) $review['rating']) ?></span>
                        <p><?= nl2br(e($review['review_text'])) ?></p>
                        <?php if ($review['seller_reply']): ?><small>Seller reply: <?= e($review['seller_reply']) ?></small><?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No reviews yet. Purchased customers can review from order detail.</p>
        <?php endif; ?>
    </section>
</main>
