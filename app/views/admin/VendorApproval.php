<section class="vendor-approval-page">

    <div class="page-header">
        <h1>Vendor Approvals</h1>
        <p>Review vendor accounts and control access to the vendor dashboard.</p>
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
                <span>Approved</span>
            </div>
            <p>Total Vendors</p>
            <h2><?= (int) ($counts['approved'] ?? 0) ?></h2>
        </div>
    </div>

    <div class="approval-card">
        <div class="approval-header">
            <h2>Vendor Applications</h2>

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
                        <td colspan="5" class="empty-cell">No vendor applications found.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($vendors as $vendor): ?>
                    <?php
                    $isApproved = (int) $vendor['is_active'] === 1;
                    $businessName = $vendor['shop_name'] ?: $vendor['name'];
                    $dateApplied = $vendor['created_at'] ? date('M d, Y', strtotime($vendor['created_at'])) : 'N/A';
                    ?>
                    <tr data-vendor-row data-search="<?= htmlspecialchars(strtolower($businessName . ' ' . $vendor['name'] . ' ' . $vendor['email'])) ?>">
                        <td>
                            <div class="business-info">
                                <div class="business-logo blue">
                                    <i class="fa-solid fa-store"></i>
                                </div>
                                <div>
                                    <h4><?= htmlspecialchars($businessName) ?></h4>
                                    <p>Vendor ID: <?= (int) $vendor['id'] ?></p>
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
                            <span class="approval-status <?= $isApproved ? 'approved' : 'pending' ?>">
                                <?= $isApproved ? 'Approved' : 'Pending' ?>
                            </span>
                        </td>

                        <td>
                            <?php if (!$isApproved): ?>
                                <button class="approve-btn" data-approval-action="approve" data-vendor-id="<?= (int) $vendor['id'] ?>">Approve</button>
                            <?php endif; ?>
                            <button class="reject-btn" data-approval-action="reject" data-vendor-id="<?= (int) $vendor['id'] ?>">Reject</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="table-footer">
            <p>Showing <?= count($vendors) ?> vendor application<?= count($vendors) === 1 ? '' : 's' ?></p>
        </div>
    </div>

</section>
