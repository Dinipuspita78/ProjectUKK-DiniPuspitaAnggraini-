<?php

require_once 'components/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user info
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);

// PERBAIKAN: Ambil dari SESSION bukan database
$items = [];
$total = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    // Filter cart items untuk user ini
    foreach ($_SESSION['cart'] as $item) {
        if (isset($item['user_id']) && $item['user_id'] == $user_id) {
            // Ambil info produk dari database untuk harga terkini
            $product_id = $item['product_id'];
            $product_query = mysqli_query($conn, "SELECT * FROM produk WHERE id = $product_id");
            
            if ($product = mysqli_fetch_assoc($product_query)) {
                $item_detail = [
                    'product_id' => $product_id,
                    'nama' => $product['nama'],
                    'harga' => $product['harga'],
                    'gambar' => $product['gambar'],
                    'stok' => $product['stok'],
                    'jumlah' => $item['quantity'],
                    'product_name' => $product['nama'], // alias
                    'produk_id' => $product_id, // alias
                    'gambar' => $product['gambar'] // alias
                ];
                
                $items[] = $item_detail;
                $total += ($product['harga'] * $item['quantity']);
            }
        }
    }
}

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = mysqli_real_escape_string($conn, $_POST['shipping_address']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    
    // Validasi stok sebelum checkout
    $stock_valid = true;
    $stock_errors = [];
    
    foreach ($items as $item) {
        if ($item['stok'] < $item['jumlah']) {
            $stock_valid = false;
            $stock_errors[] = $item['nama'] . " (stok tersedia: " . $item['stok'] . ")";
        }
    }
    
    if (!$stock_valid) {
        $error = "Stok tidak mencukupi untuk produk berikut:<br>" . implode("<br>", $stock_errors);
    } else {
        // Mulai transaction
        mysqli_begin_transaction($conn);
        
        try {
            // checkout.php - tambahkan kode ini
// ...

// Cari kurir tersedia
$query_kurir = "SELECT k.id FROM kurir k 
                WHERE k.status = 'aktif' 
                ORDER BY (
                    SELECT COUNT(*) FROM orders o 
                    WHERE o.kurir_id = k.id AND o.status IN ('dikirim', 'pending')
                ) ASC 
                LIMIT 1";

$result_kurir = mysqli_query($conn, $query_kurir);
if ($row_kurir = mysqli_fetch_assoc($result_kurir)) {
    $kurir_id = $row_kurir['id'];
} else {
    $kurir_id = NULL;
}

// Insert order dengan kurir_id
$query = "INSERT INTO orders (user_id, alamat_pengiriman, kurir_id, status, total_harga) 
          VALUES (?, ?, ?, 'menunggu_kurir', ?)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "issi", $user_id, $alamat, $kurir_id, $total_harga);

// ...
            // Buat order baru
            $order_query = "INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, notes, status, created_at) 
                           VALUES ($user_id, $total, '$shipping_address', '$payment_method', '$notes', 'pending', NOW())";
            
            if (mysqli_query($conn, $order_query)) {
                $order_id = mysqli_insert_id($conn);
                
                // Tambahkan order items
                foreach ($items as $item) {
                    $product_id = $item['product_id'];
                    $quantity = $item['jumlah'];
                    $price = $item['harga'];
                    
                    $order_item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                                        VALUES ($order_id, $product_id, $quantity, $price)";
                    mysqli_query($conn, $order_item_query);
                    
                    // Update stok produk
                    $update_stock_query = "UPDATE produk SET stok = stok - $quantity WHERE id = $product_id";
                    mysqli_query($conn, $update_stock_query);
                }
                
                // Hapus dari session cart (BUKAN dari database)
                if (isset($_SESSION['cart'])) {
                    $new_cart = [];
                    foreach ($_SESSION['cart'] as $cart_item) {
                        if (!isset($cart_item['user_id']) || $cart_item['user_id'] != $user_id) {
                            $new_cart[] = $cart_item;
                        }
                    }
                    $_SESSION['cart'] = $new_cart;
                }
                
                mysqli_commit($conn);
                
                $_SESSION['order_success'] = true;
                $_SESSION['order_id'] = $order_id;
                header('Location: order_confirmation.php?id=' . $order_id);
                exit();
            } else {
                throw new Exception("Gagal membuat pesanan: " . mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Terjadi kesalahan saat memproses pesanan: " . $e->getMessage();
        }
    }
}

$page_title = "Checkout";
include 'components/header_pengguna.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - MinShop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4a6cf7;
            --primary-dark: #3a5bd9;
            --success: #28a745;
            --error: #dc3545;
            --radius: 8px;
        }
        
        .checkout-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
        }
        
        .checkout-section {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        
        .checkout-section h2 {
            color: var(--primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .address-item {
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: var(--radius);
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .address-item:hover {
            border-color: var(--primary);
            background-color: #f0f7ff;
        }
        
        .address-item.selected {
            border-color: var(--primary);
            background-color: #e6f2ff;
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .payment-method {
            text-align: center;
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-method:hover {
            border-color: var(--primary);
        }
        
        .payment-method.selected {
            border-color: var(--primary);
            background-color: #e6f2ff;
        }
        
        .payment-method i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .order-summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-summary-total {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            font-size: 1.2rem;
            font-weight: bold;
            border-top: 2px solid var(--primary);
            margin-top: 1rem;
        }
        
        .product-checkout {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .product-checkout-image {
            width: 80px;
            height: 80px;
            object-fit: contain;
            background: #f5f5f5;
            border-radius: var(--radius);
            margin-right: 1rem;
            padding: 5px;
        }
        
        .product-checkout-info {
            flex: 1;
        }
        
        .product-checkout-price {
            font-weight: bold;
            color: var(--primary);
        }
        
        .empty-cart-message {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-cart-message i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-checkout {
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-checkout:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-checkout:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: var(--radius);
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 1.5rem 0;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: var(--radius);
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <!-- Left Column: Checkout Form -->
        <div class="checkout-left">
            <!-- Tampilkan error jika ada -->
            <?php if (isset($error)): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-triangle"></i> 
                <div><?php echo $error; ?></div>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="checkoutForm">
                <!-- Shipping Address -->
                <div class="checkout-section">
                    <h2><i class="fas fa-map-marker-alt"></i> Alamat Pengiriman</h2>
                    
                    <div class="address-item selected">
                        <input type="radio" name="shipping_address" value="<?php echo htmlspecialchars($user['alamat'] ?? $user['address'] ?? ''); ?>" 
                               checked hidden id="address1">
                        <label for="address1" style="cursor: pointer; width: 100%;">
                            <h4 style="margin-bottom: 0.5rem;">Alamat Utama</h4>
                            <p style="margin-bottom: 0.5rem;"><strong><?php echo htmlspecialchars($user['nama'] ?? $user['name'] ?? 'User'); ?></strong></p>
                            <p style="color: #666; margin: 0;"><?php echo htmlspecialchars($user['alamat'] ?? $user['address'] ?? 'Alamat belum diisi'); ?></p>
                            <p style="color: #666; margin: 0.5rem 0 0;"><?php echo htmlspecialchars($user['telepon'] ?? $user['phone'] ?? ''); ?></p>
                        </label>
                    </div>
                    
                    <!-- Alternative: Add new address -->
                    <div class="address-item" id="newAddressToggle" style="text-align: center; cursor: pointer;">
                        <i class="fas fa-plus"></i> Gunakan Alamat Lain
                    </div>
                    
                    <!-- New Address Form (hidden by default) -->
                    <div id="newAddressForm" style="display: none; margin-top: 1rem;">
                        <div class="form-group">
                            <label for="new_name">Nama Penerima</label>
                            <input type="text" id="new_name" name="new_name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="new_phone">Nomor Telepon</label>
                            <input type="tel" id="new_phone" name="new_phone" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="new_address">Alamat Lengkap</label>
                            <textarea id="new_address" name="new_address" rows="3" class="form-control"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div class="checkout-section">
                    <h2><i class="fas fa-credit-card"></i> Metode Pembayaran</h2>
                    
                    <div class="payment-methods">
                        <div class="payment-method selected" data-method="transfer">
                            <input type="radio" name="payment_method" value="transfer" checked hidden>
                            <i class="fas fa-university"></i>
                            <h4>Transfer Bank</h4>
                            <p>BCA, Mandiri, BNI</p>
                        </div>
                        
                        <div class="payment-method" data-method="cod">
                            <input type="radio" name="payment_method" value="cod" hidden>
                            <i class="fas fa-money-bill-wave"></i>
                            <h4>COD</h4>
                            <p>Bayar di Tempat</p>
                        </div>
                    </div>
                    
                    <!-- Payment Instructions -->
                    <div id="paymentInstructions" style="margin-top: 1rem; padding: 1rem; background-color: #f0f7ff; border-radius: 8px;">
                        <h4>Instruksi Transfer Bank:</h4>
                        <p>1. Transfer ke rekening BCA: 1234567890 (MinShop)</p>
                        <p>2. Jumlah transfer: <strong>Rp <?php echo number_format($total + 6000, 0, ',', '.'); ?></strong></p>
                        <p>3. Upload bukti transfer setelah checkout</p>
                    </div>
                </div>
                
                
                <!-- Order Notes -->
                <div class="checkout-section">
                    <h2><i class="fas fa-sticky-note"></i> Catatan Pesanan (Opsional)</h2>
                    <div class="form-group">
                        <textarea name="notes" rows="3" placeholder="Contoh: Tolong datang siang hari, atau catatan khusus lainnya..." class="form-control"></textarea>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Right Column: Order Summary -->
        <div class="checkout-right">
            <div class="checkout-section" style="position: sticky; top: 20px;">
                <h2><i class="fas fa-receipt"></i> Ringkasan Pesanan</h2>
                
                <!-- Products List -->
                <div style="max-height: 300px; overflow-y: auto; margin-bottom: 1rem;">
                    <?php if (empty($items)): ?>
                        <div class="empty-cart-message">
                            <i class="fas fa-shopping-cart"></i>
                            <p>Keranjang kosong</p>
                            <a href="produk.php" style="display: inline-block; margin-top: 10px; color: var(--primary);">Mulai Belanja</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($items as $item): 
                            $image_path = 'project images/' . $item['gambar'];
                            $image_src = (!empty($item['gambar']) && file_exists($image_path)) 
                                ? $image_path 
                                : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwsc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxMCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPlByb2R1azwvdGV4dD48L3N2Zz4=';
                        ?>
                        <div class="product-checkout">
                            <img src="<?php echo $image_src; ?>" 
                                 alt="<?php echo htmlspecialchars($item['nama']); ?>" 
                                 class="product-checkout-image"
                                 onerror="this.src='<?php echo $image_src; ?>'">
                            <div class="product-checkout-info">
                                <h4 style="margin-bottom: 0.5rem; font-size: 14px;"><?php echo htmlspecialchars($item['nama']); ?></h4>
                                <div style="display: flex; justify-content: space-between; font-size: 13px;">
                                    <span>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?> × <?php echo $item['jumlah']; ?></span>
                                    <span class="product-checkout-price">
                                        Rp <?php echo number_format($item['harga'] * $item['jumlah'], 0, ',', '.'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Order Summary -->
                <div class="order-summary">
                    <div class="order-summary-item">
                        <span>Subtotal</span>
                        <span>Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                    </div>
                    <div class="order-summary-item">
                        <span>Biaya Pengiriman</span>
                        <span>Rp 5.000</span>
                    </div>
                    <div class="order-summary-item">
                        <span>Biaya Layanan</span>
                        <span>Rp 1.000</span>
                    </div>
                    <div class="order-summary-total">
                        <span>Total Pembayaran</span>
                        <span style="color: var(--primary);">
                            Rp <?php echo number_format($total + 6000, 0, ',', '.'); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Terms and Conditions -->
                <div class="terms-checkbox">
                    <input type="checkbox" id="terms" required style="margin-top: 0.3rem;">
                    <label for="terms" style="font-size: 0.9rem;">
                        Saya menyetujui <a href="syarat.php" style="color: var(--primary);" target="_blank">Syarat dan Ketentuan</a> 
                        serta <a href="privasi.php" style="color: var(--primary);" target="_blank">Kebijakan Privasi</a> MinShop
                    </label>
                </div>
                
                <!-- Checkout Button -->
                <button type="submit" form="checkoutForm" class="btn-checkout" <?php echo empty($items) ? 'disabled' : ''; ?>>
                    <i class="fas fa-lock"></i> 
                    <?php echo empty($items) ? 'Keranjang Kosong' : 'Bayar Sekarang'; ?>
                </button>
                
                <!-- Continue Shopping -->
                <a href="produk.php" style="display: block; text-align: center; margin-top: 1rem; color: var(--primary); text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Lanjutkan Belanja
                </a>
            </div>
            
            <!-- Security Info -->
            <div style="text-align: center; margin-top: 1rem; font-size: 0.9rem; color: #666;">
                <p><i class="fas fa-shield-alt" style="color: var(--success);"></i> Transaksi Anda aman dan terenkripsi</p>
            </div>
        </div>
    </div>
    
    <?php include 'components/footer.php'; ?>
    
    <script>
    // Toggle new address form
    document.getElementById('newAddressToggle')?.addEventListener('click', function() {
        const form = document.getElementById('newAddressForm');
        if (form.style.display === 'none' || !form.style.display) {
            form.style.display = 'block';
            this.style.display = 'none';
        }
    });
    
    // Payment method selection
    document.querySelectorAll('.payment-method').forEach(method => {
        method.addEventListener('click', function() {
            // Remove selected class from all
            document.querySelectorAll('.payment-method').forEach(m => {
                m.classList.remove('selected');
                m.querySelector('input[type="radio"]').checked = false;
            });
            
            // Add selected class to clicked
            this.classList.add('selected');
            this.querySelector('input[type="radio"]').checked = true;
        });
    });
    
    
    // Form validation
    document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
        const terms = document.getElementById('terms');
        
        if (!terms.checked) {
            e.preventDefault();
            alert('Harap setujui Syarat dan Ketentuan terlebih dahulu');
            terms.focus();
            return false;
        }
        
        // Show loading
        const submitBtn = document.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            submitBtn.disabled = true;
        }
    });
    </script>
</body>
</html>