<?php include __DIR__ . '/../layouts/header.php'; ?>

<link rel="stylesheet" href="/public/css/signup.css">

<main class="signup-page">

    <section class="signup-card">

        <div class="signup-header">
            <h1>Create Account</h1>
            <p>Enter your credentials to access the Nexus ecosystem.</p>
        </div>

        <form class="signup-form" action="#" method="POST" enctype="multipart/form-data">

            <div class="profile-upload">
                <label for="profile_pic" class="profile-box">
                    <i class="fa-regular fa-user"></i>
                    <span class="camera">
                        <i class="fa-solid fa-camera"></i>
                    </span>
                </label>

                <input type="file" id="profile_pic" name="profile_pic" hidden>
                <p>PROFILE PICTURE</p>
            </div>

            <div class="form-group">
                <label>Account Type</label>

                <div class="account-type">
                    <input type="radio" id="customer" name="role" value="customer" checked>
                    <label for="customer">Customer</label>

                    <input type="radio" id="seller" name="role" value="seller">
                    <label for="seller">Seller</label>
                </div>
            </div>

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" placeholder="Name" required>
            </div>

            <div class="form-group">
                <label>Corporate or Personal Email</label>
                <input type="email" name="email" placeholder="name@company.com" required>
            </div>

            <div class="form-group">
                <label>Phone Number (Optional)</label>
                <input type="text" name="phone" placeholder="+880 123456789">
            </div>

            <div class="password-grid">
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="••••••••" required>
                </div>
            </div>

            <div class="terms-row">
                <input type="checkbox" id="terms" required>
                <label for="terms">
                    I agree to the <a href="#">Terms of Service</a> and <a href="#">Merchant Agreement</a>.
                </label>
            </div>

            <button type="submit" class="create-btn">Create Account</button>

            <p class="login-text">
                Already have an account?
                <a href="?page=login">Log In</a>
            </p>

        </form>

    </section>

</main>

</body>
</html>