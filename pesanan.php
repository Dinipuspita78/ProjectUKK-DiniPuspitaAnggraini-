<?php
session_start(); // Tambahkan ini di awal
require_once '../components/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../admin_login.php');
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']); // Ganti clean_input dengan mysqli_real_escape_string
    
    $query = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = 'Status pesanan berhasil diperbarui!';
    } else {
        $_SESSION['error'] = 'Gagal memperbarui status pesanan: ' . mysqli_error($conn);
    }
}

// Handle delete order
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $order_id = intval($_GET['id']);
    
    // Delete from order_items first
    $query = "DELETE FROM order_items WHERE order_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    
    // Then delete from orders
    $query = "DELETE FROM orders WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = 'Pesanan berhasil dihapus!';
    } else {
        $_SESSION['error'] = 'Gagal menghapus pesanan: ' . mysqli_error($conn);
    }
    
    header('Location: pesanan.php');
    exit();
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Debug: Cek struktur tabel orders
error_log("Checking orders table structure...");
$check_columns = mysqli_query($conn, "SHOW COLUMNS FROM orders");
$columns = [];
while ($col = mysqli_fetch_assoc($check_columns)) {
    $columns[] = $col['Field'];
}
error_log("Orders columns: " . implode(", ", $columns));

// PERBAIKAN: Query yang benar untuk tabel orders
// Gunakan JOIN yang benar: orders.user_id = users.id
$query = "SELECT o.*, u.nama as customer_name, u.email as customer_email 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE 1=1";

$params = [];
$types = '';

if (!empty($status_filter)) {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($date_from)) {
    $query .= " AND DATE(o.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $query .= " AND DATE(o.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$query .= " ORDER BY o.created_at DESC";

error_log("Final Query: $query");

// Execute query
if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $orders_result = mysqli_stmt_get_result($stmt);
} else {
    $orders_result = mysqli_query($conn, $query);
}

// Cek error query
if (!$orders_result) {
    error_log("Query Error: " . mysqli_error($conn));
    $orders_result = false;
}

// Get statistics - PERBAIKAN: untuk tabel orders
$stats_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
    SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) as diproses,
    SUM(CASE WHEN status = 'dikirim' THEN 1 ELSE 0 END) as dikirim,
    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
    SUM(total_amount) as total_revenue
    FROM orders";

