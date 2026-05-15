<section class="vendor-approval-page">

    <div class="page-header">
        <h1>Seller Accounts</h1>
        <p>Review seller registrations, approvals, suspensions, and account access.</p>
    </div>

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-icon">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
                <span>Pending</span>
            </div>
            <p>Total Pending</p>
            <h2><?= (int) ($counts['pending'] ?? 0) ?></h2>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-icon">
                    <i class="fa-solid fa-user-check"></i>
                </div>
                <span class="approved-stat-label">Approved</span>
            </div>
            <p>Approved Sellers</p>
            <h2><?= (int) ($counts['approved'] ?? 0) ?></h2>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-icon">
                    <i class="fa-solid fa-user-slash"></i>
                </div>
                <span>Suspended</span>
            </div>
            <p>Suspended Sellers</p>
            <h2><?= (int) ($counts['suspended'] ?? 0) ?></h2>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-icon">
                    <i class="fa-solid fa-ban"></i>
                </div>
                <span>Rejected</span>
            </div>
            <p>Rejected Sellers</p>
            <h2><?= (int) ($counts['rejected'] ?? 0) ?></h2>
        </div>
    </div>

    <div class="approval-card">
        <div class="approval-header">
            <h2>Seller Directory</h2>

            <div class="approval-tools">
                <div class="search-box-small">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="vendorApprovalSearch" placeholder="Search vendors...">
                </div>
            </div>
        </div>

        <table class="approval-table">
            <thead>
                <tr>
                    <th>Business Name</th>
                    <th>Contact</th>
                    <th>Date Applied</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody id="vendorApprovalRows">
                <?php if (empty($vendors)): ?>
                    <tr>
                        <td colspan="5" class="empty-cell">No seller accounts found.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($vendors as $vendor): ?>
                    <?php
                    $status = $vendor['account_status'] ?: ((int) $vendor['is_active'] === 1 ? 'approved' : 'pending');
                    $statusLabels = [
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'suspended' => 'Suspended',
                    ];
                    $businessName = $vendor['shop_name'] ?: $vendor['name'];
                    $dateApplied = $vendor['created_at'] ? date('M d, Y', strtotime($vendor['created_at'])) : 'N/A';
                    ?>
                    <tr data-vendor-row data-search="<?= htmlspecialchars(strtolower($businessName . ' ' . $vendor['name'] . ' ' . $vendor['email'] . ' ' . $status)) ?>">
                        <td>
                            <div class="business-info">
                                <div class="business-logo blue">
                                    <i class="fa-solid fa-store"></i>
                                </div>
                                <div>
                                    <h4><?= htmlspecialchars($businessName) ?></h4>
                                    <p>Seller ID: <?= (int) $vendor['id'] ?></p>
                                </div>
                            </div>
                        </td>

                        <td>
                            <?= htmlspecialchars($vendor['name']) ?>
                            <small><?= htmlspecialchars($vendor['email']) ?></small>
                            <?php if (!empty($vendor['phone'])): ?>
                                <small><?= htmlspecialchars($vendor['phone']) ?></small>
                            <?php endif; ?>
                        </td>

                        <td><?= htmlspecialchars($dateApplied) ?></td>

                        <td>
                            <span class="approval-status <?= htmlspecialchars($status) ?>">
                                <?= htmlspecialchars($statusLabels[$status] ?? ucfirst($status)) ?>
                            </span>
                            <?php if (!empty($vendor['admin_note'])): ?>
                                <small><?= htmlspecialchars($vendor['admin_note']) ?></small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if (in_array($status, ['pending', 'rejected'], true)): ?>
                                <button class="approve-btn" data-approval-action="approve" data-vendor-id="<?= (int) $vendor['id'] ?>">Approve</button>
                            <?php endif; ?>
                            <?php if ($status === 'pending'): ?>
                                <button class="reject-btn" data-approval-action="reject" data-vendor-id="<?= (int) $vendor['id'] ?>" data-requires-reason="true">Reject</button>
                            <?php endif; ?>
                            <?php if ($status === 'approved'): ?>
                                <button class="reject-btn suspend-btn" data-approval-action="suspend" data-vendor-id="<?= (int) $vendor['id'] ?>" data-requires-reason="true">Suspend</button>
                            <?php endif; ?>
                            <?php if ($status === 'suspended'): ?>
                                <button class="approve-btn" data-approval-action="reactivate" data-vendor-id="<?= (int) $vendor['id'] ?>">Reactivate</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="table-footer">
            <p>Showing <?= count($vendors) ?> seller account<?= count($vendors) === 1 ? '' : 's' ?></p>
        </div>
    </div>

    <div class="seller-action-modal-backdrop" id="sellerActionModal" hidden>
        <div class="seller-action-modal" role="dialog" aria-modal="true" aria-labelledby="sellerActionTitle">
            <div class="category-modal-heading">
                <h2 id="sellerActionTitle">Seller Action</h2>
                <p id="sellerActionText">Add a reason before continuing.</p>
            </div>

            <form id="sellerActionForm" class="seller-action-form">
                <input type="hidden" id="sellerActionVendorId">
                <input type="hidden" id="sellerActionType">

                <label>
                    Reason
                    <textarea id="sellerActionReason" placeholder="Write the reason that will be saved with this seller account..." required></textarea>
                </label>

                <div class="category-modal-actions seller-action-buttons">
                    <button class="modal-cancel-btn" id="cancelSellerAction" type="button">Cancel</button>
                    <button class="modal-create-btn" type="submit">
                        <i class="fa-solid fa-check"></i>
                        Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>

</section>
