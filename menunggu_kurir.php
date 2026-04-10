<?php
session_start();
require_once '../components/database.php';

// Cek apakah user sudah login dan rolenya kurir
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'kurir') {
    header('Location: ../login_kurir.php');
    exit();
}

// Pastikan kurir_id tersedia
if (!isset($_SESSION['kurir_id'])) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT id FROM kurir WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $_SESSION['kurir_id'] = $row['id'];
        $kurir_id = $row['id'];
    } else {
        header('Location: ../login_kurir.php?error=not_found');
        exit();
    }
} else {
    $kurir_id = $_SESSION['kurir_id'];
}

// Ambil data kurir
$query_kurir = "SELECT * FROM kurir WHERE id = ?";
$stmt = mysqli_prepare($conn, $query_kurir);
mysqli_stmt_bind_param($stmt, "i", $kurir_id);
mysqli_stmt_execute($stmt);
$result_kurir = mysqli_stmt_get_result($stmt);
$kurir = mysqli_fetch_assoc($result_kurir);

// FILTER dan PENCARIAN
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_estimasi = isset($_GET['estimasi']) ? $_GET['estimasi'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';

// Base query untuk pesanan MENUNGGU KURIR
$query = "SELECT 
    o.id,
    o.shipping_address,
    o.total_amount,
    o.created_at,
    o.payment_method,
    o.notes,
    o.kurir_id,
    u.id as user_id,
    u.nama as nama_pelanggan,
    u.email as email_pelanggan,
    u.telepon,
    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as total_item,
    (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as total_quantity,
    TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) as menit_menunggu
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.status = 'menunggu_kurir'";

// Tambahkan pencarian jika ada
if (!empty($search)) {
    $query .= " AND (o.id LIKE '%$search%' 
                OR u.nama LIKE '%$search%' 
                OR u.telepon LIKE '%$search%'
                OR o.shipping_address LIKE '%$search%')";
}

// Filter estimasi waktu
if (!empty($filter_estimasi)) {
    switch ($filter_estimasi) {
        case '<15':
            $query .= " AND TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) < 15";
            break;
        case '15-30':
            $query .= " AND TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) BETWEEN 15 AND 30";
            break;
        case '30-60':
            $query .= " AND TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) BETWEEN 30 AND 60";
            break;
        case '>60':
            $query .= " AND TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) > 60";
            break;
    }
}

// Sorting
switch ($sort) {
    case 'terlama':
        $query .= " ORDER BY o.created_at ASC";
        break;
    case 'termahal':
        $query .= " ORDER BY o.total_amount DESC";
        break;
    case 'termurah':
        $query .= " ORDER BY o.total_amount ASC";
        break;
    default: // terbaru
        $query .= " ORDER BY o.created_at DESC";
        break;
}

$result = mysqli_query($conn, $query);

// Hitung statistik pesanan menunggu
$stats_query = "SELECT 
    COUNT(*) as total_menunggu,
    SUM(total_amount) as total_nilai,
    AVG(TIMESTAMPDIFF(MINUTE, created_at, NOW())) as rata_rata_menunggu,
    SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, created_at, NOW()) > 30 THEN 1 ELSE 0 END) as urgent
    FROM orders 
    WHERE status = 'menunggu_kurir'";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

