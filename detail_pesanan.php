<?php
session_start();
require_once 'components/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ===== QUERY YANG DIPERBAIKI =====
$query = "
    SELECT 
        o.*,
        -- Data shipping langsung dari orders (ini yang digunakan di checkout)
        o.shipping_address,
        o.shipping_city,
        o.shipping_province,
        o.shipping_postal_code,
        o.shipping_name,
        o.shipping_phone,
        -- Data dari user_addresses (jika ada alamat_id)
        ua.nama_penerima as ua_nama_penerima,
        ua.telepon as ua_telepon,
        ua.alamat as ua_alamat,
        ua.kota as ua_kota,
        ua.provinsi as ua_provinsi,
        ua.kode_pos as ua_kode_pos
    FROM orders o
    LEFT JOIN user_addresses ua ON o.alamat_id = ua.id
    WHERE o.id = ? AND o.user_id = ?
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($order_result) == 0) {
    header('Location: pesanan.php');
    exit();
}

$order = mysqli_fetch_assoc($order_result);

// ===== FUNGSI UNTUK MENDAPATKAN ALAMAT =====
function getShippingAddress($order) {
    $address = [];
    
    // DEBUG: Untuk melihat data yang ada (uncomment jika perlu debug)
    /*
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0; border: 1px solid #dee2e6;'>";
    echo "<h4 style='color: #dc3545;'>DEBUG DATA ORDER:</h4>";
    echo "<p><strong>Order ID:</strong> " . ($order['id'] ?? 'N/A') . "</p>";
    echo "<p><strong>Alamat ID:</strong> " . ($order['alamat_id'] ?? 'NULL') . "</p>";
    echo "<p><strong>Shipping Address:</strong> " . ($order['shipping_address'] ?? 'NULL') . "</p>";
    echo "<p><strong>Shipping Name:</strong> " . ($order['shipping_name'] ?? 'NULL') . "</p>";
    echo "<p><strong>UA Alamat:</strong> " . ($order['ua_alamat'] ?? 'NULL') . "</p>";
    echo "</div>";
    */
    
    // PRIORITAS 1: Data shipping dari orders (seperti disimpan di checkout)
    if (!empty($order['shipping_address'])) {
        $address = [
            'nama' => !empty($order['shipping_name']) ? $order['shipping_name'] : 'Pelanggan',
            'telepon' => $order['shipping_phone'] ?? '',
            'alamat' => $order['shipping_address'],
            'kota' => $order['shipping_city'] ?? '',
            'provinsi' => $order['shipping_province'] ?? '',
            'kode_pos' => $order['shipping_postal_code'] ?? '',
            'sumber' => 'orders'
        ];
    }
    // PRIORITAS 2: Data dari user_addresses
    elseif (!empty($order['ua_alamat'])) {
        $address = [
            'nama' => $order['ua_nama_penerima'] ?? 'Pelanggan',
            'telepon' => $order['ua_telepon'] ?? '',
            'alamat' => $order['ua_alamat'],
            'kota' => $order['ua_kota'] ?? '',
            'provinsi' => $order['ua_provinsi'] ?? '',
            'kode_pos' => $order['ua_kode_pos'] ?? '',
            'sumber' => 'user_addresses'
        ];
    }
    // PRIORITAS 3: Coba field umum lainnya
    elseif (!empty($order['alamat_pengiriman'])) {
        $address = [
            'nama' => $order['nama_penerima'] ?? 'Pelanggan',
            'telepon' => $order['telepon_penerima'] ?? '',
            'alamat' => $order['alamat_pengiriman'],
            'kota' => $order['kota_pengiriman'] ?? '',
            'provinsi' => $order['provinsi_pengiriman'] ?? '',
            'kode_pos' => $order['kode_pos_pengiriman'] ?? '',
            'sumber' => 'old_format'
        ];
    }
    
    return $address;
}

// Dapatkan data alamat
$shipping_address = getShippingAddress($order);

// Get order items
$items_query = "
    SELECT 
        oi.*,
        p.nama as produk_nama,
        p.gambar,
        p.kategori
    FROM order_items oi
    JOIN produk p ON oi.product_id = p.id
    WHERE oi.order_id = ?
    ORDER BY oi.id
