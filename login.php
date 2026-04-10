<?php
session_start();
require_once 'components/database.php';

// Fungsi untuk membersihkan input
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Gunakan filter untuk sanitize email
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE email = ?";
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
            
            if ($user['role'] == 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: home.php');
            }
            exit();
        } else {
            $error = 'Password salah!';
        }
    } else {
        $error = 'Email tidak ditemukan!';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<style>
    /* ===== VARIABLES ===== */
:root {
    --primary: #4361ee;
    --primary-dark: #3a56d4;
    --secondary: #7209b7;
    --success: #4cc9f0;
    --danger: #f72585;
    --warning: #f8961e;
    --info: #4895ef;
    --light: #f8f9fa;
    --dark: #212529;
    --gray: #6c757d;
    --gray-light: #e9ecef;
    --border-radius: 12px;
    --box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    --box-shadow-hover: 0 15px 40px rgba(0, 0, 0, 0.12);
    --transition: all 0.3s ease;
}

/* ===== RESET & BASE ===== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: var(--dark);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

/* ===== LOGIN PAGE SPECIFIC ===== */
.login-page {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    background-attachment: fixed;
}

.login-container {
    width: 100%;
    max-width: 420px;
    margin: 0 auto;
    padding: 20px;
}

.login-card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
    transition: var(--transition);
    animation: slideUp 0.5s ease;
}

.login-card:hover {
    box-shadow: var(--box-shadow-hover);
}

.login-header {
    text-align: center;
    padding: 40px 30px 30px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    position: relative;
    overflow: hidden;
}

.login-header::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
    background-size: 20px 20px;
    opacity: 0.1;
}

.login-header h1 {
    font-size: 2.2rem;
    margin-bottom: 10px;
    font-weight: 700;
    position: relative;
    z-index: 1;
}

.login-header h1 i {
    margin-right: 10px;
    color: var(--success);
}

.login-header p {
    font-size: 1rem;
    opacity: 0.9;
    position: relative;
    z-index: 1;
}

/* ===== ALERTS ===== */
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin: 20px;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideDown 0.3s ease;
}

.alert-danger {
    background-color: rgba(247, 37, 133, 0.1);
    color: var(--danger);
    border: 1px solid rgba(247, 37, 133, 0.2);
}

.alert-danger::before {
    content: '⚠';
    font-size: 1.2rem;
}

/* ===== FORM ===== */
.login-form {
    padding: 30px;
}

.form-group {
    margin-bottom: 25px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--dark);
    font-size: 0.95rem;
}

.form-label i {
    margin-right: 8px;
    color: var(--primary);
    width: 20px;
    text-align: center;
}

.form-control {
    width: 100%;
    padding: 14px 16px;
    font-size: 1rem;
    border: 2px solid var(--gray-light);
    border-radius: 8px;
    background-color: white;
    transition: var(--transition);
    color: var(--dark);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

.form-control::placeholder {
    color: var(--gray);
    opacity: 0.7;
}

/* ===== BUTTONS ===== */
.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    border: none;
    padding: 16px 30px;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
    transition: var(--transition);
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-top: 10px;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(67, 97, 238, 0.3);
}

.btn-primary:active {
    transform: translateY(0);
}

.btn-login i {
    font-size: 1.2rem;
}

/* ===== LINKS ===== */
.login-links {
    text-align: center;
    margin: 25px 0;
    padding-top: 25px;
    border-top: 1px solid var(--gray-light);
}

.login-links p {
    margin-bottom: 12px;
    font-size: 0.95rem;
    color: var(--gray);
}

.login-links a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
    transition: var(--transition);
}

.login-links a:hover {
    color: var(--secondary);
    text-decoration: underline;
}

/* ===== DIVIDER ===== */
.login-divider {
    text-align: center;
    margin: 30px 0;
    position: relative;
}

.login-divider span {
    background: white;
    padding: 0 20px;
    color: var(--gray);
    font-size: 0.9rem;
    position: relative;
    z-index: 1;
}

.login-divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: var(--gray-light);
}

/* ===== DEMO LOGIN ===== */
.demo-login {
    text-align: center;
    padding: 20px;
    background: linear-gradient(135deg, rgba(67, 97, 238, 0.05) 0%, rgba(114, 9, 183, 0.05) 100%);
    border-radius: 8px;
    margin-top: 20px;
}

.demo-login p {
    margin-bottom: 15px;
    font-size: 0.9rem;
    color: var(--gray);
    background: white;
    padding: 10px;
    border-radius: 6px;
    border: 1px dashed var(--gray-light);
}

.btn-admin-login {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: var(--dark);
    color: white;
    text-decoration: none;
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: 600;
    transition: var(--transition);
    border: 2px solid var(--dark);
}

.btn-admin-login:hover {
    background: white;
    color: var(--dark);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.btn-admin-login i {
    font-size: 1.1rem;
}

/* ===== ANIMATIONS ===== */
@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ===== RESPONSIVE ===== */
@media (max-width: 576px) {
    .login-container {
        padding: 10px;
    }
    
    .login-card {
        margin: 10px;
    }
    
    .login-header {
        padding: 30px 20px 25px;
    }
    
    .login-header h1 {
        font-size: 1.8rem;
    }
    
    .login-form {
        padding: 20px;
    }
    
    .form-control {
        padding: 12px 14px;
    }
    
    .btn-primary {
        padding: 14px 20px;
    }
}

@media (max-width: 400px) {
    .login-header h1 {
        font-size: 1.6rem;
    }
    
    .login-header p {
        font-size: 0.9rem;
    }
    
    .form-label {
        font-size: 0.9rem;
    }
}

/* ===== PASSWORD VISIBILITY TOGGLE ===== */
.password-container {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--gray);
    cursor: pointer;
    font-size: 1.1rem;
    transition: var(--transition);
}

.password-toggle:hover {
    color: var(--primary);
}

/* ===== LOADING STATE ===== */
.btn-primary.loading {
    position: relative;
    color: transparent;
}

.btn-primary.loading::after {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    border: 2px solid white;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* ===== VALIDATION STATES ===== */
.form-control.valid {
    border-color: var(--success);
}

.form-control.invalid {
    border-color: var(--danger);
}

.validation-message {
    font-size: 0.85rem;
    margin-top: 5px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.validation-message.valid {
    color: var(--success);
}

.validation-message.invalid {
    color: var(--danger);
}
</style>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1><i class="fas fa-paw"></i> MinShop</h1>
                <p>Silakan login untuk melanjutkan</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <input type="email" id="email" name="email" class="form-control" required 
                           placeholder="Masukkan email Anda" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" class="form-control" required 
                           placeholder="Masukkan password">
                </div>
                
                <button type="submit" class="btn-primary btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                
                <div class="login-links">
                    <p>Belum punya akun? <a href="register.php">Daftar disini</a></p>
                   
                </div>
                
                
            </form>
        </div>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>