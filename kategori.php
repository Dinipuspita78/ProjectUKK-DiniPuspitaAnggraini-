<?php
// kategori.php
session_start();
require_once 'components/database.php';

// Set page title
$page_title = "Kategori";

// Check if user is logged in
$user_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] == 'user';

// Get selected category
$selected_category = $_GET['kategori'] ?? '';

// Get all categories
$categories_query = "SELECT DISTINCT kategori, COUNT(*) as total_produk 
                     FROM produk 
                     WHERE stok > 0 
                     GROUP BY kategori 
                     ORDER BY kategori";
$categories_result = mysqli_query($conn, $categories_query);

// Get products for selected category
$products_query = "SELECT * FROM produk WHERE stok > 0";
if (!empty($selected_category)) {
    $selected_category = mysqli_real_escape_string($conn, $selected_category);
    $products_query .= " AND kategori = '$selected_category'";
}
$products_query .= " ORDER BY created_at DESC";
$products_result = mysqli_query($conn, $products_query);
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

<main class="container">
    <h1 class="page-title"><i class="fas fa-list"></i> Kategori Produk</h1>
    <p class="page-subtitle">Temukan produk berdasarkan kategori yang Anda inginkan</p>
    
    <!-- Category Filter -->
    <div class="category-filter">
        <div class="category-list">
            <a href="kategori.php" class="category-item <?php echo empty($selected_category) ? 'active' : ''; ?>">
                <div class="category-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="category-info">
                    <h3>Semua Produk</h3>
                    <p>Lihat semua produk kami</p>
                </div>
                <div class="category-count">
                    <?php 
                    $total_all = mysqli_fetch_assoc(mysqli_query($conn, 
                        "SELECT COUNT(*) as total FROM produk WHERE stok > 0"))['total'];
                    echo $total_all;
                    ?>
                </div>
            </a>
            
            <?php while ($category = mysqli_fetch_assoc($categories_result)): 
                $category_name = $category['kategori'];
                $category_icon = getCategoryIcon($category_name);
            ?>
            <a href="kategori.php?kategori=<?php echo urlencode($category_name); ?>" 
               class="category-item <?php echo $selected_category == $category_name ? 'active' : ''; ?>">
                <div class="category-icon">
                    <i class="<?php echo $category_icon; ?>"></i>
                </div>
                <div class="category-info">
                    <h3><?php echo $category_name; ?></h3>
                    <p>Produk terbaik untuk kebutuhan Anda</p>
                </div>
                <div class="category-count">
                    <?php echo $category['total_produk']; ?>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
    </div>
    
    <!-- Products Grid -->
    <?php if (!empty($selected_category)): ?>
    <div class="category-products">
        <div class="category-header">
            <h2>
                <i class="<?php echo getCategoryIcon($selected_category); ?>"></i>
                <?php echo $selected_category; ?>
            </h2>
            <p><?php echo mysqli_num_rows($products_result); ?> produk ditemukan</p>
        </div>
        
        <?php if (mysqli_num_rows($products_result) > 0): ?>
        <div class="products-grid">
            <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
            <div class="product-card">
                <?php if ($product['stok'] < 10): ?>
                <div class="product-badge">Hampir Habis</div>
                <?php endif; ?>
                
                <img src="project images/<?php echo $product['gambar'] ?: 'default.jpg'; ?>" 
                     alt="<?php echo $product['nama']; ?>" class="product-img">
                
                <div class="product-info">
                    <h3 class="product-title"><?php echo htmlspecialchars($product['nama']); ?></h3>
                    <span class="product-category"><?php echo htmlspecialchars($product['kategori']); ?></span>
                    
                    <div class="product-price">
                        Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?>
                    </div>
                    
                    <div class="product-stock">
                        <i class="fas fa-box"></i> Stok: <?php echo $product['stok']; ?>
                    </div>
                    
                    <div class="product-actions">
                        <a href="detail_produk.php?id=<?php echo $product['id']; ?>" 
                           class="btn-view">
                            <i class="fas fa-eye"></i> Detail
                        </a>
                        
                        <?php if ($user_logged_in): ?>
                            <button class="btn-cart add-to-cart" 
                                    data-product-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-cart-plus"></i> Keranjang
                            </button>
                        <?php else: ?>
                            <a href="login.php?redirect=kategori.php?kategori=<?php echo urlencode($selected_category); ?>" 
                               class="btn-cart login-required">
                                <i class="fas fa-cart-plus"></i> Keranjang
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="no-products">
            <i class="fas fa-box-open fa-3x"></i>
            <h3>Tidak ada produk dalam kategori ini</h3>
            <p>Silakan pilih kategori lain</p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</main>

