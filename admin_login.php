<?php
require_once '../components/database.php';
require_once '../components/functions.php';

if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE email = ? AND role = 'admin'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Password salah!';
        }
    } else {
        $error = 'Email admin tidak ditemukan!';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - PetShop Modern</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    .admin-login-page {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .admin-login-container {
        width: 100%;
        max-width: 400px;
    }
    
    .admin-login-card {
        background: white;
        border-radius: var(--radius);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        overflow: hidden;
    }
    
    .admin-login-header {
        background: var(--dark);
        color: white;
        padding: 30px;
        text-align: center;
    }
    
    .admin-login-header h1 {
        font-size: 1.8rem;
        margin-bottom: 10px;
    }
    
    .admin-login-header p {
        opacity: 0.8;
    }
    
    .admin-login-form {
        padding: 30px;
    }
    
    .admin-login-links {
        margin-top: 20px;
        text-align: center;
        font-size: 0.9rem;
    }
    
    .demo-credentials {
        background: #f8f9fa;
        border-radius: var(--radius);
        padding: 15px;
        margin-top: 20px;
        font-size: 0.85rem;
    }
    
    .demo-credentials p {
        margin: 5px 0;
    }
    </style>
</head>
<body class="admin-login-page">
    <div class="admin-login-container">
        <div class="admin-login-card">
            <div class="admin-login-header">
                <h1><i class="fas fa-user-shield"></i> Admin Panel</h1>
                <p>PetShop Modern</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger" style="margin: 20px; border-radius: var(--radius);">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="admin-login-form">
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Admin
                    </label>
                    <input type="email" id="email" name="email" class="form-control" required
                           placeholder="admin@petshop.com">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" class="form-control" required
                           placeholder="Masukkan password">
                </div>
                
                <button type="submit" class="btn-primary" style="width: 100%; padding: 12px;">
                    <i class="fas fa-sign-in-alt"></i> Login sebagai Admin
                </button>
                 
                
                
                <div class="admin-login-links">
                    <a href="../login.php">
                        <i class="fas fa-arrow-left"></i> Kembali ke User Login
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../js/main.js"></script>
</body>
</html>