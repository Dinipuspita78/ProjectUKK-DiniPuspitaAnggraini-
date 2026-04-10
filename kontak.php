<?php

require_once '../components/database.php';
require_once '../components/functions.php';

$page_title = "Pesan Kontak - Admin Panel";
include '../components/sidebar.php';

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Hitung total pesan
$total_query = "SELECT COUNT(*) as total FROM pesan_kontak";
$total_result = mysqli_query($conn, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_messages = $total_row['total'];
$total_pages = ceil($total_messages / $limit);

// Ambil pesan dengan pagination
$query = "SELECT * FROM pesan_kontak ORDER BY tanggal DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

// Cek apakah ada kolom status
$check_status = mysqli_query($conn, "SHOW COLUMNS FROM pesan_kontak LIKE 'status'");
$has_status = mysqli_num_rows($check_status) > 0;

// Update status menjadi read jika melihat detail dan kolom status ada
if (isset($_GET['id']) && $has_status) {
    $message_id = mysqli_real_escape_string($conn, $_GET['id']);
    $update_query = "UPDATE pesan_kontak SET status = 'read' WHERE id = '$message_id'";
    mysqli_query($conn, $update_query);
}

// Get statistics - PERBAIKAN: Hapus referensi ke kolom status jika tidak ada
if ($has_status) {
    $stats_query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'unread' THEN 1 ELSE 0 END) as unread,
        SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied,
        DATE(tanggal) as date,
        COUNT(*) as daily_messages 
        FROM pesan_kontak 
        WHERE DATE(tanggal) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        GROUP BY DATE(tanggal) 
        ORDER BY date DESC";
} else {
    // Jika tidak ada kolom status, gunkan query yang lebih sederhana
    $stats_query = "SELECT 
        COUNT(*) as total,
        0 as unread,  // Tidak ada data status
        0 as replied, // Tidak ada data status
        DATE(tanggal) as date,
        COUNT(*) as daily_messages 
        FROM pesan_kontak 
        WHERE DATE(tanggal) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        GROUP BY DATE(tanggal) 
        ORDER BY date DESC";
}

$stats_result = mysqli_query($conn, $stats_query);
$daily_stats = [];
if ($stats_result) {
    while ($row = mysqli_fetch_assoc($stats_result)) {
        $daily_stats[] = $row;
    }
} else {
    // Jika error, set array kosong
    $daily_stats = [];
}

// Hitung statistik untuk card
$total_unread = 0;
$total_today = 0;
$total_replied = 0;
$today = date('Y-m-d');

foreach ($daily_stats as $stat) {
    if ($stat['date'] == $today) {
        $total_today = $stat['daily_messages'];
    }
    $total_unread += $stat['unread'];
    $total_replied += $stat['replied'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<div class="main-content">
    <div class="content-header">
        <div class="header-title">
            <h1><i class="fas fa-envelope"></i> </h1>
            
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon" style="background: #3498db;">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $total_messages; ?></h3>
                <p>Total Pesan</p>
            </div>
        </div>
        
        <?php if ($has_status): ?>
        <div class="stat-card">
            <div class="stat-icon" style="background: #e74c3c;">
                <i class="fas fa-envelope-open"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $total_unread; ?></h3>
                <p>Belum Dibaca</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #2ecc71;">
                <i class="fas fa-reply"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $total_replied; ?></h3>
                <p>Sudah Dibalas</p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #f39c12;">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $total_today; ?></h3>
                <p>Pesan Hari Ini</p>
            </div>
        </div>
    </div>

    <div class="content-body">
        <div class="card">
            <div class="card-header">
                <h3>Daftar Pesan</h3>
                <div class="card-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchMessages" placeholder="Cari pesan...">
                    </div>
                    <button class="btn-primary" onclick="exportMessages()">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <?php if (!$has_status): ?>
                    <button class="btn-warning" onclick="addStatusColumn()">
                        <i class="fas fa-plus"></i> Tambah Kolom Status
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card-body">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Pengirim</th>
                                    <th>Email</th>
                                    <th>Subjek</th>
                                    <th>Pesan</th>
                                    <th>Tanggal</th>
                                    <?php if ($has_status): ?>
                                    <th>Status</th>
                                    <?php endif; ?>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $counter = $offset + 1; ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr class="<?php echo ($has_status && $row['status'] == 'unread') ? 'unread-message' : ''; ?>">
                                    <td><?php echo $counter; ?></td>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-name"><?php echo htmlspecialchars($row['nama']); ?></div>
                                            <div class="user-phone"><?php echo htmlspecialchars($row['telepon']); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['subjek']); ?></td>
                                    <td>
                                        <?php 
                                        $message = htmlspecialchars($row['pesan']);
                                        if (strlen($message) > 50) {
                                            echo substr($message, 0, 50) . '...';
                                        } else {
                                            echo $message;
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                                    
                                    <?php if ($has_status): ?>
                                    <td>
                                        <span class="status-badge status-<?php echo $row['status']; ?>">
                                            <?php 
                                            if ($row['status'] == 'unread') echo 'Belum Dibaca';
                                            elseif ($row['status'] == 'read') echo 'Sudah Dibaca';
                                            elseif ($row['status'] == 'replied') echo 'Sudah Dibalas';
                                            else echo $row['status'];
                                            ?>
                                        </span>
                                    </td>
                                    <?php endif; ?>
                                    
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-view" onclick="viewMessage(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-eye"></i> Lihat
                                            </button>
                                            <button class="btn-delete" onclick="deleteMessage(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php $counter++; endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i> Sebelumnya
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="page-link">
                                Berikutnya <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-envelope-open-text"></i>
                        <h3>Tidak ada pesan</h3>
                        <p>Belum ada pesan dari pengunjung.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Chart Section -->
        <?php if (!empty($daily_stats)): ?>
        <div class="card">
            <div class="card-header">
                <h3>Statistik 7 Hari Terakhir</h3>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="messagesChart"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal untuk melihat detail pesan -->
<div class="modal" id="messageModal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <div id="messageContent">
            <!-- Konten pesan akan dimuat di sini via AJAX -->
        </div>
    </div>
</div>

<style>
/* Statistics Cards */
/* PESAN KONTAK - STYLE IMPROVEMENTS */

/* Statistics Cards - Perbaikan layout */
.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 30px;
    margin-left:260px;
}

.stat-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fe 100%);
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.3s ease;
    border: 1px solid #eef2f7;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--primary);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 22px;
    flex-shrink: 0;
}