";

$stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);
$items = mysqli_fetch_all($items_result, MYSQLI_ASSOC);

// Calculate subtotal
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += ($item['price'] * $item['quantity']);
}

// Biaya dari database atau default
$shipping_cost = isset($order['shipping_cost']) && $order['shipping_cost'] > 0 ? $order['shipping_cost'] : 5000;
$service_fee = isset($order['service_fee']) && $order['service_fee'] > 0 ? $order['service_fee'] : 1000;

// Hitung total
$total = $subtotal + $shipping_cost + $service_fee;
if (isset($order['discount']) && $order['discount'] > 0) {
    $total -= $order['discount'];
}

// Status translations
$status_labels = [
    'pending' => 'Menunggu Pembayaran',
    'processing' => 'Diproses',
    'shipped' => 'Dikirim',
    'delivered' => 'Sampai',
    'cancelled' => 'Dibatalkan'
];

$status_classes = [
    'pending' => 'status-pending',
    'processing' => 'status-processing',
    'shipped' => 'status-shipped',
    'delivered' => 'status-delivered',
    'cancelled' => 'status-cancelled'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?> - MinShop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== DETAIL ORDER PAGE ===== */
        .order-detail-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #4361ee;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .order-header {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .order-info h1 {
            color: #212529;
            margin-bottom: 10px;
            font-size: 1.8rem;
        }
        
        .order-date {
            color: #6c757d;
            font-size: 0.95rem;
        }
        
        .order-status {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-shipped { background: #d4edda; color: #155724; }
        .status-delivered { background: #d1ecf1; color: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .order-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 992px) {
            .order-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Order Items */
        .order-items-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        
        .section-title {
            font-size: 1.3rem;
            color: #212529;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .order-items-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .order-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .order-item:hover {
            border-color: #4361ee;
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.1);
        }
        
        .item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            flex-shrink: 0;
        }
        
        .item-placeholder {
            width: 100px;
            height: 100px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #6c757d;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: #212529;
            margin-bottom: 5px;
        }
        
        .item-category {
            display: inline-block;
            background: #e9ecef;
            color: #6c757d;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-bottom: 10px;
        }
        
        .item-quantity {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .item-price {
            font-weight: 600;
            color: #4361ee;
        }
        
        /* Order Summary */
        .order-summary-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            align-self: flex-start;
            position: sticky;
            top: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .summary-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .summary-label {
            color: #6c757d;
        }
        
        .summary-value {
            font-weight: 500;
            color: #212529;
        }
        
        .summary-total {
            font-size: 1.3rem;
            font-weight: 700;
            color: #4361ee;
        }
        
        /* Shipping Address */
        .shipping-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        
        .address-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
            position: relative;
            border: 1px solid #e9ecef;
        }
        
        .address-source-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #4361ee;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .address-card h4 {
            margin-bottom: 15px;
            color: #212529;
            padding-right: 80px;
        }
        
        .address-detail {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .address-detail i {
            color: #4361ee;
            margin-top: 2px;
        }
        
        .address-detail p {
            margin: 0;
            color: #495057;
            line-height: 1.5;
        }
        
        .no-address {
            text-align: center;
            padding: 40px 30px;
            color: #6c757d;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #dee2e6;
        }
        
        .no-address i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Debug Info */
        .debug-info {
            background: #fff3cd;
            border: 1px solid #ffecb5;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.85rem;
            display: none;
        }
        
        .debug-toggle {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            color: #6c757d;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            margin-top: 10px;
            display: inline-block;
        }
        
        .debug-toggle:hover {
            background: #e9ecef;
        }
        
        /* Order Actions */
        .order-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }
        
        .btn-track {
            background: linear-gradient(135deg, #4361ee 0%, #3a56d4 100%);
            color: white;
        }
        
        .btn-track:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-print {
            background: white;
            color: #4361ee;
            border: 2px solid #4361ee;
        }
        
        .btn-print:hover {
            background: #4361ee;
            color: white;
        }
        
        .btn-cancel {
            background: white;
            color: #dc3545;
            border: 2px solid #dc3545;
        }
        
        .btn-cancel:hover {
            background: #dc3545;
            color: white;
        }
        
        /* Tracking Timeline */
        .timeline-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
            margin-top: 20px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 25px;
        }
        
        .timeline-item:last-child {
            margin-bottom: 0;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -26px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #e9ecef;
            border: 3px solid white;
        }
        
        .timeline-item.active::before {
            background: #4361ee;
        }
        
        .timeline-item.completed::before {
            background: #4cc9f0;
        }
        
        .timeline-date {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .timeline-title {
            font-weight: 600;
            color: #212529;
            margin-bottom: 5px;
        }
        
        .timeline-desc {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        /* Payment Info */
        .payment-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        
        .payment-method {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .payment-icon {
            font-size: 2rem;
            color: #4361ee;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-item {
                flex-direction: column;
            }
            
            .item-image, .item-placeholder {
                width: 100%;
                height: 200px;
            }
            
            .order-actions {
                flex-direction: column;
            }
            
            .btn-action {
                width: 100%;
            }
            
            .address-card h4 {
                padding-right: 0;
            }
        }
        
        /* Loading State */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert i {
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <?php include 'components/header_pengguna.php'; ?>
    
    <div class="order-detail-container">
        <!-- Back Button -->
        <a href="orders.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Pesanan
        </a>
        
        <!-- Order Header -->
        <div class="order-header">
            <div class="order-info">
                <h1>Pesanan #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h1>
                <div class="order-date">
                    <i class="far fa-calendar"></i> 
                    <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                </div>
            </div>
            
            <div class="order-status <?php echo $status_classes[$order['status']]; ?>">
                <?php echo $status_labels[$order['status']]; ?>
            </div>
        </div>
        
        <!-- Main Content Grid -->
        <div class="order-grid">
            <!-- Left Column: Order Items -->
            <div class="order-items-section">
                <h2 class="section-title">Item Pesanan</h2>
                
                <div class="order-items-list">
                    <?php if (empty($items)): ?>
                        <div class="no-items">
                            <i class="fas fa-shopping-cart fa-2x"></i>
                            <p>Tidak ada item dalam pesanan ini</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                        <div class="order-item">
                            <?php if (!empty($item['gambar'])): ?>
                                <img src="project images/<?php echo htmlspecialchars($item['gambar']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['produk_nama']); ?>" 
                                     class="item-image"
                                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiNFM0UzRTMiLz48cGF0aCBkPSJNNjYgNDJINjJDNjIgMzggNjYgMzQgNzAgMzRDNzQgMzQgNzggMzggNzggNDJWNjZINzRWNjJINzBWNjZINjZWNjJINjJWNjZINThWNDJDNTggMzggNjIgMzQgNjYgMzRINzBDNzQgMzQgNzggMzggNzggNDJINzRDNzQgMzggNzAgMzQgNjYgMzRaTTY2IDU4VjU0SDcwVjU4SDc0VjYySDcwVjU4SDY2WiIgZmlsbD0iIzk5OTk5OSIvPjwvc3ZnPg=='">
                            <?php else: ?>
                                <div class="item-placeholder">
                                    <i class="fas fa-box"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="item-details">
                                <h3 class="item-name"><?php echo htmlspecialchars($item['produk_nama']); ?></h3>
                                <span class="item-category"><?php echo htmlspecialchars($item['kategori']); ?></span>
                                <div class="item-quantity">
                                    <strong>Jumlah:</strong> <?php echo $item['quantity']; ?>
                                </div>
                                <div class="item-price">
                                    Rp <?php echo number_format($item['price'], 0, ',', '.'); ?> × <?php echo $item['quantity']; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right Column: Order Summary -->
            <div class="order-summary-section">
                <h2 class="section-title">Ringkasan Pesanan</h2>
                
                <!-- Calculate subtotal from items -->
                <div class="summary-row">
                    <span class="summary-label">Subtotal</span>
                    <span class="summary-value">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                </div>
                
                <!-- Biaya Pengiriman -->
                <div class="summary-row">
                    <span class="summary-label">Biaya Pengiriman</span>
                    <span class="summary-value">Rp <?php echo number_format($shipping_cost, 0, ',', '.'); ?></span>
                </div>
                
                <!-- Biaya Layanan -->
                <div class="summary-row">
                    <span class="summary-label">Biaya Layanan</span>
                    <span class="summary-value">Rp <?php echo number_format($service_fee, 0, ',', '.'); ?></span>
                </div>
                
                <!-- Discount if any -->
                <?php if (isset($order['discount']) && $order['discount'] > 0): ?>
                <div class="summary-row">
                    <span class="summary-label">Diskon</span>
                    <span class="summary-value" style="color: #4cc9f0;">
                        -Rp <?php echo number_format($order['discount'], 0, ',', '.'); ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <!-- Total Calculation -->
                <div class="summary-row" style="border-top: 2px solid #e9ecef; padding-top: 20px;">
                    <span class="summary-label">Total Pembayaran</span>
                    <span class="summary-value summary-total">
                        Rp <?php echo number_format($total, 0, ',', '.'); ?>
                    </span>
                </div>
                
                <!-- Payment Method -->
                <div class="summary-row">
                    <span class="summary-label">Metode Pembayaran</span>
                    <span class="summary-value">
                        <?php 
                        $payment_methods = [
                            'cod' => 'Cash on Delivery',
                            'transfer' => 'Transfer Bank',
                            'ewallet' => 'E-Wallet'
                        ];
                        echo isset($payment_methods[$order['payment_method']]) ? 
                             $payment_methods[$order['payment_method']] : 
                             ucfirst($order['payment_method']);
                        ?>
                    </span>
                </div>
                
                <!-- Payment Status -->
                <div class="summary-row">
                    <span class="summary-label">Status Pembayaran</span>
                    <span class="summary-value" style="color: <?php echo $order['payment_status'] == 'paid' ? '#4cc9f0' : '#f72585'; ?>">
                        <?php 
                        if ($order['payment_status'] == 'paid') {
                            echo 'Lunas';
                        } elseif ($order['payment_status'] == 'pending') {
                            echo 'Menunggu Pembayaran';
                        } else {
                            echo ucfirst($order['payment_status']);
                        }
                        ?>
                    </span>
                </div>
                
                <!-- Order Actions -->
                <div class="order-actions">
                    <?php if ($order['status'] == 'shipped'): ?>
                        <button class="btn-action btn-track">
                            <i class="fas fa-truck"></i> Lacak Pengiriman
                        </button>
                    <?php endif; ?>
                    
                    <button class="btn-action btn-print" onclick="printInvoice()">
                        <i class="fas fa-print"></i> Cetak Invoice
                    </button>
                    
                    <?php if (in_array($order['status'], ['pending', 'processing'])): ?>
                        <button class="btn-action btn-cancel" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                            <i class="fas fa-times"></i> Batalkan Pesanan
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Shipping Address -->
        <div class="shipping-section">
            <h2 class="section-title">Alamat Pengiriman</h2>
            
            <?php if (!empty($shipping_address)): ?>
            <div class="address-card">
                <span class="address-source-badge"><?php echo ucfirst($shipping_address['sumber']); ?></span>
                
                <h4><?php echo htmlspecialchars($shipping_address['nama']); ?></h4>
                
                <?php if (!empty($shipping_address['telepon'])): ?>
                <div class="address-detail">
                    <i class="fas fa-phone"></i>
                    <p><?php echo htmlspecialchars($shipping_address['telepon']); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="address-detail">
                    <i class="fas fa-map-marker-alt"></i>
                    <p>
                        <?php echo nl2br(htmlspecialchars($shipping_address['alamat'])); ?>
                        <?php if (!empty($shipping_address['kota']) || !empty($shipping_address['provinsi'])): ?>
                            <br>
                            <?php if (!empty($shipping_address['kota'])): ?>
                                <?php echo htmlspecialchars($shipping_address['kota']); ?>,
                            <?php endif; ?>
                            <?php if (!empty($shipping_address['provinsi'])): ?>
                                <?php echo htmlspecialchars($shipping_address['provinsi']); ?>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (!empty($shipping_address['kode_pos'])): ?>
                            <br>Kode Pos: <?php echo htmlspecialchars($shipping_address['kode_pos']); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <!-- Debug Button (only visible for developers) -->
            <?php if (isset($_GET['debug']) || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'])): ?>
            <div class="debug-info" id="debugInfo">
                <h4>Debug Information:</h4>
                <p><strong>Order ID:</strong> <?php echo $order['id']; ?></p>
                <p><strong>Alamat ID:</strong> <?php echo $order['alamat_id'] ?? 'NULL'; ?></p>
                <p><strong>Sumber Alamat:</strong> <?php echo $shipping_address['sumber']; ?></p>
                <p><strong>Data dari Database:</strong></p>
                <ul>
                    <li>shipping_address: <?php echo $order['shipping_address'] ?? 'NULL'; ?></li>
                    <li>shipping_name: <?php echo $order['shipping_name'] ?? 'NULL'; ?></li>
                    <li>shipping_phone: <?php echo $order['shipping_phone'] ?? 'NULL'; ?></li>
                    <li>shipping_city: <?php echo $order['shipping_city'] ?? 'NULL'; ?></li>
                    <li>shipping_province: <?php echo $order['shipping_province'] ?? 'NULL'; ?></li>
                    <li>shipping_postal_code: <?php echo $order['shipping_postal_code'] ?? 'NULL'; ?></li>
                    <li>ua_alamat: <?php echo $order['ua_alamat'] ?? 'NULL'; ?></li>
                    <li>ua_nama_penerima: <?php echo $order['ua_nama_penerima'] ?? 'NULL'; ?></li>
                    <li>ua_telepon: <?php echo $order['ua_telepon'] ?? 'NULL'; ?></li>
                </ul>
            </div>
            <button class="debug-toggle" onclick="toggleDebug()">Show/Hide Debug</button>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="no-address">
                <i class="fas fa-map-marker-alt"></i>
                <h4>Tidak ada alamat yang tercatat</h4>
                <p>Alamat pengiriman tidak ditemukan untuk pesanan ini.</p>
                
                <!-- Debug info untuk developer -->
                <div style="margin-top: 20px; padding: 10px; background: #f0f0f0; border-radius: 5px; font-size: 0.8rem;">
                    <p><strong>Debug Info:</strong></p>
                    <p>Order ID: <?php echo $order['id']; ?></p>
                    <p>Shipping Address: <?php echo $order['shipping_address'] ?? 'NULL'; ?></p>
                    <p>Shipping Name: <?php echo $order['shipping_name'] ?? 'NULL'; ?></p>
                    <p>UA Alamat: <?php echo $order['ua_alamat'] ?? 'NULL'; ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Order Tracking Timeline -->
        <div class="timeline-section">
            <h2 class="section-title">Status Pesanan</h2>
            
            <div class="timeline">
                <div class="timeline-item <?php echo in_array($order['status'], ['processing', 'shipped', 'delivered']) ? 'completed' : ''; ?> <?php echo $order['status'] == 'pending' ? 'active' : ''; ?>">
                    <div class="timeline-date"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></div>
                    <div class="timeline-title">Pesanan Dibuat</div>
                    <div class="timeline-desc">Pesanan telah diterima sistem</div>
                </div>
                
                <div class="timeline-item <?php echo in_array($order['status'], ['shipped', 'delivered']) ? 'completed' : ''; ?> <?php echo $order['status'] == 'processing' ? 'active' : ''; ?>">
                    <div class="timeline-date">
                        <?php echo $order['status'] == 'pending' ? '-' : date('d/m/Y H:i', strtotime($order['updated_at'])); ?>
                    </div>
                    <div class="timeline-title">Diproses</div>
                    <div class="timeline-desc">Pesanan sedang disiapkan</div>
                </div>
                
                <div class="timeline-item <?php echo $order['status'] == 'delivered' ? 'completed' : ''; ?> <?php echo $order['status'] == 'shipped' ? 'active' : ''; ?>">
                    <div class="timeline-date">
                        <?php echo in_array($order['status'], ['pending', 'processing']) ? '-' : date('d/m/Y H:i', strtotime($order['updated_at'])); ?>
                    </div>
                    <div class="timeline-title">Dikirim</div>
                    <div class="timeline-desc">Pesanan sedang dalam pengiriman</div>
                </div>
                
                <div class="timeline-item <?php echo $order['status'] == 'delivered' ? 'active' : ''; ?>">
                    <div class="timeline-date">
                        <?php echo $order['status'] != 'delivered' ? '-' : date('d/m/Y H:i', strtotime($order['updated_at'])); ?>
                    </div>
                    <div class="timeline-title">Sampai</div>
                    <div class="timeline-desc">Pesanan telah sampai di tujuan</div>
                </div>
            </div>
        </div>
        
        <!-- Payment Information -->
        <?php if ($order['payment_method'] == 'transfer'): ?>
        <div class="payment-section">
            <h2 class="section-title">Informasi Pembayaran</h2>
            
            <div class="payment-method">
                <i class="fas fa-university payment-icon"></i>
                <div>
                    <h4>Transfer Bank</h4>
                    <p>BCA: 1234567890 a.n MinShop</p>
                    <p>Jumlah: Rp <?php echo number_format($total, 0, ',', '.'); ?></p>
                    <p>Status: <strong style="color: <?php echo $order['payment_status'] == 'paid' ? '#4cc9f0' : '#f72585'; ?>">
                        <?php 
                        if ($order['payment_status'] == 'paid') {
                            echo 'Lunas';
                        } elseif ($order['payment_status'] == 'pending') {
                            echo 'Menunggu Pembayaran';
                        } else {
                            echo ucfirst($order['payment_status']);
                        }
                        ?>
                    </strong></p>
                    <?php if ($order['payment_status'] == 'pending'): ?>
                    <p style="font-size: 0.9rem; color: #666; margin-top: 5px;">
                        Silakan transfer ke rekening di atas dan upload bukti transfer di halaman pesanan.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php include 'components/footer.php'; ?>
    
    <script>
    function cancelOrder(orderId) {
        if (confirm('Apakah Anda yakin ingin membatalkan pesanan ini?')) {
            // Show loading
            document.body.classList.add('loading');
            
            // Send cancellation request
            fetch('cancel_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + orderId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Pesanan berhasil dibatalkan');
                    location.reload();
                } else {
                    alert('Gagal membatalkan pesanan: ' + data.message);
                }
            })
            .catch(error => {
                alert('Terjadi kesalahan: ' + error);
            })
            .finally(() => {
                document.body.classList.remove('loading');
            });
        }
    }
    
    // Track order button
    document.querySelector('.btn-track')?.addEventListener('click', function() {
        const orderId = <?php echo $order['id']; ?>;
        window.open('tracking.php?id=' + orderId, '_blank');
    });
    
    // Print invoice function
    function printInvoice() {
        const printWindow = window.open('', '_blank');
        
        // Get current date
        const now = new Date();
        const dateStr = now.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
        
        // Create invoice content
        const invoiceContent = `
            <!DOCTYPE html>
            <html lang="id">
            <head>
                <title>Invoice #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        padding: 30px;
                        max-width: 800px;
                        margin: 0 auto;
                        color: #333;
                    }
                    .invoice-header {
                        text-align: center;
                        margin-bottom: 30px;
                        border-bottom: 3px solid #4361ee;
                        padding-bottom: 20px;
                    }
                    .invoice-header h1 {
                        color: #4361ee;
                        margin-bottom: 5px;
                    }
                    .company-info {
                        text-align: center;
                        margin-bottom: 30px;
                        color: #666;
                    }
                    .invoice-details {
                        display: flex;
                        justify-content: space-between;
                        margin-bottom: 30px;
                    }
                    .details-box {
                        background: #f8f9fa;
                        padding: 15px;
                        border-radius: 8px;
                        flex: 1;
                        margin: 0 10px;
                    }
                    .details-box:first-child {
                        margin-left: 0;
                    }
                    .details-box:last-child {
                        margin-right: 0;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 30px 0;
                    }
                    th {
                        background: #4361ee;
                        color: white;
                        padding: 12px;
                        text-align: left;
                    }
                    td {
                        padding: 12px;
                        border-bottom: 1px solid #ddd;
                    }
                    .summary {
                        float: right;
                        width: 300px;
                        margin-top: 30px;
                    }
                    .summary-row {
                        display: flex;
                        justify-content: space-between;
                        margin-bottom: 10px;
                        padding: 8px 0;
                        border-bottom: 1px solid #eee;
                    }
                    .total-row {
                        font-weight: bold;
                        font-size: 1.2em;
                        border-top: 2px solid #4361ee;
                        margin-top: 10px;
                        padding-top: 15px;
                    }
                    .footer {
                        margin-top: 50px;
                        text-align: center;
                        color: #666;
                        font-size: 0.9em;
                    }
                    @media print {
                        body {
                            padding: 0;
                        }
                        .no-print {
                            display: none;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="invoice-header">
                    <h1>INVOICE</h1>
                    <p>MinShop - E-Commerce Platform</p>
                </div>
                
                <div class="company-info">
                    <p><strong>MinShop</strong></p>
                    <p>Jl. Contoh No. 123, Kota Contoh</p>
                    <p>Email: info@minshop.com | Telp: (021) 123-4567</p>
                </div>
                
                <div class="invoice-details">
                    <div class="details-box">
                        <h3>Informasi Pesanan</h3>
                        <p><strong>No. Invoice:</strong> #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></p>
                        <p><strong>Tanggal:</strong> ${dateStr}</p>
                        <p><strong>Status:</strong> <?php echo $status_labels[$order['status']]; ?></p>
                    </div>
                    
                    <div class="details-box">
                        <h3>Informasi Pelanggan</h3>
                        <p><strong>Nama:</strong> <?php echo htmlspecialchars($shipping_address['nama'] ?? 'Pelanggan'); ?></p>
                        <?php if (!empty($shipping_address['telepon'])): ?>
                        <p><strong>Telepon:</strong> <?php echo htmlspecialchars($shipping_address['telepon']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <h3>Detail Produk</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th>Qty</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['produk_nama']); ?></td>
                            <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="summary">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Biaya Pengiriman:</span>
                        <span>Rp <?php echo number_format($shipping_cost, 0, ',', '.'); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Biaya Layanan:</span>
                        <span>Rp <?php echo number_format($service_fee, 0, ',', '.'); ?></span>
                    </div>
                    <?php if (isset($order['discount']) && $order['discount'] > 0): ?>
                    <div class="summary-row">
                        <span>Diskon:</span>
                        <span>-Rp <?php echo number_format($order['discount'], 0, ',', '.'); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-row total-row">
                        <span>Total Pembayaran:</span>
                        <span>Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                    </div>
                </div>
                
                <?php if (!empty($shipping_address)): ?>
                <div style="clear: both; margin-top: 200px;">
                    <h3>Alamat Pengiriman</h3>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 10px;">
                        <p><strong><?php echo htmlspecialchars($shipping_address['nama']); ?></strong></p>
                        <?php if (!empty($shipping_address['telepon'])): ?>
                        <p><?php echo htmlspecialchars($shipping_address['telepon']); ?></p>
                        <?php endif; ?>
                        <p><?php echo nl2br(htmlspecialchars($shipping_address['alamat'])); ?></p>
                        <?php if (!empty($shipping_address['kota']) || !empty($shipping_address['provinsi'])): ?>
                        <p><?php echo htmlspecialchars($shipping_address['kota']); ?>, <?php echo htmlspecialchars($shipping_address['provinsi']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($shipping_address['kode_pos'])): ?>
                        <p>Kode Pos: <?php echo htmlspecialchars($shipping_address['kode_pos']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="footer">
                    <p>Terima kasih telah berbelanja di MinShop!</p>
                    <p>Invoice ini sah dan dapat digunakan sebagai bukti pembayaran</p>
                    <p>Dicetak pada: ${new Date().toLocaleString('id-ID')}</p>
                </div>
            </body>
            </html>
        `;
        
        printWindow.document.write(invoiceContent);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 500);
    }
    
    // Toggle debug info
    function toggleDebug() {
        const debugInfo = document.getElementById('debugInfo');
        if (debugInfo) {
            debugInfo.style.display = debugInfo.style.display === 'none' ? 'block' : 'none';
        }
    }
    
    // Auto remove messages after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
    </script>
</body>
</html>