<?php
session_start();
require_once '../components/database.php';

// CEK LOGIN KURIR
if (!isset($_SESSION['kurir_id'])) {
    header('Location: ../login_kurir.php');
    exit();
}

$kurir_id = $_SESSION['kurir_id'];
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'menunggu_kurir';

// ============================================
// HITUNG STATISTIK
// ============================================
$stats_query = "SELECT 
    SUM(CASE WHEN status = 'menunggu_kurir' AND kurir_id IS NULL THEN 1 ELSE 0 END) as menunggu,
    SUM(CASE WHEN status = 'dikirim' AND kurir_id = ? THEN 1 ELSE 0 END) as dikirim,
    SUM(CASE WHEN status = 'selesai' AND kurir_id = ? THEN 1 ELSE 0 END) as selesai,
    SUM(CASE WHEN status = 'dibatalkan' AND kurir_id = ? THEN 1 ELSE 0 END) as dibatalkan,
    SUM(CASE WHEN kurir_id = ? THEN 1 ELSE 0 END) as total_semua
    FROM orders";

$stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stmt, "iiii", $kurir_id, $kurir_id, $kurir_id, $kurir_id);
mysqli_stmt_execute($stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// ============================================
// 🔥 FIX: QUERY UNTUK MASING-MASING FILTER
// ============================================

if ($status_filter == 'menunggu_kurir') {
    $query = "SELECT 
        o.id,
        o.shipping_address,
        o.total_amount,
        o.created_at,
        o.status,
        o.kurir_id,
        u.nama as nama_pelanggan,
        u.telepon,
        (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as total_qty,
        TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) as menit_menunggu
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.status = 'menunggu_kurir' 
        AND o.kurir_id IS NULL
        ORDER BY o.created_at ASC";
    
    $result = mysqli_query($conn, $query);
    $title = "Menunggu Kurir";
    $icon = "clock";
    $warna = "#ffc107";

} elseif ($status_filter == 'dikirim') {
    $query = "SELECT 
        o.id,
        o.shipping_address,
        o.total_amount,
        o.created_at,
        o.updated_at,
        o.status,
        o.kurir_id,
        u.nama as nama_pelanggan,
        u.telepon,
        (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as total_qty,
        TIMESTAMPDIFF(MINUTE, o.updated_at, NOW()) as menit_pengiriman
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.status = 'dikirim' 
        AND o.kurir_id = ?
        ORDER BY o.updated_at DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $kurir_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $title = "Sedang Dikirim";
    $icon = "truck";
    $warna = "#4a6cf7";

} elseif ($status_filter == 'selesai') {
    $query = "SELECT 
        o.id,
        o.shipping_address,
        o.total_amount,
        o.created_at,
        o.updated_at,
        o.status,
        u.nama as nama_pelanggan,
        u.telepon,
        (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as total_qty
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.status = 'selesai' 
        AND o.kurir_id = ?
        ORDER BY o.updated_at DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $kurir_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $title = "Selesai";
    $icon = "check-circle";
    $warna = "#28a745";

} elseif ($status_filter == 'dibatalkan') {
    // 🔥 PERBAIKAN: HAPUS KARAKTER TERSEMBUNYI!
    $query = "SELECT 
        o.id,
        o.shipping_address,
        o.total_amount,
        o.created_at,
        o.updated_at,
        o.status,
        u.nama as nama_pelanggan,
        u.telepon,
        (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as total_qty
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.status = 'dibatalkan' 
        AND o.kurir_id = ?
        ORDER BY o.updated_at DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $kurir_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $title = "Dibatalkan";
    $icon = "times-circle";
    $warna = "#dc3545";

} else {
    $query = "SELECT 
        o.id,
        o.shipping_address,
        o.total_amount,
        o.created_at,
        o.updated_at,
        o.status,
        u.nama as nama_pelanggan,
        u.telepon,
        (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as total_qty
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.kurir_id = ?
        ORDER BY o.updated_at DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $kurir_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $title = "Semua Pesanan";
    $icon = "list";
    $warna = "#6c757d";
}

// CEK ERROR QUERY
if (!$result) {
    die("❌ ERROR QUERY: " . mysqli_error($conn));
}

// AMBIL DATA KURIR
$query_kurir = "SELECT * FROM kurir WHERE id = ?";
$stmt = mysqli_prepare($conn, $query_kurir);
mysqli_stmt_bind_param($stmt, "i", $kurir_id);
mysqli_stmt_execute($stmt);
$kurir = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$total_data = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - MinShop Kurir</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== RESET & VARIABLES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f1f5f9;
        }

        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 25px;
            transition: margin-left 0.3s;
        }

        /* ===== PAGE HEADER ===== */
        .page-header {
            background: white;
            border-radius: 16px;
            padding: 25px 30px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            border-left: 6px solid <?php echo $warna; ?>;
        }

        .page-header h1 {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 26px;
            color: #1e293b;
        }

        .page-header h1 i {
            color: <?php echo $warna; ?>;
        }

        .kurir-badge {
            background: #eef2ff;
            padding: 10px 20px;
            border-radius: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #4a6cf7;
        }

        /* ===== STATS CARDS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-left: 4px solid;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }

        .stat-card.menunggu { border-left-color: #ffc107; }
        .stat-card.dikirim { border-left-color: #4a6cf7; }
        .stat-card.selesai { border-left-color: #28a745; }
        .stat-card.dibatalkan { border-left-color: #dc3545; }
        .stat-card.semua { border-left-color: #6c757d; }

        .stat-info h3 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #1e293b;
        }

        .stat-info p {
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: #eef2ff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4a6cf7;
            font-size: 22px;
        }

        /* ===== FILTER TABS ===== */
        .filter-tabs {
            background: white;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .filter-tabs a {
            padding: 12px 24px;
            border-radius: 40px;
            text-decoration: none;
            color: #64748b;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f8fafc;
            transition: all 0.3s;
        }

        .filter-tabs a:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .filter-tabs a.active {
            background: <?php echo $warna; ?>;
            color: white;
        }

        /* ===== INFO PANEL ===== */
        .info-panel {
            background: white;
            border-radius: 12px;
            padding: 20px 25px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 4px solid <?php echo $warna; ?>;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .info-icon {
            width: 45px;
            height: 45px;
            background: <?php echo $warna; ?>20;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: <?php echo $warna; ?>;
            font-size: 20px;
        }

        .info-text {
            flex: 1;
        }

        .info-text h4 {
            color: #1e293b;
            margin-bottom: 5px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-text p {
            color: #64748b;
            font-size: 14px;
        }

        .data-badge {
            background: <?php echo $warna; ?>;
            color: white;
            padding: 3px 12px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
        }

        /* ===== TABLE ===== */
        .table-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead tr {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }

        th {
            text-align: left;
            padding: 18px 20px;
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        tr:hover td {
            background: #f8fafc;
        }

        .order-id {
            background: #eef2ff;
            color: #4a6cf7;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 700;
            display: inline-block;
        }

        .customer-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .customer-name {
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .customer-phone {
            font-size: 12px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .address {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 13px;
            color: #334155;
            max-width: 300px;
            line-height: 1.5;
        }

        .address i {
            color: #dc3545;
            margin-top: 3px;
        }

        .item-badge {
            display: inline-block;
            background: #f1f5f9;
            color: #64748b;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            margin-top: 8px;
        }

        .amount {
            font-weight: 700;
            color: #059669;
            font-size: 16px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.menunggu_kurir {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.dikirim {
            background: #cce5ff;
            color: #004085;
        }

        .status-badge.selesai {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.dibatalkan {
            background: #f8d7da;
            color: #721c24;
        }

        .wait-time {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }

        .wait-time.normal {
            background: #dcfce7;
            color: #166534;
        }

        .wait-time.warning {
            background: #fef9c3;
            color: #854d0e;
        }

        .wait-time.urgent {
            background: #fee2e2;
            color: #991b1b;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.8; }
            100% { opacity: 1; }
        }

        .btn-group {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 11px;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40,167,69,0.3);
        }

        .btn-primary {
            background: #4a6cf7;
            color: white;
        }

        .btn-primary:hover {
            background: #3a5bd9;
            transform: translateY(-2px);
        }

        .btn-info {
            background: #e2e8f0;
            color: #1e293b;
        }

        .btn-info:hover {
            background: #cbd5e1;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state i {
            font-size: 64px;
            color: #cbd5e1;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #1e293b;
            margin-bottom: 10px;
            font-size: 20px;
        }

        .empty-state p {
            margin-bottom: 25px;
        }

        .btn-reset {
            display: inline-block;
            padding: 12px 24px;
            background: #4a6cf7;
            color: white;
            text-decoration: none;
            border-radius: 40px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-reset:hover {
            background: #3a5bd9;
            transform: translateY(-2px);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-tabs {
                flex-direction: column;
            }
            
            .filter-tabs a {
                width: 100%;
                justify-content: center;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            table {
                min-width: 1000px;
            }
        }
    </style>
</head>
<?php include '../components/sidebar_kurir.php';
         ?>
<body>
    <div class="dashboard-wrapper">
        <!-- SIDEBAR -->
        <?php include '../components/sidebar_kurir.php';
         ?>
        
        <!-- MAIN CONTENT -->
        <div class="main-content">
            <!-- PAGE HEADER -->
            <div class="page-header">
                <h1>
                    <i class="fas fa-<?php echo $icon; ?>"></i>
                    <?php echo $title; ?>
                </h1>
                <div class="kurir-badge">
                    <i class="fas fa-motorcycle"></i>
                    <?php echo htmlspecialchars($kurir['nama'] ?? $_SESSION['kurir_nama'] ?? 'Maskurir'); ?>
                    <span style="width: 8px; height: 8px; background: #28a745; border-radius: 50%; display: inline-block; margin-left: 5px;"></span>
                </div>
            </div>
            
            <!-- STATS CARDS -->
            <div class="stats-grid">
                <div class="stat-card menunggu">
                    <div class="stat-info">
                        <h3><?php echo $stats['menunggu'] ?? 0; ?></h3>
                        <p>Menunggu Kurir</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-card dikirim">
                    <div class="stat-info">
                        <h3><?php echo $stats['dikirim'] ?? 0; ?></h3>
                        <p>Sedang Dikirim</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                </div>
                <div class="stat-card selesai">
                    <div class="stat-info">
                        <h3><?php echo $stats['selesai'] ?? 0; ?></h3>
                        <p>Selesai</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-card dibatalkan">
                    <div class="stat-info">
                        <h3><?php echo $stats['dibatalkan'] ?? 0; ?></h3>
                        <p>Dibatalkan</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
                <div class="stat-card semua">
                    <div class="stat-info">
                        <h3><?php echo $stats['total_semua'] ?? 0; ?></h3>
                        <p>Total Semua</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                </div>
            </div>
            
            <!-- FILTER TABS -->
            <div class="filter-tabs">
                <a href="?status=menunggu_kurir" class="<?php echo $status_filter == 'menunggu_kurir' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Menunggu (<?php echo $stats['menunggu'] ?? 0; ?>)
                </a>
                <a href="?status=dikirim" class="<?php echo $status_filter == 'dikirim' ? 'active' : ''; ?>">
                    <i class="fas fa-truck"></i> Dikirim (<?php echo $stats['dikirim'] ?? 0; ?>)
                </a>
                <a href="?status=selesai" class="<?php echo $status_filter == 'selesai' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Selesai (<?php echo $stats['selesai'] ?? 0; ?>)
                </a>
                <a href="?status=dibatalkan" class="<?php echo $status_filter == 'dibatalkan' ? 'active' : ''; ?>">
                    <i class="fas fa-times-circle"></i> Dibatalkan (<?php echo $stats['dibatalkan'] ?? 0; ?>)
                </a>
                <a href="?status=semua" class="<?php echo $status_filter == 'semua' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> Semua (<?php echo $stats['total_semua'] ?? 0; ?>)
                </a>
            </div>
            
            <!-- INFO PANEL -->
            <div class="info-panel">
                <div class="info-icon">
                    <i class="fas fa-<?php echo $icon; ?>"></i>
                </div>
                <div class="info-text">
                    <h4>
                        <?php echo $title; ?>
                        <span class="data-badge"><?php echo $total_data; ?> Data</span>
                    </h4>
                    <p>
                        <?php if ($status_filter == 'menunggu_kurir'): ?>
                            Pesanan yang siap diambil. Ambil pesanan untuk memulai pengiriman.
                        <?php elseif ($status_filter == 'dikirim'): ?>
                            Pesanan yang sedang Anda kirim. Selesaikan setelah sampai ke tujuan.
                        <?php elseif ($status_filter == 'selesai'): ?>
                            Pesanan yang telah berhasil Anda selesaikan.
                        <?php elseif ($status_filter == 'dibatalkan'): ?>
                            Pesanan yang dibatalkan.
                        <?php else: ?>
                            Semua pesanan yang pernah Anda tangani.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <!-- TABLE PESANAN -->
            <div class="table-container">
                <?php if ($total_data > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID Pesanan</th>
                                <th>Pelanggan</th>
                                <th>Alamat</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Waktu</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <span class="order-id">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <span class="customer-name">
                                            <i class="fas fa-user-circle"></i>
                                            <?php echo htmlspecialchars($order['nama_pelanggan'] ?? 'Pelanggan'); ?>
                                        </span>
                                        <span class="customer-phone">
                                            <i class="fas fa-phone-alt"></i>
                                            <?php echo htmlspecialchars($order['telepon'] ?? '-'); ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="address">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php 
                                        $alamat = $order['shipping_address'] ?? 'Alamat tidak tersedia';
                                        echo htmlspecialchars($alamat);
                                        ?>
                                    </div>
                                    <?php if (($order['total_qty'] ?? 0) > 0): ?>
                                        <span class="item-badge">
                                            <i class="fas fa-box"></i> <?php echo $order['total_qty']; ?> item
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="amount">Rp <?php echo number_format($order['total_amount'] ?? 0, 0, ',', '.'); ?></span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $order['status']; ?>">
                                        <?php 
                                        if ($order['status'] == 'menunggu_kurir') echo 'Menunggu';
                                        elseif ($order['status'] == 'dikirim') echo 'Dikirim';
                                        elseif ($order['status'] == 'selesai') echo 'Selesai';
                                        elseif ($order['status'] == 'dibatalkan') echo 'Dibatalkan';
                                        else echo ucfirst($order['status']);
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($status_filter == 'menunggu_kurir' && isset($order['menit_menunggu'])): ?>
                                        <?php 
                                        $menit = $order['menit_menunggu'];
                                        $class = $menit > 30 ? 'urgent' : ($menit > 15 ? 'warning' : 'normal');
                                        ?>
                                        <span class="wait-time <?php echo $class; ?>">
                                            <i class="fas fa-hourglass-<?php echo $menit > 30 ? 'end' : ($menit > 15 ? 'half' : 'start'); ?>"></i>
                                            <?php echo $menit; ?> menit
                                        </span>
                                    <?php elseif ($status_filter == 'dikirim' && isset($order['menit_pengiriman'])): ?>
                                        <span class="wait-time normal">
                                            <i class="fas fa-clock"></i>
                                            <?php echo $order['menit_pengiriman']; ?> menit
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $date_field = isset($order['updated_at']) ? $order['updated_at'] : $order['created_at'];
                                    echo date('d/m/Y H:i', strtotime($date_field));
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <?php if ($order['status'] == 'menunggu_kurir'): ?>
                                            <a href="ambil_pesanan.php?id=<?php echo $order['id']; ?>" 
                                               class="btn btn-success btn-sm"
                                               onclick="return confirm('Ambil pesanan #<?php echo $order['id']; ?>?')">
                                                <i class="fas fa-hand-paper"></i> Ambil
                                            </a>
                                        <?php elseif ($order['status'] == 'dikirim' && $order['kurir_id'] == $kurir_id): ?>
                                            <a href="selesaikan_pengiriman.php?id=<?php echo $order['id']; ?>" 
                                               class="btn btn-primary btn-sm"
                                               onclick="return confirm('Selesaikan pengiriman #<?php echo $order['id']; ?>?')">
                                                <i class="fas fa-check"></i> Selesai
                                            </a>
                                        <?php endif; ?>
                                        <a href="detail_pesanan.php?id=<?php echo $order['id']; ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-<?php echo $icon; ?>"></i>
                        <h3>Tidak Ada Pesanan</h3>
                        <p>
                            <?php if ($status_filter == 'menunggu_kurir'): ?>
                                Belum ada pesanan yang menunggu kurir.
                                <br>Silakan cek kembali nanti.
                            <?php elseif ($status_filter == 'dikirim'): ?>
                                Anda tidak sedang mengirim pesanan.
                                <br>Ambil pesanan untuk memulai pengiriman.
                            <?php elseif ($status_filter == 'selesai'): ?>
                                Belum ada pesanan yang selesai.
                            <?php elseif ($status_filter == 'dibatalkan'): ?>
                                Tidak ada pesanan yang dibatalkan.
                            <?php else: ?>
                                Belum ada pesanan yang pernah Anda tangani.
                            <?php endif; ?>
                        </p>
                        <?php if ($status_filter != 'menunggu_kurir'): ?>
                            <a href="?status=menunggu_kurir" class="btn-reset" style="margin-top: 20px;">
                                <i class="fas fa-clock"></i> Lihat Pesanan Menunggu
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // AUTO REFRESH UNTUK MENUNGGU DAN DIKIRIM
    <?php if ($status_filter == 'menunggu_kurir' || $status_filter == 'dikirim'): ?>
    setTimeout(function() {
        location.reload();
    }, 15000);
    <?php endif; ?>

    console.log('✅ Halaman: <?php echo $title; ?>');
    console.log('📊 Total Data: <?php echo $total_data; ?>');
    console.log('🔍 Filter: <?php echo $status_filter; ?>');
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>