$page_title = "Pesanan Menunggu Kurir";

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menunggu Kurir - MinShop</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f8fafc;
        }

        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 25px;
            transition: margin-left 0.3s ease;
        }

        /* Page Header */
        .page-header {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title h1 {
            font-size: 24px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .header-title h1 i {
            color: #f59e0b;
        }

        .header-title p {
            color: #64748b;
            font-size: 14px;
        }

        .header-stats {
            display: flex;
            gap: 20px;
        }

        .stat-badge {
            background: #fef9c3;
            color: #854d0e;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stat-badge.urgent {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border: 1px solid rgba(0,0,0,0.03);
        }

        .stat-info h3 {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: #fef9c3;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f59e0b;
            font-size: 22px;
        }

        /* Filter & Search */
        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }

        .search-box {
            display: flex;
            align-items: center;
            background: #f1f5f9;
            border-radius: 12px;
            padding: 5px;
            flex: 1;
            max-width: 400px;
        }

        .search-box i {
            padding: 0 15px;
            color: #64748b;
        }

        .search-box input {
            flex: 1;
            padding: 12px 15px 12px 0;
            border: none;
            background: transparent;
            outline: none;
            font-size: 14px;
        }

        .filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 10px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            outline: none;
            font-size: 14px;
            color: #1e293b;
            background: white;
            cursor: pointer;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: white;
            color: #1e293b;
            border: 1px solid #e2e8f0;
        }

        .btn-outline:hover {
            background: #f1f5f9;
        }

        /* Orders Table */
        .orders-container {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th {
            text-align: left;
            padding: 15px 10px;
            color: #64748b;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }

        .orders-table td {
            padding: 20px 10px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .order-id {
            font-weight: 700;
            color: #2563eb;
            background: #eff6ff;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 13px;
            display: inline-block;
        }

        .customer-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .customer-name {
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .customer-phone {
            font-size: 13px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .address-preview {
            max-width: 250px;
            font-size: 13px;
            color: #475569;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .address-preview i {
            color: #ef4444;
            margin-top: 2px;
        }

        .wait-time {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
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

        .amount {
            font-weight: 700;
            color: #059669;
            font-size: 16px;
        }

        .items-count {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            background: #f1f5f9;
            border-radius: 20px;
            font-size: 12px;
            color: #475569;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-take {
            background: #10b981;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-take:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16,185,129,0.2);
        }

        .btn-detail {
            background: #f1f5f9;
            color: #1e293b;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .btn-detail:hover {
            background: #e2e8f0;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
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
            color: #64748b;
            margin-bottom: 25px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 30px;
        }

        .pagination a {
            padding: 10px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            text-decoration: none;
            color: #1e293b;
            transition: all 0.3s;
        }

        .pagination a:hover,
        .pagination a.active {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                max-width: 100%;
            }
            
            .orders-table {
                display: block;
                overflow-x: auto;
            }
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: #1e293b;
            font-size: 20px;
        }

        .close-modal {
            font-size: 24px;
            cursor: pointer;
            color: #64748b;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .order-detail-item {
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .order-detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <?php include '../components/sidebar_kurir.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-title">
                    <h1>
                        <i class="fas fa-clock"></i>
                        Pesanan Menunggu Kurir
                    </h1>
                    <p>Daftar pesanan yang siap untuk diambil dan dikirim</p>
                </div>
                <div class="header-stats">
                    <div class="stat-badge">
                        <i class="fas fa-hourglass-half"></i>
                        Rata-rata: <?php echo floor($stats['rata_rata_menunggu'] ?? 0); ?> menit
                    </div>
                    <?php if (($stats['urgent'] ?? 0) > 0): ?>
                    <div class="stat-badge urgent">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo $stats['urgent']; ?> Urgent
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_menunggu'] ?? 0, 0, ',', '.'); ?></h3>
                        <p>Total Menunggu</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Rp <?php echo number_format($stats['total_nilai'] ?? 0, 0, ',', '.'); ?></h3>
                        <p>Total Nilai Pesanan</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?php echo floor($stats['rata_rata_menunggu'] ?? 0); ?> menit</h3>
                        <p>Rata-rata Menunggu</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?php echo $stats['urgent'] ?? 0; ?></h3>
                        <p>Urgent (>30 menit)</p>
                    </div>
                    <div class="stat-icon" style="background: #fee2e2; color: #ef4444;">
                        <i class="fas fa-exclamation"></i>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" action="" class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Cari ID, nama, alamat..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" style="display: none;"></button>
                </form>
                
                <div class="filter-group">
                    <select name="estimasi" class="filter-select" onchange="window.location.href='?estimasi='+this.value+'&sort=<?php echo $sort; ?>&search=<?php echo $search; ?>'">
                        <option value="">Semua Waktu</option>
                        <option value="<15" <?php echo $filter_estimasi == '<15' ? 'selected' : ''; ?>>Kurang dari 15 menit</option>
                        <option value="15-30" <?php echo $filter_estimasi == '15-30' ? 'selected' : ''; ?>>15 - 30 menit</option>
                        <option value="30-60" <?php echo $filter_estimasi == '30-60' ? 'selected' : ''; ?>>30 - 60 menit</option>
                        <option value=">60" <?php echo $filter_estimasi == '>60' ? 'selected' : ''; ?>>Lebih dari 60 menit</option>
                    </select>
                    
                    <select name="sort" class="filter-select" onchange="window.location.href='?sort='+this.value+'&estimasi=<?php echo $filter_estimasi; ?>&search=<?php echo $search; ?>'">
                        <option value="terbaru" <?php echo $sort == 'terbaru' ? 'selected' : ''; ?>>Terbaru</option>
                        <option value="terlama" <?php echo $sort == 'terlama' ? 'selected' : ''; ?>>Terlama</option>
                        <option value="termahal" <?php echo $sort == 'termahal' ? 'selected' : ''; ?>>Termahal</option>
                        <option value="termurah" <?php echo $sort == 'termurah' ? 'selected' : ''; ?>>Termurah</option>
                    </select>
                    
                    <?php if (!empty($search) || !empty($filter_estimasi) || $sort != 'terbaru'): ?>
                    <a href="menunggu_kurir.php" class="btn btn-outline">
                        <i class="fas fa-redo-alt"></i> Reset
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="orders-container">
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>ID Pesanan</th>
                            <th>Pelanggan</th>
                            <th>Alamat</th>
                            <th>Menunggu</th>
                            <th>Total</th>
                            <th>Item</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($order = mysqli_fetch_assoc($result)): 
                            $menit = $order['menit_menunggu'];
                            $wait_class = 'normal';
                            if ($menit > 30) $wait_class = 'urgent';
                            elseif ($menit > 15) $wait_class = 'warning';
                        ?>
                        <tr>
                            <td>
                                <span class="order-id">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                            </td>
                            <td>
                                <div class="customer-info">
                                    <span class="customer-name">
                                        <i class="fas fa-user-circle"></i>
                                        <?php echo htmlspecialchars($order['nama_pelanggan']); ?>
                                    </span>
                                    <span class="customer-phone">
                                        <i class="fas fa-phone-alt"></i>
                                        <?php echo htmlspecialchars($order['telepon'] ?? '-'); ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="address-preview">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php 
                                    $alamat = $order['shipping_address'] ?? 'Alamat tidak tersedia';
                                    echo htmlspecialchars(substr($alamat, 0, 50)) . (strlen($alamat) > 50 ? '...' : '');
                                    ?>
                                </div>
                            </td>
                            <td>
                                <span class="wait-time <?php echo $wait_class; ?>">
                                    <i class="fas fa-hourglass-<?php echo $menit > 30 ? 'end' : ($menit > 15 ? 'half' : 'start'); ?>"></i>
                                    <?php 
                                    if ($menit < 60) {
                                        echo $menit . ' menit';
                                    } else {
                                        $jam = floor($menit / 60);
                                        $sisa = $menit % 60;
                                        echo $jam . ' jam ' . $sisa . ' menit';
                                    }
                                    ?>
                                </span>
                            </td>
                            <td>
                                <span class="amount">Rp <?php echo number_format($order['total_amount'] ?? 0, 0, ',', '.'); ?></span>
                            </td>
                            <td>
                                <span class="items-count">
                                    <i class="fas fa-box"></i>
                                    <?php echo $order['total_quantity'] ?? 0; ?> item
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="ambil_pesanan.php?id=<?php echo $order['id']; ?>" 
                                       class="btn-take"
                                       onclick="return confirm('Ambil pesanan #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>?')">
                                        <i class="fas fa-hand-paper"></i>
                                        Ambil
                                    </a>
                                    <button onclick="showDetail(<?php echo htmlspecialchars(json_encode($order)); ?>)" 
                                            class="btn-detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="pagination">
                    <a href="#" class="active">1</a>
                    <a href="#">2</a>
                    <a href="#">3</a>
                    <a href="#">4</a>
                    <a href="#">5</a>
                    <a href="#"><i class="fas fa-chevron-right"></i></a>
                </div>

                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>Tidak Ada Pesanan Menunggu</h3>
                    <p>Saat ini tidak ada pesanan yang membutuhkan kurir.</p>
                    <p style="font-size: 13px; margin-top: 5px;">Silakan cek kembali nanti atau refresh halaman.</p>
                    <a href="dashboard.php" class="btn btn-warning" style="margin-top: 20px;">
                        <i class="fas fa-tachometer-alt"></i>
                        Kembali ke Dashboard
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Detail Pesanan -->
    <div id="orderDetailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle"></i> Detail Pesanan</h3>
                <span class="close-modal" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content will be filled by JavaScript -->
            </div>
            <div class="modal-actions">
                <button class="btn btn-outline" onclick="closeModal()">Tutup</button>
                <a href="#" id="takeOrderBtn" class="btn btn-warning">
                    <i class="fas fa-hand-paper"></i> Ambil Pesanan
                </a>
            </div>
        </div>
    </div>

    <script>
    // Show order detail modal
    function showDetail(order) {
        const modal = document.getElementById('orderDetailModal');
        const modalBody = document.getElementById('modalBody');
        const takeBtn = document.getElementById('takeOrderBtn');
        
        // Format tanggal
        const date = new Date(order.created_at);
        const formattedDate = date.toLocaleDateString('id-ID', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        // Format alamat
        const alamat = order.shipping_address || 'Alamat tidak tersedia';
        
        // Format menit menunggu
        let waitTime = '';
        if (order.menit_menunggu < 60) {
            waitTime = order.menit_menunggu + ' menit';
        } else {
            const jam = Math.floor(order.menit_menunggu / 60);
            const sisa = order.menit_menunggu % 60;
            waitTime = jam + ' jam ' + sisa + ' menit';
        }
        
        // Build modal content
        modalBody.innerHTML = `
            <div class="order-detail-item">
                <div class="detail-label">ID Pesanan</div>
                <div class="detail-value">#${order.id.toString().padStart(6, '0')}</div>
            </div>
            <div class="order-detail-item">
                <div class="detail-label">Pelanggan</div>
                <div class="detail-value">${order.nama_pelanggan || 'Tidak diketahui'}</div>
            </div>
            <div class="order-detail-item">
                <div class="detail-label">Nomor Telepon</div>
                <div class="detail-value">${order.telepon || '-'}</div>
            </div>
            <div class="order-detail-item">
                <div class="detail-label">Email</div>
                <div class="detail-value">${order.email_pelanggan || '-'}</div>
            </div>
            <div class="order-detail-item">
                <div class="detail-label">Alamat Pengiriman</div>
                <div class="detail-value" style="font-weight: normal;">${alamat}</div>
            </div>
            <div class="order-detail-item">
                <div class="detail-label">Total Pesanan</div>
                <div class="detail-value" style="color: #059669;">Rp ${parseInt(order.total_amount).toLocaleString('id-ID')}</div>
            </div>
            <div class="order-detail-item">
                <div class="detail-label">Metode Pembayaran</div>
                <div class="detail-value">${order.payment_method ? order.payment_method.toUpperCase() : 'Transfer Bank'}</div>
            </div>
            <div class="order-detail-item">
                <div class="detail-label">Tanggal Pesan</div>
                <div class="detail-value">${formattedDate}</div>
            </div>
            <div class="order-detail-item">
                <div class="detail-label">Waktu Menunggu</div>
                <div class="detail-value">
                    <span style="color: ${order.menit_menunggu > 30 ? '#ef4444' : (order.menit_menunggu > 15 ? '#f59e0b' : '#10b981')};">
                        ${waitTime}
                    </span>
                </div>
            </div>
            <div class="order-detail-item">
                <div class="detail-label">Jumlah Item</div>
                <div class="detail-value">${order.total_quantity || 0} item</div>
            </div>
            ${order.notes ? `
            <div class="order-detail-item">
                <div class="detail-label">Catatan Pesanan</div>
                <div class="detail-value" style="font-weight: normal; font-style: italic;">${order.notes}</div>
            </div>
            ` : ''}
        `;
        
        // Set take button link
        takeBtn.href = `ambil_pesanan.php?id=${order.id}`;
        
        // Show modal
        modal.classList.add('show');
    }
    
    // Close modal
    function closeModal() {
        document.getElementById('orderDetailModal').classList.remove('show');
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('orderDetailModal');
        if (event.target == modal) {
            modal.classList.remove('show');
        }
    }
    
    // Auto refresh data every 30 seconds
    setTimeout(function() {
        location.reload();
    }, 30000); // Refresh setiap 30 detik
    
    // Konfirmasi ambil pesanan dengan animasi
    document.querySelectorAll('.btn-take').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Yakin ingin mengambil pesanan ini?')) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>
<?php
// Tutup koneksi
if (isset($stmt)) mysqli_stmt_close($stmt);
if (isset($result)) mysqli_free_result($result);
?>