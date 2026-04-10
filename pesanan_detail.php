<?php
session_start();
require_once '../components/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Cek apakah user adalah admin
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] == 'admin';

// Query untuk admin atau user biasa
if ($is_admin) {
    // Admin bisa melihat semua pesanan
    $order_query = mysqli_query($conn, "SELECT o.*, u.nama as customer_name, u.email as customer_email, u.telepon 
                                        FROM orders o 
                                        JOIN users u ON o.user_id = u.id 
                                        WHERE o.id = $order_id");
} else {
    // User hanya bisa melihat pesanannya sendiri
    $order_query = mysqli_query($conn, "SELECT o.*, u.nama, u.email, u.telepon 
                                        FROM orders o 
                                        JOIN users u ON o.user_id = u.id 
                                        WHERE o.id = $order_id AND o.user_id = $user_id");
}

$order = mysqli_fetch_assoc($order_query);

if (!$order) {
    header('Location: orders.php');
    exit();
}

// Ambil items order
$items_query = mysqli_query($conn, "SELECT oi.*, p.nama as product_name, p.gambar 
                                   FROM order_items oi 
                                   JOIN produk p ON oi.product_id = p.id 
                                   WHERE oi.order_id = $order_id");
$items = [];
while ($item = mysqli_fetch_assoc($items_query)) {
    $items[] = $item;
}

$page_title = "Detail Pesanan #" . str_pad($order_id, 6, '0', STR_PAD_LEFT);
include '../components/header.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - MinShop</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4a6cf7;
            --primary-dark: #3a5bd9;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --radius: 8px;
        }
        
        body {
            background-color: #f5f7fb;
        }
        
        .detail-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .detail-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .detail-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .detail-header h1 {
            margin: 0;
            font-size: 1.8rem;
        }
        
        .detail-header p {
            margin: 0.5rem 0 0;
            opacity: 0.9;
        }
        
        .order-info {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
        }
        
        .order-number {
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .order-number span {
            color: var(--primary);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-diproses { background: #cce5ff; color: #004085; }
        .status-dikirim { background: #d4edda; color: #155724; }
        .status-shipped { background: #d4edda; color: #155724; }
        .status-delivered { background: #d1ecf1; color: #0c5460; }
        .status-selesai { background: #d1ecf1; color: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-menunggu_pembayaran { background: #fff3cd; color: #856404; }
        .status-menunggu_verifikasi { background: #fff3cd; color: #856404; }
        
        .detail-section {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .detail-section h3 {
            color: var(--primary);
            margin-top: 0;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary);
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .info-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: var(--radius);
        }
        
        .info-row {
            display: flex;
            margin-bottom: 0.8rem;
        }
        
        .info-label {
            width: 120px;
            color: #666;
            font-weight: 500;
        }
        
        .info-value {
            flex: 1;
            color: #333;
            font-weight: 500;
        }
        
        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .items-table th {
            background: #f0f7ff;
            padding: 1rem;
            text-align: left;
            color: var(--primary);
            font-weight: 600;
            border-bottom: 2px solid var(--primary);
        }
        
        .items-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .items-table tfoot td {
            padding: 1rem;
            font-weight: bold;
            background: #f8f9fa;
        }
        
        .items-table tfoot .total-row {
            font-size: 1.2rem;
            color: var(--success);
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--radius);
        }
        
        /* Payment Proof Section */
        .payment-proof-section {
            background: #f0f7ff;
            padding: 1.5rem;
            border-radius: var(--radius);
            margin: 1rem 0;
        }
        
        .payment-proof-title {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .proof-image {
            max-width: 300px;
            border: 2px solid #ddd;
            border-radius: var(--radius);
            overflow: hidden;
            margin-top: 1rem;
        }
        
        .proof-image img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .proof-image:hover {
            border-color: var(--primary);
        }
        
        .no-proof {
            background: #f8f9fa;
            padding: 2rem;
            text-align: center;
            border-radius: var(--radius);
            color: #666;
        }
        
        .no-proof i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
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
            text-decoration: none;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin: 1.5rem 0;
        }
        
        .action-buttons a {
            flex: 1;
            text-align: center;
            padding: 0.8rem;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 500;
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
    </style>
</head>
<body>
    <?php include '../components/navbar.php'; ?>
    
    <div class="detail-container">
        <div class="detail-card">
            <div class="detail-header">
                <h1><i class="fas fa-receipt"></i> Detail Pesanan</h1>
                <p>Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></p>
            </div>

            <div class="order-info">
                <div class="order-number">
                    Total Pembayaran: <span>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
                </div>
                <div class="status-badge status-<?php echo $order['status']; ?>">
                    <i class="fas 
                        <?php 
                        if ($order['status'] == 'menunggu_pembayaran') echo 'fa-clock';
                        elseif ($order['status'] == 'diproses' || $order['status'] == 'processing') echo 'fa-cog';
                        elseif ($order['status'] == 'dikirim' || $order['status'] == 'shipped') echo 'fa-truck';
                        elseif ($order['status'] == 'selesai' || $order['status'] == 'delivered') echo 'fa-check-circle';
                        elseif ($order['status'] == 'dibatalkan') echo 'fa-times-circle';
                        else echo 'fa-info-circle';
                        ?>">
                    </i> 
                    <?php 
                    if ($order['status'] == 'menunggu_pembayaran') echo 'Menunggu Pembayaran';
                    elseif ($order['status'] == 'menunggu_verifikasi') echo 'Menunggu Verifikasi';
                    elseif ($order['status'] == 'diproses') echo 'Diproses';
                    elseif ($order['status'] == 'dikirim') echo 'Dikirim';
                    elseif ($order['status'] == 'selesai') echo 'Selesai';
                    elseif ($order['status'] == 'dibatalkan') echo 'Dibatalkan';
                    else echo ucfirst($order['status']);
                    ?>
                </div>
            </div>

            <!-- Reference Number -->
            <?php if (!empty($order['payment_reference'])): ?>
            <div class="reference-number" style="margin: 1rem 1.5rem 0;">
                <div class="label">No. Referensi Pembayaran</div>
                <div class="number"><?php echo $order['payment_reference']; ?></div>
            </div>
            <?php endif; ?>

            <!-- Informasi Pesanan -->
            <div class="detail-section">
                <h3><i class="fas fa-info-circle"></i> Informasi Pesanan</h3>
                
                <div class="detail-grid">
                    <div class="info-box">
                        <div class="info-row">
                            <span class="info-label">ID Pesanan</span>
                            <span class="info-value">#<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Tanggal</span>
                            <span class="info-value"><?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Metode Bayar</span>
                            <span class="info-value">
                                <?php 
                                if ($order['payment_method'] == 'transfer') echo 'Transfer Bank';
                                elseif ($order['payment_method'] == 'e-wallet') echo 'E-Wallet';
                                elseif ($order['payment_method'] == 'cod') echo 'COD';
                                else echo $order['payment_method'];
                                ?>
                            </span>
                        </div>
                        <?php if (!empty($order['payment_method_detail'])): ?>
                        <div class="info-row">
                            <span class="info-label">Bank / E-Wallet</span>
                            <span class="info-value"><?php echo ucfirst($order['payment_method_detail']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($order['payment_time'])): ?>
                        <div class="info-row">
                            <span class="info-label">Waktu Bayar</span>
                            <span class="info-value"><?php echo date('d M Y H:i', strtotime($order['payment_time'])); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <span class="info-label">Catatan</span>
                            <span class="info-value"><?php echo !empty($order['notes']) ? htmlspecialchars($order['notes']) : 'Tidak ada catatan'; ?></span>
                        </div>
                    </div>

                    <div class="info-box">
                        <h4 style="margin-top: 0; color: var(--primary);"><i class="fas fa-user"></i> Informasi Pelanggan</h4>
                        <div class="info-row">
                            <span class="info-label">Nama</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['nama'] ?? $order['customer_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['email'] ?? $order['customer_email']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Telepon</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['telepon'] ?? '-'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alamat Pengiriman -->
            <div class="detail-section">
                <h3><i class="fas fa-map-marker-alt"></i> Alamat Pengiriman</h3>
                <div class="info-box">
                    <p><?php echo !empty($order['shipping_address']) ? nl2br(htmlspecialchars($order['shipping_address'])) : 'Tidak ada alamat pengiriman'; ?></p>
                </div>
            </div>

            <!-- Bukti Transfer Section -->
            <div class="detail-section">
                <h3><i class="fas fa-receipt"></i> Bukti Pembayaran</h3>
                
                <div class="payment-proof-section">
                    <div class="payment-proof-title">
                        <i class="fas fa-image fa-2x"></i>
                        <h4 style="margin: 0;">Bukti Transfer</h4>
                    </div>
                    
                    <?php if (!empty($order['bukti_pembayaran'])): ?>
                        <div class="proof-image">
                            <img src="../uploads/bukti_pembayaran/<?php echo $order['bukti_pembayaran']; ?>" 
                                 alt="Bukti Pembayaran" 
                                 style="cursor: pointer;"
                                 onclick="window.open(this.src, '_blank')">
                        </div>
                        <p style="margin-top: 1rem; color: var(--success);">
                            <i class="fas fa-check-circle"></i> 
                            Bukti pembayaran telah diupload pada <?php echo date('d M Y H:i', strtotime($order['updated_at'])); ?>
                        </p>
                        <button onclick="window.open('../uploads/bukti_pembayaran/<?php echo $order['bukti_pembayaran']; ?>', '_blank')" 
                                class="btn btn-primary" style="margin-top: 1rem;">
                            <i class="fas fa-external-link-alt"></i> Lihat Fullscreen
                        </button>
                    <?php else: ?>
                        <div class="no-proof">
                            <i class="fas fa-image"></i>
                            <h4>Belum Ada Bukti Transfer</h4>
                            <p>Bukti transfer belum diupload untuk pesanan ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Items Pesanan -->
            <div class="detail-section">
                <h3><i class="fas fa-shopping-cart"></i> Items Pesanan</h3>
                
                <?php if (count($items) > 0): ?>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subtotal = 0;
                        foreach ($items as $item): 
                            $item_subtotal = $item['price'] * $item['quantity'];
                            $subtotal += $item_subtotal;
                        ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <?php if (!empty($item['gambar'])): ?>
                                        <img src="../uploads/produk/<?php echo $item['gambar']; ?>" 
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                             class="product-image">
                                    <?php else: ?>
                                        <div style="width: 60px; height: 60px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: var(--radius);">
                                            <i class="fas fa-image" style="color: #ccc; font-size: 1.5rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                                </div>
                            </td>
                            <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>Rp <?php echo number_format($item_subtotal, 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right;"><strong>Subtotal:</strong></td>
                            <td>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                        </tr>
                        <?php if (!empty($order['shipping_cost'])): ?>
                        <tr>
                            <td colspan="3" style="text-align: right;"><strong>Ongkos Kirim:</strong></td>
                            <td>Rp <?php echo number_format($order['shipping_cost'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="total-row">
                            <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                            <td><strong>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
                <?php else: ?>
                <div class="no-proof">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Tidak ada item pesanan</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons" style="padding: 0 1.5rem 1.5rem;">
                <a href="orders.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar Pesanan
                </a>
                <?php if (!$is_admin && $order['status'] == 'selesai'): ?>
                <a href="produk.php" class="btn-primary">
                    <i class="fas fa-shopping-bag"></i> Belanja Lagi
                </a>
                <?php endif; ?>
                <?php if ($is_admin): ?>
                <a href="admin/pesanan.php" class="btn-primary">
                    <i class="fas fa-tachometer-alt"></i> Kembali ke Admin
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // Auto-hide loading if exists
    setTimeout(function() {
        const loading = document.getElementById('loading');
        if (loading) loading.classList.remove('active');
    }, 3000);
    </script>

    <?php include '../components/footer.php'; ?>
</body>
</html>