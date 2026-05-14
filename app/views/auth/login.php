<?php
$errors = $errors ?? [];
$old = $old ?? [];

include __DIR__ . '/../layouts/header.php';
?>
<link rel="stylesheet" href="/E-Commerce-Store/public/css/login.css">
<main class="login-page">

    <section class="login-card">

        <div class="login-header">
            <h1>Welcome Back</h1>
            <p>Please enter your credentials to access the Center.</p>
        </div>

        <div class="divider">
            <span></span>
            <p>CONTINUE WITH</p>
            <span></span>
        </div>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="auth-message auth-message-success">
                <p><?= htmlspecialchars($_SESSION['success']) ?></p>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="auth-message auth-message-error">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="login-form" action="/E-Commerce-Store/index.php?page=login" method="POST">

            <div class="form-group">
                <label>Email</label>

                <div class="input-box">
                    <i class="fa-regular fa-envelope"></i>
                    <input type="email" name="email" placeholder="name@company.com" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <div class="password-row">
                    <label>Password</label>
                    <a href="#">Forgot Password?</a>
                </div>

                <div class="input-box">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="••••••••" required>
                    <i class="fa-regular fa-eye"></i>
                </div>
            </div>

            <div class="remember-row">
                <input type="checkbox" id="remember">
                <label for="remember">Keep me signed in for 30 days</label>
            </div>

            <button type="submit" class="signin-btn">Sign In</button>

        </form>

        <p class="signup-link">
            New to NexusCommerce?
            <a href="/E-Commerce-Store/index.php?page=signup">Create an account</a>
        </p>

        <div class="bottom-line"></div>

        <div class="login-footer">
            <p>Protected by enterprise-grade 256-bit encryption.</p>

            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Help Center</a>
            </div>
        </div>

    </section>

</main>

</body>
</html>
