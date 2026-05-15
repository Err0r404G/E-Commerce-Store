<section class="dispute-page">

    <div class="dispute-header">
        <h1>Dispute Management</h1>
        <p>Review customer disputes, inspect order context, and close resolved cases.</p>
    </div>

    <div class="dispute-stats">
        <div class="dispute-stat-card">
            <div class="dispute-icon urgent">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <p>Needs Review</p>
                <h2><?= (int) ($disputeCounts['urgent'] ?? 0) ?></h2>
            </div>
        </div>

        <div class="dispute-stat-card">
            <div class="dispute-icon progress">
                <i class="fa-solid fa-arrows-rotate"></i>
            </div>
            <div>
                <p>In Progress</p>
                <h2><?= (int) ($disputeCounts['progress'] ?? 0) ?></h2>
            </div>
        </div>

        <div class="dispute-stat-card">
            <div class="dispute-icon resolved">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <p>Resolved</p>
                <h2><?= (int) ($disputeCounts['resolved'] ?? 0) ?></h2>
            </div>
        </div>
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
                            data-admin-note="<?= htmlspecialchars($dispute['admin_note'] ?: '') ?>">
                            <td>
                                Case #<?= (int) $dispute['id'] ?>
                                <small>Order #<?= (int) $dispute['order_id'] ?> · <?= htmlspecialchars($createdAt) ?></small>
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
                <h2>Case Details</h2>
                <i class="fa-solid fa-scale-balanced"></i>
            </div>

            <div class="selected-case">
                <p id="selectedDisputeStatus">Select a case</p>
                <h4 id="selectedDisputeTitle">No dispute selected</h4>
                <span id="selectedDisputeDescription">Choose a row to view dispute details.</span>
            </div>

            <div class="case-timeline">
                <h4>Timeline</h4>
                <div class="timeline-item active">
                    <span></span>
                    <div>
                        <strong>Dispute opened</strong>
                        <p id="selectedDisputeDate">N/A</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <span></span>
                    <div>
                        <strong>Admin note</strong>
                        <p id="selectedDisputeNote">No admin note yet.</p>
                    </div>
                </div>
            </div>

            <form class="detail-actions" id="disputeActionForm">
                <input type="hidden" name="dispute_id" id="selectedDisputeId">
                <textarea class="dispute-note-input" name="admin_note" id="selectedDisputeNoteInput" placeholder="Add an admin note..."></textarea>
                <button class="refund-btn" type="submit" data-dispute-submit-action="resolve">Mark Resolved</button>
                <button class="info-btn" type="submit" data-dispute-submit-action="reopen">Reopen Case</button>
            </form>
        </aside>
    </div>

</section>
