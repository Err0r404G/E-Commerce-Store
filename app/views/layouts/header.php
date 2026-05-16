<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexusCommerce</title>

    <!-- CSS -->
    <link rel="stylesheet" href="/E-Commerce-Store/public/css/layouts.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<header class="navbar">

    <!-- LOGO -->
<a class="logo" href="/E-Commerce-Store/index.php">
    Nexus<span>Commerce</span>
</a>

    <!-- MENU -->
    <nav class="menu">
        <a href="/E-Commerce-Store/index.php?page=customerMarketplace" class="active">Marketplace</a>
        <a href="/E-Commerce-Store/index.php?page=customerMarketplace">Categories</a>
        <a href="/E-Commerce-Store/index.php?page=customerMarketplace&sort=price_low">Deals</a>
    </nav>

    <!-- SEARCH -->
    <form class="search-box" method="get" action="/E-Commerce-Store/index.php">
        <input type="hidden" name="page" value="customerMarketplace">
        <i class="fa fa-search"></i>
        <input type="text" name="keyword" placeholder="Search premium products...">
    </form>

    <!-- RIGHT SIDE -->
    <div class="right-section">

        <div class="icons">
            <a href="/E-Commerce-Store/index.php?page=customerCart" aria-label="Cart"><i class="fa fa-shopping-cart"></i></a>
            <a href="/E-Commerce-Store/index.php?page=customerWishlist" aria-label="Wishlist"><i class="fa fa-heart"></i></a>
        </div>

<div class="auth">
    <?php if (!empty($_SESSION['user'])): ?>
        <span class="user-name"><?= htmlspecialchars($_SESSION['user']['name']) ?></span>
        <a class="login" href="/E-Commerce-Store/index.php?page=logout">Logout</a>
    <?php else: ?>
        <a class="login" href="/E-Commerce-Store/index.php?page=login">Login</a>
        <a class="signup" href="/E-Commerce-Store/index.php?page=signup">Sign Up</a>
    <?php endif; ?>
</div>

    </div>

</header>
