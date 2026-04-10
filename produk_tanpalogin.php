<?php
// produk.php
session_start();
require_once 'components/database.php';

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

// Query produk
$query = "SELECT * FROM produk WHERE stok > 0";
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $query .= " AND (nama LIKE '%$search%' OR deskripsi LIKE '%$search%' OR kategori LIKE '%$search%')";
}
if (!empty($category)) {
    $category = mysqli_real_escape_string($conn, $category);
    $query .= " AND kategori = '$category'";
}
$query .= " ORDER BY created_at DESC";

$result = mysqli_query($conn, $query);

// Include header berdasarkan status login
$page_title = "Produk";
if (isset($_SESSION['user_id'])) {

    include 'components/header_guest.php';
}
?>
<link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<div class="container">
    <h1>Produk Kami</h1>
    
    <!-- Filter -->
    <div class="filter-section">
        <form method="GET" action="produk.php">
            <input type="text" name="search" placeholder="Cari produk..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="category">
                <option value="">Semua Kategori</option>
                <?php
                $categories = mysqli_query($conn, "SELECT DISTINCT kategori FROM produk WHERE kategori IS NOT NULL");
                while ($cat = mysqli_fetch_assoc($categories)):
                ?>
                <option value="<?php echo $cat['kategori']; ?>" <?php echo $category == $cat['kategori'] ? 'selected' : ''; ?>>
                    <?php echo $cat['kategori']; ?>
                </option>
                <?php endwhile; ?>
            </select>
            <button type="submit">Filter</button>
        </form>
    </div>
    
    <!-- Daftar Produk -->
    <div class="products-grid">
        <?php while ($product = mysqli_fetch_assoc($result)): ?>
        <div class="product-card">
            <img src="project images/<?php echo htmlspecialchars($product['gambar']); ?>" 
                 alt="<?php echo htmlspecialchars($product['nama']); ?>">
            <div class="product-info">
                <h3><?php echo htmlspecialchars($product['nama']); ?></h3>
                <span class="category"><?php echo htmlspecialchars($product['kategori']); ?></span>
                <div class="price">Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?></div>
                <div class="product-actions">
                    <a href="detail_produk.php?id=<?php echo $product['id']; ?>" class="btn">Lihat Detail</a>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button class="btn btn-cart add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                            <i class="fas fa-cart-plus"></i> Tambah
                        </button>
                    <?php else: ?>
                        <a href="login.php?redirect=detail_produk.php?id=<?php echo $product['id']; ?>" 
                           class="btn btn-cart login-required">
                            <i class="fas fa-cart-plus"></i> Tambah
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<?php include 'components/footer.php'; ?>

<script>
// Handle add to cart
document.addEventListener('DOMContentLoaded', function() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (!<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
                window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
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
                    alert(data.message);
                    // Update cart count
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    }
                } else {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        alert(data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan');
            });
        });
    });
});
</script>