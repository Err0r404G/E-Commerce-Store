<section class="dispute-page">

    <div class="dispute-header">
        <h1>Dispute Management</h1>
        <p>Open: <?= (int) (($disputeCounts['urgent'] ?? 0) + ($disputeCounts['progress'] ?? 0)) ?> · Closed: <?= (int) ($disputeCounts['resolved'] ?? 0) ?></p>
    </div>

    <div class="dispute-tools">
        <div class="category-search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="disputeSearch" placeholder="Search disputes...">
        </div>
    </div>

    <div class="dispute-content">
        <div class="dispute-table-box">
            <table class="dispute-table">
                <thead>
                    <tr>
                        <th>Case</th>
                        <th>Customer</th>
                        <th>Seller</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($disputes)): ?>
                        <tr>
                            <td colspan="4" class="empty-cell">No disputes found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($disputes as $index => $dispute): ?>
                        <?php
                        $isResolved = $dispute['status'] === 'resolved';
                        $statusClass = $isResolved ? 'resolved' : (!empty($dispute['admin_note']) ? 'progress' : 'urgent');
                        $statusLabel = $isResolved ? 'Resolved' : (!empty($dispute['admin_note']) ? 'In Progress' : 'Needs Review');
                        $sellerName = $dispute['shop_name'] ?: ($dispute['seller_name'] ?: 'Unknown seller');
                        $createdAt = $dispute['created_at'] ? date('M d, Y', strtotime($dispute['created_at'])) : 'N/A';
                        $orderTotal = isset($dispute['total_amount']) ? number_format((float) $dispute['total_amount'], 2) : '0.00';
                        $searchText = strtolower(
                            'case ' . $dispute['id'] . ' order ' . $dispute['order_id'] . ' ' .
                            $dispute['customer_name'] . ' ' . $dispute['customer_email'] . ' ' .
                            $sellerName . ' ' . $dispute['description'] . ' ' . $statusLabel
                        );
                        ?>
                        <tr class="<?= $index === 0 ? 'active' : '' ?>"
                            data-dispute-row
                            data-search="<?= htmlspecialchars($searchText) ?>"
                            data-dispute-id="<?= (int) $dispute['id'] ?>"
                            data-order-id="<?= (int) $dispute['order_id'] ?>"
                            data-customer="<?= htmlspecialchars($dispute['customer_name'] ?: 'Unknown customer') ?>"
                            data-seller="<?= htmlspecialchars($sellerName) ?>"
                            data-description="<?= htmlspecialchars($dispute['description'] ?: 'No dispute description provided.') ?>"
                            data-status="<?= htmlspecialchars($statusLabel) ?>"
                            data-status-class="<?= htmlspecialchars($statusClass) ?>"
                            data-created="<?= htmlspecialchars($createdAt) ?>"
                            data-order-total="<?= htmlspecialchars($orderTotal) ?>"
                            data-admin-note="<?= htmlspecialchars($dispute['admin_note'] ?: '') ?>">
                            <td>
                                Case #<?= (int) $dispute['id'] ?>
                                <small>Order #<?= (int) $dispute['order_id'] ?> · BDT <?= htmlspecialchars($orderTotal) ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars($dispute['customer_name'] ?: 'Unknown') ?>
                                <small><?= htmlspecialchars($dispute['customer_email'] ?: 'No email') ?></small>
                            </td>
                            <td><?= htmlspecialchars($sellerName) ?></td>
                            <td><span class="status-badge <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($statusLabel) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <aside class="dispute-detail" id="disputeDetail">
            <div class="detail-title">
                <h2>Dispute Details</h2>
            </div>

            <div class="selected-case">
                <p id="selectedDisputeStatus">Select a case</p>
                <h4 id="selectedDisputeTitle">No dispute selected</h4>
                <span id="selectedDisputeDescription">Choose a row to view dispute details.</span>
            </div>

            <dl class="case-meta">
                <div>
                    <dt>Customer</dt>
                    <dd id="selectedDisputeCustomer">N/A</dd>
                </div>
                <div>
                    <dt>Seller</dt>
                    <dd id="selectedDisputeSeller">N/A</dd>
                </div>
                <div>
                    <dt>Opened</dt>
                    <dd id="selectedDisputeDate">N/A</dd>
                </div>
                <div>
                    <dt>Order Total</dt>
                    <dd id="selectedDisputeTotal">BDT 0.00</dd>
                </div>
            </dl>

            <div class="case-note">
                <h4>Resolution Note Sent To Customer</h4>
                <p id="selectedDisputeNote">No resolution note yet.</p>
            </div>

            <form class="detail-actions" id="disputeActionForm">
                <input type="hidden" name="dispute_id" id="selectedDisputeId">
                <textarea class="dispute-note-input" name="admin_note" id="selectedDisputeNoteInput" minlength="5" placeholder="Write the resolution note the customer will see..."></textarea>
                <p class="form-feedback" id="disputeActionFeedback" aria-live="polite"></p>
                <button class="refund-btn" type="submit" data-dispute-submit-action="resolve">Close Dispute</button>
                <button class="info-btn" type="submit" data-dispute-submit-action="reopen">Reopen Case</button>
            </form>
        </aside>
    </div>

</section>
