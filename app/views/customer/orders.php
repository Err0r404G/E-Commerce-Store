<main class="customer-shell">
    <section class="panel">
        <div class="section-title"><h1>Order History</h1></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Total</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <?php
                    $returnStatus = '';
                    if ((int) ($order['approved_return_count'] ?? 0) > 0) {
                        $returnStatus = 'Return Approved';
                    } elseif ((int) ($order['pending_return_count'] ?? 0) > 0) {
                        $returnStatus = 'Return Pending';
                    } elseif ((int) ($order['completed_return_count'] ?? 0) > 0) {
                        $returnStatus = 'Return Completed';
                    } elseif ((int) ($order['rejected_return_count'] ?? 0) > 0) {
                        $returnStatus = 'Return Rejected';
                    }
                    ?>
                    <tr>
                        <td>#<?= (int) $order['id'] ?></td>
                        <td><?= e(date('M d, Y', strtotime($order['created_at']))) ?></td>
                        <td>
                            <span class="status-pill"><?= e(ucwords(str_replace('_', ' ', $order['status']))) ?></span>
                            <?php if ($returnStatus !== ''): ?>
                                <span class="status-pill"><?= e($returnStatus) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= money((float) $order['total_amount']) ?></td>
                        <td><a href="<?= customerUrl('order', ['id' => (int) $order['id']]) ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
