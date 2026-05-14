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
        <a href="#" class="active">Marketplace</a>
        <a href="#">Categories</a>
        <a href="#">Deals</a>
    </nav>

    <!-- SEARCH -->
    <div class="search-box">
        <i class="fa fa-search"></i>
        <input type="text" placeholder="Search premium products...">
    </div>

    <!-- RIGHT SIDE -->
    <div class="right-section">

        <div class="icons">
            <i class="fa fa-shopping-cart"></i>
            <i class="fa fa-heart"></i>
        </div>

<div class="auth">
    <a class="login" href="/E-Commerce-Store/app/views/auth/login.php">Login</a>

    <a class="signup" href="/E-Commerce-Store/app/views/auth/signup.php">Sign Up</a>
</div>

    </div>

</header>