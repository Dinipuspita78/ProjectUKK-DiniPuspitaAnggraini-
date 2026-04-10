<?php
// kurir/profil_kurir.php
session_start();
require_once '../components/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kurir') {
    header('Location: ../login_kurir.php');
    exit();
}

$kurir_id = $_SESSION['kurir_id'];
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Ambil data kurir
$query = "SELECT k.*, u.email, u.created_at as user_created 
          FROM kurir k 
          JOIN users u ON k.user_id = u.id 
          WHERE k.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $kurir_id);
mysqli_stmt_execute($stmt);
$kurir = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Handle update profil
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['update_password'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $kendaraan = mysqli_real_escape_string($conn, $_POST['kendaraan']);
    $plat_nomor = mysqli_real_escape_string($conn, $_POST['plat_nomor']);
    
    // Update kurir table
    $query_update = "UPDATE kurir SET nama = ?, no_hp = ?, kendaraan = ?, plat_nomor = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query_update);
    mysqli_stmt_bind_param($stmt, "ssssi", $nama, $no_hp, $kendaraan, $plat_nomor, $kurir_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Update users table
        $query_user = "UPDATE users SET nama = ?, no_hp = ? WHERE id = ?";
        $stmt_user = mysqli_prepare($conn, $query_user);
        mysqli_stmt_bind_param($stmt_user, "ssi", $nama, $no_hp, $user_id);
        mysqli_stmt_execute($stmt_user);
        
        // Update session
        $_SESSION['nama'] = $nama;
        $_SESSION['kurir_nama'] = $nama;
        $kurir['nama'] = $nama;
        $kurir['no_hp'] = $no_hp;
        $kurir['kendaraan'] = $kendaraan;
        $kurir['plat_nomor'] = $plat_nomor;
        
        $success = "✅ Profil berhasil diperbarui!";
    } else {
        $error = "❌ Gagal memperbarui profil: " . mysqli_error($conn);
    }
}

// Handle update password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $query_user = "SELECT password FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query_user);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $query_update = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query_update);
                mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = "🔐 Password berhasil diubah!";
                } else {
                    $error = "❌ Gagal mengubah password!";
                }
            } else {
                $error = "❌ Password minimal 6 karakter!";
            }
        } else {
            $error = "❌ Password baru tidak cocok!";
        }
    } else {
        $error = "❌ Password saat ini salah!";
    }
}

