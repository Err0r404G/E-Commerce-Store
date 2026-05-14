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

    <header class="vendor-appbar">

        <!-- SEARCH -->
        <div class="search-box">
            <i class="fa fa-search"></i>
            <input type="text" placeholder="Search orders, reviews...">
        </div>



        <!-- RIGHT SIDE -->
        <div class="right-section">

            <div class="icons">
                <i class="fa fa-bell"></i>
            </div>

            <div class="divider-vertical"></div>

            <div class="auth column gap-2">
                <p class="title">Welcome, <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Vendor') ?>!</p>
                <p class="subtitle">Vendor</p>
            </div>
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>

        </div>

    </header>
