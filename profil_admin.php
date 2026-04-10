<?php
session_start();
require_once '../components/database.php';
require_once '../components/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../admin_login.php');
    exit();
}

$admin_id = $_SESSION['user_id'];

// Get admin data
$query = "SELECT * FROM users WHERE id = ? AND role = 'admin'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

if (!$admin) {
    session_destroy();
    header('Location: ../admin_login.php');
    exit();
}

// Tentukan nama kolom total di tabel orders
$check_orders = mysqli_query($conn, "SHOW COLUMNS FROM orders");
$total_column = 'total'; // default
while ($column = mysqli_fetch_assoc($check_orders)) {
    if (in_array($column['Field'], ['total_amount', 'total', 'grand_total', 'amount'])) {
        $total_column = $column['Field'];
        break;
    }
}

// Debug: Cek kolom yang tersedia
// echo "Kolom total yang digunakan: $total_column";

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nama = clean_input($_POST['nama']);
    $email = clean_input($_POST['email']);
    $telepon = clean_input($_POST['telepon']);
    
    // Check if email is changed and exists
    if ($email != $admin['email']) {
        $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "si", $email, $admin_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $_SESSION['error'] = 'Email sudah digunakan oleh pengguna lain!';
            header('Location: profil_admin.php');
            exit();
        }
    }
    
    $update_query = "UPDATE users SET nama = ?, email = ?, telepon = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "sssi", $nama, $email, $telepon, $admin_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = 'Profil berhasil diperbarui!';
        $_SESSION['nama'] = $nama;
        $_SESSION['email'] = $email;
        header('Location: profil_admin.php');
        exit();
    } else {
        $_SESSION['error'] = 'Gagal memperbarui profil!';
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if (!password_verify($current_password, $admin['password'])) {
        $_SESSION['error'] = 'Password saat ini salah!';
        header('Location: profil_admin.php');
        exit();
    }
    
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = 'Password baru tidak cocok!';
        header('Location: profil_admin.php');
        exit();
    }
    
    if (strlen($new_password) < 6) {
        $_SESSION['error'] = 'Password minimal 6 karakter!';
        header('Location: profil_admin.php');
        exit();
    }
    
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $update_query = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "si", $hashed_password, $admin_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = 'Password berhasil diubah!';
        header('Location: profil_admin.php');
        exit();
    } else {
        $_SESSION['error'] = 'Gagal mengubah password!';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Admin - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    .profile-page {
        padding: 20px;
    }
    
    .profile-header {
        display: flex;
        align-items: center;
        gap: 25px;
        margin-bottom: 40px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border);
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 3rem;
        position: relative;
        box-shadow: 0 5px 20px rgba(109, 157, 197, 0.3);
    }
    
    .profile-avatar:hover .avatar-upload {
        opacity: 1;
    }
    
    .avatar-upload {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
        cursor: pointer;
    }
    
    .avatar-upload i {
        color: white;
        font-size: 1.5rem;
    }
    
    .profile-info h1 {
        color: var(--dark);
        margin-bottom: 10px;
    }
    
    .profile-role {
        display: inline-block;
        background: var(--danger);
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .profile-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        text-align: center;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-icon {
        font-size: 2.5rem;
        color: var(--primary);
        margin-bottom: 15px;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 5px;
        color: var(--dark);
    }
    
    .stat-label {
        font-size: 0.9rem;
        color: var(--text);
    }
    
    .profile-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 30px;
        border-bottom: 1px solid var(--border);
        padding-bottom: 10px;
    }
    
    .tab-button {
        padding: 12px 25px;
        background: none;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        font-weight: 600;
        color: var(--text);
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .tab-button.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }
    
    .tab-button:hover:not(.active) {
        color: var(--secondary);
    }
    
    .tab-content {
        display: none;
        background: white;
        padding: 30px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
    }
    
    .tab-content.active {
        display: block;
        animation: fadeIn 0.5s ease;
    }
    
    .form-container {
        max-width: 600px;
    }
    
    .security-note {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        color: #856404;
        padding: 15px;
        border-radius: var(--radius);
        margin-bottom: 20px;
        font-size: 0.9rem;
    }
    
    .security-note i {
        margin-right: 8px;
    }
    
    .activity-log {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .activity-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        border-bottom: 1px solid var(--border);
    }
    
    .activity-item:last-child {
        border-bottom: none;
    }
    
    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--light);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        font-size: 1.2rem;
    }
    
    .activity-content {
        flex: 1;
    }
    
    .activity-title {
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 5px;
    }
    
    .activity-time {
        font-size: 0.85rem;
        color: var(--text);
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .profile-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid var(--border);
    }
    
    @media (max-width: 768px) {
        .profile-header {
            flex-direction: column;
            text-align: center;
        }
        
        .profile-stats {
            grid-template-columns: 1fr;
        }
        
        .profile-tabs {
            flex-direction: column;
        }
        
        .tab-button {
            justify-content: center;
        }
        
        .profile-actions {
            flex-direction: column;
        }
    }
    </style>
