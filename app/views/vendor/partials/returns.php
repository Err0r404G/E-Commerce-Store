<section class="vendor-returns-page">
    <div class="page-header">
        <h1>Return Requests</h1>
        <p>Approve or reject return requests for your products with a clear reason.</p>
    </div>

    <section class="vendor-profile-panel vendor-returns-panel">
        <div class="vendor-panel-heading">
            <div>
                <h2>Product Returns</h2>
                <p class="vendor-panel-subtitle">Pending requests need a decision reason before they can be updated.</p>
            </div>

            <div class="category-search-box vendor-return-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="vendorReturnSearch" placeholder="Search returns...">
            </div>
        </div>

        <div class="vendor-return-list">
            <?php if (empty($returnRequests)): ?>
                <p class="empty-cell">No return requests found for your products.</p>
            <?php endif; ?>

            <?php foreach ($returnRequests as $request): ?>
                <?php
                $status = $request['status'] ?? 'pending';
                $amount = (float) $request['unit_price'] * (int) $request['quantity'];
                $search = strtolower(
                    '#' . $request['order_id'] . ' return ' . $request['return_request_id'] . ' ' .
                    ($request['product_name'] ?? '') . ' ' .
                    ($request['customer_name'] ?? '') . ' ' .
                    ($request['customer_email'] ?? '') . ' ' .
                    ($request['customer_reason'] ?? '') . ' ' .
                    ($request['vendor_response_reason'] ?? '')
                );
                ?>
                <article class="vendor-return-card" data-vendor-return-card data-search="<?= htmlspecialchars($search) ?>">
                    <div class="vendor-return-main">
                        <div>
                            <span class="status-badge return-status <?= htmlspecialchars($status) ?>">
                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $status))) ?>
                            </span>
                            <h3><?= htmlspecialchars($request['product_name'] ?? 'Unknown product') ?></h3>
                            <p>
                                Order #<?= (int) $request['order_id'] ?> · Item #<?= (int) $request['order_item_id'] ?> ·
                                Qty <?= (int) $request['quantity'] ?> · $<?= number_format($amount, 2) ?>
                            </p>
                        </div>

                        <div class="vendor-return-customer">
                            <strong><?= htmlspecialchars($request['customer_name'] ?? 'Customer') ?></strong>
                            <span><?= htmlspecialchars($request['customer_email'] ?? '') ?></span>
                            <small><?= htmlspecialchars(date('M d, Y', strtotime($request['created_at']))) ?></small>
                        </div>
                    </div>

                    <div class="vendor-return-reasons">
                        <div>
                            <strong>Customer reason</strong>
                            <p><?= htmlspecialchars($request['customer_reason'] ?: 'No reason provided.') ?></p>
                        </div>

                        <?php if (!empty($request['vendor_response_reason'])): ?>
                            <div>
                                <strong>Vendor decision reason</strong>
                                <p><?= htmlspecialchars($request['vendor_response_reason']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($status === 'pending'): ?>
                        <form class="vendor-return-form" data-return-action-form>
                            <input type="hidden" name="return_request_id" value="<?= (int) $request['return_request_id'] ?>">
                            <textarea name="vendor_response_reason" placeholder="Write the approval or rejection reason..." required></textarea>
                            <div class="vendor-return-actions">
                                <button type="submit" name="return_action" value="approve" class="vendor-return-approve">
                                    Approve
                                </button>
                                <button type="submit" name="return_action" value="reject" class="vendor-return-reject">
                                    Reject
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <span class="vendor-muted-action">Decision recorded</span>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</section>
