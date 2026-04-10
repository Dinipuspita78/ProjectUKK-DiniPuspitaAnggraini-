<?php
// kurir/pengiriman.php
session_start();
require_once '../components/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kurir') {
    header('Location: ../login_kurir.php');
    exit();
}

$kurir_id = $_SESSION['kurir_id'];

// Handle update lokasi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_lokasi'])) {
    $order_id = $_POST['order_id'];
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);
    
    $query = "UPDATE orders SET lokasi_sekarang = ?, updated_at = NOW() WHERE id = ? AND kurir_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sii", $lokasi, $order_id, $kurir_id);
    mysqli_stmt_execute($stmt);
    
    $_SESSION['success'] = "Lokasi berhasil diupdate!";
    header('Location: pengiriman.php');
    exit();
}

// Ambil pengiriman aktif
$query = "SELECT o.*, u.nama as nama_pelanggan, u.telepon, u.email,
                 (SELECT SUM(oi.price * oi.quantity) 
                  FROM order_items oi
                  WHERE oi.order_id = o.id) as total_harga
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE o.kurir_id = ? AND o.status IN ('dikirim', 'menunggu_kurir')
          ORDER BY FIELD(o.status, 'dikirim', 'menunggu_kurir'), o.created_at DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $kurir_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengiriman Aktif - Kurir MinShop</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
         .kurir-dashboard {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        
        .kurir-sidebar {
            background: #2c3e50;
            color: white;
            padding: 1rem;
        }
        
        .kurir-profile {
            text-align: center;
            padding: 1rem 0;
            border-bottom: 1px solid #34495e;
            margin-bottom: 1rem;
        }
        
        .kurir-profile img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 0.5rem;
        }
        
        .kurir-menu {
            list-style: none;
            padding: 0;
        }
        
        .kurir-menu li {
            margin-bottom: 0.5rem;
        }
        
        .kurir-menu a {
            display: block;
            color: #ecf0f1;
            text-decoration: none;
            padding: 0.8rem;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .kurir-menu a:hover,
        .kurir-menu a.active {
            background: #34495e;
        }
        
        .kurir-menu i {
            width: 20px;
            margin-right: 10px;
        }
        
        .kurir-content {
            padding: 2rem;
            background: #f5f7fa;
        }
        .pengiriman-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .status-tracker {
            display: flex;
            justify-content: space-between;
            margin: 2rem 0;
            position: relative;
        }
        
        .status-tracker::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 50px;
            right: 50px;
            height: 2px;
            background: #ddd;
            z-index: 1;
        }
        
        .status-step {
            text-align: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        
        .status-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 3px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
        }
        
        .status-step.active .status-circle {
            border-color: #4CAF50;
            background: #4CAF50;
            color: white;
        }
        
        .status-step.completed .status-circle {
            border-color: #4CAF50;
            background: #4CAF50;
            color: white;
        }
        
        .status-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        .status-step.active .status-label {
            color: #4CAF50;
            font-weight: 500;
        }
        
        .pengiriman-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .card-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .info-item {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .info-item h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-item p {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .lokasi-form {
            background: #e3f2fd;
            padding: 1.5rem;
            border-radius: 5px;
            margin-top: 1rem;
        }
        
        .lokasi-input {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .lokasi-input input {
            flex: 1;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .btn-update {
            background: #2196F3;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-selesai {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            margin-left: 1rem;
        }
        
        .products-list {
            margin-top: 1.5rem;
        }
        
        .product-item {
            display: flex;
            align-items: center;
            padding: 0.8rem;
            border-bottom: 1px solid #eee;
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 1rem;
        }
        
        .product-info {
            flex: 1;
        }
        
        .product-name {
            font-weight: 500;
            margin-bottom: 0.3rem;
        }
        
        .product-meta {
            color: #666;
            font-size: 0.9rem;
        }
        
        .no-pengiriman {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .no-pengiriman i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #ddd;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
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
                <p><i class="fas fa-shipping-fast"></i> Kurir Aktif</p>
            </div>
            
            <ul class="kurir-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="pesanan.php"><i class="fas fa-clipboard-list"></i> Pesanan</a></li>
                <li><a href="pengiriman.php" class="active"><i class="fas fa-shipping-fast"></i> Pengiriman Aktif</a></li>
                <li><a href="riwayat.php"><i class="fas fa-history"></i> Riwayat</a></li>
                <li><a href="profil.php"><i class="fas fa-user"></i> Profil</a></li>
                <li><a href="../components/user_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="kurir-content">
            <div class="pengiriman-container">
                <h1><i class="fas fa-shipping-fast"></i> Pengiriman Aktif</h1>
                <p>Kelola pengiriman yang sedang berjalan</p>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($order = mysqli_fetch_assoc($result)): 
                        // Get order items
                        $query_items = "SELECT od.*, p.nama, p.gambar 
                                        FROM order_items od 
                                        JOIN produk p ON oi.product_id = p.id 
                                        WHERE oi.order_id = ?";
                        $stmt_items = mysqli_prepare($conn, $query_items);
                        mysqli_stmt_bind_param($stmt_items, "i", $order['id']);
                        mysqli_stmt_execute($stmt_items);
                        $items = mysqli_stmt_get_result($stmt_items);
                    ?>
                    <div class="pengiriman-card">
                        <div class="card-header">
                            <h3>Order #<?php echo $order['id']; ?></h3>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo str_replace('_', ' ', $order['status']); ?>
                            </span>
                        </div>
                        
                        <div class="card-body">
                            <!-- Status Tracker -->
                            <div class="status-tracker">
                                <div class="status-step <?php echo in_array($order['status'], ['menunggu_kurir', 'dikirim', 'diterima']) ? 'completed' : ''; ?>">
                                    <div class="status-circle">1</div>
                                    <div class="status-label">Pesanan</div>
                                </div>
                                <div class="status-step <?php echo in_array($order['status'], ['dikirim', 'diterima']) ? 'completed' : ''; ?> 
                                    <?php echo $order['status'] == 'menunggu_kurir' ? 'active' : ''; ?>">
                                    <div class="status-circle">2</div>
                                    <div class="status-label">Diambil</div>
                                </div>
                                <div class="status-step <?php echo $order['status'] == 'diterima' ? 'completed' : ''; ?>
                                    <?php echo $order['status'] == 'dikirim' ? 'active' : ''; ?>">
                                    <div class="status-circle">3</div>
                                    <div class="status-label">Dikirim</div>
                                </div>
                                <div class="status-step <?php echo $order['status'] == 'diterima' ? 'completed active' : ''; ?>">
                                    <div class="status-circle">4</div>
                                    <div class="status-label">Selesai</div>
                                </div>
                            </div>
                            
                            <!-- Informasi Pesanan -->
                            <div class="info-grid">
                                <div class="info-item">
                                    <h4><i class="fas fa-user"></i> Pelanggan</h4>
                                    <p><?php echo htmlspecialchars($order['nama_pelanggan']); ?></p>
                                </div>
                                <div class="info-item">
                                    <h4><i class="fas fa-phone"></i> Kontak</h4>
                                    <p><?php echo $order['no_hp']; ?></p>
                                </div>
                                <div class="info-item">
                                    <h4><i class="fas fa-map-marker-alt"></i> Alamat</h4>
                                    <p><?php echo htmlspecialchars($order['alamat_pengiriman']); ?></p>
                                </div>
                                <div class="info-item">
                                    <h4><i class="fas fa-money-bill-wave"></i> Total</h4>
                                    <p>Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></p>
                                </div>
                            </div>
                            
                            <!-- Update Lokasi -->
                            <div class="lokasi-form">
                                <h4><i class="fas fa-map-pin"></i> Update Lokasi Pengiriman</h4>
                                <form method="POST" action="">
                                    <div class="lokasi-input">
                                        <input type="text" name="lokasi" 
                                               placeholder="Contoh: Sedang di Jl. Sudirman No. 123" required>
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" name="update_lokasi" class="btn-update">
                                            <i class="fas fa-save"></i> Update Lokasi
                                        </button>
                                    </div>
                                </form>
                                
                                <?php if (!empty($order['lokasi_sekarang'])): ?>
                                <p><strong>Lokasi Terakhir:</strong> <?php echo htmlspecialchars($order['lokasi_sekarang']); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Daftar Produk -->
                            <div class="products-list">
                                <h4><i class="fas fa-box-open"></i> Items dalam Pesanan</h4>
                                <?php while ($item = mysqli_fetch_assoc($items)): ?>
                                <div class="product-item">
                                    <?php if (!empty($item['gambar'])): ?>
                                    <img src="../project images/<?php echo htmlspecialchars($item['gambar']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['nama']); ?>" 
                                         class="product-img">
                                    <?php else: ?>
                                    <img src="../project images/default-product.jpg" 
                                         alt="Produk" 
                                         class="product-img">
                                    <?php endif; ?>
                                    
                                    <div class="product-info">
                                        <div class="product-name"><?php echo htmlspecialchars($item['nama']); ?></div>
                                        <div class="product-meta">
                                            <?php echo $item['jumlah']; ?> x Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?> 
                                            = Rp <?php echo number_format($item['jumlah'] * $item['harga'], 0, ',', '.'); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="action-buttons">
                                <?php if ($order['status'] == 'menunggu_kurir'): ?>
                                <button onclick="updateStatus(<?php echo $order['id']; ?>, 'dikirim')" 
                                        class="btn-update">
                                    <i class="fas fa-check-circle"></i> Ambil Pesanan
                                </button>
                                <?php elseif ($order['status'] == 'dikirim'): ?>
                                <button onclick="updateStatus(<?php echo $order['id']; ?>, 'diterima')" 
                                        class="btn-selesai">
                                    <i class="fas fa-flag-checkered"></i> Tandai Selesai
                                </button>
                                <?php endif; ?>
                                
                                <a href="detail_pesanan.php?id=<?php echo $order['id']; ?>" 
                                   class="btn-update" style="background: #6c757d;">
                                    <i class="fas fa-info-circle"></i> Detail Lengkap
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-pengiriman">
                        <i class="fas fa-box-open"></i>
                        <h3>Tidak Ada Pengiriman Aktif</h3>
                        <p>Belum ada pesanan yang ditugaskan untuk Anda.</p>
                        <a href="pesanan.php" class="btn btn-primary">
                            <i class="fas fa-clipboard-list"></i> Lihat Semua Pesanan
                        </a>
                    </div>
                <?php endif; ?>
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