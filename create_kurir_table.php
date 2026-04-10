<?php
// create_kurir_table.php
session_start();
require_once 'components/database.php';

// Hanya admin yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$success = false;
$message = '';

try {
    // Tabel kurir
    $sql_kurir = "CREATE TABLE IF NOT EXISTS kurir (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        nama VARCHAR(100) NOT NULL,
        no_hp VARCHAR(20) NOT NULL,
        kendaraan VARCHAR(50) NOT NULL,
        plat_nomor VARCHAR(20) NOT NULL,
        status ENUM('aktif', 'nonaktif', 'sedang_delivery') DEFAULT 'aktif',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    mysqli_query($conn, $sql_kurir);
    
    // Tambah kolom kurir_id di tabel orders
    $check_column = "SHOW COLUMNS FROM orders LIKE 'kurir_id'";
    $result = mysqli_query($conn, $check_column);
    
    if (mysqli_num_rows($result) == 0) {
        $sql_add_kurir = "ALTER TABLE orders ADD COLUMN kurir_id INT(11) NULL AFTER status";
        mysqli_query($conn, $sql_add_kurir);
        
        $sql_add_foreign = "ALTER TABLE orders ADD FOREIGN KEY (kurir_id) REFERENCES kurir(id) ON DELETE SET NULL";
        mysqli_query($conn, $sql_add_foreign);
    }
    
    // Update enum status di tabel orders untuk kurir
    $sql_update_status = "ALTER TABLE orders MODIFY COLUMN status ENUM(
        'pending', 
        'diproses', 
        'dikirim', 
        'diterima', 
        'dibatalkan',
        'menunggu_kurir'
    ) DEFAULT 'pending'";
    mysqli_query($conn, $sql_update_status);
    
    $success = true;
    $message = "Tabel kurir berhasil dibuat!";
    
} catch (Exception $e) {
    $message = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Kurir</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'components/header_guest.php'; ?>
    
    <div class="container" style="max-width: 800px; margin: 50px auto; padding: 20px;">
        <h1>Setup Tabel Kurir</h1>
        
        <?php if ($message): ?>
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Struktur Tabel yang Dibuat:</h2>
            <ul>
                <li><strong>Tabel kurir</strong> - Menyimpan data kurir</li>
                <li><strong>Kolom kurir_id di tabel orders</strong> - Menghubungkan pesanan dengan kurir</li>
                <li><strong>Update status orders</strong> - Menambah status 'menunggu_kurir'</li>
            </ul>
            
            <p>Role kurir akan memiliki kemampuan:</p>
            <ol>
                <li>Melihat pesanan yang ditugaskan</li>
                <li>Update status pengiriman</li>
                <li>Melacak lokasi pengiriman</li>
                <li>Konfirmasi penerimaan</li>
            </ol>
            
            <a href="admin/dashboard.php" class="btn btn-primary">Kembali ke Dashboard</a>
        </div>
    </div>
</body>
</html>