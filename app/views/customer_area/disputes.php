<main class="customer-shell">
    <section class="panel dispute-submit-panel">
        <div class="section-title">
            <div>
                <p class="eyebrow">Admin Support</p>
                <h1>Submit a Dispute</h1>
            </div>
        </div>
        <?php if ($disputeTargets): ?>
            <form method="post" class="form-grid">
                <input type="hidden" name="customer_action" value="submit_dispute">
                <select name="dispute_target" required>
                    <option value="">Choose order and seller</option>
                    <?php foreach ($disputeTargets as $target): ?>
                        <option value="<?= (int) $target['order_id'] ?>:<?= (int) $target['seller_id'] ?>">
                            Order #<?= (int) $target['order_id'] ?> - <?= e($target['shop_name']) ?> - <?= e(ucwords(str_replace('_', ' ', $target['status']))) ?> - <?= (int) $target['item_count'] ?> item<?= (int) $target['item_count'] === 1 ? '' : 's' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <textarea name="description" minlength="10" required placeholder="Describe the issue for admin review"></textarea>
                <button class="primary-button" type="submit">Submit Dispute</button>
            </form>
        <?php else: ?>
            <p>No orders are available for disputes yet.</p>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="section-title"><h1>Dispute Status</h1></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Order</th><th>Seller</th><th>Description</th><th>Status</th><th>Admin Note</th></tr></thead>
                <tbody>
                <?php foreach ($disputes as $dispute): ?>
                    <tr>
                        <td>#<?= (int) $dispute['order_id'] ?></td>
                        <td><?= e($dispute['shop_name']) ?></td>
                        <td><?= e($dispute['description']) ?></td>
                        <td><span class="status-pill"><?= e(ucwords($dispute['status'])) ?></span></td>
                        <td><?= e($dispute['admin_note']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (!$disputes): ?><p>No disputes submitted yet.</p><?php endif; ?>
    </section>
</main>
