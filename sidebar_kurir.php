<?php
// File: components/sidebar_kurir.php
// Sidebar khusus untuk role kurir

// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user adalah kurir
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'kurir') {
    return; // Jangan tampilkan sidebar jika bukan kurir
}

// Pastikan koneksi database tersedia
if (!isset($conn)) {
    require_once 'database.php';
}

// Ambil kurir_id dari session
if (!isset($_SESSION['kurir_id'])) {
    // Jika tidak ada, coba ambil dari database berdasarkan user_id
    $user_id = $_SESSION['user_id'];
    $query = "SELECT id, nama, kendaraan, status FROM kurir WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $_SESSION['kurir_id'] = $row['id'];
        $_SESSION['kurir_nama'] = $row['nama'];
        $_SESSION['kurir_kendaraan'] = $row['kendaraan'];
        $_SESSION['kurir_status'] = $row['status'];
        $kurir_id = $row['id'];
        $kurir = $row;
    }
} else {
    $kurir_id = $_SESSION['kurir_id'];
    
    // Ambil data kurir terbaru
    $query = "SELECT * FROM kurir WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $kurir_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $kurir = mysqli_fetch_assoc($result);
    
    // Update session
    if ($kurir) {
        $_SESSION['kurir_nama'] = $kurir['nama'];
        $_SESSION['kurir_kendaraan'] = $kurir['kendaraan'];
        $_SESSION['kurir_status'] = $kurir['status'];
    }
}

// Ambil statistik untuk badge notifikasi
$stats_notif = "SELECT 
    COUNT(CASE WHEN status = 'menunggu_kurir' THEN 1 END) as menunggu,
    COUNT(CASE WHEN status = 'dikirim' AND kurir_id = ? THEN 1 END) as dikirim
    FROM orders";
$stmt_stats = mysqli_prepare($conn, $stats_notif);
mysqli_stmt_bind_param($stmt_stats, "i", $kurir_id);
mysqli_stmt_execute($stmt_stats);
$result_stats = mysqli_stmt_get_result($stmt_stats);
$notifikasi = mysqli_fetch_assoc($result_stats);

// Halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Sidebar Kurir Styles - Modern Professional */
        :root {
            --sidebar-width: 280px;
            --sidebar-bg: linear-gradient(180deg, #1e2b3a 0%, #15232e 100%);
            --primary-color: #4a6cf7;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --text-light: rgba(255,255,255,0.9);
            --text-lighter: rgba(255,255,255,0.6);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Layout Utama */
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
            background: #f4f7fc;
        }

        .kurir-sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            color: white;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            overflow-x: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0,0,0,0.08);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .kurir-sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .kurir-sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
        }

        .kurir-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
        }

        .kurir-sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Brand / Logo */
        .sidebar-brand {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            width: 45px;
            height: 45px;
            background: var(--primary-color);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: bold;
            color: white;
            box-shadow: 0 4px 12px rgba(74,108,247,0.3);
        }

        .brand-text {
            font-size: 20px;
            font-weight: 700;
            color: white;
            letter-spacing: 0.5px;
        }

        .brand-text span {
            display: block;
            font-size: 11px;
            font-weight: 400;
            color: var(--text-lighter);
            margin-top: 2px;
        }

        /* Profile Card */
        .kurir-profile {
            padding: 20px;
            background: rgba(255,255,255,0.06);
            border-radius: 16px;
            margin: 20px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.08);
        }

        .profile-avatar {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), #6d8aff);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: bold;
            color: white;
            margin: 0 auto 12px;
            border: 3px solid rgba(255,255,255,0.15);
            box-shadow: 0 6px 12px rgba(0,0,0,0.2);
        }

        .profile-info {
            text-align: center;
        }

        .profile-info h4 {
            margin: 0 0 8px;
            font-size: 18px;
            font-weight: 600;
            color: white;
        }

        .profile-info p {
            margin: 5px 0;
            font-size: 13px;
            color: var(--text-lighter);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 500;
            background: rgba(40,167,69,0.15);
            color: #a3e9a4;
            border: 1px solid rgba(40,167,69,0.3);
            margin-top: 8px;
        }

        .status-badge i {
            font-size: 10px;
            margin-right: 6px;
            color: #28a745;
            text-shadow: 0 0 8px #28a745;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        /* Navigation Menu */
        .sidebar-menu {
            padding: 0 15px;
            margin-top: 10px;
        }

        .menu-section {
            margin-bottom: 25px;
        }

        .menu-section-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-lighter);
            padding: 0 12px;
            margin-bottom: 10px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            margin-bottom: 4px;
            position: relative;
        }

        .menu-item i {
            width: 22px;
            font-size: 16px;
            margin-right: 12px;
            text-align: center;
        }

        .menu-item span {
            flex: 1;
            font-size: 14px;
            font-weight: 500;
        }

        .menu-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        .menu-item.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 6px 12px rgba(74,108,247,0.25);
        }

        /* Badge Notifikasi */
        .badge {
            background: var(--danger-color);
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 8px;
            box-shadow: 0 2px 6px rgba(220,53,69,0.3);
            animation: pulse-badge 2s infinite;
        }

        @keyframes pulse-badge {
            0% { box-shadow: 0 0 0 0 rgba(220,53,69,0.4); }
            70% { box-shadow: 0 0 0 6px rgba(220,53,69,0); }
            100% { box-shadow: 0 0 0 0 rgba(220,53,69,0); }
        }

        /* Footer Sidebar */
        .sidebar-footer {
            position: sticky;
            bottom: 0;
            margin-top: 30px;
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.08);
            background: rgba(21,35,46,0.95);
            backdrop-filter: blur(5px);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            background: rgba(220,53,69,0.1);
            color: #ff9e9e;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            border: 1px solid rgba(220,53,69,0.2);
        }

        .logout-btn i {
            width: 22px;
            margin-right: 12px;
        }

        .logout-btn:hover {
            background: var(--danger-color);
            color: white;
            border-color: var(--danger-color);
        }

        .version-text {
            text-align: center;
            margin-top: 15px;
            font-size: 10px;
            color: var(--text-lighter);
            opacity: 0.5;
        }

        /* Mobile Toggle */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 18px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(74,108,247,0.3);
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            backdrop-filter: blur(3px);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 25px;
            transition: margin-left 0.3s ease;
            min-height: 100vh;
            background: #f4f7fc;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .kurir-sidebar {
                left: calc(var(--sidebar-width) * -1);
            }

            .kurir-sidebar.show {
                left: 0;
            }

            .sidebar-toggle {
                display: block;
            }

            .sidebar-overlay.show {
                display: block;
            }

            .main-content {
                margin-left: 0;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }
            
            .profile-avatar {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Toggle Button Mobile -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Overlay Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- SIDEBAR -->
    <div class="kurir-sidebar" id="kurirSidebar">
        <!-- Brand -->
        <div class="sidebar-brand">
            <div class="brand-logo">
                <i class="fas fa-truck-fast"></i>
            </div>
            <div class="brand-text">
                MinShop
                <span>Delivery Partner</span>
            </div>
        </div>

        <!-- Profile -->
        <div class="kurir-profile">
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
                <h4><?php echo htmlspecialchars($kurir['nama'] ?? 'Maskurir'); ?></h4>
                <p><i class="fas fa-motorcycle"></i> <?php echo htmlspecialchars($kurir['kendaraan'] ?? 'Motor'); ?></p>
                <span class="status-badge">
                    <i class="fas fa-circle"></i> 
                    <?php 
                    $status = isset($kurir['status']) ? $kurir['status'] : 'aktif';
                    echo ucwords(str_replace('_', ' ', $status));
                    ?>
                </span>
            </div>
        </div>

        <!-- Navigation Menu -->
        <div class="sidebar-menu">
            <div class="menu-section">
                <div class="menu-section-title">Utama</div>
                <a href="dashboard.php" class="menu-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Manajemen Pesanan</div>
                <a href="../kurir/pesanan.php?status=menunggu_kurir" class="menu-item <?php echo (strpos($current_page, 'pesanan') !== false && isset($_GET['status']) && $_GET['status'] == 'menunggu_kurir') ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i>
                    <span>Menunggu Kurir</span>
                    <?php if (isset($notifikasi['menunggu']) && $notifikasi['menunggu'] > 0): ?>
                        <span class="badge"><?php echo $notifikasi['menunggu']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="../kurir/pesanan.php?status=dikirim" class="menu-item <?php echo (strpos($current_page, 'pesanan') !== false && isset($_GET['status']) && $_GET['status'] == 'dikirim') ? 'active' : ''; ?>">
                    <i class="fas fa-truck"></i>
                    <span>Pengiriman Aktif</span>
                    <?php if (isset($notifikasi['dikirim']) && $notifikasi['dikirim'] > 0): ?>
                        <span class="badge"><?php echo $notifikasi['dikirim']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="pesanan.php?status=selesai" class="menu-item <?php echo (strpos($current_page, 'pesanan') !== false && isset($_GET['status']) && $_GET['status'] == 'selesai') ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i>
                    <span>Selesai</span>
                </a>
                <a href="pesanan.php?status=dibatalkan" class="menu-item <?php echo (strpos($current_page, 'pesanan') !== false && isset($_GET['status']) && $_GET['status'] == 'dibatalkan') ? 'active' : ''; ?>">
                    <i class="fas fa-times-circle"></i>
                    <span>Dibatalkan</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Akun</div>
                <a href="../kurir/profil_kurir.php" class="menu-item <?php echo ($current_page == 'profil_kurir.php') ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span>Profil Saya</span>
                </a>
                <a href="pengaturan_kurir.php" class="menu-item <?php echo ($current_page == 'pengaturan_kurir.php') ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Pengaturan</span>
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="sidebar-footer">
            <a href="login_kurir.php" class="logout-btn" onclick="return confirm('Yakin ingin logout?')">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
            <div class="version-text">
                MinShop Kurir v1.0.0
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('kurirSidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        const overlay = document.getElementById('sidebarOverlay');
        
        // Toggle sidebar
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            });
        }
        
        // Close sidebar when overlay clicked
        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });
        }
        
        // Auto close sidebar on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                sidebar.classList.remove('show');
                if (overlay) overlay.classList.remove('show');
            }
        });
        
        // Set active menu based on URL
        const currentPath = window.location.pathname;
        const menuItems = document.querySelectorAll('.menu-item');
        
        menuItems.forEach(item => {
            const href = item.getAttribute('href');
            if (href && currentPath.includes(href)) {
                item.classList.add('active');
            }
        });
    });
    </script>
</body>
</html>
<?php
// Kembalikan resource database
if (isset($stmt_stats)) mysqli_stmt_close($stmt_stats);
?>