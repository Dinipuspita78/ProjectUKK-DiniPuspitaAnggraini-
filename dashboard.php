<?php
session_start();
require_once '../components/database.php';

// Cek apakah user sudah login dan rolenya kurir
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'kurir') {
    header('Location: ../login_kurir.php');
    exit();
}

// Ambil kurir_id dari session
if (isset($_SESSION['kurir_id'])) {
    $kurir_id = $_SESSION['kurir_id'];
} else {
    // Jika tidak ada, coba ambil dari database berdasarkan user_id
    $user_id = $_SESSION['user_id'];
    $query_user = "SELECT id, nama, kendaraan, status FROM kurir WHERE user_id = ?";
    $stmt_user = mysqli_prepare($conn, $query_user);
    mysqli_stmt_bind_param($stmt_user, "i", $user_id);
    mysqli_stmt_execute($stmt_user);
    $result_user = mysqli_stmt_get_result($stmt_user);
    
    if ($row = mysqli_fetch_assoc($result_user)) {
        $_SESSION['kurir_id'] = $row['id'];
        $_SESSION['kurir_nama'] = $row['nama'];
        $_SESSION['kurir_kendaraan'] = $row['kendaraan'];
        $_SESSION['kurir_status'] = $row['status'];
        $kurir_id = $row['id'];
    } else {
        // Jika tidak ditemukan, redirect ke login
        header('Location: ../login_kurir.php?error=not_found');
        exit();
    }
    mysqli_stmt_close($stmt_user);
}

// AMBIL DATA KURIR
$query_kurir = "SELECT * FROM kurir WHERE id = ?";
$stmt_kurir = mysqli_prepare($conn, $query_kurir);
mysqli_stmt_bind_param($stmt_kurir, "i", $kurir_id);
mysqli_stmt_execute($stmt_kurir);
$result_kurir = mysqli_stmt_get_result($stmt_kurir);
$kurir = mysqli_fetch_assoc($result_kurir);
mysqli_stmt_close($stmt_kurir);

// HITUNG STATISTIK UNTUK DASHBOARD
$query_stats = "SELECT 
    COUNT(*) as total_pesanan,
    SUM(CASE WHEN status = 'dikirim' AND kurir_id = ? THEN 1 ELSE 0 END) as sedang_dikirim,
    SUM(CASE WHEN status = 'selesai' AND kurir_id = ? THEN 1 ELSE 0 END) as sukses,
    SUM(CASE WHEN status = 'dibatalkan' AND kurir_id = ? THEN 1 ELSE 0 END) as dibatalkan
    FROM orders";
    
$stmt_stats = mysqli_prepare($conn, $query_stats);
mysqli_stmt_bind_param($stmt_stats, "iii", $kurir_id, $kurir_id, $kurir_id);
mysqli_stmt_execute($stmt_stats);
$result_stats = mysqli_stmt_get_result($stmt_stats);
$stats = mysqli_fetch_assoc($result_stats);
mysqli_stmt_close($stmt_stats);

// AMBIL PESANAN MENUNGGU KURIR (BUKAN dikirim)
$query_menunggu = "SELECT 
    o.id, o.shipping_address, o.total_amount, o.created_at,
    u.nama as nama_pelanggan, u.telepon,
    o.status
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.status = 'menunggu_kurir' 
    ORDER BY o.created_at ASC 
    LIMIT 5";

$result_menunggu = mysqli_query($conn, $query_menunggu);

// AMBIL PESANAN YANG SEDANG DIKIRIM OLEH KURIR INI
$query_dikirim = "SELECT 
    o.id, o.shipping_address, o.total_amount, o.created_at,
    u.nama as nama_pelanggan, u.telepon
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.status = 'dikirim' AND o.kurir_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT 5";

$stmt_dikirim = mysqli_prepare($conn, $query_dikirim);
mysqli_stmt_bind_param($stmt_dikirim, "i", $kurir_id);
mysqli_stmt_execute($stmt_dikirim);
$result_dikirim = mysqli_stmt_get_result($stmt_dikirim);
mysqli_stmt_close($stmt_dikirim);

