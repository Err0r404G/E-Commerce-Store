<main class="customer-shell">
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
