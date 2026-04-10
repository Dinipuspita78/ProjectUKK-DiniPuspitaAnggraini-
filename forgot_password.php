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

$success = '';
$error = '';
$step = isset($_GET['step']) ? $_GET['step'] : 'email';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['request_reset'])) {
        // Step 1: Request reset dengan email
        $email = clean_input($_POST['email']);
        
        if (empty($email)) {
            $error = 'Email harus diisi!';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid!';
        } else {
            // Cek apakah email terdaftar
            $query = "SELECT id, nama FROM users WHERE email = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);
                
                // Generate token reset password
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Simpan token ke database
                $update_query = "UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "ssi", $token, $expires, $user['id']);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    // Simpan data di session untuk step berikutnya
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['reset_token'] = $token;
                    
                    header('Location: forgot_password.php?step=verify&email=' . urlencode($email));
                    exit();
                } else {
                    $error = 'Terjadi kesalahan. Silakan coba lagi!';
                }
            } else {
                $error = 'Email tidak terdaftar!';
            }
        }
    }
    
    elseif (isset($_POST['verify_code'])) {
        // Step 2: Verifikasi kode (simulasi dengan token)
        $input_token = clean_input($_POST['token']);
        $email = $_POST['email'];
        
        // Verifikasi token
        $query = "SELECT reset_token, reset_expires FROM users WHERE email = ? AND reset_token = ? AND reset_expires > NOW()";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $email, $input_token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $_SESSION['verified_email'] = $email;
            header('Location: forgot_password.php?step=reset');
            exit();
        } else {
            $error = 'Kode verifikasi tidak valid atau telah kadaluarsa!';
        }
    }
    
    elseif (isset($_POST['reset_password'])) {
        // Step 3: Reset password baru
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $email = $_SESSION['verified_email'] ?? '';
        
        if (empty($email)) {
            $error = 'Sesi telah berakhir. Silakan mulai kembali!';
        } elseif (empty($new_password) || empty($confirm_password)) {
            $error = 'Semua field harus diisi!';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Password tidak cocok!';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password minimal 6 karakter!';
        } else {
            // Hash password baru
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password dan hapus token
            $update_query = "UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE email = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $email);
            
            if (mysqli_stmt_execute($stmt)) {
                // Hapus session data
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_token']);
                unset($_SESSION['verified_email']);
                
                $success = 'Password berhasil direset!';
                $step = 'success';
            } else {
                $error = 'Terjadi kesalahan. Silakan coba lagi!';
            }
        }
    }
}

