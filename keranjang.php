<?php
ob_start();
session_start();
require_once 'components/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$page_title = "Keranjang Belanja";
include 'components/header_pengguna.php';

// Ambil user ID
$user_id = $_SESSION['user_id'];

// Debug: Tampilkan isi session cart
// echo "<pre>Session Cart: "; print_r($_SESSION['cart'] ?? []); echo "</pre>";

// Inisialisasi keranjang
$cart_items = [];
$total_price = 0;

// Jika ada session cart, filter berdasarkan user_id
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $key => $item) {
        // Pastikan item memiliki struktur yang lengkap
        if (isset($item['user_id']) && $item['user_id'] == $user_id &&
            isset($item['product_id']) && isset($item['quantity']) &&
            isset($item['product_price']) && isset($item['product_name'])) {
            
            $cart_items[$key] = $item;
            $total_price += ($item['product_price'] * $item['quantity']);
        } else {
            // Hapus item yang tidak valid
            unset($_SESSION['cart'][$key]);
        }
    }
    
    // Re-index array setelah penghapusan
    if (!empty($cart_items)) {
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }
}

// Handle remove item dengan konfirmasi JavaScript
if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    $user_id = $_SESSION['user_id'];
    
    if (isset($_SESSION['cart'])) {
        $removed = false;
        foreach ($_SESSION['cart'] as $key => $item) {
            if (isset($item['user_id']) && $item['user_id'] == $user_id && 
                isset($item['product_id']) && $item['product_id'] == $remove_id) {
                
                unset($_SESSION['cart'][$key]);
                $_SESSION['cart'] = array_values($_SESSION['cart']);
                $removed = true;
                break;
            }
        }
        
        if ($removed) {
            // Redirect dengan parameter removed
            header('Location: keranjang.php?removed=1');
            exit();
        }
    }
}