</head>
<body class="admin-body">
    <?php include '../components/sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="profile-page">
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
            
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php 
                    $initials = strtoupper(substr($admin['nama'], 0, 2));
                    echo $initials; 
                    ?>
                    <div class="avatar-upload" onclick="document.getElementById('avatarInput').click()">
                        <i class="fas fa-camera"></i>
                    </div>
                    <input type="file" id="avatarInput" accept="image/*" style="display: none;" onchange="uploadAvatar(this)">
                </div>
                
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($admin['nama']); ?></h1>
                    <p class="profile-role">
                        <i class="fas fa-user-shield"></i> Administrator
                    </p>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($admin['email']); ?></p>
                    <p><i class="fas fa-id-badge"></i> ID: #<?php echo str_pad($admin['id'], 3, '0', STR_PAD_LEFT); ?></p>
                    <p><i class="fas fa-calendar-alt"></i> Bergabung: <?php echo date('d F Y', strtotime($admin['created_at'])); ?></p>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="profile-stats">
                <?php
                // Get admin statistics
                $today = date('Y-m-d');
                
                // Total orders today
                $orders_today_result = mysqli_query($conn, 
                    "SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = '$today'");
                $orders_today_row = mysqli_fetch_assoc($orders_today_result);
                $orders_today = $orders_today_row['total'] ?? 0;
                
                // Total products
                $total_products_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM produk");
                $total_products_row = mysqli_fetch_assoc($total_products_result);
                $total_products = $total_products_row['total'] ?? 0;
                
                // Total users (excluding admin)
                $total_users_result = mysqli_query($conn, 
                    "SELECT COUNT(*) as total FROM users WHERE role = 'user' OR role IS NULL");
                $total_users_row = mysqli_fetch_assoc($total_users_result);
                $total_users = $total_users_row['total'] ?? 0;
                
                // Revenue today - PERBAIKAN DI SINI
                // Cek status apa yang ada di tabel orders
                $check_status = mysqli_query($conn, "SELECT DISTINCT status FROM orders LIMIT 5");
                $status_options = [];
                while ($status_row = mysqli_fetch_assoc($check_status)) {
                    $status_options[] = $status_row['status'];
                }
                
                // Gunakan status yang sesuai (delivered, selesai, completed, dll)
                $completed_status = 'delivered'; // default
                if (in_array('selesai', $status_options)) {
                    $completed_status = 'selesai';
                } elseif (in_array('completed', $status_options)) {
                    $completed_status = 'completed';
                }
                
                // Query revenue dengan kolom dan status yang benar
                $revenue_query = "SELECT SUM($total_column) as total FROM orders 
                                 WHERE DATE(created_at) = '$today' 
                                 AND status = '$completed_status'";
                
                $revenue_today_result = mysqli_query($conn, $revenue_query);
                $revenue_today_row = mysqli_fetch_assoc($revenue_today_result);
                $revenue_today = $revenue_today_row['total'] ?? 0;
                ?>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-number"><?php echo $orders_today; ?></div>
                    <div class="stat-label">Pesanan Hari Ini</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_products; ?></div>
                    <div class="stat-label">Total Produk</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Pelanggan</div>
                </div>
                
                
            </div>
            
            <!-- Tabs -->
            <div class="profile-tabs">
                <button type="button" class="tab-button active" onclick="showTab('profile')">
                    <i class="fas fa-user-cog"></i> Edit Profil
                </button>
                <button type="button" class="tab-button" onclick="showTab('password')">
                    <i class="fas fa-lock"></i> Ubah Password
                </button>
                <button type="button" class="tab-button" onclick="showTab('activity')">
                    <i class="fas fa-history"></i> Aktivitas Terbaru
                </button>
            </div>
            
            <!-- Profile Tab -->
            <div id="profileTab" class="tab-content active">
                <div class="form-container">
                    <h2><i class="fas fa-user-edit"></i> Edit Profil</h2>
                    <form method="POST" action="">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nama">Nama Lengkap *</label>
                                <input type="text" id="nama" name="nama" class="form-control" required
                                       value="<?php echo htmlspecialchars($admin['nama']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" class="form-control" required
                                       value="<?php echo htmlspecialchars($admin['email']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="telepon">Telepon</label>
                                <input type="tel" id="telepon" name="telepon" class="form-control"
                                       value="<?php echo htmlspecialchars($admin['telepon'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Password Tab -->
            <div id="passwordTab" class="tab-content">
                <div class="form-container">
                    <h2><i class="fas fa-key"></i> Ubah Password</h2>
                    
                    <div class="security-note">
                        <i class="fas fa-shield-alt"></i>
                        Pastikan password Anda kuat dan unik. Minimal 6 karakter dengan kombinasi huruf dan angka.
                    </div>
                    
                    <form method="POST" action="" id="passwordForm">
                        <input type="hidden" name="change_password" value="1">
                        
                        <div class="form-group">
                            <label for="current_password">Password Saat Ini *</label>
                            <input type="password" id="current_password" name="current_password" 
                                   class="form-control" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">Password Baru *</label>
                                <input type="password" id="new_password" name="new_password" 
                                       class="form-control" required>
                                <small class="password-strength" id="passwordStrength"></small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Konfirmasi Password Baru *</label>
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       class="form-control" required>
                                <small class="password-match" id="passwordMatch"></small>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-key"></i> Ubah Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Activity Tab -->
            <div id="activityTab" class="tab-content">
                <h2><i class="fas fa-history"></i> Aktivitas Terbaru</h2>
                <p>Aktivitas admin dalam 7 hari terakhir.</p>
                
                <div class="activity-log">
                    <?php
                    // Cek apakah tabel admin_activity_log ada
                    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'admin_activity_log'");
                    if (mysqli_num_rows($check_table) > 0) {
                        // Get recent admin activities
                        $activity_query = "SELECT * FROM admin_activity_log 
                                          WHERE admin_id = ? 
                                          ORDER BY created_at DESC 
                                          LIMIT 10";
                        $stmt = mysqli_prepare($conn, $activity_query);
                        mysqli_stmt_bind_param($stmt, "i", $admin_id);
                        mysqli_stmt_execute($stmt);
                        $activities_result = mysqli_stmt_get_result($stmt);
                        
                        if (mysqli_num_rows($activities_result) > 0):
                            while ($activity = mysqli_fetch_assoc($activities_result)):
                                $icon = '';
                                $color = '';
                                
                                switch ($activity['activity_type']) {
                                    case 'login':
                                        $icon = 'sign-in-alt';
                                        $color = 'success';
                                        break;
                                    case 'logout':
                                        $icon = 'sign-out-alt';
                                        $color = 'warning';
                                        break;
                                    case 'add_product':
                                        $icon = 'plus-circle';
                                        $color = 'primary';
                                        break;
                                    case 'edit_product':
                                        $icon = 'edit';
                                        $color = 'info';
                                        break;
                                    case 'update_order':
                                        $icon = 'shopping-cart';
                                        $color = 'secondary';
                                        break;
                                    default:
                                        $icon = 'cog';
                                        $color = 'dark';
                                }
                    ?>
                    <div class="activity-item">
                        <div class="activity-icon" style="background: var(--<?php echo $color; ?>); color: white;">
                            <i class="fas fa-<?php echo $icon; ?>"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">
                                <?php echo $activity['description']; ?>
                            </div>
                            <div class="activity-time">
                                <i class="fas fa-clock"></i> 
                                <?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?> |
                                <i class="fas fa-globe"></i> <?php echo $activity['ip_address']; ?>
                            </div>
                        </div>
                    </div>
                    <?php 
                            endwhile;
                        else:
                    ?>
                    <div class="no-activity">
                        <i class="fas fa-history fa-3x"></i>
                        <h3>Tidak ada aktivitas</h3>
                        <p>Belum ada aktivitas yang tercatat dalam 7 hari terakhir.</p>
                    </div>
                    <?php 
                        endif;
                    } else {
                        // Tabel tidak ada
                    ?>
                    <div class="no-activity">
                        <i class="fas fa-database fa-3x"></i>
                        <h3>Tabel Aktivitas Belum Tersedia</h3>
                        <p>Tabel admin_activity_log belum dibuat. Silakan jalankan query untuk membuat tabel.</p>
                    </div>
                    <?php } ?>
                </div>
            </div>
            
            <!-- Profile Actions -->
            <div class="profile-actions">
                <button class="btn-secondary" onclick="exportData()">
                    <i class="fas fa-file-export"></i> Export Data
                </button>
                <button class="btn-warning" onclick="showSessionInfo()">
                    <i class="fas fa-info-circle"></i> Info Session
                </button>
                <a href="../components/admin_logout.php" class="btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </main>
    
    <!-- Session Info Modal -->
    <div id="sessionModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2><i class="fas fa-info-circle"></i> Informasi Session</h2>
            <div class="session-info">
                <p><strong>Admin ID:</strong> <?php echo $_SESSION['user_id']; ?></p>
                <p><strong>Nama:</strong> <?php echo $_SESSION['nama']; ?></p>
                <p><strong>Email:</strong> <?php echo $_SESSION['email']; ?></p>
                <p><strong>Role:</strong> <?php echo $_SESSION['role']; ?></p>
                <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
                <p><strong>Login Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            </div>
        </div>
    </div>
    
    <script>
    // Tab switching
    function showTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Remove active class from all buttons
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
        });
        
        // Show selected tab
        document.getElementById(tabName + 'Tab').classList.add('active');
        
        // Activate selected button
        event.target.classList.add('active');
    }
    
    // Password strength checker
    document.getElementById('new_password').addEventListener('input', function() {
        const password = this.value;
        const strengthText = document.getElementById('passwordStrength');
        
        let strength = 0;
        let message = '';
        let color = 'red';
        
        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        switch(strength) {
            case 0:
            case 1:
                message = 'Lemah';
                color = 'red';
                break;
            case 2:
                message = 'Cukup';
                color = 'orange';
                break;
            case 3:
                message = 'Baik';
                color = 'blue';
                break;
            case 4:
                message = 'Sangat Kuat';
                color = 'green';
                break;
        }
        
        strengthText.textContent = 'Kekuatan: ' + message;
        strengthText.style.color = color;
    });
    
    // Password match checker
    document.getElementById('confirm_password').addEventListener('input', function() {
        const password = document.getElementById('new_password').value;
        const confirmPassword = this.value;
        const matchText = document.getElementById('passwordMatch');
        
        if (confirmPassword === '') {
            matchText.textContent = '';
        } else if (password === confirmPassword) {
            matchText.textContent = '✓ Password cocok';
            matchText.style.color = 'green';
        } else {
            matchText.textContent = '✗ Password tidak cocok';
            matchText.style.color = 'red';
        }
    });
    
    // Form validation
    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        const password = document.getElementById('new_password').value;
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
        
        return true;
    });
    
    // Avatar upload
    function uploadAvatar(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            
            // Check file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran file maksimal 2MB');
                return;
            }
            
            // Check file type
            const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                alert('Hanya file JPG, PNG, atau GIF yang diizinkan');
                return;
            }
            
            // Show loading
            const avatar = document.querySelector('.profile-avatar');
            avatar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Simulate upload (in real app, you would use AJAX)
            setTimeout(() => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // In real app, you would save the image and update the avatar
                    alert('Fitur upload avatar sedang dalam pengembangan');
                    avatar.innerHTML = '<?php echo $initials; ?>' + 
                        '<div class="avatar-upload" onclick="document.getElementById(\'avatarInput\').click()">' +
                        '<i class="fas fa-camera"></i></div>';
                };
                reader.readAsDataURL(file);
            }, 1500);
        }
    }
    
    // Show session info modal
    function showSessionInfo() {
        document.getElementById('sessionModal').style.display = 'block';
    }
    
    // Close modal
    function closeModal() {
        document.getElementById('sessionModal').style.display = 'none';
    }
    
    // Export data
    function exportData() {
        const data = {
            admin_id: <?php echo $admin_id; ?>,
            nama: "<?php echo $admin['nama']; ?>",
            email: "<?php echo $admin['email']; ?>",
            telepon: "<?php echo $admin['telepon'] ?? ''; ?>",
            join_date: "<?php echo $admin['created_at']; ?>",
            export_date: new Date().toISOString()
        };
        
        const dataStr = JSON.stringify(data, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = window.URL.createObjectURL(dataBlob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'admin_profile_?php echo $admin_id; ?>.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        
        showNotification('Data berhasil diexport!', 'success');
    }
    
    // Notification system
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Show animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == document.getElementById('sessionModal')) {
            closeModal();
        }
    }
    </script>
    
    <style>
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
        margin: 10% auto;
        padding: 30px;
        border-radius: var(--radius);
        width: 90%;
        max-width: 500px;
        position: relative;
    }
    
    .close-modal {
        position: absolute;
        right: 20px;
        top: 20px;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--text);
    }
    
    .session-info {
        background: #f8f9fa;
        padding: 20px;
        border-radius: var(--radius);
        margin-top: 20px;
    }
    
    .session-info p {
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #dee2e6;
    }
    
    .session-info p:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .notification {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: white;
        padding: 15px 20px;
        border-radius: var(--radius);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 10px;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        z-index: 10000;
    }
    
    .notification.show {
        transform: translateX(0);
    }
    
    .notification-success {
        border-left: 4px solid var(--success);
    }
    
    .notification-success i {
        color: var(--success);
    }
    
    .no-activity {
        text-align: center;
        padding: 40px;
        color: var(--text);
    }
    
    .no-activity i {
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    .password-strength, .password-match {
        display: block;
        margin-top: 5px;
        font-size: 0.85rem;
    }
    </style>
</body>
</html>