<?php 
// Function to get icon for category
function getCategoryIcon($category_name) {
    $icons = [
        'Makanan Kucing' => 'fas fa-cat',
        'Makanan Anjing' => 'fas fa-dog',
        'Aksesoris' => 'fas fa-paw',
        'Mainan' => 'fas fa-gamepad',
        'Perawatan' => 'fas fa-shower',
        'Vitamin' => 'fas fa-pills',
        'Kandang' => 'fas fa-home',
        'Pasir Kucing' => 'fas fa-box',
        'Peralatan' => 'fas fa-tools',
        'Pakaian' => 'fas fa-tshirt'
    ];
    
    return $icons[$category_name] ?? 'fas fa-box';
}
?>

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
                    if (document.querySelector('.cart-count')) {
                        document.querySelector('.cart-count').textContent = data.cart_count;
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
});
</script>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.page-title {
    text-align: center;
    color: #4a90e2;
    margin-bottom: 10px;
}

.page-subtitle {
    text-align: center;
    color: #666;
    margin-bottom: 40px;
}

/* Category Filter */
.category-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.category-item {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.category-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    border-color: #4a90e2;
}

.category-item.active {
    border-color: #4a90e2;
    background: #f0f7ff;
}

.category-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #4a90e2;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.category-info {
    flex: 1;
}

.category-info h3 {
    color: #333;
    margin-bottom: 5px;
    font-size: 1.2rem;
}

.category-info p {
    font-size: 0.9rem;
    color: #666;
}

.category-count {
    background: #f0f7ff;
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 600;
    color: #4a90e2;
}

/* Category Products */
.category-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #eee;
}

.category-header h2 {
    color: #4a90e2;
    font-size: 2rem;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.category-header p {
    color: #666;
    font-size: 1.1rem;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 25px;
}

.product-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
    position: relative;
}

.product-card:hover {
    transform: translateY(-5px);
}

.product-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #ff6b6b;
    color: white;
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 0.8rem;
    z-index: 1;
}

.product-img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.product-info {
    padding: 20px;
}

.product-title {
    margin-bottom: 5px;
    color: #333;
    font-size: 1.1rem;
}

.product-category {
    display: inline-block;
    background: #f0f7ff;
    color: #4a90e2;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 0.8rem;
    margin-bottom: 10px;
}

.product-price {
    color: #4a90e2;
    font-size: 1.2rem;
    font-weight: bold;
    margin-bottom: 10px;
}

.product-stock {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.product-actions {
    display: flex;
    gap: 10px;
}

.btn-view, .btn-cart {
    flex: 1;
    padding: 8px 15px;
    border-radius: 5px;
    text-decoration: none;
    text-align: center;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.btn-view {
    background: #4a90e2;
    color: white;
}

.btn-view:hover {
    background: #357ae8;
}

.btn-cart {
    background: #50c878;
    color: white;
    border: none;
    cursor: pointer;
}

.btn-cart:hover {
    background: #3cb371;
}

.no-products {
    text-align: center;
    padding: 50px;
    color: #666;
    grid-column: 1 / -1;
}

.no-products i {
    margin-bottom: 20px;
    color: #ddd;
}

/* Notification styles */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    background: white;
    border-radius: 5px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateX(150%);
    transition: transform 0.3s ease;
    z-index: 9999;
    min-width: 300px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification.show {
    transform: translateX(0);
}

.notification-success {
    border-left: 4px solid #50c878;
}

.notification-error {
    border-left: 4px solid #ff6b6b;
}

.notification-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #666;
    line-height: 1;
}

@media (max-width: 768px) {
    .category-list {
        grid-template-columns: 1fr;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
    
    .category-header h2 {
        font-size: 1.5rem;
        flex-direction: column;
        gap: 5px;
    }
}

@media (min-width: 769px) and (max-width: 1024px) {
    .category-list {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
</body>
</html>