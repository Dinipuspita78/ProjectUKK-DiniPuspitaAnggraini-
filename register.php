<?php
require_once 'components/database.php';
require_once 'components/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = clean_input($_POST['nama']);
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $telepon = clean_input($_POST['telepon']);
    
    // Validasi
    if (empty($nama) || empty($email) || empty($password)) {
        $error = 'Semua field wajib diisi!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        // Cek apakah email sudah terdaftar
        $check_email = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
        
        if (mysqli_num_rows($check_email) > 0) {
            $error = 'Email sudah terdaftar!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert ke database
            $query = "INSERT INTO users (nama, email, password, telepon, role) 
                     VALUES (?, ?, ?, ?, 'user')";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssss", $nama, $email, $hashed_password, $telepon);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Registrasi berhasil! Silakan login.';
                header('refresh:2;url=login.php');
            } else {
                $error = 'Terjadi kesalahan. Silakan coba lagi!';
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
    <title>Register</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="register-page">
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h1><i class="fas fa-user-plus"></i> Buat Akun Baru</h1>
                <p>Bergabung dengan MinShop</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
            <?php endif; ?>
            <!-- Tambahkan di dalam .register-card, sebelum .register-form -->
<div class="register-steps">
    <div class="step completed">
        <div class="step-number">
            <i class="fas fa-check"></i>
        </div>
        <span class="step-label">Informasi Dasar</span>
    </div>
    <div class="step active">
        <div class="step-number">2</div>
        <span class="step-label">Data Akun</span>
    </div>
    <div class="step">
        <div class="step-number">3</div>
        <span class="step-label">Verifikasi</span>
    </div>
</div>


</div>


            <form method="POST" action="" class="register-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nama" class="form-label">
                            <i class="fas fa-user"></i> Nama Lengkap
                        </label>
                        <input type="text" id="nama" name="nama" class="form-control" required 
                               placeholder="Masukkan nama lengkap" value="<?php echo $_POST['nama'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <input type="email" id="email" name="email" class="form-control" required 
                               placeholder="Masukkan email" value="<?php echo $_POST['email'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="telepon" class="form-label">
                            <i class="fas fa-phone"></i> Nomor Telepon
                        </label>
                        <input type="tel" id="telepon" name="telepon" class="form-control" 
                               placeholder="Masukkan nomor telepon" value="<?php echo $_POST['telepon'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" id="password" name="password" class="form-control" required 
                               placeholder="Minimal 6 karakter">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-lock"></i> Konfirmasi Password
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required 
                               placeholder="Ulangi password">
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" id="terms" name="terms" required class="form-check-input">
                        <label for="terms" class="form-check-label">
                            Saya setuju dengan <a href="#">Syarat & Ketentuan</a>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary btn-register">
                    <i class="fas fa-user-plus"></i> Daftar Sekarang
                </button>
                
                <div class="register-links">
                    <p>Sudah punya akun? <a href="login.php">Login disini</a></p>
                </div>
            </form>
        </div>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>