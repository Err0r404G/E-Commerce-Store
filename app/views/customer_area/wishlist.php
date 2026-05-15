<main class="customer-shell">
    <section class="panel">
        <div class="section-title"><h1>Wishlist</h1><a href="/E-Commerce-Store/customer.php?page=marketplace">Find more</a></div>
        <div class="product-grid compact">
            <?php foreach ($items as $item): ?>
                <article class="product-card">
                    <a class="product-image" href="/E-Commerce-Store/customer.php?page=product&id=<?= (int) $item['id'] ?>">
                        <img src="<?= e(productImage($item['primary_image_path'])) ?>" alt="<?= e($item['name']) ?>">
                    </a>
                    <div class="product-body">
                        <p class="seller-name"><?= e($item['shop_name']) ?></p>
                        <h2><?= e($item['name']) ?></h2>
                        <div class="product-footer"><strong><?= money((float) $item['price']) ?></strong></div>
                        <form method="post" class="button-row">
                            <input type="hidden" name="customer_action" value="toggle_wishlist">
                            <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                            <button class="ghost-button dark" type="submit">Remove</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if (!$items): ?><p>No saved wishlist products yet.</p><?php endif; ?>
    </section>
</main>
