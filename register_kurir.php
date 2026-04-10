<?php
// register_kurir.php
session_start();
require_once '../components/database.php';



$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $telepon = isset($_POST['telepon']) ? $_POST['telepon'] : '';
    $kendaraan = mysqli_real_escape_string($conn, $_POST['kendaraan']);
    $plat_nomor = mysqli_real_escape_string($conn, $_POST['plat_nomor']); 
    
    // Validasi
    if ($password != $confirm_password) {
        $error = "Password tidak cocok!";
    } else {
        // Cek email sudah terdaftar
        $check_email = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $check_email);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "Email sudah terdaftar!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Mulai transaksi
            mysqli_begin_transaction($conn);
            
            try {
                // Insert ke users
                $query_user = "INSERT INTO users (nama, email, password, role, telepon) 
                               VALUES (?, ?, ?, 'kurir', ?)";
                $stmt = mysqli_prepare($conn, $query_user);
                mysqli_stmt_bind_param($stmt, "ssss", $nama, $email, $hashed_password, $telepon);
                mysqli_stmt_execute($stmt);
                $user_id = mysqli_insert_id($conn);
                
                // Insert ke kurir
                $query_kurir = "INSERT INTO kurir (user_id, nama, telepon, kendaraan, plat_nomor, status) 
                                VALUES (?, ?, ?, ?, ?, 'aktif')";
                $stmt = mysqli_prepare($conn, $query_kurir);
                mysqli_stmt_bind_param($stmt, "issss", $user_id, $nama, $telepon, $kendaraan, $plat_nomor);
                mysqli_stmt_execute($stmt);
                
                mysqli_commit($conn);
                $success = "Pendaftaran berhasil! Silakan login.";
                
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = "Pendaftaran gagal: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Kurir - MinShop</title>
    <link rel="stylesheet" href="../css/style.css">
    <!-- Gunakan styling yang sama dengan login_kurir.php -->
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-user-plus"></i> Daftar Kurir</h1>
            <p>Bergabung sebagai mitra pengiriman MinShop</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="nama"><i class="fas fa-user"></i> Nama Lengkap</label>
                <input type="text" id="nama" name="nama" required>
            </div>
            
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-lock"></i> Konfirmasi Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <label for="telepon"><i class="fas fa-phone"></i> No. HP</label>
                <input type="text" id="telepon" name="telepon" required>
            </div>
            
            <div class="form-group">
                <label for="kendaraan"><i class="fas fa-motorcycle"></i> Jenis Kendaraan</label>
                <select id="kendaraan" name="kendaraan" required>
                    <option value="">Pilih Kendaraan</option>
                    <option value="Motor">Motor</option>
                    <option value="Mobil">Mobil</option>
                    <option value="Sepeda">Sepeda</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="plat_nomor"><i class="fas fa-car"></i> Plat Nomor</label>
                <input type="text" id="plat_nomor" name="plat_nomor" required 
                       placeholder="Contoh: B 1234 ABC">
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-user-plus"></i> Daftar sebagai Kurir
            </button>
        </form>
        
        <div class="links">
            <p>Sudah punya akun? <a href="login_kurir.php">Login di sini</a></p>
        </div>
    </div>
</body>
</html>