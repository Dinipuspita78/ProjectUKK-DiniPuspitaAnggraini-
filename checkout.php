<?php
session_start();
require_once 'components/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user info
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);

// Ambil items dari session cart
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
                    'jumlah' => $item['quantity']
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
    
    // Validasi alamat
    if (empty($shipping_address)) {
        $error = "Alamat pengiriman harus diisi!";
    }
    // Validasi stok sebelum checkout
    else {
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
            // Mulai transaction dengan try-catch
            try {
                mysqli_begin_transaction($conn);
                
                // Hitung total + biaya kirim + layanan
                $total_pembayaran = $total + 6000; // Subtotal + Ongkir 5000 + Biaya layanan 1000
                
                // Cari kurir tersedia (opsional)
                $kurir_id = 'NULL';
                $query_kurir = "SELECT id FROM kurir WHERE status = 'aktif' LIMIT 1";
                $result_kurir = mysqli_query($conn, $query_kurir);
                if ($row_kurir = mysqli_fetch_assoc($result_kurir)) {
                    $kurir_id = $row_kurir['id'];
                }
                
                // Buat order baru - GUNAKAN NAMA KOLOM YANG SESUAI DENGAN STRUKTUR TABEL
                $order_query = "INSERT INTO orders (user_id, total_amount, shipping_address, kurir_id, payment_method, notes, status, created_at) 
                               VALUES ($user_id, $total_pembayaran, '$shipping_address', $kurir_id, '$payment_method', '$notes', 'menunggu_kurir', NOW())";
                
                if (!mysqli_query($conn, $order_query)) {
                    throw new Exception("Gagal membuat pesanan: " . mysqli_error($conn));
                }
                
                $order_id = mysqli_insert_id($conn);
                
                // Tambahkan order items
                foreach ($items as $item) {
                    $product_id = $item['product_id'];
                    $quantity = $item['jumlah'];
                    $price = $item['harga'];
                    
                    $order_item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                                        VALUES ($order_id, $product_id, $quantity, $price)";
                    
                    if (!mysqli_query($conn, $order_item_query)) {
                        throw new Exception("Gagal menambahkan item pesanan: " . mysqli_error($conn));
                    }
                    
                    // Update stok produk
                    $update_stock_query = "UPDATE produk SET stok = stok - $quantity WHERE id = $product_id";
                    if (!mysqli_query($conn, $update_stock_query)) {
                        throw new Exception("Gagal mengupdate stok: " . mysqli_error($conn));
                    }
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
                
                // Redirect ke halaman bayar_pesanan
                header('Location: bayar_pesanan.php?id=' . $order_id);
                exit();
                
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = "Terjadi kesalahan saat memproses pesanan: " . $e->getMessage();
            }
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
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border-left: 4px solid #2e7d32;
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
        
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            display: none;
        }
        
        .loading.active {
            display: flex;
        }
        
        .loading-content {
            background: white;
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .loading i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Loading Spinner -->
    <div class="loading" id="loading">
        <div class="loading-content">
            <i class="fas fa-spinner fa-spin"></i>
            <h3>Memproses Pesanan...</h3>
            <p>Mohon tunggu sebentar</p>
        </div>
    </div>

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
            
            <!-- Tampilkan success jika ada -->
            <?php if (isset($_SESSION['order_success'])): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle"></i> 
                <div>Pesanan berhasil dibuat! Nomor pesanan: #<?php echo $_SESSION['order_id']; ?></div>
            </div>
            <?php unset($_SESSION['order_success']); ?>
            <?php endif; ?>
            
            <form method="POST" action="" id="checkoutForm">
                <!-- Shipping Address -->
                <div class="checkout-section">
                    <h2><i class="fas fa-map-marker-alt"></i> Alamat Pengiriman</h2>
                    
                    <?php if (!empty($user['alamat'])): ?>
                    <div class="address-item selected" id="existingAddress">
                        <input type="radio" name="shipping_address" value="<?php echo htmlspecialchars($user['alamat']); ?>" 
                               checked hidden id="address1">
                        <label for="address1" style="cursor: pointer; width: 100%;">
                            <h4 style="margin-bottom: 0.5rem;">Alamat Utama</h4>
                            <p style="margin-bottom: 0.5rem;"><strong><?php echo htmlspecialchars($user['nama'] ?? $user['name'] ?? 'User'); ?></strong></p>
                            <p style="color: #666; margin: 0;"><?php echo htmlspecialchars($user['alamat']); ?></p>
                            <p style="color: #666; margin: 0.5rem 0 0;"><?php echo htmlspecialchars($user['telepon'] ?? $user['phone'] ?? ''); ?></p>
                        </label>
                    </div>
                    <?php else: ?>
                    <div class="alert-error" style="margin-bottom: 1rem;">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>Anda belum mengatur alamat pengiriman. Silakan isi alamat di bawah ini.</div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Alternative: Add new address -->
                    <div class="address-item" id="newAddressToggle" style="text-align: center; cursor: pointer;">
                        <i class="fas fa-plus-circle"></i> Gunakan Alamat Lain
                    </div>
                    
                    <!-- New Address Form (hidden by default) -->
                    <div id="newAddressForm" style="display: none; margin-top: 1rem; padding: 1rem; background: #f9f9f9; border-radius: var(--radius);">
                        <h4 style="margin-bottom: 1rem; color: var(--primary);">Alamat Baru</h4>
                        <div class="form-group">
                            <label for="new_name">Nama Penerima</label>
                            <input type="text" id="new_name" name="new_name" class="form-control" placeholder="Masukkan nama penerima">
                        </div>
                        <div class="form-group">
                            <label for="new_phone">Nomor Telepon</label>
                            <input type="tel" id="new_phone" name="new_phone" class="form-control" placeholder="Contoh: 081234567890">
                        </div>
                        <div class="form-group">
                            <label for="new_address">Alamat Lengkap</label>
                            <textarea id="new_address" name="new_address" rows="3" class="form-control" placeholder="Masukkan alamat lengkap"></textarea>
                        </div>
                        <div style="display: flex; gap: 10px; margin-top: 10px;">
                            <button type="button" id="useNewAddressBtn" class="btn-checkout" style="padding: 0.7rem;">
                                <i class="fas fa-check"></i> Gunakan Alamat Ini
                            </button>
                            <button type="button" id="cancelNewAddressBtn" style="padding: 0.7rem 1rem; background: #f0f0f0; border: none; border-radius: var(--radius); cursor: pointer;">
                                Batal
                            </button>
                        </div>
                    </div>
                    
                    <!-- Hidden input untuk menyimpan alamat yang dipilih -->
                    <input type="hidden" name="shipping_address" id="selectedAddress" value="<?php echo htmlspecialchars($user['alamat'] ?? ''); ?>">
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
                        
                        <div class="payment-method" data-method="e-wallet">
                            <input type="radio" name="payment_method" value="e-wallet" hidden>
                            <i class="fas fa-mobile-alt"></i>
                            <h4>E-Wallet</h4>
                            <p>GoPay, OVO, Dana</p>
                        </div>
                    </div>
                    
                    <!-- Payment Instructions -->
                    <div id="paymentInstructions" style="margin-top: 1rem; padding: 1rem; background-color: #f0f7ff; border-radius: 8px;">
                        <h4 style="margin-bottom: 0.5rem; color: var(--primary);">
                            <i class="fas fa-info-circle"></i> Instruksi Transfer Bank:
                        </h4>
                        <p>1. Transfer ke rekening BCA: 1234567890 (MinShop)</p>
                        <p>2. Jumlah transfer: <strong>Rp <?php echo number_format($total + 6000, 0, ',', '.'); ?></strong></p>
                        <p>3. Upload bukti transfer setelah checkout</p>
                        <p style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
                            <i class="fas fa-clock"></i> Batas pembayaran: 24 jam
                        </p>
                    </div>
                </div>
                
                <!-- Order Notes -->
                <div class="checkout-section">
                    <h2><i class="fas fa-sticky-note"></i> Catatan Pesanan (Opsional)</h2>
                    <div class="form-group">
                        <textarea name="notes" rows="3" placeholder="Contoh: Tolong datang siang hari, atau catatan khusus lainnya..." class="form-control"></textarea>
                        <small style="color: #666;">Tambahkan catatan untuk penjual atau kurir</small>
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
                            <p>Keranjang belanja Anda kosong</p>
                            <a href="produk.php" style="display: inline-block; margin-top: 10px; padding: 10px 20px; background: var(--primary); color: white; text-decoration: none; border-radius: var(--radius);">
                                Mulai Belanja
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($items as $item): 
                            $image_path = 'project images/' . $item['gambar'];
                            $image_src = (!empty($item['gambar']) && file_exists($image_path)) 
                                ? $image_path 
                                : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwsc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPlByb2R1a2VuPC90ZXh0Pjwvc3ZnPg==';
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
                                <?php if ($item['stok'] < 10): ?>
                                <p style="color: #f57c00; font-size: 11px; margin-top: 5px;">
                                    <i class="fas fa-exclamation-triangle"></i> Stok tersisa: <?php echo $item['stok']; ?>
                                </p>
                                <?php endif; ?>
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
                        <span style="color: var(--primary); font-size: 1.3rem;">
                            Rp <?php echo number_format($total + 6000, 0, ',', '.'); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Terms and Conditions -->
                <div class="terms-checkbox">
                    <input type="checkbox" id="terms" required style="width: 18px; height: 18px; cursor: pointer;">
                    <label for="terms" style="font-size: 0.9rem; cursor: pointer;">
                        Saya menyetujui <a href="syarat.php" style="color: var(--primary); text-decoration: none;" target="_blank">Syarat dan Ketentuan</a> 
                        serta <a href="privasi.php" style="color: var(--primary); text-decoration: none;" target="_blank">Kebijakan Privasi</a> MinShop
                    </label>
                </div>
                
                <!-- Checkout Button -->
                <button type="submit" form="checkoutForm" class="btn-checkout" <?php echo empty($items) ? 'disabled' : ''; ?>>
                    <i class="fas fa-lock"></i> 
                    <?php echo empty($items) ? 'Keranjang Kosong' : 'Bayar Sekarang'; ?>
                </button>
                
                <!-- Continue Shopping -->
                <a href="produk.php" style="display: block; text-align: center; margin-top: 1rem; color: #666; text-decoration: none; font-size: 0.9rem;">
                    <i class="fas fa-arrow-left"></i> Lanjutkan Belanja
                </a>
            </div>
            
            <!-- Security Info -->
            <div style="text-align: center; margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: var(--radius);">
                <p style="margin: 0; color: #666; font-size: 0.9rem;">
                    <i class="fas fa-shield-alt" style="color: var(--success);"></i> Transaksi Anda aman dan terenkripsi
                </p>
                <p style="margin: 0.5rem 0 0; color: #999; font-size: 0.8rem;">
                    <i class="fas fa-clock"></i> Estimasi pengiriman: 1-3 hari kerja
                </p>
            </div>
        </div>
    </div>
    
    <?php include 'components/footer.php'; ?>
    
    <script>
    // Toggle loading spinner saat submit form
    document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
        const terms = document.getElementById('terms');
        const selectedAddress = document.getElementById('selectedAddress').value;
        
        // Validasi terms
        if (!terms.checked) {
            e.preventDefault();
            alert('Harap setujui Syarat dan Ketentuan terlebih dahulu');
            terms.focus();
            return false;
        }
        
        // Validasi alamat
        if (!selectedAddress || selectedAddress.trim() === '') {
            e.preventDefault();
            alert('Harap pilih atau isi alamat pengiriman terlebih dahulu');
            return false;
        }
        
        // Tampilkan loading
        document.getElementById('loading').classList.add('active');
        
        // Disable submit button
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
        }
        
        return true;
    });
    
    // Toggle new address form
    document.getElementById('newAddressToggle')?.addEventListener('click', function() {
        const form = document.getElementById('newAddressForm');
        form.style.display = 'block';
        this.style.display = 'none';
        
        // Unselect existing address
        const existingAddress = document.getElementById('existingAddress');
        if (existingAddress) {
            existingAddress.classList.remove('selected');
        }
    });
    
    // Cancel new address
    document.getElementById('cancelNewAddressBtn')?.addEventListener('click', function() {
        const form = document.getElementById('newAddressForm');
        form.style.display = 'none';
        
        const toggle = document.getElementById('newAddressToggle');
        toggle.style.display = 'block';
        
        // Reselect existing address
        const existingAddress = document.getElementById('existingAddress');
        if (existingAddress) {
            existingAddress.classList.add('selected');
            const addressValue = existingAddress.querySelector('input[type="radio"]')?.value || '';
            document.getElementById('selectedAddress').value = addressValue;
        }
    });
    
    // Use new address
    document.getElementById('useNewAddressBtn')?.addEventListener('click', function() {
        const newName = document.getElementById('new_name').value.trim();
        const newPhone = document.getElementById('new_phone').value.trim();
        const newAddress = document.getElementById('new_address').value.trim();
        
        if (!newName || !newPhone || !newAddress) {
            alert('Harap lengkapi semua data alamat');
            return;
        }
        
        // Format alamat lengkap
        const fullAddress = newName + ' - ' + newPhone + ' - ' + newAddress;
        document.getElementById('selectedAddress').value = fullAddress;
        
        // Hide form
        document.getElementById('newAddressForm').style.display = 'none';
        document.getElementById('newAddressToggle').style.display = 'block';
        
        // Show success message
        alert('Alamat baru berhasil dipilih');
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
            
            // Update payment instructions
            const methodValue = this.dataset.method;
            const instructions = document.getElementById('paymentInstructions');
            const total = <?php echo $total + 6000; ?>;
            
            if (methodValue === 'transfer') {
                instructions.innerHTML = `
                    <h4 style="margin-bottom: 0.5rem; color: var(--primary);">
                        <i class="fas fa-info-circle"></i> Instruksi Transfer Bank:
                    </h4>
                    <p>1. Transfer ke rekening BCA: 1234567890 (MinShop)</p>
                    <p>2. Jumlah transfer: <strong>Rp ${formatRupiah(total)}</strong></p>
                    <p>3. Upload bukti transfer setelah checkout</p>
                    <p style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
                        <i class="fas fa-clock"></i> Batas pembayaran: 24 jam
                    </p>
                `;
            } else if (methodValue === 'cod') {
                instructions.innerHTML = `
                    <h4 style="margin-bottom: 0.5rem; color: var(--primary);">
                        <i class="fas fa-info-circle"></i> Instruksi COD:
                    </h4>
                    <p>1. Bayar langsung saat kurir mengantarkan pesanan</p>
                    <p>2. Siapkan uang pas dengan total <strong>Rp ${formatRupiah(total)}</strong></p>
                    <p>3. Periksa pesanan sebelum membayar</p>
                    <p style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
                        <i class="fas fa-check-circle"></i> Bebas biaya tambahan
                    </p>
                `;
            } else if (methodValue === 'e-wallet') {
                instructions.innerHTML = `
                    <h4 style="margin-bottom: 0.5rem; color: var(--primary);">
                        <i class="fas fa-info-circle"></i> Instruksi E-Wallet:
                    </h4>
                    <p>1. Pilih metode pembayaran via GoPay, OVO, atau Dana</p>
                    <p>2. No E-Wallet:081931194443</p>
                    <p>3. Jumlah pembayaran: <strong>Rp ${formatRupiah(total)}</strong></p>
                    <p style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
                        <i class="fas fa-clock"></i> Batas pembayaran: 15 menit
                    </p>
                `;
            }
        });
    });
    
    // Format Rupiah
    function formatRupiah(number) {
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        document.querySelectorAll('.alert-success, .alert-error').forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
    </script>
</body>
</html>