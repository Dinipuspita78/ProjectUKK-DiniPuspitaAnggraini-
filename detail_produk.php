<?php
session_start();
require_once 'components/database.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Ambil ID produk dari URL
$product_id = intval($_GET['id'] ?? 0);

if ($product_id <= 0) {
    header('Location: produk.php');
    exit();
}

// Query produk
$query = "SELECT * FROM produk WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$product_result = mysqli_stmt_get_result($stmt);

if (!$product = mysqli_fetch_assoc($product_result)) {
    header('Location: produk.php');
    exit();
}

$page_title = $product['nama'];
include 'components/header_pengguna.php';

// Get related products
$related_query = "SELECT * FROM produk 
                 WHERE kategori = ? AND id != ? AND stok > 0 
                 ORDER BY RAND() LIMIT 4";
$stmt = mysqli_prepare($conn, $related_query);
mysqli_stmt_bind_param($stmt, "si", $product['kategori'], $product_id);
mysqli_stmt_execute($stmt);
$related_products = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Produk - <?php echo htmlspecialchars($product['nama']); ?> | MinShop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS tambahan untuk detail produk */
        :root {
            --primary: #4a6cf7;
            --primary-dark: #3a5bd9;
            --success: #28a745;
            --error: #dc3545;
            --warning: #ffc107;
            --radius: 8px;
        }
        
        /* Product Detail Section */
        .product-detail-section {
            padding: 30px 0;
            background-color: #f9f9f9;
            min-height: calc(100vh - 200px);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .product-detail-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin-bottom: 40px;
        }
        
        .product-detail-row {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            margin-bottom: 30px;
        }
        
        /* Product Images */
        .product-images {
            flex: 1;
            min-width: 300px;
        }
        
        .main-image {
            width: 100%;
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #eee;
        }
        
        .main-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            max-width: 100%;
            max-height: 100%;
        }
        
        .image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 16px;
        }
        
        .image-placeholder i {
            font-size: 64px;
            margin-bottom: 10px;
            color: #ccc;
        }
        
        /* Product Info */
        .product-info-detail {
            flex: 1;
            min-width: 300px;
        }
        
        .product-title-detail {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            line-height: 1.3;
        }
        
        .product-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .product-meta span {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #666;
        }
        
        .product-meta i {
            color: var(--primary);
        }
        
        .product-price-detail {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 25px;
        }
        
        /* Quantity Selector */
        .product-quantity {
            margin-bottom: 25px;
        }
        
        .product-quantity label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: #333;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .quantity-btn {
            width: 40px;
            height: 40px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 6px;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .quantity-btn:hover {
            background: #f5f5f5;
            border-color: var(--primary);
        }
        
        .quantity-selector input {
            width: 70px;
            height: 40px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
        }
        
        .quantity-selector input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .max-quantity {
            font-size: 13px;
            color: #888;
        }
        
        /* Product Actions */
        .product-actions-detail {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .btn-add-cart-detail {
            flex: 1;
            padding: 15px 20px;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .btn-add-cart-detail:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(106, 17, 203, 0.3);
        }
        
        .btn-add-cart-detail:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn-buy-now {
            flex: 1;
            padding: 15px 20px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-buy-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 87, 108, 0.3);
            color: white;
        }
        
        /* Share Section */
        .product-share {
            padding-top: 20px;
            border-top: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .product-share span {
            font-weight: 500;
            color: #333;
        }
        
        .product-share a {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .product-share a:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
        }
        
        /* Product Description */
        .product-description {
            padding: 25px;
            background: #f9f9f9;
            border-radius: 10px;
        }
        
        .product-description h3 {
            font-size: 20px;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .product-description h3 i {
            color: var(--primary);
        }
        
        .description-content {
            font-size: 15px;
            line-height: 1.6;
            color: #555;
            margin-bottom: 25px;
            white-space: pre-line;
        }
        
        .product-specs h4 {
            font-size: 18px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .product-specs ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .product-specs li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            display: flex;
        }
        
        .product-specs li:last-child {
            border-bottom: none;
        }
        
        .product-specs strong {
            min-width: 150px;
            color: #333;
        }
        
        /* Related Products */
        .related-products {
            padding: 40px 0;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
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
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
        }
        
        .product-img {
            width: 100%;
            height: 200px;
            object-fit: contain;
            background: #f5f5f5;
            padding: 15px;
        }
        
        .product-info {
            padding: 20px;
        }
        
        .product-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            height: 40px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .product-category {
            display: inline-block;
            padding: 4px 12px;
            background: #eef2ff;
            color: var(--primary);
            border-radius: 20px;
            font-size: 12px;
            margin-bottom: 10px;
        }
        
        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .btn-add-cart {
            display: block;
            width: 100%;
            padding: 10px;
            background: var(--primary);
            color: white;
            text-align: center;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-add-cart:hover {
            background: var(--primary-dark);
            color: white;
        }
        
        /* Out of Stock */
        .out-of-stock {
            padding: 20px;
            background: #fff3f3;
            border: 1px solid #ffcdd2;
            border-radius: 8px;
            color: var(--error);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .out-of-stock i {
            font-size: 20px;
        }
        
        .low-stock {
            padding: 10px 15px;
            background: #fff8e1;
            border: 1px solid #ffecb3;
            border-radius: 6px;
            color: var(--warning);
            font-size: 14px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .low-stock i {
            color: var(--warning);
        }
        
        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: var(--radius);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            transform: translateX(120%);
            transition: transform 0.3s ease;
            z-index: 10000;
            min-width: 300px;
            max-width: 400px;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification-success {
            border-left: 4px solid var(--success);
        }
        
        .notification-success i {
            color: var(--success);
        }
        
        .notification-error {
            border-left: 4px solid var(--error);
        }
        
        .notification-error i {
            color: var(--error);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .product-detail-row {
                flex-direction: column;
                gap: 30px;
            }
            
            .main-image {
                height: 300px;
            }
            
            .product-title-detail {
                font-size: 24px;
            }
            
            .product-price-detail {
                font-size: 28px;
            }
            
            .product-actions-detail {
                flex-direction: column;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
            
            .notification {
                min-width: 280px;
                left: 20px;
                right: 20px;
                transform: translateY(-120%);
            }
            
            .notification.show {
                transform: translateY(0);
            }
        }
        
        @media (max-width: 480px) {
            .product-detail-card {
                padding: 20px;
            }
            
            .main-image {
                height: 250px;
            }
            
            .product-meta {
                flex-direction: column;
                gap: 10px;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Product Detail Section -->
    <section class="product-detail-section">
        <div class="container">
            <div class="product-detail-card">
                <div class="product-detail-row">
                    <!-- Product Images -->
                    <div class="product-images">
                        <div class="main-image">
                            <?php
                            // Tentukan gambar produk
                            $image_folder = 'project images/';
                            $gambar_file = $product['gambar'];
                            
                            if (!empty($gambar_file) && file_exists($image_folder . $gambar_file)) {
                                $gambar_path = $image_folder . $gambar_file;
                                ?>
                                <img src="<?php echo $gambar_path; ?>" 
                                     alt="<?php echo htmlspecialchars($product['nama']); ?>" 
                                     id="mainImage">
                                <?php
                            } else {
                                ?>
                                <div class="image-placeholder">
                                    <i class="fas fa-image"></i>
                                    <p>Gambar tidak tersedia</p>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- Product Info -->
                    <div class="product-info-detail">
                        <h1 class="product-title-detail"><?php echo htmlspecialchars($product['nama']); ?></h1>
                        
                        <div class="product-meta">
                            <span class="product-category-detail">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['kategori']); ?>
                            </span>
                            <span class="product-stock-detail">
                                <i class="fas fa-box"></i> Stok: <?php echo htmlspecialchars($product['stok']); ?>
                            </span>
                            <span class="product-rating">
                                <i class="fas fa-star"></i> 4.5 (120 reviews)
                            </span>
                        </div>
                        
                        <div class="product-price-detail">
                            Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?>
                        </div>
                        
                        <?php if ($product['stok'] > 0): ?>
                            <?php if ($product['stok'] < 10): ?>
                            <div class="low-stock">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Stok hampir habis! Segera pesan sebelum kehabisan.
                            </div>
                            <?php endif; ?>
                            
                            <div class="product-quantity">
                                <label for="quantity">Jumlah:</label>
                                <div class="quantity-selector">
                                    <button type="button" class="quantity-btn minus">-</button>
                                    <input type="number" id="quantity" name="quantity" 
                                           value="1" min="1" max="<?php echo $product['stok']; ?>">
                                    <button type="button" class="quantity-btn plus">+</button>
                                </div>
                                <span class="max-quantity">Maks: <?php echo $product['stok']; ?> pcs</span>
                            </div>
                            
                            <div class="product-actions-detail">
                                <button class="btn-add-cart-detail" 
                                        data-product-id="<?php echo $product['id']; ?>"
                                        onclick="addToCart(this)">
                                    <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                                </button>
                                
                                <a href="checkout.php?product_id=<?php echo $product['id']; ?>&quantity=1" 
                                   class="btn-buy-now">
                                    <i class="fas fa-bolt"></i> Beli Sekarang
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="out-of-stock">
                                <i class="fas fa-times-circle"></i> 
                                Stok Habis. Produk ini saat ini tidak tersedia.
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-share">
                            <span>Bagikan:</span>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                               target="_blank" title="Share on Facebook">
                                <i class="fab fa-facebook"></i>
                            </a>
                            <a href="https://wa.me/?text=<?php echo urlencode('Lihat produk ini: ' . $product['nama'] . ' - ' . $_SERVER['REQUEST_URI']); ?>" 
                               target="_blank" title="Share on WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode($product['nama']); ?>&url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                               target="_blank" title="Share on Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" onclick="copyToClipboard(window.location.href); return false;" 
                               title="Copy Link">
                                <i class="fas fa-link"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Product Description -->
                <div class="product-description">
                    <h3><i class="fas fa-info-circle"></i> Deskripsi Produk</h3>
                    <div class="description-content">
                        <?php 
                        if (!empty($product['deskripsi'])) {
                            echo nl2br(htmlspecialchars($product['deskripsi']));
                        } else {
                            echo 'Tidak ada deskripsi tersedia untuk produk ini.';
                        }
                        ?>
                    </div>
                    
                    <div class="product-specs">
                        <h4>Spesifikasi:</h4>
                        <ul>
                            <li><strong>Kategori:</strong> <?php echo htmlspecialchars($product['kategori']); ?></li>
                            <li><strong>Stok Tersedia:</strong> <?php echo htmlspecialchars($product['stok']); ?> pcs</li>
                            <li><strong>Harga:</strong> Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?></li>
                            <li><strong>Ditambahkan:</strong> <?php echo date('d M Y', strtotime($product['created_at'] ?? 'now')); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Related Products -->
    <?php if (mysqli_num_rows($related_products) > 0): ?>
    <section class="related-products">
        <div class="container">
            <h2 class="section-title">Produk Terkait</h2>
            <div class="products-grid">
                <?php while ($related = mysqli_fetch_assoc($related_products)): 
                    $related_image_path = 'project images/' . $related['gambar'];
                    $related_image_src = (!empty($related['gambar']) && file_exists($related_image_path)) 
                        ? $related_image_path 
                        : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjVmNWY1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCxzYW5zLXNlcmlmIiBmb250LXNpemU9IjE0IiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+UHJvZHVrIEludmVudGFyaTwvdGV4dD48L3N2Zz4=';
                ?>
                <div class="product-card">
                    <img src="<?php echo $related_image_src; ?>" 
                         alt="<?php echo htmlspecialchars($related['nama']); ?>" 
                         class="product-img">
                    <div class="product-info">
                        <h3 class="product-title"><?php echo htmlspecialchars($related['nama']); ?></h3>
                        <span class="product-category"><?php echo htmlspecialchars($related['kategori']); ?></span>
                        <div class="product-price">Rp <?php echo number_format($related['harga'], 0, ',', '.'); ?></div>
                        <a href="detail_produk.php?id=<?php echo $related['id']; ?>" class="btn-add-cart">
                            <i class="fas fa-eye"></i> Lihat Detail
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Quantity selector
        const quantityInput = document.getElementById('quantity');
        const minusBtn = document.querySelector('.quantity-btn.minus');
        const plusBtn = document.querySelector('.quantity-btn.plus');
        
        if (minusBtn && plusBtn && quantityInput) {
            const maxQuantity = parseInt(quantityInput.max) || 10;
            
            minusBtn.addEventListener('click', function() {
                let value = parseInt(quantityInput.value);
                if (value > 1) {
                    quantityInput.value = value - 1;
                }
            });
            
            plusBtn.addEventListener('click', function() {
                let value = parseInt(quantityInput.value);
                if (value < maxQuantity) {
                    quantityInput.value = value + 1;
                }
            });
            
            quantityInput.addEventListener('change', function() {
                let value = parseInt(this.value);
                if (isNaN(value) || value < 1) {
                    this.value = 1;
                } else if (value > maxQuantity) {
                    this.value = maxQuantity;
                }
            });
        }
    });
    
    // Function untuk menambahkan ke keranjang
    function addToCart(button) {
        const productId = button.dataset.productId;
        const quantityInput = document.getElementById('quantity');
        const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
        
        // Disable button sementara
        button.disabled = true;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menambahkan...';
        
        // Kirim request AJAX ke file tambah_keranjang.php
        fetch('components/tambah_keranjang.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&quantity=${quantity}&action=add_with_qty`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showNotification(data.message || '✓ Produk berhasil ditambahkan ke keranjang!', 'success');
                
                // Update cart count di header
                updateCartCount(data.cart_count);
            } else {
                showNotification(data.message || '✗ Terjadi kesalahan saat menambahkan ke keranjang', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('✗ Terjadi kesalahan koneksi. Coba lagi nanti.', 'error');
        })
        .finally(() => {
            // Enable button kembali setelah 1 detik
            setTimeout(() => {
                button.disabled = false;
                button.innerHTML = originalText;
            }, 1000);
        });
    }
    
    // Function untuk update cart count
    function updateCartCount(count) {
        // Cari semua elemen dengan class cart-count
        const cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(element => {
            if (count !== undefined) {
                element.textContent = count;
            } else {
                // Jika count tidak dikirim, tambah 1
                const currentCount = parseInt(element.textContent) || 0;
                const quantityInput = document.getElementById('quantity');
                const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
                element.textContent = currentCount + quantity;
            }
        });
    }
    
    // Function untuk show notification
    function showNotification(message, type) {
        // Hapus notifikasi lama
        const oldNotifications = document.querySelectorAll('.notification');
        oldNotifications.forEach(n => n.remove());
        
        // Buat notifikasi baru
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;color:#666;cursor:pointer;">
                <i class="fas fa-times"></i>
            </button>
        `;
        document.body.appendChild(notification);
        
        // Animasi masuk
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Auto hapus setelah 5 detik
        setTimeout(() => {
            if (notification.parentElement) {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 300);
            }
        }, 5000);
    }
    
    // Function untuk copy link ke clipboard
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('✓ Link berhasil disalin!', 'success');
        }).catch(err => {
            console.error('Gagal menyalin: ', err);
            showNotification('✗ Gagal menyalin link', 'error');
        });
    }
    
    // Share functionality
    document.querySelectorAll('.product-share a').forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.getAttribute('href') === '#') {
                e.preventDefault();
            }
            
            // Untuk copy link, sudah ditangani di onclick
            if (this.title === 'Copy Link') {
                return;
            }
            
            // Untuk social media, buka di tab baru
            if (this.target === '_blank') {
                const width = 600;
                const height = 400;
                const left = (screen.width - width) / 2;
                const top = (screen.height - height) / 2;
                window.open(this.href, 'share', 
                    `width=${width},height=${height},left=${left},top=${top}`);
                e.preventDefault();
            }
        });
    });
    </script>

</body>
</html>