// Reset step jika ada parameter di URL
if (isset($_GET['step'])) {
    $step = $_GET['step'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - MinShop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* ===== FORGOT PASSWORD CONTAINER ===== */
        .forgot-container {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
            padding: 20px;
        }

        .forgot-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: var(--transition);
            animation: slideUp 0.5s ease;
        }

        .forgot-card:hover {
            box-shadow: var(--box-shadow-hover);
        }

        /* ===== HEADER ===== */
        .forgot-header {
            text-align: center;
            padding: 40px 30px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .forgot-header::before {
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

        .forgot-header h1 {
            font-size: 2rem;
            margin-bottom: 15px;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .forgot-header h1 i {
            margin-right: 10px;
            color: var(--success);
        }

        .forgot-header p {
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
            line-height: 1.5;
        }

        /* ===== PROGRESS STEPS ===== */
        .progress-steps {
            display: flex;
            justify-content: space-between;
            padding: 25px 30px 10px;
            position: relative;
            background: var(--light);
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 45px;
            left: 50px;
            right: 50px;
            height: 2px;
            background: var(--gray-light);
            z-index: 1;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 10px;
            border: 2px solid white;
            transition: var(--transition);
        }

        .step.active .step-number {
            background: var(--primary);
            color: white;
            transform: scale(1.1);
        }

        .step.completed .step-number {
            background: var(--success);
            color: white;
        }

        .step.completed .step-number i {
            font-size: 0.9rem;
        }

        .step-label {
            font-size: 0.85rem;
            color: var(--gray);
            text-align: center;
            font-weight: 500;
        }

        .step.active .step-label {
            color: var(--primary);
            font-weight: 600;
        }

        /* ===== FORM CONTENT ===== */
        .forgot-content {
            padding: 30px;
        }

        /* ===== ALERTS ===== */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }

        .alert-success {
            background-color: rgba(76, 201, 240, 0.1);
            color: #0c5460;
            border: 1px solid rgba(76, 201, 240, 0.2);
        }

        .alert-danger {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--danger);
            border: 1px solid rgba(247, 37, 133, 0.2);
        }

        /* ===== FORMS ===== */
        .forgot-form {
            animation: fadeIn 0.5s ease;
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
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label i {
            color: var(--primary);
            width: 20px;
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

        /* ===== PASSWORD STRENGTH ===== */
        .password-strength {
            margin-top: 8px;
        }

        .strength-meter {
            height: 4px;
            background: var(--gray-light);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .strength-fill {
            height: 100%;
            width: 0;
            border-radius: 2px;
            transition: width 0.3s ease;
        }

        .strength-text {
            font-size: 0.85rem;
            color: var(--gray);
        }

        .strength-weak { background: var(--danger); }
        .strength-medium { background: var(--warning); }
        .strength-strong { background: var(--success); }

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
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(67, 97, 238, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-secondary {
            background: var(--light);
            color: var(--dark);
            border: 2px solid var(--gray-light);
            padding: 14px 30px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            margin-top: 15px;
        }

        .btn-secondary:hover {
            background: var(--gray-light);
        }

        /* ===== SUCCESS MESSAGE ===== */
        .success-message {
            text-align: center;
            padding: 40px 20px;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: var(--success);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 25px;
            animation: bounce 1s ease;
        }

        .success-message h2 {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 1.8rem;
        }

        .success-message p {
            color: var(--gray);
            margin-bottom: 25px;
            line-height: 1.6;
        }

        /* ===== TOKEN INPUT ===== */
        .token-inputs {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
            margin-bottom: 25px;
        }

        .token-input {
            width: 100%;
            height: 60px;
            font-size: 1.5rem;
            text-align: center;
            border: 2px solid var(--gray-light);
            border-radius: 8px;
            transition: var(--transition);
        }

        .token-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .token-input.filled {
            border-color: var(--success);
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

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-20px);
            }
            60% {
                transform: translateY(-10px);
            }
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 576px) {
            .forgot-container {
                padding: 10px;
            }
            
            .forgot-card {
                margin: 10px;
            }
            
            .forgot-header {
                padding: 30px 20px;
            }
            
            .forgot-header h1 {
                font-size: 1.6rem;
            }
            
            .forgot-content {
                padding: 20px;
            }
            
            .progress-steps {
                padding: 20px 15px 10px;
            }
            
            .progress-steps::before {
                left: 30px;
                right: 30px;
            }
            
            .step-number {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
            }
            
            .step-label {
                font-size: 0.75rem;
            }
            
            .token-inputs {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }
            
            .token-input {
                height: 50px;
                font-size: 1.2rem;
            }
        }

        @media (max-width: 400px) {
            .token-inputs {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* ===== HELP TEXT ===== */
        .help-text {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 8px;
            line-height: 1.4;
        }

        .help-text a {
            color: var(--primary);
            text-decoration: none;
        }

        .help-text a:hover {
            text-decoration: underline;
        }

        /* ===== BACK LINK ===== */
        .back-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-light);
        }

        .back-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .back-link a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }
    </style>
</head>
<body class="forgot-page">
    <div class="forgot-container">
        <div class="forgot-card">
            <!-- Header -->
            <div class="forgot-header">
                <h1><i class="fas fa-key"></i> Lupa Password</h1>
                <p>Atur ulang password Anda dengan mengikuti langkah-langkah berikut</p>
            </div>
            
            <!-- Progress Steps -->
            <div class="progress-steps">
                <div class="step <?php echo in_array($step, ['email', 'verify', 'reset', 'success']) ? 'completed' : ''; ?> <?php echo $step == 'email' ? 'active' : ''; ?>">
                    <div class="step-number">
                        <?php echo $step == 'email' ? '1' : '<i class="fas fa-check"></i>'; ?>
                    </div>
                    <span class="step-label">Masukkan Email</span>
                </div>
                
                <div class="step <?php echo in_array($step, ['verify', 'reset', 'success']) ? 'completed' : ''; ?> <?php echo $step == 'verify' ? 'active' : ''; ?>">
                    <div class="step-number">
                        <?php echo $step == 'verify' ? '2' : (in_array($step, ['reset', 'success']) ? '<i class="fas fa-check"></i>' : '2'); ?>
                    </div>
                    <span class="step-label">Verifikasi</span>
                </div>
                
                <div class="step <?php echo in_array($step, ['reset', 'success']) ? 'completed' : ''; ?> <?php echo $step == 'reset' ? 'active' : ''; ?>">
                    <div class="step-number">
                        <?php echo $step == 'reset' ? '3' : ($step == 'success' ? '<i class="fas fa-check"></i>' : '3'); ?>
                    </div>
                    <span class="step-label">Password Baru</span>
                </div>
            </div>
            
            <!-- Content -->
            <div class="forgot-content">
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
                <?php endif; ?>
                
                <!-- Step 1: Email Input -->
                <?php if ($step == 'email'): ?>
                <div class="forgot-form">
                    <h2 style="color: var(--primary); margin-bottom: 20px; font-size: 1.5rem;">Masukkan Email Anda</h2>
                    <p style="color: var(--gray); margin-bottom: 25px; line-height: 1.6;">
                        Masukkan alamat email yang terdaftar di MinShop. Kami akan mengirimkan kode verifikasi ke email Anda.
                    </p>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> Email
                            </label>
                            <input type="email" id="email" name="email" class="form-control" required 
                                   placeholder="nama@email.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        
                        <button type="submit" name="request_reset" class="btn-primary">
                            <i class="fas fa-paper-plane"></i> Kirim Kode Verifikasi
                        </button>
                    </form>
                    
                    <div class="help-text">
                        <p><i class="fas fa-info-circle"></i> Pastikan email yang Anda masukkan sudah terdaftar di sistem kami.</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Step 2: Verification Code -->
                <?php if ($step == 'verify' && isset($_GET['email'])): 
                    $email = urldecode($_GET['email']);
                ?>
                <div class="forgot-form">
                    <h2 style="color: var(--primary); margin-bottom: 20px; font-size: 1.5rem;">Verifikasi Kode</h2>
                    <p style="color: var(--gray); margin-bottom: 25px; line-height: 1.6;">
                        Kami telah mengirimkan kode verifikasi ke <strong><?php echo htmlspecialchars($email); ?></strong>. 
                        Masukkan kode 6 digit di bawah ini.
                    </p>
                    
                    <form method="POST" action="" id="verifyForm">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-shield-alt"></i> Kode Verifikasi
                            </label>
                            <div class="token-inputs" id="tokenContainer">
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                <input type="text" 
                                       class="token-input" 
                                       maxlength="1" 
                                       data-index="<?php echo $i; ?>" 
                                       oninput="moveToNext(this, <?php echo $i; ?>)" 
                                       onkeydown="handleTokenInput(event, <?php echo $i; ?>)" 
                                       onpaste="handleTokenPaste(event)">
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="token" id="fullToken" required>
                        </div>
                        
                        <button type="submit" name="verify_code" class="btn-primary" id="verifyBtn" disabled>
                            <i class="fas fa-check-circle"></i> Verifikasi
                        </button>
                    </form>
                    
                    <div class="help-text">
                        <p><i class="fas fa-clock"></i> Kode verifikasi berlaku selama 1 jam.</p>
                        <p style="margin-top: 10px;">
                            Tidak menerima kode? 
                            <a href="forgot_password.php?step=email" style="color: var(--primary); text-decoration: none; font-weight: 600;">
                                <i class="fas fa-redo"></i> Kirim ulang
                            </a>
                        </p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Step 3: Reset Password -->
                <?php if ($step == 'reset' && isset($_SESSION['verified_email'])): ?>
                <div class="forgot-form">
                    <h2 style="color: var(--primary); margin-bottom: 20px; font-size: 1.5rem;">Buat Password Baru</h2>
                    <p style="color: var(--gray); margin-bottom: 25px; line-height: 1.6;">
                        Buat password baru untuk akun Anda. Pastikan password kuat dan mudah diingat.
                    </p>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="new_password" class="form-label">
                                <i class="fas fa-lock"></i> Password Baru
                            </label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required 
                                   placeholder="Minimal 6 karakter" minlength="6">
                            <div class="password-strength">
                                <div class="strength-meter">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <div class="strength-text" id="strengthText">Kekuatan password</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock"></i> Konfirmasi Password
                            </label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required 
                                   placeholder="Ulangi password baru">
                            <div class="help-text" id="confirmText"></div>
                        </div>
                        
                        <button type="submit" name="reset_password" class="btn-primary" id="resetBtn">
                            <i class="fas fa-save"></i> Simpan Password Baru
                        </button>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- Success Message -->
                <?php if ($step == 'success'): ?>
                <div class="success-message">
                    <div class="success-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2>Password Berhasil Direset!</h2>
                    <p>Password Anda telah berhasil diubah. Silakan login dengan password baru Anda.</p>
                    <a href="login.php" class="btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Login Sekarang
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Back Link -->
                <?php if ($step != 'success'): ?>
                <div class="back-link">
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i> Kembali ke Login
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    // Step 2: Token Input Handling
    function moveToNext(input, index) {
        const value = input.value;
        if (value.length === 1 && index < 6) {
            const nextInput = document.querySelector(`.token-input[data-index="${index + 1}"]`);
            if (nextInput) nextInput.focus();
        }
        updateToken();
    }

    function handleTokenInput(event, index) {
        if (event.key === 'Backspace' || event.key === 'Delete') {
            if (event.target.value === '' && index > 1) {
                const prevInput = document.querySelector(`.token-input[data-index="${index - 1}"]`);
                if (prevInput) {
                    prevInput.value = '';
                    prevInput.focus();
                }
            }
            event.target.value = '';
            updateToken();
        } else if (event.key === 'ArrowLeft' && index > 1) {
            const prevInput = document.querySelector(`.token-input[data-index="${index - 1}"]`);
            if (prevInput) prevInput.focus();
        } else if (event.key === 'ArrowRight' && index < 6) {
            const nextInput = document.querySelector(`.token-input[data-index="${index + 1}"]`);
            if (nextInput) nextInput.focus();
        }
    }

    function handleTokenPaste(event) {
        event.preventDefault();
        const pasteData = event.clipboardData.getData('text').slice(0, 6);
        const inputs = document.querySelectorAll('.token-input');
        
        pasteData.split('').forEach((char, index) => {
            if (inputs[index]) {
                inputs[index].value = char;
                inputs[index].classList.add('filled');
            }
        });
        
        if (inputs[pasteData.length]) {
            inputs[pasteData.length].focus();
        }
        
        updateToken();
    }

    function updateToken() {
        const inputs = document.querySelectorAll('.token-input');
        let token = '';
        
        inputs.forEach(input => {
            token += input.value;
            if (input.value) {
                input.classList.add('filled');
            } else {
                input.classList.remove('filled');
            }
        });
        
        document.getElementById('fullToken').value = token;
        document.getElementById('verifyBtn').disabled = token.length !== 6;
    }

    // Step 3: Password Strength Checker
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    const confirmText = document.getElementById('confirmText');
    const resetBtn = document.getElementById('resetBtn');

    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', checkPasswordStrength);
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    }

    function checkPasswordStrength() {
        const password = newPasswordInput.value;
        let strength = 0;
        
        // Length check
        if (password.length >= 6) strength += 25;
        if (password.length >= 8) strength += 25;
        
        // Complexity checks
        if (/[A-Z]/.test(password)) strength += 25;
        if (/[0-9]/.test(password)) strength += 25;
        
        // Update UI
        strengthFill.style.width = strength + '%';
        
        if (strength <= 25) {
            strengthFill.className = 'strength-fill strength-weak';
            strengthText.textContent = 'Lemah';
            strengthText.style.color = 'var(--danger)';
        } else if (strength <= 50) {
            strengthFill.className = 'strength-fill strength-medium';
            strengthText.textContent = 'Cukup';
            strengthText.style.color = 'var(--warning)';
        } else if (strength <= 75) {
            strengthFill.className = 'strength-fill strength-medium';
            strengthText.textContent = 'Baik';
            strengthText.style.color = 'var(--warning)';
        } else {
            strengthFill.className = 'strength-fill strength-strong';
            strengthText.textContent = 'Kuat';
            strengthText.style.color = 'var(--success)';
        }
        
        checkPasswordMatch();
    }

    function checkPasswordMatch() {
        const password = newPasswordInput.value;
        const confirm = confirmPasswordInput.value;
        
        if (!confirm) {
            confirmText.textContent = '';
            confirmText.style.color = '';
            return;
        }
        
        if (password === confirm) {
            confirmText.innerHTML = '<i class="fas fa-check-circle"></i> Password cocok';
            confirmText.style.color = 'var(--success)';
            if (resetBtn) resetBtn.disabled = false;
        } else {
            confirmText.innerHTML = '<i class="fas fa-times-circle"></i> Password tidak cocok';
            confirmText.style.color = 'var(--danger)';
            if (resetBtn) resetBtn.disabled = true;
        }
    }

    // Initialize token inputs if they exist
    document.addEventListener('DOMContentLoaded', function() {
        const tokenInputs = document.querySelectorAll('.token-input');
        if (tokenInputs.length > 0) {
            tokenInputs[0].focus();
            updateToken();
        }
        
        // Auto-focus on first input in each step
        const firstInput = document.querySelector('input[type="email"], .token-input[data-index="1"], #new_password');
        if (firstInput) firstInput.focus();
    });

    // Form validation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // Check if button is disabled
            if (submitBtn && submitBtn.disabled) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                submitBtn.disabled = true;
            }
            
            return true;
        });
    });
    </script>
</body>
</html>