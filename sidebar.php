<?php
// components/sidebar_admin.php
if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../admin_login.php');
    exit();
}
?>
<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="logo">
            <i class="fas fa-paw"></i>
             <span style="color: #000000; font-weight: 600;">Admin MinShop</span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="produk.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'produk.php' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i>
                    <span>Manajemen Produk</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="pesanan.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pesanan.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Daftar Pesanan</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="pengguna.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pengguna.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Data Pengguna</span>
                </a>
            </li>
            
            </li>
            <li class="nav-item">
                <a href="profil_admin.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-cog"></i>
                    <span>Akun Admin</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <a href="../components/admin_logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>
</aside>