.stat-info h3 {
    font-size: 28px;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 4px;
    line-height: 1;
}

.stat-info p {
    color: var(--text);
    font-size: 13px;
    opacity: 0.8;
    margin: 0;
}

/* Card Header Improvements */
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    flex-wrap: wrap;
    gap: 16px;
}

.card-header h3 {
    margin: 0;
    font-size: 18px;
    color: var(--dark);
}

.card-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Search Box Improvements */
.search-box {
    position: relative;
    width: 250px;
}

.search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text);
    opacity: 0.6;
}

.search-box input {
    width: 100%;
    padding: 10px 16px 10px 40px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #fff;
}

.search-box input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(var(--primary-rgb, 52, 152, 219), 0.1);
}

/* Button Improvements */
.btn-primary, .btn-warning {
    padding: 10px 18px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    height: 40px;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--secondary);
    transform: translateY(-2px);
}

.btn-warning {
    background: #ffc107;
    color: #212529;
}

.btn-warning:hover {
    background: #e0a800;
    transform: translateY(-2px);
}

/* Table Improvements */
.card-body {
    padding: 0 !important;
    margin-left:250px;
}

.table-responsive {
    overflow-x: auto;
    padding: 0;
}

.table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1000px;
}

.table thead {
    background: #f8f9fe;
}

.table th {
    padding: 16px 20px;
    text-align: left;
    font-weight: 600;
    color: var(--dark);
    border-bottom: 2px solid var(--border);
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table td {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    vertical-align: top;
    font-size: 14px;
}

.table tbody tr {
    transition: background-color 0.2s ease;
}

.table tbody tr:hover {
    background-color: #f8f9fe;
}

/* User Info in Table */
.user-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.user-name {
    font-weight: 600;
    color: var(--dark);
}

.user-phone {
    font-size: 12px;
    color: var(--text);
    opacity: 0.7;
}

/* Status Badges */
.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
    min-width: 100px;
    text-align: center;
}

.status-unread {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.status-read {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-replied {
    background-color: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: nowrap;
}

.btn-view {
    padding: 6px 12px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.3s ease;
    height: 32px;
}

.btn-view:hover {
    background: var(--secondary);
    transform: translateY(-1px);
}

.btn-delete {
    width: 32px;
    height: 32px;
    background: #ff6b6b;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.btn-delete:hover {
    background: #ff4757;
    transform: translateY(-1px);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text);
}

.empty-state i {
    font-size: 64px;
    color: #e0e6ed;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h3 {
    color: var(--dark);
    margin-bottom: 10px;
    font-size: 18px;
    font-weight: 600;
}

.empty-state p {
    color: var(--text);
    opacity: 0.7;
    max-width: 300px;
    margin: 0 auto;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 6px;
    margin-top: 30px;
    padding: 20px;
    border-top: 1px solid var(--border);
    flex-wrap: wrap;
}

.page-link {
    padding: 8px 14px;
    background: #fff;
    color: var(--dark);
    text-decoration: none;
    border-radius: 6px;
    border: 1px solid var(--border);
    font-size: 14px;
    transition: all 0.3s ease;
    min-width: 40px;
    text-align: center;
}

.page-link:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.page-link.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
    font-weight: 600;
}

