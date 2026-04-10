<?php
// kurir/riwayat.php
session_start();
require_once '../components/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kurir') {
    header('Location: ../login_kurir.php');
    exit();
}

$kurir_id = $_SESSION['kurir_id'];

// Filter
$filter_status = $_GET['status'] ?? 'all';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Build query
$where = "o.kurir_id = $kurir_id";
if ($filter_status != 'all') {
    $where .= " AND o.status = '$filter_status'";
}
if (!empty($start_date)) {
    $where .= " AND DATE(o.created_at) >= '$start_date'";
}
if (!empty($end_date)) {
    $where .= " AND DATE(o.created_at) <= '$end_date'";
}

$query = "SELECT o.*, u.nama as nama_pelanggan, u.telepon,
                 (SELECT SUM(oi.price * oi.quantity) 
                  FROM order_items oi 
                  WHERE oi.order_id = o.id) as total_harga
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE $where 
          ORDER BY o.created_at DESC";
$result = mysqli_query($conn, $query);
$total_rows = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pengiriman - Kurir MinShop</title>
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
        .riwayat-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .btn-filter {
            background: #2196F3;
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-reset {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }
        
        .riwayat-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f8f9fa;
        }
        
        th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #eee;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-action {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            text-decoration: none;
        }
        
        .btn-view { background: #2196F3; color: white; }
        .btn-print { background: #6c757d; color: white; }
        
        .summary-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #2196F3;
            margin: 0.5rem 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
            gap: 0.5rem;
        }
        
        .page-link {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
        }
        
        .page-link.active {
            background: #2196F3;
            color: white;
            border-color: #2196F3;
        }
        
        .no-data {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .no-data i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #ddd;
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
                <p><i class="fas fa-history"></i> Riwayat Pengiriman</p>
            </div>
            
            <ul class="kurir-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="pesanan.php"><i class="fas fa-clipboard-list"></i> Pesanan</a></li>
                <li><a href="pengiriman.php"><i class="fas fa-shipping-fast"></i> Pengiriman Aktif</a></li>
                <li><a href="riwayat.php" class="active"><i class="fas fa-history"></i> Riwayat</a></li>
                <li><a href="profil.php"><i class="fas fa-user"></i> Profil</a></li>
                <li><a href="../components/user_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="kurir-content">
            <div class="riwayat-container">
                <h1><i class="fas fa-history"></i> Riwayat Pengiriman</h1>
                <p>Lihat semua pengiriman yang telah Anda lakukan</p>
                
                <!-- Summary Stats -->
                <div class="summary-card">
                    <h3><i class="fas fa-chart-line"></i> Ringkasan</h3>
                    <div class="stats-grid">
                        <?php
                        // Hitung statistik
                        $stats_query = "SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'diterima' THEN 1 ELSE 0 END) as sukses,
                            SUM(CASE WHEN status = 'dibatalkan' THEN 1 ELSE 0 END) as batal,
                            AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_time
                            FROM orders WHERE kurir_id = ?";
                        
                        $stmt = mysqli_prepare($conn, $stats_query);
                        mysqli_stmt_bind_param($stmt, "i", $kurir_id);
                        mysqli_stmt_execute($stmt);
                        $stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                        ?>
                        
                        <div class="stat-item">
                            <div class="stat-label">Total Pengiriman</div>
                            <div class="stat-value"><?php echo $stats['total'] ?: 0; ?></div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-label">Sukses</div>
                            <div class="stat-value"><?php echo $stats['sukses'] ?: 0; ?></div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-label">Dibatalkan</div>
                            <div class="stat-value"><?php echo $stats['batal'] ?: 0; ?></div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-label">Rata-rata Waktu</div>
                            <div class="stat-value">
                                <?php 
                                if ($stats['avg_time']) {
                                    echo round($stats['avg_time']) . ' menit';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <form method="GET" action="" class="filter-form">
                        <div class="form-group">
                            <label for="status"><i class="fas fa-filter"></i> Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>Semua Status</option>
                                <option value="diterima" <?php echo $filter_status == 'diterima' ? 'selected' : ''; ?>>Sukses</option>
                                <option value="dibatalkan" <?php echo $filter_status == 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
                                <option value="dikirim" <?php echo $filter_status == 'dikirim' ? 'selected' : ''; ?>>Sedang Dikirim</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="start_date"><i class="fas fa-calendar-alt"></i> Dari Tanggal</label>
                            <input type="date" id="start_date" name="start_date" 
                                   value="<?php echo $start_date; ?>" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date"><i class="fas fa-calendar-alt"></i> Sampai Tanggal</label>
                            <input type="date" id="end_date" name="end_date" 
                                   value="<?php echo $end_date; ?>" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn-filter">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="riwayat.php" class="btn-reset">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Table Riwayat -->
                <div class="riwayat-table">
                    <?php if ($total_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID Order</th>
                                    <th>Pelanggan</th>
                                    <th>Tanggal</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Durasi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = mysqli_fetch_assoc($result)): 
                                    $durasi = '';
                                    if ($order['created_at'] && $order['updated_at']) {
                                        $start = new DateTime($order['created_at']);
                                        $end = new DateTime($order['updated_at']);
                                        $interval = $start->diff($end);
                                        $durasi = $interval->format('%h jam %i menit');
                                    }
                                ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['nama_pelanggan']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $order['status'] == 'diterima' ? 'success' : 
                                                                   ($order['status'] == 'dibatalkan' ? 'danger' : 
                                                                   ($order['status'] == 'dikirim' ? 'warning' : 'info')); ?>">
                                            <?php echo $order['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $durasi ?: '-'; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="detail_pesanan.php?id=<?php echo $order['id']; ?>" 
                                               class="btn-action btn-view">
                                                <i class="fas fa-eye"></i> Lihat
                                            </a>
                                            <a href="../admin/print_invoice.php?id=<?php echo $order['id']; ?>" 
                                               target="_blank" class="btn-action btn-print">
                                                <i class="fas fa-print"></i> Invoice
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <div class="pagination">
                            <a href="?page=1" class="page-link active">1</a>
                            <a href="?page=2" class="page-link">2</a>
                            <a href="?page=3" class="page-link">3</a>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-clipboard"></i>
                            <h3>Tidak Ada Data Riwayat</h3>
                            <p>Belum ada pengiriman yang tercatat.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>