<?php
session_start();
require_once 'components/database.php';
require_once 'components/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get user data
$query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Helper function untuk member level
function getMemberLevel($totalSpent) {
    $total = floatval($totalSpent); // Konversi ke float
    if ($total >= 10000000) return 'Platinum';
    if ($total >= 5000000) return 'Gold';
    if ($total >= 1000000) return 'Silver';
    return 'Bronze';
}

// === QUERY STATISTIK YANG BENAR ===
// Get user statistics - SEMUA pesanan (tidak hanya 'selesai')
$stats_query = "SELECT 
    COUNT(*) as total_orders,
    COALESCE(SUM(total_amount), 0) as total_spent,
    COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) as total_paid,
    MAX(created_at) as last_order_date
    FROM orders 
    WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$stats_result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($stats_result);

// Jika tidak ada pesanan sama sekali
if (!$stats || $stats['total_orders'] == 0) {
    $stats = [
        'total_orders' => 0,
        'total_spent' => 0,
        'total_paid' => 0,
        'last_order_date' => null
    ];
}

// Get latest order for "Pesanan Terakhir"
$last_order_query = "SELECT * FROM orders 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 1";
$stmt_last = mysqli_prepare($conn, $last_order_query);
mysqli_stmt_bind_param($stmt_last, "i", $user_id);
mysqli_stmt_execute($stmt_last);
$last_order_result = mysqli_stmt_get_result($stmt_last);
$last_order = mysqli_fetch_assoc($last_order_result);

// Get user's main address
$address_query = "SELECT * FROM user_addresses WHERE user_id = ? AND utama = 1 LIMIT 1";
$stmt_address = mysqli_prepare($conn, $address_query);
mysqli_stmt_bind_param($stmt_address, "i", $user_id);
mysqli_stmt_execute($stmt_address);
$address_result = mysqli_stmt_get_result($stmt_address);
$main_address = mysqli_fetch_assoc($address_result);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_updateprofile'])) {
    $nama = clean_input($_POST['nama']);
    $email = clean_input($_POST['email']);
    $telepon = clean_input($_POST['telepon']);
    $alamat = clean_input($_POST['alamat']);
    
    // Check if email is changed and exists
    if ($email != $user['email']) {
        $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error = 'Email sudah digunakan oleh pengguna lain!';
        }
    }
    
    if (empty($error)) {
        $update_query = "UPDATE users SET nama = ?, email = ?, telepon = ?, alamat = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ssssi", $nama, $email, $telepon, $alamat, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['nama'] = $nama;
            $_SESSION['email'] = $email;
            $success = 'Profil berhasil diperbarui!';
            
            // Update main address if exists
            if ($main_address) {
                $update_address_query = "UPDATE user_addresses SET alamat = ? WHERE id = ?";
                $stmt_addr = mysqli_prepare($conn, $update_address_query);
                mysqli_stmt_bind_param($stmt_addr, "si", $alamat, $main_address['id']);
                mysqli_stmt_execute($stmt_addr);
            }
            
            // Refresh user data
            $user['nama'] = $nama;
            $user['email'] = $email;
            $user['telepon'] = $telepon;
            $user['alamat'] = $alamat;
        } else {
            $error = 'Gagal memperbarui profil!';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        $error = 'Password saat ini salah!';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Password baru tidak cocok!';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Password berhasil diubah!';
        } else {
            $error = 'Gagal mengubah password!';
        }
    }
}

// Get recent orders
$orders_query = "SELECT * FROM orders 
                 WHERE user_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT 5";
$stmt = mysqli_prepare($conn, $orders_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);

