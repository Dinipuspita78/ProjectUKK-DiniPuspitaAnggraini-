<?php
// components/header_guest.php

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MinShop - <?php echo $page_title ?? 'Home'; ?></title>
    <link rel="stylesheet" href="css/style1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header Guest -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <!-- Logo -->
                <a href="index.php" class="logo">
                    <i class="fas fa-paw"></i>
                    <span>MinShop</span>
                </a>

                <!-- Mobile Menu Toggle -->
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>

                <!-- Navigation Links untuk Guest -->
                <ul class="nav-menu" id="navMenu">
                    <li><a href="index.php" class="nav-link"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="produk.php" class="nav-link"><i class="fas fa-shopping-bag"></i> Produk</a></li>
                    <li><a href="kategori.php" class="nav-link"><i class="fas fa-list"></i> Kategori</a></li>
                    <li><a href="tentang.php" class="nav-link"><i class="fas fa-info-circle"></i> Tentang</a></li>
                    
                    <!-- Tombol Login dan Daftar -->
                    <li><a href="login.php" class="nav-link btn-login"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="register.php" class="nav-link btn-register"><i class="fas fa-user-plus"></i> Daftar</a></li>
                    
                    <!-- Keranjang dengan tooltip -->
                    <li class="cart-icon">
                        <a href="login.php" class="nav-link login-required" title="Login untuk melihat keranjang">
                            <i class="fas fa-shopping-cart"></i>
                        </a>
                    </li>
                </ul>

                <!-- Search Form -->
                <div class="search-container">
                    <form action="produk.php" method="GET" class="search-form">
                        <input type="text" name="search" id="searchInput" placeholder="Cari produk..." class="search-input">
                        <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>
        </nav>
    </header>

    <!-- Mobile Menu Overlay -->
    <div class="mobile-overlay" id="mobileOverlay"></div>
</body>
</html>