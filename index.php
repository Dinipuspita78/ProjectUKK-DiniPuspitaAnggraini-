<?php
// index.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'components/database.php';

// Jika user sudah login, redirect berdasarkan role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin/dashboard.php');
        exit();
    } else {
        header('Location: home.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MinShop - Belanja Kebutuhan Hewan Peliharaan</title>
    <link rel="stylesheet" href="css/style1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Hanya Satu Header/Navbar -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <a href="index.php" class="logo">
                    <i class="fas fa-paw"></i>
                    <span>MinShop</span>
                </a>
                
                <ul class="nav-menu">
                    <li><a href="index.php" class="nav-link active"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="produk.php" class="nav-link"><i class="fas fa-shopping-bag"></i> Produk</a></li>
                    <li><a href="kategori.php" class="nav-link"><i class="fas fa-list"></i> Kategori</a></li>
                    <li><a href="tentang.php" class="nav-link"><i class="fas fa-info-circle"></i> Tentang</a></li>
                    
                    <?php 
                    if(isset($_SESSION['user_id'])) { 
                        // Jika sudah login
                        ?>
                        <li><a href="keranjang.php" class="nav-link"><i class="fas fa-shopping-cart"></i> Keranjang</a></li>
                        <li><a href="profil.php" class="nav-link"><i class="fas fa-user"></i> Profil</a></li>
                        <li><a href="logout.php" class="nav-link btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    <?php 
                    } else { 
                        // Jika belum login
                        ?>
                        <li><a href="login.php" class="nav-link btn-login"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <li><a href="register.php" class="nav-link btn-register"><i class="fas fa-user-plus"></i> Daftar</a></li>
                    <?php 
                    } 
                    ?>
                </ul>
                
                <div class="search-container">
                    <form action="produk.php" method="GET">
                        <input type="text" name="search" placeholder="Cari produk...">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>

                <!-- Mobile Menu Toggle -->
                <div class="hamburger">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Selamat Datang di MinShop</h1>
            <p>Temukan kebutuhan terbaik untuk hewan peliharaan kesayangan Anda. Produk berkualitas dengan harga terbaik.</p>
            <div class="hero-buttons">
                <a href="produk.php" class="btn btn-primary">Lihat Produk</a>
                <a href="register.php" class="btn btn-secondary">Daftar Sekarang</a>
            </div>
        </div>
        <div class="hero-image">
            <img src="images/hero-pets.png" alt="">
        </div>
    </section>

    <!-- Featured Products -->
    <section class="featured-products">
        <div class="container">
            <h2><i class="fas fa-star"></i> Produk Unggulan</h2>
            <?php
            // Query produk unggulan
            $featured_query = "SELECT * FROM produk WHERE stok > 0 ORDER BY RAND() LIMIT 6";
            $featured_result = mysqli_query($conn, $featured_query);
            
            if (!$featured_result) {
                echo "<p class='error'>Error: " . mysqli_error($conn) . "</p>";
            } elseif (mysqli_num_rows($featured_result) > 0) {
            ?>
            <div class="products-grid">
                <?php 
                while ($product = mysqli_fetch_assoc($featured_result)) { 
                ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php 
                        $image_path = "project images/" . htmlspecialchars($product['gambar']);
                        if (file_exists($image_path) && !empty($product['gambar'])) { 
                        ?>
                            <img src="<?php echo $image_path; ?>" 
                                 alt="<?php echo htmlspecialchars($product['nama']); ?>">
                        <?php 
                        } else { 
                        ?>
                            <img src="images/default-product.jpg" 
                                 alt="Produk Default">
                        <?php 
                        } 
                        ?>
                        <div class="product-badge">BEST</div>
                    </div>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['nama']); ?></h3>
                        <span class="category"><?php echo htmlspecialchars($product['kategori']); ?></span>
                        <div class="price">Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?></div>
                        <div class="product-stock">
                            <i class="fas fa-box"></i> 
                            Stok: <?php echo $product['stok']; ?>
                        </div>
                        <div class="product-actions">
                            <a href="detail_produk.php?id=<?php echo $product['id']; ?>" 
                               class="btn btn-view">
                               <i class="fas fa-eye"></i> Lihat Detail
                            </a>
                            <?php 
                            if(isset($_SESSION['user_id'])) { 
                            ?>
                                <a href="add_to_cart.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-cart">
                                   <i class="fas fa-cart-plus"></i> Tambah
                                </a>
                            <?php 
                            } else { 
                            ?>
                                <a href="login.php?redirect=detail_produk.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-cart login-required">
                                   <i class="fas fa-cart-plus"></i> Tambah
                                </a>
                            <?php 
                            } 
                            ?>
                        </div>
                    </div>
                </div>
                <?php 
                } 
                ?>
            </div>
            <?php 
            } else { 
            ?>
            <div class="no-products">
                <i class="fas fa-box-open"></i>
                <p>Tidak ada produk tersedia untuk saat ini.</p>
                <a href="produk.php" class="btn btn-primary">Lihat Semua Produk</a>
            </div>
            <?php 
            } 
            ?>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="features-grid">
                <div class="feature-item">
                    <i class="fas fa-shipping-fast"></i>
                    <h3>Gratis Ongkir</h3>
                    <p>Untuk pembelian di atas Rp 100.000</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Garansi 100%</h3>
                    <p>Produk original & bergaransi</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-headset"></i>
                    <h3>Support 24/7</h3>
                    <p>Customer service siap membantu</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-undo-alt"></i>
                    <h3>Pengembalian</h3>
                    <p>Gratis pengembalian 7 hari</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'components/footer.php'; ?>
    
    <!-- JavaScript -->
    <script>
        // Mobile Menu Toggle
        document.querySelector('.hamburger').addEventListener('click', function() {
            this.classList.toggle('active');
            document.querySelector('.nav-menu').classList.toggle('active');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const navbar = document.querySelector('.navbar');
            const hamburger = document.querySelector('.hamburger');
            const navMenu = document.querySelector('.nav-menu');
            
            if (!navbar.contains(event.target) && navMenu.classList.contains('active')) {
                hamburger.classList.remove('active');
                navMenu.classList.remove('active');
            }
        });

        // Active link highlighting
        const currentPage = window.location.pathname.split('/').pop();
        document.querySelectorAll('.nav-link').forEach(link => {
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active');
            }
        });

        // Login required buttons
        document.querySelectorAll('.login-required').forEach(button => {
            button.addEventListener('click', function(e) {
                // Check if user is logged in (using PHP variable in JS)
                const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
                if (!isLoggedIn) {
                    e.preventDefault();
                    window.location.href = 'login.php?redirect=' + encodeURIComponent(this.getAttribute('href'));
                }
            });
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>