$page_title = "Profil Pengguna";
include 'components/header_pengguna.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna - MinShop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Profile Section */
        .profile-section {
            padding: 40px 0;
            background: #f8f9fa;
            min-height: calc(100vh - 200px);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            animation: slideInDown 0.3s ease;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert i {
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        /* Profile Header */
        .profile-header {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            padding: 40px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 40px;
        }

        .profile-avatar-container {
            text-align: center;
            flex-shrink: 0;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4361ee 0%, #3a56d4 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0 auto 20px;
            position: relative;
            cursor: pointer;
            overflow: hidden;
        }

        .avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .profile-avatar:hover .avatar-overlay {
            opacity: 1;
        }

        .avatar-overlay i {
            color: white;
            font-size: 1.5rem;
        }

        .profile-avatar-container h1 {
            color: #333;
            margin-bottom: 5px;
            font-size: 1.8rem;
        }

        .profile-email {
            color: #666;
            margin-bottom: 5px;
        }

        .profile-join-date {
            color: #666;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Profile Stats */
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            flex: 1;
        }

        @media (max-width: 768px) {
            .profile-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .profile-stats {
                grid-template-columns: 1fr;
            }
        }

        .stat-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s ease;
            border: 1px solid #e9ecef;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: #4361ee;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .stat-content {
            flex: 1;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 2px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }

        /* Profile Content */
        .profile-content {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }

        @media (max-width: 992px) {
            .profile-content {
                grid-template-columns: 1fr;
            }
        }

        /* Profile Cards */
        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .card-header {
            padding: 25px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            color: #4361ee;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-edit {
            background: #4361ee;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-edit:hover {
            background: #3a56d4;
        }

        .btn-view-all {
            color: #4361ee;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-view-all:hover {
            color: #3a56d4;
        }

        /* Profile Form */
        .profile-form {
            padding: 25px;
            display: none;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 576px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        .btn-primary, .btn-secondary {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #4361ee;
            color: white;
        }

        .btn-primary:hover {
            background: #3a56d4;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #666;
        }

        .btn-secondary:hover {
            background: #e9ecef;
        }

        /* Profile Info View */
        .profile-info-view {
            padding: 25px;
        }

        .info-row {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            width: 200px;
            color: #333;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-value {
            flex: 1;
            color: #666;
        }

        .badge {
            background: #f72585;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            margin-left: 5px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: 30px;
            }
            
            .info-row {
                flex-direction: column;
                gap: 5px;
            }
            
            .info-label {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<!-- Profile Section -->
<section class="profile-section">
    <div class="container">
        <!-- Notifications -->
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <div class="profile-header">
            <div class="profile-avatar-container">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['nama'], 0, 2)); ?>
                    <div class="avatar-overlay" onclick="document.getElementById('avatarUpload').click()">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>
                <input type="file" id="avatarUpload" accept="image/*" style="display: none;" onchange="uploadAvatar(this)">
                <h1><?php echo htmlspecialchars($user['nama']); ?></h1>
                <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                <p class="profile-join-date">Bergabung: <?php echo date('d F Y', strtotime($user['created_at'])); ?></p>
            </div>
            
            <div class="profile-stats">
                <!-- Total Pesanan -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo number_format($stats['total_orders'], 0, ',', '.'); ?></div>
                        <div class="stat-label">Total Pesanan</div>
                    </div>
                </div>
                
                <!-- Total Belanja -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">Rp <?php 
                            echo number_format($stats['total_spent'], 0, ',', '.'); 
                        ?></div>
                        <div class="stat-label">Total Belanja</div>
                    </div>
                </div>
                
                <!-- Level Member -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php 
                            echo getMemberLevel($stats['total_spent']); 
                        ?></div>
                        <div class="stat-label">Level Member</div>
                    </div>
                </div>
                
                <!-- Pesanan Terakhir -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">
                            <?php 
                                if ($last_order && !empty($last_order['created_at'])) {
                                    echo date('d/m/Y', strtotime($last_order['created_at']));
                                } else {
                                    echo '-';
                                }
                            ?>
                        </div>
                        <div class="stat-label">Pesanan Terakhir</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="profile-content">
            <!-- Left Column: Profile Info & Orders -->
            <div class="profile-left">
                <!-- Profile Information -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2><i class="fas fa-user-circle"></i> Informasi Profil</h2>
                        <button class="btn-edit" onclick="toggleEditMode()">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </div>
                    
                    <form method="POST" action="" id="profileForm" class="profile-form">
                        <input type="hidden" name="user_updateprofile" value="1">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nama"><i class="fas fa-user"></i> Nama Lengkap</label>
                                <input type="text" id="nama" name="nama" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($user['nama']); ?>"
                                       placeholder="Masukkan nama lengkap" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                                <input type="email" id="email" name="email" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>"
                                       placeholder="email@contoh.com" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="telepon"><i class="fas fa-phone"></i> Telepon</label>
                                <input type="tel" id="telepon" name="telepon" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($user['telepon'] ?? ''); ?>"
                                       placeholder="0812-3456-7890">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="alamat"><i class="fas fa-map-marker-alt"></i> Alamat</label>
                            <textarea id="alamat" name="alamat" 
                                      class="form-control" 
                                      rows="4"
                                      placeholder="Masukkan alamat lengkap"><?php 
                                // Tampilkan alamat dari users, jika kosong tampilkan dari main address
                                if (!empty($user['alamat'])) {
                                    echo htmlspecialchars($user['alamat']);
                                } elseif ($main_address) {
                                    echo htmlspecialchars($main_address['alamat']);
                                }
                              ?></textarea>
                        </div>
                        
                        <div class="form-actions" id="profileActions" style="display: none;">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                            <button type="button" class="btn-secondary" onclick="cancelEdit()">
                                <i class="fas fa-times"></i> Batal
                            </button>
                        </div>
                    </form>
                    
                    <!-- Read-only view -->
                    <div class="profile-info-view" id="profileInfoView">
                        <div class="info-row">
                            <div class="info-label">
                                <i class="fas fa-user"></i> Nama Lengkap
                            </div>
                            <div class="info-value"><?php echo htmlspecialchars($user['nama']); ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">
                                <i class="fas fa-envelope"></i> Email
                            </div>
                            <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">
                                <i class="fas fa-phone"></i> Telepon
                            </div>
                            <div class="info-value"><?php echo htmlspecialchars($user['telepon'] ?? 'Belum diisi'); ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">
                                <i class="fas fa-map-marker-alt"></i> Alamat
                            </div>
                            <div class="info-value">
                                <?php 
                                if (!empty($user['alamat'])) {
                                    echo htmlspecialchars($user['alamat']);
                                } elseif ($main_address) {
                                    echo htmlspecialchars($main_address['alamat']) . ' <span class="badge">Alamat Utama</span>';
                                } else {
                                    echo 'Belum diisi';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2><i class="fas fa-history"></i> Pesanan Terbaru</h2>
                        <a href="orders.php" class="btn-view-all">
                            Lihat Semua <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <?php if (mysqli_num_rows($orders_result) > 0): ?>
                    <div class="orders-list" style="padding: 25px;">
                        <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                        <div class="order-item" style="border: 1px solid #e9ecef; border-radius: 8px; margin-bottom: 15px; padding: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <div style="font-weight: 600; color: #333;">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                <div style="color: #666; font-size: 0.9rem;"><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></div>
                                <div style="padding: 5px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; 
                                    <?php 
                                    $statusColor = [
                                        'pending' => 'background: #fff3cd; color: #856404;',
                                        'diproses' => 'background: #cce5ff; color: #004085;',
                                        'dikirim' => 'background: #d4edda; color: #155724;',
                                        'selesai' => 'background: #d1ecf1; color: #0c5460;',
                                        'batal' => 'background: #f8d7da; color: #721c24;'
                                    ];
                                    echo $statusColor[$order['status']] ?? 'background: #e9ecef; color: #333;';
                                    ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <span style="color: #666;">Total:</span>
                                    <strong style="color: #4361ee;">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong>
                                </div>
                                <div style="color: #666; font-size: 0.9rem; line-height: 1.4;">
                                    <?php echo substr($order['alamat_pengiriman'] ?? 'Tidak ada alamat', 0, 50); ?>...
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 10px;">
                                <a href="detail_pesanan.php?id=<?php echo $order['id']; ?>" style="flex: 1; background: #4361ee; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                    <i class="fas fa-eye"></i> Detail
                                </a>
                                <?php if ($order['status'] == 'menunggu_kurir'): ?>
                                <a href="bayar_pesanan.php?id=<?php echo $order['id']; ?>" style="flex: 1; background: #28a745; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                    <i class="fas fa-credit-card"></i> Bayar
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="no-orders" style="padding: 40px 25px; text-align: center;">
                        <i class="fas fa-shopping-cart" style="font-size: 3rem; color: #4361ee; margin-bottom: 20px; opacity: 0.5;"></i>
                        <h3 style="color: #333; margin-bottom: 10px;">Belum Ada Pesanan</h3>
                        <p style="color: #666; margin-bottom: 20px;">Mulai belanja untuk melihat riwayat pesanan Anda</p>
                        <a href="produk.php" class="btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-shopping-bag"></i> Mulai Belanja
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right Column: Password & Settings -->
            <div class="profile-right">
                <!-- Change Password -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2><i class="fas fa-lock"></i> Ubah Password</h2>
                    </div>
                    
                    <form method="POST" action="" id="passwordForm" class="password-form" style="padding: 25px;">
                        <input type="hidden" name="change_password" value="1">
                        
                        <div class="form-group">
                            <label for="current_password">
                                <i class="fas fa-key"></i> Password Saat Ini
                            </label>
                            <div style="position: relative;">
                                <input type="password" id="current_password" name="current_password" 
                                       class="form-control" style="padding-right: 50px;" required>
                                <button type="button" class="btn-show-password" onclick="togglePassword('current_password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #666; cursor: pointer; padding: 5px;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">
                                <i class="fas fa-key"></i> Password Baru
                            </label>
                            <div style="position: relative;">
                                <input type="password" id="new_password" name="new_password" 
                                       class="form-control" style="padding-right: 50px;" required>
                                <button type="button" class="btn-show-password" onclick="togglePassword('new_password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #666; cursor: pointer; padding: 5px;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="passwordStrength" style="margin-top: 5px; font-size: 0.85rem;"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">
                                <i class="fas fa-key"></i> Konfirmasi Password Baru
                            </label>
                            <div style="position: relative;">
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       class="form-control" style="padding-right: 50px;" required>
                                <button type="button" class="btn-show-password" onclick="togglePassword('confirm_password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #666; cursor: pointer; padding: 5px;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-match" id="passwordMatch" style="margin-top: 5px; font-size: 0.85rem;"></div>
                        </div>
                        
                        <div class="password-requirements" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <p style="font-weight: 600; margin-bottom: 10px; color: #333;">Password harus mengandung:</p>
                            <ul style="margin: 0; padding-left: 20px;">
                                <li id="reqLength" style="margin-bottom: 5px; color: #666; font-size: 0.9rem;">Minimal 6 karakter</li>
                                <li id="reqUppercase" style="margin-bottom: 5px; color: #666; font-size: 0.9rem;">Minimal 1 huruf besar</li>
                                <li id="reqNumber" style="margin-bottom: 5px; color: #666; font-size: 0.9rem;">Minimal 1 angka</li>
                            </ul>
                        </div>
                        
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-key"></i> Ubah Password
                        </button>
                    </form>
                </div>
                
                <!-- Quick Actions -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2><i class="fas fa-bolt"></i> Akses Cepat</h2>
                    </div>
                    
                    <div class="quick-actions" style="padding: 25px;">
                        <a href="keranjang.php" class="quick-action" style="display: flex; align-items: center; gap: 15px; padding: 20px; border: 1px solid #e9ecef; border-radius: 8px; margin-bottom: 15px; text-decoration: none; color: #666; transition: all 0.3s ease;">
                            <div class="action-icon" style="width: 50px; height: 50px; background: #f8f9fa; color: #4361ee; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                                <i class="fas fa-shopping-cart"></i>  
                            </div>
                            <div class="action-info" style="flex: 1;">
                                <h3 style="color: #333; margin-bottom: 5px; font-size: 1.1rem;">Keranjang</h3>
                                <p style="font-size: 0.9rem; opacity: 0.8;">Lihat dan kelola keranjang belanja</p>
                            </div>
                            <div class="action-arrow" style="color: #4361ee; font-size: 1.2rem;">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                        
                        <a href="wishlist.php" class="quick-action" style="display: flex; align-items: center; gap: 15px; padding: 20px; border: 1px solid #e9ecef; border-radius: 8px; margin-bottom: 15px; text-decoration: none; color: #666; transition: all 0.3s ease;">
                            <div class="action-icon" style="width: 50px; height: 50px; background: #f8f9fa; color: #4361ee; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div class="action-info" style="flex: 1;">
                                <h3 style="color: #333; margin-bottom: 5px; font-size: 1.1rem;">Wishlist</h3>
                                <p style="font-size: 0.9rem; opacity: 0.8;">Produk yang disimpan untuk nanti</p>
                            </div>
                            <div class="action-arrow" style="color: #4361ee; font-size: 1.2rem;">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                        
                        <a href="alamat.php" class="quick-action" style="display: flex; align-items: center; gap: 15px; padding: 20px; border: 1px solid #e9ecef; border-radius: 8px; margin-bottom: 15px; text-decoration: none; color: #666; transition: all 0.3s ease;">
                            <div class="action-icon" style="width: 50px; height: 50px; background: #f8f9fa; color: #4361ee; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="action-info" style="flex: 1;">
                                <h3 style="color: #333; margin-bottom: 5px; font-size: 1.1rem;">Alamat Saya</h3>
                                <p style="font-size: 0.9rem; opacity: 0.8;">Kelola alamat pengiriman</p>
                            </div>
                            <div class="action-arrow" style="color: #4361ee; font-size: 1.2rem;">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                        
                        <a href="voucher.php" class="quick-action" style="display: flex; align-items: center; gap: 15px; padding: 20px; border: 1px solid #e9ecef; border-radius: 8px; text-decoration: none; color: #666; transition: all 0.3s ease;">
                            <div class="action-icon" style="width: 50px; height: 50px; background: #f8f9fa; color: #4361ee; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                                <i class="fas fa-tag"></i>
                            </div>
                            <div class="action-info" style="flex: 1;">
                                <h3 style="color: #333; margin-bottom: 5px; font-size: 1.1rem;">Voucher Saya</h3>
                                <p style="font-size: 0.9rem; opacity: 0.8;">Lihat voucher yang tersedia</p>
                            </div>
                            <div class="action-arrow" style="color: #4361ee; font-size: 1.2rem;">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle edit mode for profile
    window.toggleEditMode = function() {
        const form = document.getElementById('profileForm');
        const view = document.getElementById('profileInfoView');
        const actions = document.getElementById('profileActions');
        
        form.style.display = 'block';
        view.style.display = 'none';
        actions.style.display = 'flex';
        
        // Focus on first input
        document.getElementById('nama').focus();
    };
    
    window.cancelEdit = function() {
        const form = document.getElementById('profileForm');
        const view = document.getElementById('profileInfoView');
        const actions = document.getElementById('profileActions');
        
        form.style.display = 'none';
        view.style.display = 'block';
        actions.style.display = 'none';
    };
    
    // Toggle password visibility
    window.togglePassword = function(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fas fa-eye';
        }
    };
    
    // Password strength checker
    const newPasswordInput = document.getElementById('new_password');
    const strengthIndicator = document.getElementById('passwordStrength');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const matchIndicator = document.getElementById('passwordMatch');
    
    const requirements = {
        length: document.getElementById('reqLength'),
        uppercase: document.getElementById('reqUppercase'),
        number: document.getElementById('reqNumber')
    };
    
    newPasswordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        
        // Check length
        if (password.length >= 6) {
            strength++;
            requirements.length.classList.add('valid');
            requirements.length.style.color = '#28a745';
            requirements.length.innerHTML = '✓ Minimal 6 karakter';
        } else {
            requirements.length.classList.remove('valid');
            requirements.length.style.color = '#666';
            requirements.length.innerHTML = 'Minimal 6 karakter';
        }
        
        // Check uppercase
        if (/[A-Z]/.test(password)) {
            strength++;
            requirements.uppercase.classList.add('valid');
            requirements.uppercase.style.color = '#28a745';
            requirements.uppercase.innerHTML = '✓ Minimal 1 huruf besar';
        } else {
            requirements.uppercase.classList.remove('valid');
            requirements.uppercase.style.color = '#666';
            requirements.uppercase.innerHTML = 'Minimal 1 huruf besar';
        }
        
        // Check number
        if (/[0-9]/.test(password)) {
            strength++;
            requirements.number.classList.add('valid');
            requirements.number.style.color = '#28a745';
            requirements.number.innerHTML = '✓ Minimal 1 angka';
        } else {
            requirements.number.classList.remove('valid');
            requirements.number.style.color = '#666';
            requirements.number.innerHTML = 'Minimal 1 angka';
        }
        
        // Update strength indicator
        let strengthText = '';
        let strengthClass = '';
        
        switch(strength) {
            case 0:
            case 1:
                strengthText = 'Lemah';
                strengthClass = 'weak';
                strengthIndicator.style.color = '#dc3545';
                break;
            case 2:
                strengthText = 'Cukup';
                strengthClass = 'medium';
                strengthIndicator.style.color = '#fd7e14';
                break;
            case 3:
                strengthText = 'Kuat';
                strengthClass = 'strong';
                strengthIndicator.style.color = '#28a745';
                break;
        }
        
        strengthIndicator.textContent = 'Kekuatan: ' + strengthText;
        
        // Check password match
        checkPasswordMatch();
    });
    
    confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    
    function checkPasswordMatch() {
        const password = newPasswordInput.value;
        const confirm = confirmPasswordInput.value;
        
        if (confirm === '') {
            matchIndicator.textContent = '';
        } else if (password === confirm) {
            matchIndicator.textContent = '✓ Password cocok';
            matchIndicator.style.color = '#28a745';
        } else {
            matchIndicator.textContent = '✗ Password tidak cocok';
            matchIndicator.style.color = '#dc3545';
        }
    }
    
    // Form validation
    const profileForm = document.getElementById('profileForm');
    const passwordForm = document.getElementById('passwordForm');
    
    profileForm.addEventListener('submit', function(e) {
        // Basic validation
        const nama = document.getElementById('nama').value.trim();
        const email = document.getElementById('email').value.trim();
        
        if (!nama || nama.length < 2) {
            e.preventDefault();
            alert('Nama harus minimal 2 karakter');
            return false;
        }
        
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('Email tidak valid');
            return false;
        }
        
        return true;
    });
    
    passwordForm.addEventListener('submit', function(e) {
        const currentPassword = document.getElementById('current_password').value;
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (!currentPassword) {
            e.preventDefault();
            alert('Masukkan password saat ini');
            return false;
        }
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('Password baru tidak cocok');
            return false;
        }
        
        if (newPassword.length < 6) {
            e.preventDefault();
            alert('Password minimal 6 karakter');
            return false;
        }
        
        return true;
    });
    
    // Avatar upload
    window.uploadAvatar = function(input) {
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
            const originalText = avatar.textContent;
            avatar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Simulate upload
            setTimeout(() => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    alert('Fitur upload avatar sedang dalam pengembangan');
                    avatar.textContent = originalText;
                    avatar.innerHTML = originalText + 
                        '<div class="avatar-overlay" onclick="document.getElementById(\'avatarUpload\').click()">' +
                        '<i class="fas fa-camera"></i></div>';
                };
                reader.readAsDataURL(file);
            }, 1500);
        }
    };
});
</script>

<?php include 'components/footer.php'; ?>