<section class="vendor-reviews-page">
    <div class="page-header">
        <h1>Reviews</h1>
        <p>View customer product reviews and reply from your shop account.</p>
    </div>

    <section class="vendor-profile-panel vendor-reviews-panel">
        <div class="vendor-panel-heading">
            <h2>Product Reviews</h2>
            <div class="category-search-box vendor-review-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="vendorReviewSearch" placeholder="Search reviews...">
            </div>
        </div>

        <div class="vendor-review-list">
            <?php if (empty($reviews)): ?>
                <p class="empty-cell">No product reviews found.</p>
            <?php endif; ?>

            <?php foreach ($reviews as $review): ?>
                <article
                    class="vendor-review-item"
                    data-vendor-review-row
                    data-search="<?= htmlspecialchars(strtolower(($review['product_name'] ?? '') . ' ' . ($review['customer_name'] ?? '') . ' ' . ($review['review_text'] ?? '') . ' ' . ($review['seller_reply'] ?? ''))) ?>"
                >
                    <div class="vendor-review-meta">
                        <div>
                            <h3><?= htmlspecialchars($review['product_name'] ?? 'Product') ?></h3>
                            <p>
                                <?= htmlspecialchars($review['customer_name'] ?? 'Customer') ?>
                                <span>Order #<?= (int) $review['order_id'] ?></span>
                            </p>
                        </div>

                        <div class="vendor-review-rating" aria-label="<?= (int) $review['rating'] ?> star rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="<?= $i <= (int) $review['rating'] ? 'fa-solid' : 'fa-regular' ?> fa-star"></i>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <p class="vendor-review-text"><?= htmlspecialchars($review['review_text'] ?: 'No review text provided.') ?></p>

                    <?php if (!empty($review['seller_reply'])): ?>
                        <div class="vendor-review-reply">
                            <strong>Your reply</strong>
                            <p><?= htmlspecialchars($review['seller_reply']) ?></p>
                        </div>
                    <?php endif; ?>

                    <form class="vendor-review-reply-form" data-review-reply-form>
                        <input type="hidden" name="review_id" value="<?= (int) $review['id'] ?>">
                        <textarea name="seller_reply" placeholder="Write a reply..." required><?= htmlspecialchars($review['seller_reply'] ?? '') ?></textarea>
                        <button type="submit">
                            <i class="fa-solid fa-reply"></i>
                            Save Reply
                        </button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</section>
