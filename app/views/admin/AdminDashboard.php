<?php include __DIR__ . '/../layouts/header.php'; ?>

<link rel="stylesheet" href="/public/css/admin-dashboard.css">

<section class="admin-layout">

    <aside class="admin-sidebar">

        <!-- MENU -->
        <nav class="admin-menu">

            <a href="#" class="active">
                <i class="fa-solid fa-table-cells-large"></i>
                <span>Dashboard</span>
            </a>

            <a href="#">
                <i class="fa-solid fa-user-check"></i>
                <span>Seller Approvals</span>
            </a>

            <a href="#">
                <i class="fa-solid fa-shapes"></i>
                <span>Category Management</span>
            </a>

            <a href="#">
                <i class="fa-solid fa-gavel"></i>
                <span>Disputes</span>
            </a>

            <a href="#">
                <i class="fa-solid fa-chart-column"></i>
                <span>Platform Reports</span>
            </a>

        </nav>

        <!-- USER -->
        <div class="admin-user-box">

            <div class="user-info">

                <div class="user-icon">
                    <i class="fa-regular fa-user"></i>
                </div>

                <div>
                    <h4>USER ADMIN</h4>
                    <p>ADMIN</p>
                </div>

            </div>

            <a href="#" class="logout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span>Log Out</span>
            </a>

        </div>

    </aside>

    <main class="admin-content">

    </main>

</section>

</body>
</html>