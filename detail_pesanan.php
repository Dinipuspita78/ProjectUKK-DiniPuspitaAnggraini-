<?php
// kurir/detail_pesanan.php
session_start();
require_once '../components/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kurir') {
    header('Location: login_kurir.php');
    exit();
}

$kurir_id = $_SESSION['kurir_id'];
$order_id = $_GET['id'] ?? 0;

// Ambil detail pesanan
$query = "SELECT o.*, u.nama as nama_pelanggan, u.email, u.telepon, 
                 k.nama as nama_kurir, k.telepon as hp_kurir,
                 (SELECT SUM(oi.price * oi.quantity) 
                  FROM order_items oi
                  WHERE oi.order_id = o.id) as total_harga
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          LEFT JOIN kurir k ON o.kurir_id = k.id 
          WHERE o.id = ? AND o.kurir_id = ?";
          
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $kurir_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    header('Location: pesanan.php');
    exit();
}

// Ambil items pesanan
$query_items = "SELECT od.*, p.nama, p.gambar, p.deskripsi 
                FROM order_details od 
                JOIN produk p ON od.product_id = p.id 
                WHERE od.order_id = ?";
$stmt_items = mysqli_prepare($conn, $query_items);
mysqli_stmt_bind_param($stmt_items, "i", $order_id);
mysqli_stmt_execute($stmt_items);
$items = mysqli_stmt_get_result($stmt_items);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?php echo $order_id; ?> - Kurir MinShop</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .detail-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .back-button {
            margin-bottom: 1rem;
        }
        
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1rem;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
        }
        
        .order-header {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-title h1 {
            margin: 0 0 0.5rem 0;
        }
        
        .order-meta {
            display: flex;
            gap: 2rem;
            color: #666;
        }
        
        .order-status {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .status-pending { background: #ffeaa7; color: #d35400; }
        .status-dikirim { background: #74b9ff; color: #0984e3; }
        .status-diterima { background: #55efc4; color: #00b894; }
        .status-dibatalkan { background: #fab1a0; color: #d63031; }
        
        .order-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .order-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            margin: 0 0 1.5rem 0;
            padding-bottom: 0.8rem;
            border-bottom: 2px solid #f1f1f1;
            color: #333;
        }
        
        .section-title i {
            margin-right: 0.5rem;
            color: #2196F3;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .items-table th {
            text-align: left;
            padding: 1rem;
            background: #f8f9fa;
            border-bottom: 2px solid #eee;
            color: #555;
        }
        
        .items-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .item-name {
            font-weight: 500;
            margin-bottom: 0.3rem;
        }
        
        .item-meta {
            color: #666;
            font-size: 0.9rem;
        }
        
        .summary-grid {
            display: grid;
            gap: 1rem;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .summary-item.total {
            border-top: 2px solid #eee;
            margin-top: 1rem;
            padding-top: 1rem;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .info-item {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .info-item label {
            display: block;
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.3rem;
        }
        
        .info-item span {
            font-weight: 500;
            color: #333;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: #2196F3;
            color: white;
        }
        
        .btn-success {
            background: #4CAF50;
            color: white;
        }
        
        .btn-danger {
            background: #f44336;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .timeline {
            position: relative;
            padding-left: 2rem;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #ddd;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
            padding-left: 1rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -13px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #2196F3;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #2196F3;
        }
        
        .timeline-date {
            font-size: 0.9rem;
            color: #666;
        }
        
        .timeline-content {
            background: #f8f9fa;
            padding: 0.8rem;
            border-radius: 5px;
            margin-top: 0.3rem;
        }
        
        .note-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 5px;
        }
        
        .note-box h4 {
            margin: 0 0 0.5rem 0;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="kurir-dashboard">
        <!-- Sidebar -->
        <div class="kurir-sidebar">
            <div class="kurir-profile">
                <img src="../project images/avatar_kurir.png" alt="Foto Kurir">
                <h3><?php echo htmlspecialchars($_SESSION['nama']); ?></h3>
                <p><i class="fas fa-box"></i> Detail Pesanan</p>
            </div>
            
            <ul class="kurir-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="pesanan.php"><i class="fas fa-clipboard-list"></i> Pesanan</a></li>
                <li><a href="pengiriman.php"><i class="fas fa-shipping-fast"></i> Pengiriman Aktif</a></li>
                <li><a href="riwayat.php"><i class="fas fa-history"></i> Riwayat</a></li>
                <li><a href="profil.php"><i class="fas fa-user"></i> Profil</a></li>
                <li><a href="../components/user_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="kurir-content">
            <div class="detail-container">
                <!-- Back Button -->
                <div class="back-button">
                    <a href="javascript:history.back()" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
                
                <!-- Order Header -->
                <div class="order-header">
                    <div class="order-title">
                        <h1><i class="fas fa-file-invoice"></i> Pesanan #<?php echo $order['id']; ?></h1>
                        <div class="order-meta">
                            <span><i class="fas fa-calendar"></i> <?php echo date('d F Y H:i', strtotime($order['created_at'])); ?></span>
                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($order['nama_pelanggan']); ?></span>
                        </div>
                    </div>
                    <span class="order-status status-<?php echo $order['status']; ?>">
                        <?php echo $order['status']; ?>
                    </span>
                </div>
                
                <div class="order-content">
                    <!-- Left Column -->
                    <div>
                        <!-- Items Section -->
                        <div class="order-section">
                            <h3 class="section-title"><i class="fas fa-box-open"></i> Items Pesanan</h3>
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Harga</th>
                                        <th>Qty</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_items = 0;
                                    while ($item = mysqli_fetch_assoc($items)): 
                                        $total_items += $item['jumlah'];
                                    ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <?php if (!empty($item['gambar'])): ?>
                                                <img src="../project images/<?php echo htmlspecialchars($item['gambar']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['nama']); ?>" 
                                                     class="item-image">
                                                <?php else: ?>
                                                <img src="../project images/default-product.jpg" 
                                                     alt="Produk" 
                                                     class="item-image">
                                                <?php endif; ?>
                                                
                                                <div>
                                                    <div class="item-name"><?php echo htmlspecialchars($item['nama']); ?></div>
                                                    <div class="item-meta"><?php echo substr($item['deskripsi'], 0, 50); ?>...</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                                        <td><?php echo $item['jumlah']; ?></td>
                                        <td>Rp <?php echo number_format($item['harga'] * $item['jumlah'], 0, ',', '.'); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Shipping Info -->
                        <div class="order-section">
                            <h3 class="section-title"><i class="fas fa-map-marker-alt"></i> Informasi Pengiriman</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Nama Penerima</label>
                                    <span><?php echo htmlspecialchars($order['nama_pelanggan']); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>No. HP</label>
                                    <span><?php echo $order['no_hp']; ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Email</label>
                                    <span><?php echo $order['email']; ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Alamat Lengkap</label>
                                    <span><?php echo nl2br(htmlspecialchars($order['alamat_pengiriman'])); ?></span>
                                </div>
                                <?php if (!empty($order['catatan'])): ?>
                                <div class="info-item" style="grid-column: 1 / -1;">
                                    <label>Catatan Pelanggan</label>
                                    <span><?php echo nl2br(htmlspecialchars($order['catatan'])); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div>
                        <!-- Order Summary -->
                        <div class="order-section">
                            <h3 class="section-title"><i class="fas fa-receipt"></i> Ringkasan Pesanan</h3>
                            <div class="summary-grid">
                                <div class="summary-item">
                                    <span>Total Items</span>
                                    <span><?php echo $total_items; ?> items</span>
                                </div>
                                <div class="summary-item">
                                    <span>Subtotal</span>
                                    <span>Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="summary-item">
                                    <span>Ongkos Kirim</span>
                                    <span>Rp <?php echo number_format(10000, 0, ',', '.'); ?></span>
                                </div>
                                <div class="summary-item total">
                                    <span>Total Pembayaran</span>
                                    <span>Rp <?php echo number_format($order['total_harga'] + 10000, 0, ',', '.'); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Kurir Info -->
                        <div class="order-section">
                            <h3 class="section-title"><i class="fas fa-user-tie"></i> Informasi Kurir</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Nama Kurir</label>
                                    <span><?php echo htmlspecialchars($order['nama_kurir'] ?? $_SESSION['nama']); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>No. HP Kurir</label>
                                    <span><?php echo $order['hp_kurir'] ?? '-'; ?></span>
                                </div>
                                <?php if (!empty($order['lokasi_sekarang'])): ?>
                                <div class="info-item" style="grid-column: 1 / -1;">
                                    <label>Lokasi Terakhir</label>
                                    <span><?php echo htmlspecialchars($order['lokasi_sekarang']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Order Timeline -->
                        <div class="order-section">
                            <h3 class="section-title"><i class="fas fa-history"></i> Timeline Pesanan</h3>
                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="timeline-date">
                                        <?php echo date('d M Y H:i', strtotime($order['created_at'])); ?>
                                    </div>
                                    <div class="timeline-content">
                                        Pesanan dibuat oleh <?php echo htmlspecialchars($order['nama_pelanggan']); ?>
                                    </div>
                                </div>
                                
                                <?php if ($order['status'] != 'pending'): ?>
                                <div class="timeline-item">
                                    <div class="timeline-date">
                                        <?php echo date('d M Y H:i', strtotime($order['updated_at'])); ?>
                                    </div>
                                    <div class="timeline-content">
                                        Status diubah menjadi: <?php echo $order['status']; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="order-section">
                            <h3 class="section-title"><i class="fas fa-cogs"></i> Aksi</h3>
                            <div class="action-buttons">
                                <?php if ($order['status'] == 'menunggu_kurir'): ?>
                                <button onclick="updateStatus(<?php echo $order['id']; ?>, 'dikirim')" 
                                        class="btn btn-primary">
                                    <i class="fas fa-check-circle"></i> Ambil Pesanan
                                </button>
                                <?php elseif ($order['status'] == 'dikirim'): ?>
                                <button onclick="updateStatus(<?php echo $order['id']; ?>, 'diterima')" 
                                        class="btn btn-success">
                                    <i class="fas fa-flag-checkered"></i> Tandai Selesai
                                </button>
                                <?php endif; ?>
                                
                                <a href="../admin/print_invoice.php?id=<?php echo $order['id']; ?>" 
                                   target="_blank" class="btn btn-secondary">
                                    <i class="fas fa-print"></i> Cetak Invoice
                                </a>
                            </div>
                            
                            <?php if ($order['status'] == 'dikirim'): ?>
                            <div class="note-box">
                                <h4><i class="fas fa-info-circle"></i> Catatan Pengiriman</h4>
                                <p>Pastikan untuk mengupdate lokasi secara berkala agar pelanggan dapat melacak pengiriman.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function updateStatus(orderId, status) {
        if (confirm('Apakah Anda yakin ingin mengupdate status pesanan?')) {
            fetch('update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + orderId + '&status=' + status
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Status berhasil diupdate!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    }
    </script>
</body>
</html>