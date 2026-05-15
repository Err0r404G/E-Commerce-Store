<section class="vendor-profile-page admin-settings-page">
    <div class="page-header">
        <h1>Settings</h1>
        <p>Update your admin profile, phone, password, and picture.</p>
    </div>

    <div class="auth-message vendor-profile-message" id="adminSettingsMessage" hidden></div>

    <form class="vendor-profile-form" id="adminSettingsForm" action="/E-Commerce-Store/index.php?page=adminSettingsAction" method="POST" enctype="multipart/form-data">
        <section class="vendor-profile-panel vendor-logo-panel">
            <div class="vendor-profile-logo" id="adminProfileImagePreview">
                <?php if (!empty($profile['profile_pic'])): ?>
                    <img src="/E-Commerce-Store/<?= htmlspecialchars($profile['profile_pic']) ?>" alt="">
                <?php else: ?>
                    <i class="fa-regular fa-user"></i>
                <?php endif; ?>
            </div>

            <div>
                <h2><?= htmlspecialchars($profile['name'] ?? 'Admin') ?></h2>
                <p>Upload a new profile picture for your admin account.</p>
                <label class="vendor-file-btn">
                    <i class="fa-solid fa-camera"></i>
                    Choose Picture
                    <input type="file" name="profile_pic" id="adminProfileImageInput" accept="image/jpeg,image/png,image/webp" hidden>
                </label>
            </div>
        </section>

        <section class="vendor-profile-panel">
            <h2>Personal Information</h2>

            <div class="vendor-profile-grid">
                <label>
                    Full Name
                    <input type="text" name="name" value="<?= htmlspecialchars($profile['name'] ?? '') ?>" required>
                </label>

                <label>
                    Phone
                    <input type="text" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>">
                </label>
            </div>
        </section>

        <section class="vendor-profile-panel">
            <h2>Change Password</h2>

            <div class="vendor-profile-grid">
                <label>
                    Current Password
                    <input type="password" name="current_password" autocomplete="current-password">
                </label>

                <label>
                    New Password
                    <input type="password" name="new_password" autocomplete="new-password">
                </label>

                <label>
                    Confirm New Password
                    <input type="password" name="confirm_password" autocomplete="new-password">
                </label>
            </div>
        </section>

        <div class="vendor-profile-actions">
            <button type="submit">
                <i class="fa-solid fa-floppy-disk"></i>
                Save Changes
            </button>
        </div>
    </form>
</section>
