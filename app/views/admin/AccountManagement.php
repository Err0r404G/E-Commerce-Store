<?php
$role = $role ?? 'customer';
$accounts = $accounts ?? [];
$accountCounts = $accountCounts ?? ['active' => 0, 'inactive' => 0, 'total' => 0];
$pageTitle = $pageTitle ?? 'Manage Accounts';
$pageDescription = $pageDescription ?? 'Search, view, deactivate, and reactivate accounts.';
$roleLabel = ucwords(str_replace('_', ' ', $role));
?>

<section class="page-section account-management-page" data-account-role="<?= htmlspecialchars($role) ?>">

    <div class="page-header">
        <div>
            <h1><?= htmlspecialchars($pageTitle) ?></h1>
            <p><?= htmlspecialchars($pageDescription) ?></p>
        </div>

        <?php if ($role === 'delivery_manager'): ?>
            <button class="add-category-btn" id="showDeliveryManagerForm" type="button">
                <i class="fa-solid fa-plus"></i>
                Create Delivery Manager
            </button>
        <?php endif; ?>
    </div>

    <div class="stats-row account-stats-row">
        <div class="stat-card">
            <div class="stat-top">
                <span>Total <?= htmlspecialchars($roleLabel) ?></span>
                <i class="fa-solid fa-users"></i>
            </div>
            <h2><?= number_format((int) $accountCounts['total']) ?></h2>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span class="approved-stat-label">Active</span>
                <i class="fa-solid fa-user-check"></i>
            </div>
            <h2><?= number_format((int) $accountCounts['active']) ?></h2>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span>Inactive</span>
                <i class="fa-solid fa-user-slash"></i>
            </div>
            <h2><?= number_format((int) $accountCounts['inactive']) ?></h2>
        </div>
    </div>

    <div class="approval-card">
        <div class="approval-header">
            <div>
                <h2><?= htmlspecialchars($roleLabel) ?> Directory</h2>
                <p class="account-subtitle">View contact details and control account access.</p>
            </div>

            <div class="approval-tools">
                <div class="search-box-small">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="accountSearch" placeholder="Search account...">
                </div>
            </div>
        </div>

        <p class="category-feedback" id="accountFeedback" hidden></p>

        <table class="approval-table account-table">
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Contact</th>
                    <th>Joined</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($accounts)): ?>
                    <tr>
                        <td colspan="5" class="empty-cell">No <?= htmlspecialchars(strtolower($roleLabel)) ?> accounts found.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($accounts as $account): ?>
                    <?php
                    $isActive = (int) ($account['is_active'] ?? 0) === 1;
                    $joinedDate = !empty($account['created_at']) ? date('M j, Y', strtotime((string) $account['created_at'])) : 'N/A';
                    $searchText = strtolower(($account['name'] ?? '') . ' ' . ($account['email'] ?? '') . ' ' . ($account['phone'] ?? ''));
                    ?>
                    <tr data-account-row data-search="<?= htmlspecialchars($searchText) ?>">
                        <td>
                            <div class="business-info">
                                <div class="business-logo <?= $isActive ? 'green' : 'blue' ?>">
                                    <i class="fa-regular fa-user"></i>
                                </div>
                                <div>
                                    <h4><?= htmlspecialchars($account['name'] ?? 'Unnamed account') ?></h4>
                                    <p>ID #<?= (int) $account['id'] ?></p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?= htmlspecialchars($account['email'] ?? 'No email') ?>
                            <small><?= htmlspecialchars($account['phone'] ?: 'No phone') ?></small>
                        </td>
                        <td><?= htmlspecialchars($joinedDate) ?></td>
                        <td>
                            <span class="approval-status <?= $isActive ? 'approved' : 'suspended' ?>">
                                <?= $isActive ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($isActive): ?>
                                <button class="reject-btn suspend-btn" type="button"
                                    data-account-action="deactivate"
                                    data-account-id="<?= (int) $account['id'] ?>"
                                    data-account-role="<?= htmlspecialchars($role) ?>">
                                    Deactivate
                                </button>
                            <?php else: ?>
                                <button class="approve-btn" type="button"
                                    data-account-action="reactivate"
                                    data-account-id="<?= (int) $account['id'] ?>"
                                    data-account-role="<?= htmlspecialchars($role) ?>">
                                    Reactivate
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="table-footer">
            <p>Showing <?= count($accounts) ?> <?= htmlspecialchars(strtolower($roleLabel)) ?> account<?= count($accounts) === 1 ? '' : 's' ?></p>
        </div>
    </div>

    <?php if ($role === 'delivery_manager'): ?>
        <div class="seller-action-modal-backdrop" id="deliveryManagerModal" hidden>
            <div class="seller-action-modal account-create-modal" role="dialog" aria-modal="true" aria-labelledby="deliveryManagerModalTitle">
                <div class="category-modal-heading">
                    <h2 id="deliveryManagerModalTitle">Create Delivery Manager</h2>
                    <p>Add a delivery manager account that can log in immediately.</p>
                </div>

                <form id="deliveryManagerForm" class="seller-action-form account-create-form">
                    <label>
                        Full Name
                        <input type="text" name="name" placeholder="e.g. Rahim Ahmed" required>
                    </label>

                    <label>
                        Email
                        <input type="email" name="email" placeholder="manager@example.com" required>
                    </label>

                    <label>
                        Phone
                        <input type="text" name="phone" placeholder="Optional phone number">
                    </label>

                    <label>
                        Password
                        <input type="password" name="password" placeholder="At least 6 characters" required>
                    </label>

                    <label>
                        Confirm Password
                        <input type="password" name="confirm_password" placeholder="Repeat password" required>
                    </label>

                    <div class="category-modal-actions seller-action-buttons">
                        <button class="modal-cancel-btn" id="cancelDeliveryManagerForm" type="button">Cancel</button>
                        <button class="modal-create-btn" type="submit">
                            <i class="fa-solid fa-user-plus"></i>
                            Create
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

</section>
