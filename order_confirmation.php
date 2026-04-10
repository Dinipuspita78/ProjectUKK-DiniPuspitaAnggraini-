<?php
session_start();
require_once 'components/database.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$order_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($order_id <= 0) {
    header('Location: index.php');
    exit();
}

// DEBUG: Cek apakah tabel dan kolom ada
error_log("Getting order #$order_id for user #$user_id");

// Cek struktur tabel users terlebih dahulu
$check_users = mysqli_query($conn, "SHOW COLUMNS FROM users");
$user_columns = [];
while ($col = mysqli_fetch_assoc($check_users)) {
    $user_columns[] = $col['Field'];
}

error_log("Users table columns: " . implode(", ", $user_columns));

// Tentukan nama kolom yang benar
$name_col = 'name'; // default
if (in_array('nama', $user_columns)) {
    $name_col = 'nama';
} elseif (in_array('fullname', $user_columns)) {
    $name_col = 'fullname';
}

$phone_col = 'phone'; // default
if (in_array('telepon', $user_columns)) {
    $phone_col = 'telepon';
} elseif (in_array('no_telp', $user_columns)) {
    $phone_col = 'no_telp';
} elseif (in_array('telp', $user_columns)) {
    $phone_col = 'telp';
}

// Get order details dengan kolom yang benar
$order_query = mysqli_query($conn, 
    "SELECT o.*, u.$name_col as customer_name, u.email, u.$phone_col as phone 
     FROM orders o 
     JOIN users u ON o.user_id = u.id 
     WHERE o.id = $order_id AND o.user_id = $user_id");

$order = mysqli_fetch_assoc($order_query);

// Jika order tidak ditemukan, tampilkan pesan error
if (!$order) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Pesanan Tidak Ditemukan</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            .error { background: #ffecec; padding: 20px; border-radius: 8px; border-left: 4px solid #ff4757; }
        </style>
    </head>
    <body>
        <div class='error'>
            <h1>Pesanan Tidak Ditemukan</h1>
            <p>Pesanan dengan ID #$order_id tidak ditemukan atau Anda tidak memiliki akses.</p>
            <a href='orders.php'>Lihat Semua Pesanan</a> | 
            <a href='index.php'>Kembali ke Beranda</a>
        </div>
    </body>
    </html>";
    exit();
}

// Cek tabel order_items dan products/produk
$check_products = mysqli_query($conn, "SHOW TABLES LIKE 'products'");
if (mysqli_num_rows($check_products) > 0) {
    $product_table = 'products';
    $product_name_col = 'name';
    $product_image_col = 'image_url';
} else {
    $product_table = 'produk';
    $product_name_col = 'nama';
    $product_image_col = 'gambar';
}

// Get order items
$items_query = mysqli_query($conn, 
    "SELECT oi.*, p.$product_name_col as name, p.$product_image_col as image_url 
     FROM order_items oi 
     JOIN $product_table p ON oi.product_id = p.id 
     WHERE oi.order_id = $order_id");

// Jika tidak ada items, set default
if (!$items_query) {
    $items_query = false;
    error_log("Error getting order items: " . mysqli_error($conn));
}

$page_title = "Konfirmasi Pesanan";
include 'components/header_pengguna.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pesanan - MinShop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4a6cf7;
            --primary-dark: #3a5bd9;
            --success: #28a745;
            --warning: #ffc107;
            --info: #17a2b8;
            --radius: 8px;
        }
        
        .confirmation-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .confirmation-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 2rem;
            text-align: center;
        }
        
        .confirmation-icon {
            width: 100px;
            height: 100px;
            background-color: var(--success);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 3rem;
        }
        
        .order-details {
            background-color: #f0f7ff;
            border-radius: var(--radius);
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
            border: 1px solid #cce5ff;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: var(--radius);
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .whatsapp-button {
            background-color: #25D366;
            color: white;
            padding: 12px 24px;
            border-radius: var(--radius);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        
        .whatsapp-button:hover {
            background-color: #128C7E;
            transform: translateY(-2px);
        }
        
        .tracking-steps {
            display: flex;
            justify-content: space-between;
            margin: 2rem 0;
            position: relative;
        }
        
        .tracking-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 5%;
            right: 5%;
            height: 2px;
            background-color: #ddd;
            z-index: 1;
        }
        
        .tracking-step {
            text-align: center;
            position: relative;
            z-index: 2;
            width: 20%;
        }
        
        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #ddd;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .tracking-step.active .step-circle {
            background-color: var(--primary);
            color: white;
        }
        
        .step-label {
            font-size: 0.9rem;
            color: #666;
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
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-shipped {
            background: #d4edda;
            color: #155724;
        }
        
        .status-delivered {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        @media (max-width: 768px) {
            .tracking-steps {
                flex-wrap: wrap;
            }
            
            .tracking-step {
                width: 50%;
                margin-bottom: 1rem;
            }
            
            .tracking-steps::before {
                display: none;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn, .whatsapp-button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="confirmation-card">
            <div class="confirmation-icon">
                <i class="fas fa-check"></i>
            </div>
            
            <h1 style="color: var(--success); margin-bottom: 1rem;">Pesanan Berhasil!</h1>
            <p style="font-size: 1.1rem; margin-bottom: 2rem; color: #666;">
                Terima kasih telah berbelanja di MinShop. Pesanan Anda telah diterima dan sedang diproses.
            </p>
            
            <div style="background-color: var(--primary); color: white; padding: 1rem; border-radius: var(--radius); display: inline-block; margin-bottom: 2rem;">
                <h3 style="margin: 0;">Nomor Pesanan: #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h3>
            </div>
            
            <!-- Order Tracking Steps -->
            <div class="tracking-steps">
                <div class="tracking-step active">
                    <div class="step-circle">1</div>
                    <div class="step-label">Pesanan<br>Diterima</div>
                </div>
                <div class="tracking-step">
                    <div class="step-circle">2</div>
                    <div class="step-label">Diproses</div>
                </div>
                <div class="tracking-step">
                    <div class="step-circle">3</div>
                    <div class="step-label">Dikirim</div>
                </div>
                <div class="tracking-step">
                    <div class="step-circle">4</div>
                    <div class="step-label">Selesai</div>
                </div>
            </div>
            
            <!-- Order Details -->
            <div class="order-details">
                <h3 style="color: var(--primary); margin-bottom: 1rem; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-receipt"></i> Detail Pesanan
                </h3>
                
                <div class="detail-row">
                    <span><i class="fas fa-calendar"></i> Tanggal Pesanan</span>
                    <span><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                </div>
                
                <div class="detail-row">
                    <span><i class="fas fa-user"></i> Nama Pelanggan</span>
                    <span><?php echo htmlspecialchars($order['customer_name']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span><i class="fas fa-envelope"></i> Email</span>
                    <span><?php echo htmlspecialchars($order['email']); ?></span>
                </div>
                
                <?php if (!empty($order['phone'])): ?>
                <div class="detail-row">
                    <span><i class="fas fa-phone"></i> Telepon</span>
                    <span><?php echo htmlspecialchars($order['phone']); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="detail-row">
                    <span><i class="fas fa-map-marker-alt"></i> Alamat Pengiriman</span>
                    <span style="max-width: 200px; text-align: right;">
                        <?php echo htmlspecialchars($order['shipping_address'] ?? 'Alamat tidak tersedia'); ?>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span><i class="fas fa-credit-card"></i> Metode Pembayaran</span>
                    <span>
                        <?php 
                        $payment_method = $order['payment_method'] ?? 'transfer';
                        echo strtoupper($payment_method); 
                        ?>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span><i class="fas fa-money-bill-wave"></i> Total Pembayaran</span>
                    <span style="font-weight: bold; color: var(--primary);">
                        Rp <?php echo number_format($order['total_amount'] ?? $order['total'] ?? 0, 0, ',', '.'); ?>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span><i class="fas fa-info-circle"></i> Status Pesanan</span>
                    <span class="status-badge status-<?php echo $order['status']; ?>">
                        <?php echo ucfirst($order['status'] ?? 'pending'); ?>
                    </span>
                </div>
            </div>
            
            <!-- Order Items -->
            <h3 style="text-align: left; margin-top: 2rem; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-shopping-cart"></i> Produk yang Dipesan
            </h3>
            <div style="max-height: 300px; overflow-y: auto; margin-top: 1rem; border: 1px solid #eee; border-radius: var(--radius);">
                <?php if ($items_query && mysqli_num_rows($items_query) > 0): ?>
                    <?php while ($item = mysqli_fetch_assoc($items_query)): 
                        $image_path = 'project images/' . $item['image_url'];
                        $image_src = (!empty($item['image_url']) && file_exists($image_path)) 
                            ? $image_path 
                            : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwsc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxMCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPlByb2R1azwvdGV4dD48L3N2Zz4=';
                    ?>
                    <div class="product-checkout">
                        <img src="<?php echo $image_src; ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                             class="product-checkout-image"
                             onerror="this.src='<?php echo $image_src; ?>'">
                        <div class="product-checkout-info">
                            <h4 style="margin-bottom: 0.5rem; font-size: 14px;"><?php echo htmlspecialchars($item['name']); ?></h4>
                            <div style="display: flex; justify-content: space-between; font-size: 13px;">
                                <span>Rp <?php echo number_format($item['price'] ?? $item['harga'] ?? 0, 0, ',', '.'); ?> × <?php echo $item['quantity']; ?></span>
                                <span class="product-checkout-price">
                                    Rp <?php echo number_format(($item['price'] ?? $item['harga'] ?? 0) * $item['quantity'], 0, ',', '.'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px; color: #666;">
                        <i class="fas fa-box-open fa-2x"></i>
                        <p>Detail produk tidak tersedia</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="orders.php" class="btn">
                    <i class="fas fa-clipboard-list"></i> Lihat Semua Pesanan
                </a>
                
                <a href="produk.php" class="btn btn-secondary">
                    <i class="fas fa-shopping-cart"></i> Lanjut Belanja
                </a>
                
                <a href="https://wa.me/6281234567890?text=Halo MinShop, saya ingin konfirmasi pembayaran untuk pesanan %23<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>" 
                   target="_blank" class="whatsapp-button">
                    <i class="fab fa-whatsapp"></i> Konfirmasi via WhatsApp
                </a>
            </div>
            
            <!-- Next Steps -->
            <div style="margin-top: 2rem; padding: 1.5rem; background-color: #f8f9fa; border-radius: var(--radius); border-left: 4px solid var(--info);">
                <h4 style="color: var(--info); margin-bottom: 1rem; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-info-circle"></i> Langkah Selanjutnya
                </h4>
                <ul style="text-align: left; padding-left: 1.5rem; color: #666;">
                    <li>Lakukan pembayaran sesuai metode yang dipilih</li>
                    <li>Konfirmasi pembayaran via WhatsApp atau Customer Service</li>
                    <li>Pesanan akan diproses dalam 1-2 jam kerja</li>
                    <li>Anda akan menerima notifikasi via WhatsApp/Email</li>
                    <li>Estimasi pengiriman 1-3 hari kerja</li>
                </ul>
            </div>
            
            <!-- Support Info -->
            <div style="margin-top: 2rem; padding: 1rem; text-align: center; color: #666; font-size: 0.9rem;">
                <p><i class="fas fa-headset"></i> Butuh bantuan? Hubungi Customer Service: (021) 1234-5678</p>
            </div>
        </div>
    </div>
    
    <script>
    // Update tracking steps based on order status
    const status = "<?php echo $order['status'] ?? 'pending'; ?>";
    const steps = document.querySelectorAll('.tracking-step');
    
    // Reset semua step
    steps.forEach(step => step.classList.remove('active'));
    
    // Aktifkan step sesuai status
    steps[0].classList.add('active'); // Step 1 selalu aktif
    
    switch(status) {
        case 'processing':
            steps[1].classList.add('active');
            break;
        case 'shipped':
            steps[1].classList.add('active');
            steps[2].classList.add('active');
            break;
        case 'delivered':
        case 'completed':
            steps[1].classList.add('active');
            steps[2].classList.add('active');
            steps[3].classList.add('active');
            break;
    }
    
    // Print order function
    function printOrder() {
        window.print();
    }
    
    // Add print button event listener
    document.addEventListener('DOMContentLoaded', function() {
        const printBtn = document.createElement('button');
        printBtn.className = 'btn';
        printBtn.innerHTML = '<i class="fas fa-print"></i> Cetak Invoice';
        printBtn.onclick = printOrder;
        
        const actionButtons = document.querySelector('.action-buttons');
        if (actionButtons) {
            actionButtons.appendChild(printBtn);
        }
    });
    
    // Auto scroll to order details
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            document.querySelector('.order-details')?.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }, 1000);
    });
    </script>
</body>
</html>