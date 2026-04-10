<?php
session_start();
require_once 'components/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data order
$order_query = mysqli_query($conn, "SELECT o.*, u.nama, u.email, u.telepon 
                                    FROM orders o 
                                    JOIN users u ON o.user_id = u.id 
                                    WHERE o.id = $order_id AND o.user_id = $user_id");
$order = mysqli_fetch_assoc($order_query);

if (!$order) {
    header('Location: orders.php');
    exit();
}

// Ambil items order
$items_query = mysqli_query($conn, "SELECT oi.*, p.nama, p.gambar 
                                   FROM order_items oi 
                                   JOIN produk p ON oi.product_id = p.id 
                                   WHERE oi.order_id = $order_id");
$items = [];
while ($item = mysqli_fetch_assoc($items_query)) {
    $items[] = $item;
}

$page_title = "Pembayaran Berhasil";
include 'components/header_pengguna.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil - MinShop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4a6cf7;
            --primary-dark: #3a5bd9;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --radius: 8px;
        }
        
        body {
            background-color: #f5f7fb;
        }
        
        .success-container {
            max-width: 700px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .success-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .success-header {
            background: linear-gradient(135deg, var(--success), #218838);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .success-header i {
            font-size: 5rem;
            margin-bottom: 1rem;
        }
        
        .success-header h1 {
            margin: 0;
            font-size: 2rem;
        }
        
        .success-header p {
            margin: 0.5rem 0 0;
            opacity: 0.9;
        }
        
        .success-body {
            padding: 2rem;
        }
        
        .order-details {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
        }
        
        .order-details h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-row.total {
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--success);
            border-top: 2px solid var(--success);
            margin-top: 0.5rem;
            padding-top: 1rem;
        }
        
        .detail-label {
            color: #666;
        }
        
        .detail-value {
            font-weight: 600;
            color: #333;
        }
        
        .payment-info {
            background: #e3f2fd;
            padding: 1.5rem;
            border-radius: var(--radius);
            margin: 1.5rem 0;
            border-left: 4px solid var(--primary);
        }
        
        .payment-info p {
            margin: 0.5rem 0;
        }
        
        .payment-info i {
            color: var(--primary);
            margin-right: 0.5rem;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .btn-block {
            width: 100%;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin: 1.5rem 0;
        }
        
        .action-buttons a {
            flex: 1;
            text-align: center;
        }
        
        .alert {
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
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
        
        .reference-number {
            background: white;
            padding: 1rem;
            border-radius: var(--radius);
            text-align: center;
            margin-bottom: 1.5rem;
            border: 2px solid var(--primary);
        }
        
        .reference-number .label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .reference-number .number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
            letter-spacing: 2px;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: #666;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .back-link:hover {
            color: var(--primary);
        }
        
        .back-link i {
            margin-right: 0.5rem;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            .success-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
        
        /* Items List */
        .items-list {
            margin-top: 1rem;
        }
        
        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="loading" id="loading">
        <div class="loading-content">
            <i class="fas fa-spinner fa-spin"></i>
            <h3>Memproses...</h3>
            <p>Mohon tunggu sebentar</p>
        </div>
    </div>

    <div class="success-container">
        <div class="success-card" id="paymentReceipt">
            <div class="success-header">
                <i class="fas fa-check-circle"></i>
                <h1>Pembayaran Berhasil!</h1>
                <p>Terima kasih, pembayaran Anda telah kami terima</p>
            </div>
            
            <div class="success-body">
                <!-- Alert Messages -->
                <?php if (isset($_SESSION['payment_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div><?php echo $_SESSION['payment_message']; unset($_SESSION['payment_message']); ?></div>
                </div>
                <?php endif; ?>
                
                <!-- Reference Number -->
                <div class="reference-number">
                    <div class="label">No. Referensi</div>
                    <div class="number"><?php echo $order['payment_reference'] ?? 'TRF' . date('Ymd') . rand(1000, 9999); ?></div>
                </div>
                
                <!-- Order Details -->
                <div class="order-details">
                    <h3><i class="fas fa-receipt"></i> Detail Pembayaran</h3>
                    
                    <div class="detail-row">
                        <span class="detail-label">Nomor Order</span>
                        <span class="detail-value">#<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Tanggal</span>
                        <span class="detail-value"><?php echo date('d M Y H:i', strtotime($order['payment_time'] ?? date('Y-m-d H:i:s'))); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Metode Pembayaran</span>
                        <span class="detail-value">
                            <?php 
                            if ($order['payment_method'] == 'transfer') echo 'Transfer Bank';
                            elseif ($order['payment_method'] == 'e-wallet') echo 'E-Wallet';
                            elseif ($order['payment_method'] == 'cod') echo 'COD';
                            ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($order['payment_method_detail'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Bank / E-Wallet</span>
                        <span class="detail-value"><?php echo $order['payment_method_detail']; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Items List -->
                    <div style="margin-top: 1rem;">
                        <h4 style="margin-bottom: 0.5rem; color: var(--primary);">Detail Pesanan</h4>
                        <?php foreach ($items as $item): ?>
                        <div class="item-row">
                            <span><?php echo htmlspecialchars($item['nama']); ?> x<?php echo $item['quantity']; ?></span>
                            <span>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="detail-row total">
                        <span class="detail-label">Total Pembayaran</span>
                        <span class="detail-value">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
                    </div>
                </div>
                
                <!-- Info Notification -->
                <div class="payment-info no-print">
                    <p><i class="fas fa-check-circle"></i> Pesanan Anda akan segera diproses.</p>
                    <p><i class="fas fa-envelope"></i> Kami akan mengirimkan notifikasi melalui email dan SMS.</p>
                    <p><i class="fas fa-truck"></i> Estimasi pengiriman: 1-3 hari kerja.</p>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons no-print">
                    <a href="orders.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> Lihat Pesanan
                    </a>
                    <a href="produk.php" class="btn btn-secondary">
                        <i class="fas fa-shopping-bag"></i> Belanja Lagi
                    </a>
                </div>
                
                <!-- Continue Shopping Link -->
                <div style="text-align: center; margin-top: 1rem;" class="no-print">
                    <a href="index.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Auto-hide loading after 3 seconds (for demo)
    window.addEventListener('load', function() {
        setTimeout(function() {
            document.getElementById('loading').classList.remove('active');
        }, 3000);
    });
    </script>
    
    <?php include 'components/footer.php'; ?>
</body>
</html>