// AMBIL PESANAN HARI INI
$today = date('Y-m-d');
$query_hari_ini = "SELECT COUNT(*) as jumlah FROM orders 
                  WHERE kurir_id = ? AND DATE(created_at) = ?";
$stmt_hari_ini = mysqli_prepare($conn, $query_hari_ini);
mysqli_stmt_bind_param($stmt_hari_ini, "is", $kurir_id, $today);
mysqli_stmt_execute($stmt_hari_ini);
$result_hari_ini = mysqli_stmt_get_result($stmt_hari_ini);
$pesanan_hari_ini = mysqli_fetch_assoc($result_hari_ini);
mysqli_stmt_close($stmt_hari_ini);

$page_title = "Dashboard Kurir";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kurir - MinShop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Main Content Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f4f7fc;
        }

        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 25px;
            transition: margin-left 0.3s ease;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px 25px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .page-header h1 {
            font-size: 26px;
            color: #1e2b3a;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header h1 i {
            color: #4a6cf7;
        }

        .welcome-text {
            color: #6c757d;
            font-size: 15px;
        }

        .welcome-text strong {
            color: #4a6cf7;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.03);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }

        .stat-info h3 {
            font-size: 28px;
            font-weight: 700;
            color: #1e2b3a;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #6c757d;
            font-size: 14px;
            font-weight: 500;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #f0f7ff, #e8f0fe);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #4a6cf7;
        }

        /* Order Sections */
        .orders-section {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 25px;
        }

        .order-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .order-card h2 {
            font-size: 18px;
            color: #1e2b3a;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .order-card h2 i {
            color: #4a6cf7;
        }

        .order-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item:hover {
            background: #f8f9fa;
            border-radius: 8px;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .order-id {
            font-weight: 700;
            color: #4a6cf7;
            background: #eef2ff;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 13px;
        }

        .order-date {
            font-size: 12px;
            color: #adb5bd;
        }

        .order-customer {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .order-customer i {
            width: 16px;
            color: #6c757d;
            font-size: 13px;
        }

        .order-customer span {
            font-size: 14px;
            color: #495057;
        }

        .order-address {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 12px;
            padding-left: 28px;
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #dee2e6;
        }

        .order-total {
            font-weight: 700;
            color: #28a745;
            font-size: 15px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #4a6cf7;
            color: white;
        }

        .btn-primary:hover {
            background: #3a5bd9;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-outline {
            background: white;
            color: #4a6cf7;
            border: 1px solid #4a6cf7;
        }

        .btn-outline:hover {
            background: #4a6cf7;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #adb5bd;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #dee2e6;
        }

        .empty-state p {
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }
            
            .orders-section {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                padding: 20px;
            }
        }

        /* Alert */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- INCLUDE SIDEBAR -->
        <?php include '../components/sidebar_kurir.php'; ?>
        
        <!-- MAIN CONTENT -->
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1>
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard Kurir
                </h1>
                <div class="welcome-text">
                    Selamat datang, <strong><?php echo htmlspecialchars($kurir['nama'] ?? 'Kurir'); ?></strong>
                    <span style="margin-left: 10px; padding: 5px 12px; background: #eef2ff; border-radius: 30px; font-size: 13px;">
                        <i class="fas fa-motorcycle" style="color: #4a6cf7;"></i> 
                        <?php echo htmlspecialchars($kurir['kendaraan'] ?? 'Motor'); ?>
                    </span>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_pesanan'] ?? 0, 0, ',', '.'); ?></h3>
                        <p>Total Pesanan</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?php echo $stats['sedang_dikirim'] ?? 0; ?></h3>
                        <p>Sedang Dikirim</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?php echo $stats['sukses'] ?? 0; ?></h3>
                        <p>Sukses</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?php echo $stats['dibatalkan'] ?? 0; ?></h3>
                        <p>Dibatalkan</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>

            <!-- Orders Sections -->
            <div class="orders-section">
                <!-- Pesanan Menunggu Kurir -->
                <div class="order-card">
                    <h2>
                        <i class="fas fa-clock"></i>
                        Pesanan Menunggu Kurir
                    </h2>
                    
                    <?php if ($result_menunggu && mysqli_num_rows($result_menunggu) > 0): ?>
                        <?php while ($order = mysqli_fetch_assoc($result_menunggu)): ?>
                        <div class="order-item">
                            <div class="order-header">
                                <span class="order-id">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                <span class="order-date"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="order-customer">
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($order['nama_pelanggan'] ?? 'Pelanggan'); ?></span>
                                <i class="fas fa-phone" style="margin-left: 15px;"></i>
                                <span><?php echo htmlspecialchars($order['telepon'] ?? '-'); ?></span>
                            </div>
                            <div class="order-address">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php 
                                $alamat = $order['shipping_address'] ?? 'Alamat tidak tersedia';
                                echo htmlspecialchars(substr($alamat, 0, 60)) . (strlen($alamat) > 60 ? '...' : '');
                                ?>
                            </div>
                            <div class="order-footer">
                                <span class="order-total">Rp <?php echo number_format($order['total_amount'] ?? 0, 0, ',', '.'); ?></span>
                                <a href="ambil_pesanan.php?id=<?php echo $order['id']; ?>" 
                                   class="btn btn-success" 
                                   onclick="return confirm('Ambil pesanan #<?php echo $order['id']; ?>?')">
                                    <i class="fas fa-hand-paper"></i> Ambil
                                </a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <p>Tidak ada pesanan yang menunggu</p>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 20px; text-align: right;">
                        <a href="pesanan.php?status=menunggu_kurir" class="btn btn-outline">
                            Lihat Semua <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Pengiriman Aktif -->
                <div class="order-card">
                    <h2>
                        <i class="fas fa-truck"></i>
                        Pengiriman Aktif
                    </h2>
                    
                    <?php if ($result_dikirim && mysqli_num_rows($result_dikirim) > 0): ?>
                        <?php while ($order = mysqli_fetch_assoc($result_dikirim)): ?>
                        <div class="order-item">
                            <div class="order-header">
                                <span class="order-id">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                <span class="order-date"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="order-customer">
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($order['nama_pelanggan'] ?? 'Pelanggan'); ?></span>
                                <i class="fas fa-phone" style="margin-left: 15px;"></i>
                                <span><?php echo htmlspecialchars($order['telepon'] ?? '-'); ?></span>
                            </div>
                            <div class="order-address">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php 
                                $alamat = $order['shipping_address'] ?? 'Alamat tidak tersedia';
                                echo htmlspecialchars(substr($alamat, 0, 60)) . (strlen($alamat) > 60 ? '...' : '');
                                ?>
                            </div>
                            <div class="order-footer">
                                <span class="order-total">Rp <?php echo number_format($order['total_amount'] ?? 0, 0, ',', '.'); ?></span>
                                <a href="selesaikan_pengiriman.php?id=<?php echo $order['id']; ?>" 
                                   class="btn btn-success"
                                   onclick="return confirm('Selesaikan pengiriman #<?php echo $order['id']; ?>?')">
                                    <i class="fas fa-check"></i> Selesai
                                </a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-truck"></i>
                            <p>Tidak ada pengiriman aktif</p>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 20px; text-align: right;">
                        <a href="pesanan.php?status=dikirim" class="btn btn-outline">
                            Lihat Semua <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div style="background: white; border-radius: 16px; padding: 20px; margin-top: 15px; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 45px; height: 45px; background: #eef2ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #4a6cf7;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div>
                        <h4 style="color: #1e2b3a; margin-bottom: 5px;">Ringkasan Hari Ini</h4>
                        <p style="color: #6c757d; font-size: 14px;">
                            <strong><?php echo $pesanan_hari_ini['jumlah'] ?? 0; ?></strong> pesanan ditangani hari ini
                        </p>
                    </div>
                </div>
                <div>
                    <span style="background: #eef2ff; color: #4a6cf7; padding: 8px 16px; border-radius: 30px; font-size: 13px; font-weight: 500;">
                        <i class="fas fa-clock"></i> <?php echo date('l, d F Y'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
// 🔥 FIX: TIDAK PERLU MENUTUP STATEMENT LAGI!
// Semua statement sudah ditutup satu per satu setelah digunakan
// Hanya tutup koneksi database
mysqli_close($conn);
?>