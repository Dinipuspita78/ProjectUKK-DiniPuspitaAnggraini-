<?php
require_once '../components/database.php';
require_once '../components/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../admin_login.php');
    exit();
}

// Handle user deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    // Prevent deleting admin accounts
    $check_query = "SELECT role FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    if ($user['role'] !== 'admin') {
        // Delete user (cascade will delete related orders and cart items)
        $delete_query = "DELETE FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'Pengguna berhasil dihapus!';
        } else {
            $_SESSION['error'] = 'Gagal menghapus pengguna!';
        }
    } else {
        $_SESSION['error'] = 'Tidak dapat menghapus akun admin!';
    }
    
    header('Location: pengguna.php');
    exit();
}

// Handle add admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $nama = clean_input($_POST['nama']);
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $_SESSION['error'] = 'Password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $_SESSION['error'] = 'Password minimal 6 karakter!';
    } else {
        // Check if email exists
        $check_query = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $_SESSION['error'] = 'Email sudah terdaftar!';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $insert_query = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'admin')";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "sss", $nama, $email, $hashed_password);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = 'Admin baru berhasil ditambahkan!';
            } else {
                $_SESSION['error'] = 'Gagal menambahkan admin!';
            }
        }
    }
    
    header('Location: pengguna.php');
    exit();
}

// Get filter parameters
$role_filter = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = '';

if (!empty($role_filter)) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

if (!empty($search)) {
    $query .= " AND (nama LIKE ? OR email LIKE ? OR telepon LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
    $types .= str_repeat('s', 3);
}

$query .= " ORDER BY created_at DESC";

// Execute query
if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $users_result = mysqli_stmt_get_result($stmt);
} else {
    $users_result = mysqli_query($conn, $query);
}

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as total_admins,
    SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as total_customers,
    DATE(created_at) as date,
    COUNT(*) as daily_registrations
    FROM users 
    WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC";
