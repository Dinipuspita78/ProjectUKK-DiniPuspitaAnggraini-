<?php
// produk.php
session_start();
require_once 'components/database.php';

// Set page title
$page_title = "Produk";

// Check if user is logged in
$user_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] == 'user';

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$kategori = $_GET['kategori'] ?? '';

// Build query
$query = "SELECT * FROM produk WHERE stok > 0";
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $query .= " AND (nama LIKE '%$search%' OR deskripsi LIKE '%$search%')";
}
if (!empty($kategori)) {
    $kategori = mysqli_real_escape_string($conn, $kategori);
    $query .= " AND kategori = '$kategori'";
}
$query .= " ORDER BY created_at DESC";

$result = mysqli_query($conn, $query);
$products = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get all categories for filter
$categories_query = mysqli_query($conn, "SELECT DISTINCT kategori FROM produk WHERE kategori IS NOT NULL");
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
        <div class="container">
            <h1 class="section-title">Daftar Produk</h1>
            
            <!-- Search and Filter -->
            <div class="filter-section" style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <form method="GET" action="produk.php" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 1; position: relative; min-width: 250px;">
                        <input type="text" name="search" placeholder="Cari produk..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               style="width: 100%; padding: 10px 40px 10px 15px; border: 1px solid #ddd; border-radius: 5px;">
                        <button type="submit" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #666; cursor: pointer;">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <select name="kategori" onchange="this.form.submit()" style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 5px; min-width: 150px;">
                        <option value="">Semua Kategori</option>
                        <?php while ($cat = mysqli_fetch_assoc($categories_query)): ?>
                        <option value="<?php echo $cat['kategori']; ?>" <?php echo $kategori == $cat['kategori'] ? 'selected' : ''; ?>>
                            <?php echo $cat['kategori']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    
                    <a href="produk.php" style="padding: 10px 20px; background: #ff6b6b; color: white; border-radius: 5px; text-decoration: none;">Reset</a>
                </form>
            </div>
            
            <!-- Products Grid -->
            <div class="products-grid">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
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
                                    
                                <?php else: ?>
                                    <a href="login.php?redirect=produk.php" class="btn btn-cart login-required">
                                        <i class="fas fa-cart-plus"></i> Tambah
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 50px; color: #666;">
                        <i class="fas fa-box-open fa-3x" style="margin-bottom: 20px; color: #ddd;"></i>
                        <h3>Tidak ada produk ditemukan</h3>
                        <p>Coba gunakan kata kunci pencarian yang berbeda</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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
        
        // Mobile menu functionality (reuse from home.php)
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
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            hideNotification(notification);
        }, 3000);
        
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