// Ambil statistik pengiriman
$query_total = "SELECT COUNT(*) as total FROM orders WHERE kurir_id = ?";
$stmt = mysqli_prepare($conn, $query_total);
mysqli_stmt_bind_param($stmt, "i", $kurir_id);
mysqli_stmt_execute($stmt);
$total = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$query_active = "SELECT COUNT(*) as active FROM orders WHERE kurir_id = ? AND status = 'dikirim'";
$stmt = mysqli_prepare($conn, $query_active);
mysqli_stmt_bind_param($stmt, "i", $kurir_id);
mysqli_stmt_execute($stmt);
$active = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$query_completed = "SELECT COUNT(*) as completed FROM orders WHERE kurir_id = ? AND status = 'selesai'";
$stmt = mysqli_prepare($conn, $query_completed);
mysqli_stmt_bind_param($stmt, "i", $kurir_id);
mysqli_stmt_execute($stmt);
$completed = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$page_title = "Profil Kurir";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Kurir - MinShop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== GLOBAL STYLES ===== */
        :root {
            --primary: #4a6cf7;
            --primary-dark: #3a5bd9;
            --primary-light: #eef2ff;
            --secondary: #6c757d;
            --success: #28a745;
            --success-light: #d4edda;
            --warning: #ffc107;
            --warning-light: #fff3cd;
            --danger: #dc3545;
            --danger-light: #f8d7da;
            --info: #17a2b8;
            --info-light: #d1ecf1;
            --dark: #1e2b3a;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --gray-lighter: #f8f9fa;
            --white: #ffffff;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.05);
            --shadow: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
            --radius-sm: 8px;
            --radius: 12px;
            --radius-lg: 16px;
            --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-family);
            background: #f4f7fc;
            color: var(--dark);
            line-height: 1.5;
        }

        /* ===== LAYOUT ===== */
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
            background: #f4f7fc;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            transition: margin-left 0.3s ease;
        }

        /* ===== PROFILE CONTAINER ===== */
        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        /* ===== PROFILE HEADER ===== */
        .profile-header {
            background: linear-gradient(135deg, var(--primary) 0%, #6d8aff 50%, #8a9eff 100%);
            border-radius: var(--radius-lg);
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
            color: white;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            z-index: 0;
        }

        .profile-header::after {
            content: '';
            position: absolute;
            bottom: -50%;
            left: -20%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            z-index: 0;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            color: var(--primary);
            border: 5px solid rgba(255,255,255,0.3);
            box-shadow: var(--shadow-lg);
            position: relative;
            z-index: 1;
        }

        .profile-info {
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .profile-info h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .profile-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 30px;
            font-size: 14px;
            font-weight: 500;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .profile-meta {
            display: flex;
            gap: 30px;
            margin-top: 15px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            opacity: 0.95;
        }

        .meta-item i {
            width: 20px;
            text-align: center;
        }

        /* ===== PROFILE STATS ===== */
        .profile-stats {
            display: flex;
            gap: 25px;
            margin-top: 20px;
        }

        .stat-card {
            background: rgba(255,255,255,0.15);
            padding: 15px 25px;
            border-radius: var(--radius);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.2);
            min-width: 120px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 13px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ===== BADGES ===== */
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-success {
            background: var(--success-light);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .badge-danger {
            background: var(--danger-light);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .badge-warning {
            background: var(--warning-light);
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .badge-info {
            background: var(--info-light);
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        /* ===== TABS ===== */
        .tabs-container {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .tabs {
            display: flex;
            border-bottom: 1px solid var(--gray-light);
            background: var(--gray-lighter);
            padding: 0 10px;
        }

        .tab {
            padding: 16px 24px;
            cursor: pointer;
            font-weight: 600;
            color: var(--gray);
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .tab i {
            font-size: 16px;
        }

        .tab:hover {
            color: var(--primary);
            background: rgba(74,108,247,0.05);
        }

        .tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background: white;
        }

        /* ===== TAB CONTENT ===== */
        .tab-content {
            display: none;
            padding: 30px;
        }

        .tab-content.active {
            display: block;
        }

        .tab-header {
            margin-bottom: 25px;
        }

        .tab-header h2 {
            font-size: 22px;
            color: var(--dark);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tab-header h2 i {
            color: var(--primary);
        }

        .tab-header p {
            color: var(--gray);
            font-size: 14px;
        }

        /* ===== ALERTS ===== */
        .alert {
            padding: 16px 20px;
            border-radius: var(--radius);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: var(--success-light);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: var(--danger-light);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert i {
            font-size: 18px;
        }

        /* ===== FORMS ===== */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 5px;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }

        .form-group label i {
            color: var(--primary);
            width: 18px;
            text-align: center;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--gray-light);
            border-radius: var(--radius);
            font-size: 14px;
            transition: all 0.2s;
            font-family: var(--font-family);
        }

        .form-control:hover {
            border-color: #cbd5e0;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(74,108,247,0.1);
        }

        .form-control[readonly] {
            background: var(--gray-lighter);
            cursor: not-allowed;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236c757d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 40px;
        }

        /* ===== BUTTONS ===== */
        .btn {
            padding: 12px 28px;
            border: none;
            border-radius: var(--radius);
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
            background: white;
            color: var(--dark);
            border: 1px solid var(--gray-light);
        }

        .btn:hover {
            background: var(--gray-lighter);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            box-shadow: 0 6px 16px rgba(74,108,247,0.3);
        }

        .btn-block {
            width: 100%;
        }

        /* ===== INFO CARDS ===== */
        .info-card {
            background: var(--info-light);
            padding: 24px;
            border-radius: var(--radius);
            margin-top: 30px;
            border: 1px solid #bee5eb;
        }

        .info-card h4 {
            color: #0c5460;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
        }

        .info-card h4 i {
            font-size: 20px;
        }

        .info-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .info-card ul li {
            padding: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #0c5460;
            font-size: 14px;
        }

        .info-card ul li i {
            color: #17a2b8;
            width: 18px;
        }

        /* ===== INFO GRID ===== */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-item {
            background: var(--gray-lighter);
            padding: 20px;
            border-radius: var(--radius);
            border: 1px solid var(--gray-light);
            transition: all 0.2s;
        }

        .info-item:hover {
            background: white;
            border-color: var(--primary);
            box-shadow: var(--shadow-sm);
        }

        .info-item label {
            display: block;
            font-size: 12px;
            color: var(--gray);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-item span {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ===== PASSWORD REQUIREMENTS ===== */
        .password-requirements {
            background: var(--gray-lighter);
            padding: 16px 20px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border-left: 4px solid var(--warning);
        }

        .password-requirements p {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .requirements-list {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            list-style: none;
            padding: 0;
        }

        .requirements-list li {
            font-size: 13px;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .requirements-list li i {
            color: var(--warning);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
                padding: 25px;
            }
            
            .profile-stats {
                justify-content: center;
            }
            
            .profile-meta {
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }
            
            .profile-header {
                padding: 20px;
            }
            
            .profile-avatar {
                width: 100px;
                height: 100px;
                font-size: 40px;
            }
            
            .profile-info h1 {
                font-size: 24px;
                flex-direction: column;
                gap: 10px;
            }
            
            .profile-stats {
                flex-wrap: wrap;
                gap: 15px;
            }
            
            .stat-card {
                flex: 1;
                min-width: calc(50% - 15px);
            }
            
            .tabs {
                overflow-x: auto;
                padding: 0 5px;
            }
            
            .tab {
                padding: 14px 16px;
                white-space: nowrap;
            }
            
            .tab-content {
                padding: 20px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }
            
            .profile-header {
                padding: 20px 15px;
            }
            
            .profile-avatar {
                width: 80px;
                height: 80px;
                font-size: 32px;
            }
            
            .profile-meta {
                flex-direction: column;
                gap: 10px;
                align-items: center;
            }
            
            .stat-card {
                min-width: 100%;
            }
            
            .btn {
                width: 100%;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .profile-container {
            animation: fadeIn 0.5s ease;
        }

        /* ===== CUSTOM SCROLLBAR ===== */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--gray-lighter);
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <?php include '../components/sidebar_kurir.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="profile-container">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php 
                        $inisial = '';
                        if (isset($kurir['nama'])) {
                            $kata = explode(' ', $kurir['nama']);
                            foreach ($kata as $k) {
                                if (!empty($k)) $inisial .= strtoupper(substr($k, 0, 1));
                            }
                        }
                        echo $inisial ?: 'K';
                        ?>
                    </div>
                    
                    <div class="profile-info">
                        <h1>
                            <?php echo htmlspecialchars($kurir['nama'] ?? 'Maskurir'); ?>
                            <span class="profile-badge">
                                <i class="fas fa-motorcycle"></i>
                                Kurir
                            </span>
                        </h1>
                        
                        <div class="profile-meta">
                            <div class="meta-item">
                                <i class="fas fa-envelope"></i>
                                <?php echo htmlspecialchars($kurir['email'] ?? '-'); ?>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-phone-alt"></i>
                                <?php echo htmlspecialchars($kurir['no_hp'] ?? $kurir['telepon'] ?? '-'); ?>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-calendar-alt"></i>
                                Bergabung: <?php echo date('d F Y', strtotime($kurir['user_created'] ?? date('Y-m-d'))); ?>
                            </div>
                        </div>
                        
                        <div class="profile-stats">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $total['total'] ?? 0; ?></div>
                                <div class="stat-label">Total Pengiriman</div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $active['active'] ?? 0; ?></div>
                                <div class="stat-label">Sedang Dikirim</div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $completed['completed'] ?? 0; ?></div>
                                <div class="stat-label">Selesai</div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-value">
                                    <span class="badge badge-<?php echo ($kurir['status'] ?? 'aktif') == 'aktif' ? 'success' : 'danger'; ?>">
                                        <?php echo str_replace('_', ' ', $kurir['status'] ?? 'aktif'); ?>
                                    </span>
                                </div>
                                <div class="stat-label">Status</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabs Navigation -->
                <div class="tabs-container">
                    <div class="tabs">
                        <div class="tab active" onclick="showTab('edit-profil', this)">
                            <i class="fas fa-user-edit"></i>
                            Edit Profil
                        </div>
                        <div class="tab" onclick="showTab('ubah-password', this)">
                            <i class="fas fa-lock"></i>
                            Ubah Password
                        </div>
                        <div class="tab" onclick="showTab('info-akun', this)">
                            <i class="fas fa-info-circle"></i>
                            Info Akun
                        </div>
                    </div>
                    
                    <!-- Alert Messages -->
                    <?php if ($success): ?>
                        <div class="alert alert-success" id="successAlert">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error" id="errorAlert">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Tab 1: Edit Profil -->
                    <div id="edit-profil" class="tab-content active">
                        <div class="tab-header">
                            <h2><i class="fas fa-user-edit"></i> Edit Profil</h2>
                            <p>Perbarui informasi pribadi dan kendaraan Anda</p>
                        </div>
                        
                        <form method="POST" action="">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="nama">
                                        <i class="fas fa-user"></i>
                                        Nama Lengkap
                                    </label>
                                    <input type="text" id="nama" name="nama" 
                                           value="<?php echo htmlspecialchars($kurir['nama'] ?? ''); ?>" 
                                           class="form-control" required
                                           placeholder="Masukkan nama lengkap">
                                </div>
                                
                                <div class="form-group">
                                    <label for="no_hp">
                                        <i class="fas fa-phone-alt"></i>
                                        No. HP
                                    </label>
                                    <input type="tel" id="no_hp" name="no_hp" 
                                           value="<?php echo htmlspecialchars($kurir['no_hp'] ?? $kurir['telepon'] ?? ''); ?>" 
                                           class="form-control" required
                                           placeholder="Contoh: 081234567890">
                                </div>
                                
                                <div class="form-group">
                                    <label for="kendaraan">
                                        <i class="fas fa-motorcycle"></i>
                                        Jenis Kendaraan
                                    </label>
                                    <select id="kendaraan" name="kendaraan" class="form-control" required>
                                        <option value="Motor" <?php echo ($kurir['kendaraan'] ?? '') == 'Motor' ? 'selected' : ''; ?>>🏍️ Motor</option>
                                        <option value="Mobil" <?php echo ($kurir['kendaraan'] ?? '') == 'Mobil' ? 'selected' : ''; ?>>🚗 Mobil</option>
                                        <option value="Sepeda" <?php echo ($kurir['kendaraan'] ?? '') == 'Sepeda' ? 'selected' : ''; ?>>🚲 Sepeda</option>
                                        <option value="Lainnya" <?php echo ($kurir['kendaraan'] ?? '') == 'Lainnya' ? 'selected' : ''; ?>>🛵 Lainnya</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="plat_nomor">
                                        <i class="fas fa-id-card"></i>
                                        Plat Nomor
                                    </label>
                                    <input type="text" id="plat_nomor" name="plat_nomor" 
                                           value="<?php echo htmlspecialchars($kurir['plat_nomor'] ?? ''); ?>" 
                                           class="form-control" required
                                           placeholder="Contoh: B 1234 ABC">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Simpan Perubahan
                            </button>
                        </form>
                    </div>
                    
                    <!-- Tab 2: Ubah Password -->
                    <div id="ubah-password" class="tab-content">
                        <div class="tab-header">
                            <h2><i class="fas fa-lock"></i> Ubah Password</h2>
                            <p>Ganti password akun Anda secara berkala untuk keamanan</p>
                        </div>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="update_password" value="1">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="current_password">
                                        <i class="fas fa-key"></i>
                                        Password Saat Ini
                                    </label>
                                    <input type="password" id="current_password" name="current_password" 
                                           class="form-control" required
                                           placeholder="Masukkan password lama">
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">
                                        <i class="fas fa-key"></i>
                                        Password Baru
                                    </label>
                                    <input type="password" id="new_password" name="new_password" 
                                           class="form-control" required minlength="6"
                                           placeholder="Minimal 6 karakter">
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">
                                        <i class="fas fa-key"></i>
                                        Konfirmasi Password Baru
                                    </label>
                                    <input type="password" id="confirm_password" name="confirm_password" 
                                           class="form-control" required minlength="6"
                                           placeholder="Ketik ulang password baru">
                                </div>
                            </div>
                            
                            <div class="password-requirements">
                                <p>
                                    <i class="fas fa-shield-alt"></i>
                                    Tips Password Aman:
                                </p>
                                <ul class="requirements-list">
                                    <li><i class="fas fa-check-circle"></i> Minimal 6 karakter</li>
                                    <li><i class="fas fa-check-circle"></i> Kombinasi huruf dan angka</li>
                                    <li><i class="fas fa-check-circle"></i> Huruf besar & kecil</li>
                                    <li><i class="fas fa-check-circle"></i> Hindari tanggal lahir</li>
                                </ul>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key"></i>
                                Ubah Password
                            </button>
                        </form>
                    </div>
                    
                    <!-- Tab 3: Info Akun -->
                    <div id="info-akun" class="tab-content">
                        <div class="tab-header">
                            <h2><i class="fas fa-info-circle"></i> Informasi Akun</h2>
                            <p>Detail lengkap akun kurir Anda</p>
                        </div>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <label>ID Kurir</label>
                                <span>
                                    <i class="fas fa-id-badge" style="color: var(--primary);"></i>
                                    KUR-<?php echo str_pad($kurir_id ?? 0, 5, '0', STR_PAD_LEFT); ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <label>Email</label>
                                <span>
                                    <i class="fas fa-envelope" style="color: var(--primary);"></i>
                                    <?php echo htmlspecialchars($kurir['email'] ?? '-'); ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <label>No. HP</label>
                                <span>
                                    <i class="fas fa-phone-alt" style="color: var(--primary);"></i>
                                    <?php echo htmlspecialchars($kurir['no_hp'] ?? $kurir['telepon'] ?? '-'); ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <label>Kendaraan</label>
                                <span>
                                    <i class="fas fa-motorcycle" style="color: var(--primary);"></i>
                                    <?php echo htmlspecialchars($kurir['kendaraan'] ?? '-'); ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <label>Plat Nomor</label>
                                <span>
                                    <i class="fas fa-id-card" style="color: var(--primary);"></i>
                                    <?php echo htmlspecialchars($kurir['plat_nomor'] ?? '-'); ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <label>Bergabung Sejak</label>
                                <span>
                                    <i class="fas fa-calendar-alt" style="color: var(--primary);"></i>
                                    <?php echo date('d F Y', strtotime($kurir['user_created'] ?? date('Y-m-d'))); ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <label>Total Pengiriman</label>
                                <span>
                                    <i class="fas fa-box" style="color: var(--primary);"></i>
                                    <?php echo $total['total'] ?? 0; ?> pengiriman
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <label>Status Akun</label>
                                <span>
                                    <span class="badge badge-<?php echo ($kurir['status'] ?? 'aktif') == 'aktif' ? 'success' : 'danger'; ?>">
                                        <?php echo str_replace('_', ' ', $kurir['status'] ?? 'aktif'); ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <h4><i class="fas fa-shield-alt"></i> Tips Keamanan Akun</h4>
                            <ul>
                                <li><i class="fas fa-check"></i> Jangan berikan password kepada siapapun, termasuk admin</li>
                                <li><i class="fas fa-check"></i> Ganti password secara berkala (minimal 3 bulan sekali)</li>
                                <li><i class="fas fa-check"></i> Pastikan Anda logout setelah menggunakan perangkat bersama</li>
                                <li><i class="fas fa-check"></i> Hubungi admin segera jika menemukan aktivitas mencurigakan</li>
                                <li><i class="fas fa-check"></i> Gunakan password yang berbeda untuk setiap akun</li>
                            </ul>
                        </div>
                        
                        <div style="margin-top: 20px; text-align: right;">
                            <small style="color: var(--gray);">
                                <i class="fas fa-clock"></i>
                                Terakhir diperbarui: <?php echo date('d/m/Y H:i', strtotime($kurir['updated_at'] ?? date('Y-m-d H:i:s'))); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Show tab function
        function showTab(tabId, element) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabId).classList.add('active');
            
            // Update tab navigation
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            element.classList.add('active');
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const successAlert = document.getElementById('successAlert');
            const errorAlert = document.getElementById('errorAlert');
            
            if (successAlert) {
                successAlert.style.transition = 'opacity 0.5s';
                successAlert.style.opacity = '0';
                setTimeout(() => successAlert.remove(), 500);
            }
            
            if (errorAlert) {
                errorAlert.style.transition = 'opacity 0.5s';
                errorAlert.style.opacity = '0';
                setTimeout(() => errorAlert.remove(), 500);
            }
        }, 5000);
        
        // Password match validation
        document.querySelector('#ubah-password form')?.addEventListener('submit', function(e) {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            
            if (newPass !== confirmPass) {
                e.preventDefault();
                alert('❌ Password baru dan konfirmasi password tidak cocok!');
            }
        });
        
        // Format nomor telepon
        document.getElementById('no_hp')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // Format plat nomor (uppercase)
        document.getElementById('plat_nomor')?.addEventListener('input', function(e) {
            this.value = this.value.toUpperCase();
        });
    </script>
</body>
</html>
<?php
// Tutup koneksi
if (isset($stmt)) mysqli_stmt_close($stmt);
if (isset($stmt_user)) mysqli_stmt_close($stmt_user);
?>