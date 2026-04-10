<?php
// components/header_pengguna.php

require_once 'database.php';

$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $cart_query = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM keranjang WHERE user_id = $user_id");
    $cart_data = mysqli_fetch_assoc($cart_query);
    $cart_count = $cart_data['total'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MinShop - <?php echo $page_title ?? 'Home'; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header untuk User yang Login -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <!-- Logo -->
                <a href="home.php" class="logo">
                    <i class="fas fa-paw"></i>
                    <span>MinShop</span>
                </a>

                <!-- Mobile Menu Toggle -->
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>

                <!-- Navigation Links untuk User Login -->
                <ul class="nav-menu" id="navMenu">
                    <li><a href="home.php" class="nav-link"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="produk.php" class="nav-link"><i class="fas fa-shopping-bag"></i> Produk</a></li>
                    <li><a href="kategori.php" class="nav-link"><i class="fas fa-list"></i> Kategori</a></li>
                    <li><a href="tentang.php" class="nav-link"><i class="fas fa-info-circle"></i> Tentang</a></li>
                    <li><a href="orders.php" class="nav-link"><i class="fas fa-clipboard-list"></i> Pesanan Saya</a></li>
                    
                    <!-- Dropdown User -->
                    <li class="dropdown">
                        <a href="#" class="nav-link dropdown-toggle">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['nama'] ?? 'User'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="profil.php"><i class="fas fa-user-circle"></i> Profil</a></li>
                            <li><a href="alamat.php"><i class="fas fa-map-marker-alt"></i> Alamat</a></li>
                            <li><a href="orders.php"><i class="fas fa-clipboard-list"></i> Pesanan Saya</a></li>
                            <li><a href="components/user_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                    
                    <!-- Keranjang -->
                    <li class="cart-icon">
                        <a href="keranjang.php" class="nav-link">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-count"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
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