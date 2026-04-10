<?php
// admin/kurir.php
session_start();
require_once '../components/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit();
}

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = $_GET['id'] ?? 0;
    
    if ($action == 'toggle_status') {
        $new_status = $_GET['status'];
        $query = "UPDATE kurir SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $new_status, $id);
        mysqli_stmt_execute($stmt);
        
        header('Location: kurir.php?success=Status berhasil diupdate');
        exit();
    }
    
    if ($action == 'delete') {
        // Hapus user dan kurir (cascade)
        $query = "DELETE FROM users WHERE id = (SELECT user_id FROM kurir WHERE id = ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        
        header('Location: kurir.php?success=Kurir berhasil dihapus');
        exit();
    }
}

// Ambil data kurir
$query = "SELECT k.*, u.email, u.created_at as user_created 
          FROM kurir k 
          JOIN users u ON k.user_id = u.id 
          ORDER BY k.created_at DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kurir - Admin MinShop</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <?php include 'components/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'components/sidebar.php'; ?>
        
        <main class="main-content">
            <h1><i class="fas fa-shipping-fast"></i> Kelola Kurir</h1>
            
            <div class="card">
                <div class="card-header">
                    <h2>Daftar Kurir</h2>
                    <a href="tambah_kurir.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Kurir
                    </a>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Kendaraan</th>
                                <th>Plat Nomor</th>
                                <th>Status</th>
                                <th>Bergabung</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($kurir = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $kurir['id']; ?></td>
                                <td><?php echo htmlspecialchars($kurir['nama']); ?></td>
                                <td><?php echo $kurir['email']; ?></td>
                                <td><?php echo $kurir['kendaraan']; ?></td>
                                <td><?php echo $kurir['plat_nomor']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $kurir['status']; ?>">
                                        <?php echo str_replace('_', ' ', $kurir['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($kurir['user_created'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_kurir.php?id=<?php echo $kurir['id']; ?>" 
                                           class="btn btn-sm btn-info">
                                           <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <?php if ($kurir['status'] == 'aktif'): ?>
                                        <a href="kurir.php?action=toggle_status&id=<?php echo $kurir['id']; ?>&status=nonaktif" 
                                           class="btn btn-sm btn-warning">
                                           <i class="fas fa-pause"></i>
                                        </a>
                                        <?php else: ?>
                                        <a href="kurir.php?action=toggle_status&id=<?php echo $kurir['id']; ?>&status=aktif" 
                                           class="btn btn-sm btn-success">
                                           <i class="fas fa-play"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <a href="kurir.php?action=delete&id=<?php echo $kurir['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Hapus kurir ini?')">
                                           <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>