$stats_result = mysqli_query($conn, $stats_query);
$daily_stats = [];
while ($row = mysqli_fetch_assoc($stats_result)) {
    $daily_stats[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    .user-stats {
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
    
    .stat-card.total { border-left: 4px solid var(--primary); }
    .stat-card.admins { border-left: 4px solid var(--danger); }
    .stat-card.customers { border-left: 4px solid var(--success); }
    
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
    
    .user-actions {
        display: flex;
        gap: 5px;
    }
    
    .role-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    .role-admin {
        background: #f8d7da;
        color: #721c24;
    }
    
    .role-user {
        background: #d4edda;
        color: #155724;
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1.2rem;
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
        width: 90%;
        max-width: 500px;
    }
    
    .close-modal {
        float: right;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--text);
    }
    
    .registration-chart {
        background: white;
        padding: 20px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        margin-bottom: 30px;
    }
    
    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .chart-bars {
        display: flex;
        align-items: flex-end;
        gap: 10px;
        height: 150px;
        padding: 20px 0;
    }
    
    .chart-bar {
        flex: 1;
        background: var(--primary);
        border-radius: 5px 5px 0 0;
        position: relative;
        min-height: 10px;
    }
    
    .chart-label {
        text-align: center;
        margin-top: 10px;
        font-size: 0.85rem;
        color: var(--text);
    }
    
    .chart-value {
        position: absolute;
        top: -25px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--primary);
    }
    
    .no-data {
        text-align: center;
        padding: 40px;
        color: var(--text);
    }
    </style>
</head>
<body class="admin-body">
    <?php include '../components/sidebar.php'; ?>
    
    <main class="admin-main">
        <header class="admin-header">
            <h1><i class="fas fa-users"></i> Manajemen Pengguna</h1>
            <div class="admin-actions">
                <button class="btn-primary" onclick="showAddAdminModal()">
                    <i class="fas fa-user-plus"></i> Tambah Admin
                </button>
            </div>
        </header>
        
        <section class="admin-content">
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <?php
            $total_users = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users"));
            $total_admins = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE role = 'admin'"));
            $total_customers = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE role = 'user'"));
            ?>
            
            <div class="user-stats">
                <div class="stat-card total">
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Pengguna</div>
                </div>
                <div class="stat-card admins">
                    <div class="stat-number"><?php echo $total_admins; ?></div>
                    <div class="stat-label">Admin</div>
                </div>
                <div class="stat-card customers">
                    <div class="stat-number"><?php echo $total_customers; ?></div>
                    <div class="stat-label">Pelanggan</div>
                </div>
            </div>
            
            <!-- Registration Chart -->
            <div class="registration-chart">
                <div class="chart-header">
                    <h3>Registrasi 7 Hari Terakhir</h3>
                </div>
                
                <?php if (!empty($daily_stats)): ?>
                <div class="chart-bars">
                    <?php 
                    // Find max value for scaling
                    $max_value = max(array_column($daily_stats, 'daily_registrations'));
                    foreach ($daily_stats as $stat): 
                        $height = ($stat['daily_registrations'] / max($max_value, 1)) * 100;
                        $date = date('d/m', strtotime($stat['date']));
                    ?>
                    <div class="chart-bar-container">
                        <div class="chart-bar" style="height: <?php echo $height; ?>%">
                            <span class="chart-value"><?php echo $stat['daily_registrations']; ?></span>
                        </div>
                        <div class="chart-label"><?php echo $date; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-chart-bar fa-3x"></i>
                    <p>Tidak ada data registrasi</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Filter Section -->
            <div class="filter-card">
                <h3><i class="fas fa-filter"></i> Filter Pengguna</h3>
                <form method="GET" action="" class="filter-form">
                    <div class="filter-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" class="form-control">
                            <option value="">Semua Role</option>
                            <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="user" <?php echo $role_filter == 'user' ? 'selected' : ''; ?>>Pelanggan</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="search">Cari Pengguna</label>
                        <input type="text" id="search" name="search" class="form-control"
                               placeholder="Nama, email, atau telepon..." value="<?php echo $search; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-search"></i> Terapkan Filter
                        </button>
                        <a href="pengguna.php" class="btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Users Table -->
            <div class="card">
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pengguna</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Role</th>
                                <th>Tanggal Bergabung</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = mysqli_fetch_assoc($users_result)): 
                                $initials = strtoupper(substr($user['nama'], 0, 2));
                                $join_date = date('d/m/Y', strtotime($user['created_at']));
                            ?>
                            <tr>
                                <td>#<?php echo str_pad($user['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="user-avatar">
                                            <?php echo $initials; ?>
                                        </div>
                                        <div>
                                            <div><strong><?php echo $user['nama']; ?></strong></div>
                                            <small><?php echo $user['alamat'] ? substr($user['alamat'], 0, 30) . '...' : 'Tidak ada alamat'; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $user['email']; ?></td>
                                <td><?php echo $user['telepon'] ?: '-'; ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php echo $user['role'] == 'admin' ? 'Admin' : 'Pelanggan'; ?>
                                    </span>
                                </td>
                                <td><?php echo $join_date; ?></td>
                                <td>
                                    <div class="user-actions">
                                        <button class="btn-action btn-view" 
                                                onclick="viewUserDetails(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" 
                                           class="btn-action btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['role'] == 'user'): ?>
                                        <a href="pengguna.php?action=delete&id=<?php echo $user['id']; ?>" 
                                           class="btn-action btn-delete"
                                           onclick="return confirm('Hapus pengguna ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
    
    <!-- Modal for Add Admin -->
    <div id="addAdminModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2><i class="fas fa-user-plus"></i> Tambah Admin Baru</h2>
            <form method="POST" action="">
                <input type="hidden" name="add_admin" value="1">
                
                <div class="form-group">
                    <label for="nama">Nama Lengkap *</label>
                    <input type="text" id="nama" name="nama" class="form-control" required
                           placeholder="Masukkan nama lengkap">
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" class="form-control" required
                           placeholder="admin@example.com">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" class="form-control" required
                               placeholder="Minimal 6 karakter">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Konfirmasi Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required
                               placeholder="Ulangi password">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Tambah Admin
                    </button>
                    <button type="button" class="btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i> Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal for User Details -->
    <div id="userDetailsModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2>Detail Pengguna</h2>
            <div id="userDetailsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
    
    <script src="../js/admin.js"></script>
    <script>
    function showAddAdminModal() {
        document.getElementById('addAdminModal').style.display = 'block';
    }
    
    function viewUserDetails(userId) {
        fetch(`get_user_details.php?id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const user = data.user;
                    const stats = data.stats;
                    
                    let html = `
                        <div class="user-detail-grid">
                            <div style="text-align: center; margin-bottom: 20px;">
                                <div class="user-avatar-large">
                                    ${user.nama.charAt(0).toUpperCase()}
                                </div>
                                <h3>${user.nama}</h3>
                                <span class="role-badge role-${user.role}" style="font-size: 1rem;">
                                    ${user.role === 'admin' ? 'Admin' : 'Pelanggan'}
                                </span>
                            </div>
                            
                            <div class="info-section">
                                <h4><i class="fas fa-info-circle"></i> Informasi Pribadi</h4>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <strong>Email:</strong><br>
                                        ${user.email}
                                    </div>
                                    <div class="info-item">
                                        <strong>Telepon:</strong><br>
                                        ${user.telepon || '-'}
                                    </div>
                                    <div class="info-item">
                                        <strong>Bergabung:</strong><br>
                                        ${new Date(user.created_at).toLocaleDateString('id-ID')}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="info-section">
                                <h4><i class="fas fa-map-marker-alt"></i> Alamat</h4>
                                <p>${user.alamat || 'Tidak ada alamat'}</p>
                            </div>
                            
                            <div class="info-section">
                                <h4><i class="fas fa-chart-bar"></i> Statistik</h4>
                                <div class="info-grid">
                                    <div class="stat-item">
                                        <div class="stat-number">${stats.total_orders || 0}</div>
                                        <div class="stat-label">Total Pesanan</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number">Rp ${parseInt(stats.total_spent || 0).toLocaleString('id-ID')}</div>
                                        <div class="stat-label">Total Belanja</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number">${stats.last_order_date || '-'}</div>
                                        <div class="stat-label">Pesanan Terakhir</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="info-section">
                                <h4><i class="fas fa-shopping-cart"></i> Pesanan Terbaru</h4>
                    `;
                    
                    if (data.recent_orders && data.recent_orders.length > 0) {
                        html += `<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                            <thead>
                                <tr>
                                    <th style="padding: 8px; text-align: left; border-bottom: 1px solid #ddd;">ID</th>
                                    <th style="padding: 8px; text-align: left; border-bottom: 1px solid #ddd;">Total</th>
                                    <th style="padding: 8px; text-align: left; border-bottom: 1px solid #ddd;">Status</th>
                                    <th style="padding: 8px; text-align: left; border-bottom: 1px solid #ddd;">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>`;
                        
                        data.recent_orders.forEach(order => {
                            html += `<tr>
                                <td style="padding: 8px;">#${order.id.toString().padStart(6, '0')}</td>
                                <td style="padding: 8px;">Rp ${parseInt(order.total_harga).toLocaleString('id-ID')}</td>
                                <td style="padding: 8px;"><span class="status-badge status-${order.status}">${order.status}</span></td>
                                <td style="padding: 8px;">${new Date(order.created_at).toLocaleDateString('id-ID')}</td>
                            </tr>`;
                        });
                        
                        html += `</tbody></table>`;
                    } else {
                        html += `<p>Tidak ada pesanan</p>`;
                    }
                    
                    html += `</div></div>`;
                    
                    document.getElementById('userDetailsContent').innerHTML = html;
                    document.getElementById('userDetailsModal').style.display = 'block';
                }
            });
    }
    
    function closeModal() {
        document.getElementById('addAdminModal').style.display = 'none';
        document.getElementById('userDetailsModal').style.display = 'none';
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
    
    // Validate password match in add admin form
    const addAdminForm = document.querySelector('#addAdminModal form');
    if (addAdminForm) {
        addAdminForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Password tidak cocok!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter!');
                return false;
            }
        });
    }
    </script>
    
    <style>
    .user-avatar-large {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 2rem;
        margin: 0 auto 15px;
    }
    
    .user-detail-grid {
        display: grid;
        gap: 20px;
    }
    
    .info-section {
        background: #f8f9fa;
        padding: 15px;
        border-radius: var(--radius);
    }
    
    .info-section h4 {
        margin-bottom: 15px;
        color: var(--primary);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .info-item {
        background: white;
        padding: 10px;
        border-radius: var(--radius);
    }
    
    .stat-item {
        text-align: center;
        padding: 10px;
    }
    
    .stat-item .stat-number {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary);
    }
    
    .stat-item .stat-label {
        font-size: 0.9rem;
        color: var(--text);
    }
    </style>
</body>
</html>