// Handle update quantity
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_cart'])) {
    if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {
        foreach ($_POST['quantity'] as $product_id => $quantity) {
            $product_id = intval($product_id);
            $quantity = intval($quantity);
            
            if ($quantity <= 0) {
                // Remove item if quantity is 0 or negative
                foreach ($_SESSION['cart'] as $key => $item) {
                    if (isset($item['product_id']) && $item['product_id'] == $product_id && 
                        isset($item['user_id']) && $item['user_id'] == $user_id) {
                        unset($_SESSION['cart'][$key]);
                    }
                }
            } else {
                // Update quantity
                foreach ($_SESSION['cart'] as &$item) {
                    if (isset($item['product_id']) && $item['product_id'] == $product_id && 
                        isset($item['user_id']) && $item['user_id'] == $user_id) {
                        
                        // Cek stok di database
                        $stmt = mysqli_prepare($conn, "SELECT stok FROM produk WHERE id = ?");
                        mysqli_stmt_bind_param($stmt, "i", $product_id);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        $product = mysqli_fetch_assoc($result);
                        
                        if ($product && $quantity <= $product['stok']) {
                            $item['quantity'] = $quantity;
                        } else {
                            $_SESSION['error'] = "Stok tidak mencukupi untuk produk ID: $product_id";
                        }
                        break;
                    }
                }
            }
        }
        
        // Re-index array
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        
        // Recalculate total
        $cart_items = [];
        $total_price = 0;
        foreach ($_SESSION['cart'] as $item) {
            if (isset($item['user_id']) && $item['user_id'] == $user_id) {
                $cart_items[] = $item;
                if (isset($item['product_price']) && isset($item['quantity'])) {
                    $total_price += ($item['product_price'] * $item['quantity']);
                }
            }
        }
        
        header('Location: keranjang.php?updated=1');
        exit();
    }
}
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja | MinShop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4a6cf7;
            --primary-dark: #3a5bd9;
            --success: #28a745;
            --error: #dc3545;
            --warning: #ffc107;
            --radius: 8px;
        }
        
        .cart-section {
            padding: 40px 0;
            min-height: 70vh;
            background-color: #f9f9f9;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        
        /* Empty Cart */
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: var(--radius);
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }
        
        .empty-cart i {
            font-size: 64px;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        .empty-cart h2 {
            font-size: 24px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .empty-cart p {
            color: #888;
            margin-bottom: 30px;
        }
        
        .btn-primary {
            display: inline-block;
            padding: 12px 30px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: var(--radius);
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        /* Cart with Items */
        .cart-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        
        .cart-items {
            flex: 1;
            min-width: 300px;
        }
        
        .cart-summary {
            width: 300px;
            background: white;
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            position: sticky;
            top: 20px;
        }
        
        .cart-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .cart-item {
            display: flex;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-item-image {
            width: 120px;
            height: 120px;
            background: #f5f5f5;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            flex-shrink: 0;
        }
        
        .cart-item-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .cart-item-info {
            flex: 1;
        }
        
        .cart-item-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .cart-item-category {
            display: inline-block;
            padding: 3px 10px;
            background: #eef2ff;
            color: var(--primary);
            border-radius: 12px;
            font-size: 12px;
            margin-bottom: 10px;
        }
        
        .cart-item-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .cart-item-quantity {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .qty-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .qty-btn:hover {
            background: #f5f5f5;
            border-color: var(--primary);
        }
        
        .qty-input {
            width: 50px;
            height: 30px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .cart-item-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-remove {
            color: var(--error);
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-remove:hover {
            text-decoration: underline;
        }
        
        /* Cart Summary */
        .summary-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            color: #666;
        }
        
        .summary-total {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: 700;
            color: #333;
            padding-top: 15px;
            border-top: 1px solid #eee;
            margin-top: 15px;
        }
        
        .checkout-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: var(--success);
            color: white;
            text-align: center;
            border: none;
            border-radius: var(--radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .checkout-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .continue-shopping {
            display: inline-block;
            width: 100%;
            padding: 10px;
            text-align: center;
            color: var(--primary);
            text-decoration: none;
            margin-top: 10px;
            font-weight: 500;
        }
        
        .continue-shopping:hover {
            text-decoration: underline;
        }
        
        /* Notification */
        .notification {
            padding: 15px 20px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: var(--radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .notification-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .notification i {
            font-size: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .cart-container {
                flex-direction: column;
            }
            
            .cart-summary {
                width: 100%;
                position: static;
            }
            
            .cart-item {
                flex-direction: column;
            }
            
            .cart-item-image {
                width: 100%;
                height: 200px;
                margin-right: 0;
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <section class="cart-section">
        <div class="container">
            <h1 class="page-title">Keranjang Belanja</h1>
            
            <?php if (isset($_SESSION['error'])): ?>
            <div class="notification notification-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['removed'])): ?>
            <div class="notification">
                <i class="fas fa-check-circle"></i>
                Produk berhasil dihapus dari keranjang
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['updated'])): ?>
            <div class="notification">
                <i class="fas fa-check-circle"></i>
                Keranjang berhasil diperbarui
            </div>
            <?php endif; ?>
            
            <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Keranjang Belanja Kosong</h2>
                <p>Tambahkan produk ke keranjang untuk melanjutkan belanja</p>
                <a href="produk.php" class="btn-primary">
                    <i class="fas fa-shopping-bag"></i> Mulai Belanja
                </a>
            </div>
            <?php else: ?>
            <form method="POST" action="keranjang.php" id="cartForm">
                <div class="cart-container">
                    <div class="cart-items">
                        <?php foreach ($cart_items as $key => $item): 
                            $image_path = 'project images/' . $item['product_image'];
                            $image_src = (!empty($item['product_image']) && file_exists($image_path)) 
                                ? $image_path 
                                : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjVmNWY1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCxzYW5zLXNlcmlmIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+Tm8gSW1hZ2U8L3RleHQ+PC9zdmc+';
                        ?>
                        <div class="cart-card">
                            <div class="cart-item">
                                <div class="cart-item-image">
                                    <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                </div>
                                
                                <div class="cart-item-info">
                                    <h3 class="cart-item-title"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                    <div class="cart-item-price">
                                        Rp <?php echo number_format($item['product_price'], 0, ',', '.'); ?>
                                    </div>
                                    
                                    <div class="cart-item-quantity">
                                        <label>Jumlah:</label>
                                        <div class="quantity-control">
                                            <button type="button" class="qty-btn minus" data-id="<?php echo $item['product_id']; ?>">-</button>
                                            <input type="number" 
                                                   name="quantity[<?php echo $item['product_id']; ?>]" 
                                                   class="qty-input" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" 
                                                   max="99"
                                                   data-original="<?php echo $item['quantity']; ?>">
                                            <button type="button" class="qty-btn plus" data-id="<?php echo $item['product_id']; ?>">+</button>
                                        </div>
                                        <span style="margin-left: 10px; color: #666;">
                                            Total: Rp <?php echo number_format($item['product_price'] * $item['quantity'], 0, ',', '.'); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="cart-item-actions">
                                        <a href="keranjang.php?remove=<?php echo $item['product_id']; ?>" 
                                           class="btn-remove"
                                           onclick="return confirm('Hapus produk dari keranjang?')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div style="text-align: right; margin-top: 20px;">
                            <button type="submit" name="update_cart" class="btn-primary" id="updateBtn">
                                <i class="fas fa-sync-alt"></i> Perbarui Keranjang
                            </button>
                        </div>
                    </div>
                    
                    <div class="cart-summary">
                        <h3 class="summary-title">Ringkasan Belanja</h3>
                        
                        <div class="summary-item">
                            <span>Total Harga</span>
                            <span>Rp <?php echo number_format($total_price, 0, ',', '.'); ?></span>
                        </div>
                        
                        <div class="summary-item">
                            <span>Ongkos Kirim</span>
                            <span>Gratis</span>
                        </div>
                        
                        <div class="summary-item">
                            <span>Diskon</span>
                            <span>Rp 0</span>
                        </div>
                        
                        <div class="summary-total">
                            <span>Total Bayar</span>
                            <span>Rp <?php echo number_format($total_price, 0, ',', '.'); ?></span>
                        </div>
                        
                        <a href="checkout.php" class="checkout-btn">
                            <i class="fas fa-credit-card"></i> Lanjut ke Pembayaran
                        </a>
                        
                        <a href="produk.php" class="continue-shopping">
                            <i class="fas fa-arrow-left"></i> Lanjutkan Belanja
                        </a>
                    </div>
                </div>
            </form>
            
            <?php endif; ?>
        </div>
    </section>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Quantity controls
        document.querySelectorAll('.qty-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.dataset.id;
                const isMinus = this.classList.contains('minus');
                const input = this.parentElement.querySelector('.qty-input');
                
                let value = parseInt(input.value) || 1;
                
                if (isMinus) {
                    if (value > 1) {
                        input.value = value - 1;
                    }
                } else {
                    if (value < 99) {
                        input.value = value + 1;
                    }
                }
                
                // Trigger change event
                input.dispatchEvent(new Event('change'));
            });
        });
        
        // Validate quantity inputs
        document.querySelectorAll('.qty-input').forEach(input => {
            input.addEventListener('change', function() {
                let value = parseInt(this.value);
                const min = parseInt(this.min) || 1;
                const max = parseInt(this.max) || 99;
                
                if (isNaN(value) || value < min) {
                    this.value = min;
                } else if (value > max) {
                    this.value = max;
                }
            });
        });
        
        // Prevent form resubmission on refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Disable update button after click to prevent double submission
        document.getElementById('cartForm')?.addEventListener('submit', function() {
            const updateBtn = document.getElementById('updateBtn');
            if (updateBtn) {
                updateBtn.disabled = true;
                updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memperbarui...';
            }
        });
    });
    </script>
    
</body>
</html>