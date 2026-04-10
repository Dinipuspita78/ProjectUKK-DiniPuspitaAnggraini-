<?php
// login_kurir.php
session_start();
require_once '../components/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    $query = "SELECT u.*, k.id as kurir_id, k.nama as nama_kurir, k.status as status_kurir 
              FROM users u 
              LEFT JOIN kurir k ON u.id = k.user_id 
              WHERE u.email = ? AND u.role = 'kurir'";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama_kurir'] ?: $user['nama'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['kurir_id'] = $user['kurir_id'];
            $_SESSION['status_kurir'] = $user['status_kurir'];
            
            header('Location:dashboard.php');
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Email tidak terdaftar sebagai kurir!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Kurir - MinShop</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: #4a90e2;
        }
        
        .login-type {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .login-type a {
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 5px;
        }
        
        .login-type .active {
            background: #4a90e2;
            color: white;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .btn-login {
            width: 100%;
            padding: 0.8rem;
            background: #50c878;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-login:hover {
            background: #3cb371;
        }
        
        .error {
            background: #ffe6e6;
            color: #d32f2f;
            padding: 0.8rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .links {
            text-align: center;
            margin-top: 1rem;
        }
        
        .links a {
            color: #4a90e2;
            text-decoration: none;
        }
    </style>
</head>
<body>
    
    
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-shipping-fast"></i> Login Kurir</h1>
            <p>Masuk ke sistem pengiriman MinShop</p>
        </div>
        
        <div class="login-type">
            
            <a href="login_kurir.php" class="active">Kurir</a>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" id="email" name="email" required 
                       placeholder="email@kurir.com">
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Password">
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login sebagai Kurir
            </button>
        </form>
        
        <div class="links">
            <p>Belum mendaftar sebagai kurir? <a href="register_kurir.php">Daftar di sini</a></p>
            <p><a href="login.php">← Kembali ke login pelanggan</a></p>
        </div>
    </div>
</body>
</html>