<?php include __DIR__ . '/../layouts/header.php'; ?>

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

        <form class="login-form" action="#" method="POST">

            <div class="form-group">
                <label>Email</label>

                <div class="input-box">
                    <i class="fa-regular fa-envelope"></i>
                    <input type="email" name="email" placeholder="name@company.com" required>
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