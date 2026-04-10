<?php
// home.php
session_start();
require_once 'components/database.php';

// Set page title
$page_title = "Home";

// Check if user is logged in
$user_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] == 'user';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MinShop - <?php echo $page_title; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php 
    if ($user_logged_in) {
        include 'components/header_pengguna.php';
    } else {
        include 'components/header_guest.php';
    }
    ?>

    <main class="main-content">
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h1>Selamat Datang di MinShop</h1>
                <?php if ($user_logged_in): ?>
                    <p>Halo, <?php echo htmlspecialchars($_SESSION['nama'] ?? 'User'); ?>! Selamat berbelanja kebutuhan hewan peliharaan kesayangan Anda.</p>
                <?php else: ?>
                    <p>Temukan kebutuhan terbaik untuk hewan peliharaan kesayangan Anda. Produk berkualitas dengan harga terbaik.</p>
                <?php endif; ?>
                
                <a href="produk.php" class="btn-hero">Lihat Produk <i class="fas fa-arrow-right"></i></a>
                
                <?php if (!$user_logged_in): ?>
                    <a href="register.php" class="btn-hero btn-secondary">Daftar Sekarang</a>
                <?php endif; ?>
            </div>
        </section>

        <!-- Featured Products -->
        <section class="container">
            <h2 class="section-title">Produk Unggulan</h2>
            
            <div class="products-grid">
                <?php
                $featured_query = "SELECT * FROM produk WHERE stok > 0 ORDER BY RAND() LIMIT 6";
                $featured_result = mysqli_query($conn, $featured_query);
                
                while ($product = mysqli_fetch_assoc($featured_result)):
                ?>
                <div class="product-card">
                    <img src="project images/<?php echo htmlspecialchars($product['gambar'] ?: 'default.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($product['nama']); ?>" class="product-img">
                    <div class="product-info">
                        <h3 class="product-title"><?php echo htmlspecialchars($product['nama']); ?></h3>
                        <span class="product-category"><?php echo htmlspecialchars($product['kategori']); ?></span>
                        <div class="product-price">Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?></div>
                        <p class="product-desc"><?php echo substr(htmlspecialchars($product['deskripsi']), 0, 100); ?>...</p>
                        
                        <div class="product-actions">
                            <a href="detail_produk.php?id=<?php echo $product['id']; ?>" class="btn btn-view">
                                <i class="fas fa-eye"></i> Detail
                            </a>
                            
                            <?php if ($user_logged_in): ?>
                              
                                         
                                   
                                </button>
                            <?php else: ?>
                                <a href="login.php?redirect=home.php" 
                                   class="btn btn-cart login-required">
                                    <i class="fas fa-cart-plus"></i> Tambah
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </section>

        <!-- Features -->
        <section class="features">
            <div class="container">
                <h2 class="section-title">Mengapa Memilih Kami?</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <i class="fas fa-shipping-fast"></i>
                        <h3>Gratis Ongkir</h3>
                        <p>Untuk pembelian di atas Rp 500.000</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-shield-alt"></i>
                        <h3>Garansi 100%</h3>
                        <p>Produk terjamin kualitasnya</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-headset"></i>
                        <h3>Support 24/7</h3>
                        <p>Customer service siap membantu</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-undo"></i>
                        <h3>Return Mudah</h3>
                        <p>Pengembalian dalam 7 hari</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'components/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add to cart functionality
        const addToCartButtons = document.querySelectorAll('.add-to-cart');
        
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                if (!<?php echo $user_logged_in ? 'true' : 'false'; ?>) {
                    showLoginModal('Untuk menambahkan produk ke keranjang, silakan login terlebih dahulu.');
                    return;
                }
                
                const productId = this.dataset.productId;
                
                fetch('ajax/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Produk berhasil ditambahkan ke keranjang!', 'success');
                        // Update cart count
                        const cartCountElement = document.querySelector('.cart-count');
                        if (cartCountElement) {
                            cartCountElement.textContent = data.cart_count;
                        }
                    } else {
                        showNotification(data.message || 'Gagal menambahkan produk', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Terjadi kesalahan', 'error');
                });
            });
        });
        
        // Login required links
        const loginRequiredButtons = document.querySelectorAll('.login-required');
        loginRequiredButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!<?php echo $user_logged_in ? 'true' : 'false'; ?>) {
                    e.preventDefault();
                    showLoginModal('Untuk mengakses fitur ini, silakan login terlebih dahulu.');
                }
            });
        });
        
        // Mobile menu functionality
        const menuToggle = document.getElementById('menuToggle');
        const navMenu = document.getElementById('navMenu');
        const mobileOverlay = document.getElementById('mobileOverlay');
        
        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                navMenu.classList.toggle('active');
                mobileOverlay.classList.toggle('active');
                document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
            });
        }
        
        if (mobileOverlay) {
            mobileOverlay.addEventListener('click', function() {
                navMenu.classList.remove('active');
                this.classList.remove('active');
                document.body.style.overflow = '';
            });
        }
        
        // Close mobile menu when clicking a link
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 900) {
                    navMenu.classList.remove('active');
                    mobileOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
        
        // Dropdown functionality for mobile
        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                if (window.innerWidth <= 900) {
                    e.preventDefault();
                    const dropdown = this.closest('.dropdown');
                    dropdown.classList.toggle('active');
                }
            });
        });
    });
    
    function showLoginModal(message) {
        const modal = document.createElement('div');
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        `;
        
        modal.innerHTML = `
            <div style="background: white; border-radius: 10px; padding: 2rem; max-width: 400px; width: 90%;">
                <h3 style="margin-bottom: 1rem; color: #4a90e2;">
                    <i class="fas fa-info-circle"></i> Login Diperlukan
                </h3>
                <p>${message}</p>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <a href="login.php?redirect=${encodeURIComponent(window.location.href)}" 
                       style="flex: 1; padding: 0.8rem; background: #4a90e2; color: white; text-decoration: none; border-radius: 5px; text-align: center;">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="register.php" 
                       style="flex: 1; padding: 0.8rem; background: #50c878; color: white; text-decoration: none; border-radius: 5px; text-align: center;">
                        <i class="fas fa-user-plus"></i> Daftar
                    </a>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.remove();
            }
        });
    }
    
    function showNotification(message, type) {
        // Remove existing notification
        const existingNotification = document.querySelector('.notification');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        `;
        
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            hideNotification(notification);
        }, 3000);
        
        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            hideNotification(notification);
        });
    }
    
    function hideNotification(notification) {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }
    </script>
</body>
</html>