/* Modal Improvements */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(2px);
}

.modal-content {
    background: white;
    margin: 5% auto;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    max-width: 600px;
    width: 90%;
    max-height: 85vh;
    overflow-y: auto;
    position: relative;
}

.close-modal {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 24px;
    cursor: pointer;
    color: var(--text);
    z-index: 10;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.close-modal:hover {
    background: #f8f9fe;
    color: var(--dark);
}

/* Message Detail in Modal */
.message-detail {
    padding: 30px;
}

.message-header {
    border-bottom: 1px solid var(--border);
    padding-bottom: 20px;
    margin-bottom: 24px;
}

.message-header h3 {
    color: var(--primary);
    margin: 0 0 16px 0;
    font-size: 20px;
    line-height: 1.4;
}

.message-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-top: 0;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text);
    font-size: 14px;
}

.meta-item i {
    color: var(--primary);
    width: 16px;
}

.message-body {
    margin: 24px 0;
}

.message-body h4 {
    color: var(--dark);
    margin-bottom: 12px;
    font-size: 16px;
    font-weight: 600;
}

.message-body p {
    background: #f8f9fe;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid var(--primary);
    margin: 0;
    line-height: 1.6;
    white-space: pre-wrap;
}

.message-actions {
    display: flex;
    gap: 10px;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid var(--border);
}

/* Chart Section */
.chart-container {
    height: 300px;
    position: relative;
    padding: 20px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .stats-cards {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .card-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .card-actions {
        width: 100%;
        flex-wrap: wrap;
    }
    
    .search-box {
        width: 100%;
        margin-bottom: 10px;
    }
    
    .action-buttons {
        flex-wrap: wrap;
    }
    
    .modal-content {
        margin: 10% auto;
        width: 95%;
    }
}

@media (max-width: 480px) {
    .stats-cards {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        padding: 16px;
    }
    
    .stat-icon {
        width: 48px;
        height: 48px;
        font-size: 20px;
    }
    
    .table th, .table td {
        padding: 12px 16px;
    }
}

/* Unread Message Highlight */
.unread-message {
    background-color: #f8f9fe;
    position: relative;
}

.unread-message::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 4px;
    height: 100%;
    background: var(--primary);
}

/* Scrollbar Styling */
.table-responsive::-webkit-scrollbar {
    height: 6px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 3px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: var(--secondary);
}

.modal-content::-webkit-scrollbar {
    width: 6px;
}

.modal-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.modal-content::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 3px;
}

.modal-content::-webkit-scrollbar-thumb:hover {
    background: var(--secondary);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Fungsi untuk menambahkan kolom status jika belum ada
function addStatusColumn() {
    if (confirm('Apakah Anda yakin ingin menambahkan kolom status ke tabel pesan_kontak?')) {
        fetch('add_status_column.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Kolom status berhasil ditambahkan!');
                    location.reload();
                } else {
                    alert('Gagal menambahkan kolom: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Gagal menambahkan kolom status');
            });
    }
}

// Fungsi untuk melihat detail pesan
function viewMessage(id) {
    fetch(`get_message.php?id=${id}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('messageContent').innerHTML = data;
            document.getElementById('messageModal').style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Gagal memuat pesan');
        });
}

// Fungsi untuk menghapus pesan
function deleteMessage(id) {
    if (confirm('Apakah Anda yakin ingin menghapus pesan ini?')) {
        fetch(`delete_message.php?id=${id}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal menghapus pesan');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Gagal menghapus pesan');
        });
    }
}

// Fungsi pencarian
document.getElementById('searchMessages').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Fungsi export
function exportMessages() {
    window.open('export_messages.php', '_blank');
}

// Modal functionality
const modal = document.getElementById('messageModal');
const closeModal = document.querySelector('.close-modal');

closeModal.onclick = function() {
    modal.style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && modal.style.display === 'block') {
        modal.style.display = 'none';
    }
});

// Chart.js untuk statistik
<?php if (!empty($daily_stats)): ?>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('messagesChart').getContext('2d');
    
    // Siapkan data untuk chart
    const labels = <?php echo json_encode(array_column($daily_stats, 'date')); ?>;
    const totalData = <?php echo json_encode(array_column($daily_stats, 'daily_messages')); ?>;
    
    <?php if ($has_status): ?>
    const unreadData = <?php echo json_encode(array_column($daily_stats, 'unread')); ?>;
    <?php endif; ?>
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Total Pesan',
                    data: totalData,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }
                <?php if ($has_status): ?>
                ,
                {
                    label: 'Belum Dibaca',
                    data: unreadData,
                    backgroundColor: 'rgba(255, 99, 132, 0.6)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
                <?php endif; ?>
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
<?php endif; ?>
</script>