$stats_result = mysqli_query($conn, $stats_query);
if ($stats_result) {
    $stats = mysqli_fetch_assoc($stats_result);
} else {
    $stats = [
        'total_orders' => 0,
        'pending' => 0,
        'total_revenue' => 0
    ];
    error_log("Stats query error: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pesanan - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    .order-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        text-align: center;
    }
    
    .stat-card.pending { border-left: 4px solid #ffc107; }
    .stat-card.processing { border-left: 4px solid #17a2b8; }
    .stat-card.shipped { border-left: 4px solid #007bff; }
    .stat-card.delivered { border-left: 4px solid #28a745; }
    .stat-card.revenue { border-left: 4px solid #6f42c1; }
    
    .stat-number {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 5px;
    }
    
    .stat-label {
        font-size: 0.9rem;
        color: var(--text);
    }
    
    .filter-card {
        background: white;
        padding: 20px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        margin-bottom: 30px;
    }
    
    .filter-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        align-items: end;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
    }
    
    .filter-group label {
        margin-bottom: 5px;
        font-weight: 500;
        color: var(--dark);
    }
    
    .order-actions {
        display: flex;
        gap: 5px;
    }
    
    .btn-small {
        padding: 5px 10px;
        font-size: 0.85rem;
    }
    
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    
    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 30px;
        border-radius: var(--radius);
        width: 80%;
        max-width: 800px;
        max-height: 80vh;
        overflow-y: auto;
    }
    
    .close-modal {
        float: right;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--text);
    }
    
    .order-detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .detail-section {
        background: #f8f9fa;
        padding: 15px;
        border-radius: var(--radius);
    }
    
    .order-items-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    
    .order-items-table th,
    .order-items-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #dee2e6;
    }
    
    .order-items-table th {
        background-color: var(--primary);
        color: white;
    }
    
    .status-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    .status-pending { background: #fff3cd; color: #856404; }
    .status-processing { background: #cce5ff; color: #004085; }
    .status-shipped { background: #d4edda; color: #155724; }
    .status-delivered { background: #d1ecf1; color: #0c5460; }
    .status-diproses { background: #cce5ff; color: #004085; }
    .status-dikirim { background: #d4edda; color: #155724; }
    .status-selesai { background: #d1ecf1; color: #0c5460; }
    
    .no-orders {
        text-align: center;
        padding: 40px;
        color: #666;
    }
    
    .no-orders i {
        font-size: 48px;
        color: #ccc;
        margin-bottom: 15px;
    }
    
    .error-message {
        background: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: var(--radius);
        margin-bottom: 20px;
        border: 1px solid #f5c6cb;
    }
    </style>
</head>
<body class="admin-body">
    <?php include '../components/sidebar.php'; ?>
    
    <main class="admin-main">
        <header class="admin-header">
            <h1><i class="fas fa-shopping-cart"></i> Manajemen Pesanan</h1>
            <div class="admin-actions">
                <button class="btn-primary" onclick="printOrders()">
                    <i class="fas fa-print"></i> Cetak
                </button>
                <button class="btn-secondary" onclick="exportOrders()">
                    <i class="fas fa-file-export"></i> Export
                </button>
            </div>
        </header>
        
        <section class="admin-content">
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="order-stats">
                <div class="stat-card pending">
                    <div class="stat-number"><?php echo ($stats['pending'] ?? 0) + ($stats['diproses'] ?? 0); ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card processing">
                    <div class="stat-number"><?php echo ($stats['processing'] ?? 0); ?></div>
                    <div class="stat-label">Processing</div>
                </div>
                <div class="stat-card shipped">
                    <div class="stat-number"><?php echo ($stats['shipped'] ?? 0) + ($stats['dikirim'] ?? 0); ?></div>
                    <div class="stat-label">Shipped</div>
                </div>
                <div class="stat-card delivered">
                    <div class="stat-number"><?php echo ($stats['delivered'] ?? 0) + ($stats['selesai'] ?? 0); ?></div>
                    <div class="stat-label">Delivered</div>
                </div>
                <div class="stat-card revenue">
                    <div class="stat-number">Rp <?php echo number_format($stats['total_revenue'] ?? 0, 0, ',', '.'); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
            
            <!-- Filter Section -->
            <div class="filter-card">
                <h3><i class="fas fa-filter"></i> Filter Pesanan</h3>
                <form method="GET" action="" class="filter-form">
                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="diproses" <?php echo $status_filter == 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                            <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="dikirim" <?php echo $status_filter == 'dikirim' ? 'selected' : ''; ?>>Dikirim</option>
                            <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="selesai" <?php echo $status_filter == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_from">Dari Tanggal</label>
                        <input type="date" id="date_from" name="date_from" 
                               class="form-control" value="<?php echo $date_from; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_to">Sampai Tanggal</label>
                        <input type="date" id="date_to" name="date_to" 
                               class="form-control" value="<?php echo $date_to; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-search"></i> Terapkan Filter
                        </button>
                        <a href="pesanan.php" class="btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Orders Table -->
            <div class="card">
                <div class="table-container">
                    <?php if (!$orders_result): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Error query database: <?php echo mysqli_error($conn); ?>
                        </div>
                    <?php elseif (mysqli_num_rows($orders_result) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID Pesanan</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = mysqli_fetch_assoc($orders_result)): 
                                // Tentukan nama kolom total yang benar
                                $total_col = isset($order['total_amount']) ? 'total_amount' : 
                                            (isset($order['total']) ? 'total' : 'total_harga');
                                
                                // Tentukan status display
                                $status_display = $order['status'];
                                $status_class = $order['status'];
                            ?>
                            <tr>
                                <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <div><strong><?php echo htmlspecialchars($order['customer_name'] ?? 'Tidak diketahui'); ?></strong></div>
                                    <small><?php echo htmlspecialchars($order['customer_email'] ?? '-'); ?></small>
                                </td>
                                <td>Rp <?php echo number_format($order[$total_col] ?? 0, 0, ',', '.'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $status_class; ?>">
                                        <?php echo ucfirst($status_display); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <div class="order-actions">
                                        <button class="btn-action btn-view" 
                                                onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-action btn-edit" 
                                                onclick="editOrderStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="pesanan.php?action=delete&id=<?php echo $order['id']; ?>" 
                                           class="btn-action btn-delete"
                                           onclick="return confirm('Hapus pesanan ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="no-orders">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Tidak Ada Pesanan</h3>
                        <p>Belum ada pesanan yang masuk.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
    
    <!-- Modal for Order Details -->
    <div id="orderDetailsModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2>Detail Pesanan</h2>
            <div id="orderDetailsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
    
    <!-- Modal for Edit Status -->
    <!-- Modal for Edit Status - PERBAIKI OPSI STATUS -->
<div id="editStatusModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <h2>Ubah Status Pesanan</h2>
        <form method="POST" action="" id="editStatusForm">
            <input type="hidden" name="order_id" id="editOrderId">
            <input type="hidden" name="update_status" value="1">
            
            <div class="form-group">
                <label for="statusSelect">Status Baru</label>
                <select id="statusSelect" name="status" class="form-control" required>
                    <option value="menunggu_kurir">⏳ Menunggu Kurir</option>
                    <option value="dikirim">🚚 Dikirim</option>
                    <option value="selesai">✅ Selesai</option>
                    <option value="dibatalkan">❌ Dibatalkan</option>
                    <option value="diproses">⚙️ Diproses</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
                <button type="button" class="btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </form>
    </div>
</div>
    
    <script src="../js/admin.js"></script>
    <script>
    function viewOrderDetails(orderId) {
    // Gunakan API endpoint yang mengembalikan JSON
    fetch(`api_order_detail.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const order = data.order;
                const items = data.items || [];
                
                // Format tanggal
                const orderDate = new Date(order.created_at);
                const formattedDate = orderDate.toLocaleDateString('id-ID', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                // Build HTML content
                let html = `
                    <div class="order-detail-grid">
                        <div class="detail-section">
                            <h3><i class="fas fa-info-circle"></i> Informasi Pesanan</h3>
                            <p><strong>ID Pesanan:</strong> #${order.id.toString().padStart(6, '0')}</p>
                            <p><strong>Tanggal:</strong> ${formattedDate}</p>
                            <p><strong>Status:</strong> <span class="status-badge status-${order.status}">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span></p>
                            <p><strong>Metode Pembayaran:</strong> ${order.payment_method || 'Transfer Bank'}</p>
                            <p><strong>Catatan:</strong> ${order.notes || 'Tidak ada catatan'}</p>
                        </div>
                        
                        <div class="detail-section">
                            <h3><i class="fas fa-user"></i> Informasi Pelanggan</h3>
                            <p><strong>Nama:</strong> ${order.customer_name}</p>
                            <p><strong>Email:</strong> ${order.customer_email}</p>
                            <p><strong>Telepon:</strong> ${order.telepon || '-'}</p>
                        </div>
                        
                        <div class="detail-section">
                            <h3><i class="fas fa-map-marker-alt"></i> Alamat Pengiriman</h3>
                            <p>${order.shipping_address || 'Tidak ada alamat pengiriman'}</p>
                        </div>
                    </div>
                    
                    <div class="detail-section" style="grid-column: 1 / -1; margin-top: 20px;">
                        <h3><i class="fas fa-shopping-cart"></i> Items Pesanan</h3>
                `;
                
                if (items.length > 0) {
                    html += `
                        <table class="order-items-table">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Harga</th>
                                    <th>Jumlah</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    
                    let total = 0;
                    items.forEach(item => {
                        const subtotal = item.subtotal || (item.harga * item.jumlah);
                        total += subtotal;
                        
                        html += `
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        ${item.gambar ? `<img src="../uploads/${item.gambar}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">` : ''}
                                        <span>${item.nama}</span>
                                    </div>
                                </td>
                                <td>Rp ${parseInt(item.harga).toLocaleString('id-ID')}</td>
                                <td>${item.jumlah}</td>
                                <td>Rp ${parseInt(subtotal).toLocaleString('id-ID')}</td>
                            </tr>
                        `;
                    });
                    
                    html += `
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" style="text-align: right; font-weight: bold;">Total:</td>
                                    <td style="font-weight: bold; color: #28a745;">Rp ${parseInt(order.total_amount || total).toLocaleString('id-ID')}</td>
                                </tr>
                            </tfoot>
                        </table>
                    `;
                } else {
                    html += `<p>Tidak ada item pesanan</p>`;
                }
                
                html += `</div>`;
                
                document.getElementById('orderDetailsContent').innerHTML = html;
                document.getElementById('orderDetailsModal').style.display = 'block';
            } else {
                document.getElementById('orderDetailsContent').innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> ${data.message || 'Gagal memuat detail pesanan'}
                    </div>
                `;
                document.getElementById('orderDetailsModal').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('orderDetailsContent').innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> 
                    Gagal memuat detail pesanan. Coba buka halaman secara langsung.
                    <br><br>
                    Error: ${error.message}
                </div>
                <div style="margin-top: 20px;">
                    <a href="pesanan_detail.php?id=${orderId}" class="btn-primary" target="_blank">
                        <i class="fas fa-external-link-alt"></i> Buka Halaman Detail
                    </a>
                </div>
            `;
            document.getElementById('orderDetailsModal').style.display = 'block';
        });

    }
    
    function editOrderStatus(orderId, currentStatus) {
        document.getElementById('editOrderId').value = orderId;
        document.getElementById('statusSelect').value = currentStatus;
        document.getElementById('editStatusModal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('orderDetailsModal').style.display = 'none';
        document.getElementById('editStatusModal').style.display = 'none';
    }
    
    function printOrders() {
        const table = document.querySelector('.data-table');
        if (!table) {
            alert('Tidak ada data untuk dicetak');
            return;
        }
        
        // Clone the table without buttons
        const printContent = table.cloneNode(true);
        const actions = printContent.querySelectorAll('td:last-child, th:last-child');
        actions.forEach(action => action.remove());
        
        // Create new window
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Cetak Daftar Pesanan</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        h2 { color: #333; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                        th { background-color: #4a6cf7; color: white; }
                        @media print {
                            @page { margin: 0.5cm; }
                            body { padding: 10px; }
                        }
                    </style>
                </head>
                <body>
                    <h2>Daftar Pesanan - MinShop</h2>
                    <p>Tanggal Cetak: ${new Date().toLocaleDateString('id-ID', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    })}</p>
                    ${printContent.outerHTML}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    }
    
    function exportOrders() {
        const rows = document.querySelectorAll('.data-table tbody tr');
        if (rows.length === 0) {
            alert('Tidak ada data untuk diexport');
            return;
        }
        
        // Simple CSV export
        let csv = 'ID Pesanan,Pelanggan,Email,Total,Status,Tanggal\n';
        
        rows.forEach(row => {
            const cols = row.querySelectorAll('td');
            if (cols.length >= 6) {
                const orderId = cols[0].textContent.trim();
                const customer = cols[1].querySelector('div')?.textContent.trim() || '';
                const email = cols[1].querySelector('small')?.textContent.trim() || '';
                const total = cols[2].textContent.trim().replace('Rp ', '').replace(/\./g, '');
                const status = cols[3].textContent.trim();
                const date = cols[4].textContent.trim();
                
                csv += `"${orderId}","${customer}","${email}","${total}","${status}","${date}"\n`;
            }
        });
        
        // Create download link
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `orders_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });
    }
    </script>
</body>
</html>