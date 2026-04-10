<?php
session_start();
require_once 'components/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data order
$order_query = mysqli_query($conn, "SELECT o.*, u.nama, u.email, u.telepon 
                                    FROM orders o 
                                    JOIN users u ON o.user_id = u.id 
                                    WHERE o.id = $order_id AND o.user_id = $user_id");
$order = mysqli_fetch_assoc($order_query);

if (!$order) {
    header('Location: orders.php');
    exit();
}

// Jika status sudah bukan menunggu_pembayaran, redirect ke riwayat
if ($order['status'] != 'menunggu_pembayaran' && $order['status'] != 'menunggu_kurir') {
    header('Location: orders.php');
    exit();
}

// Ambil items order
$items_query = mysqli_query($conn, "SELECT oi.*, p.nama, p.gambar 
                                   FROM order_items oi 
                                   JOIN produk p ON oi.product_id = p.id 
                                   WHERE oi.order_id = $order_id");
$items = [];
while ($item = mysqli_fetch_assoc($items_query)) {
    $items[] = $item;
}

// Process pembayaran E-Wallet dengan PIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_ewallet'])) {
    $ewallet_type = mysqli_real_escape_string($conn, $_POST['ewallet_selected']);
    $ewallet_pin = mysqli_real_escape_string($conn, $_POST['ewallet_pin']);
    
    // Validasi input
    $errors = [];
    
    if (empty($ewallet_type)) {
        $errors[] = "Pilih metode E-Wallet";
    }
    
    if (empty($ewallet_pin)) {
        $errors[] = "PIN E-Wallet harus diisi";
    } elseif (!preg_match('/^[0-9]{6}$/', $ewallet_pin)) {
        $errors[] = "PIN harus 6 digit angka";
    }
    
    // Simulasi validasi PIN E-Wallet (untuk demo, PIN yang valid adalah 123456)
    if ($ewallet_pin != '123456') {
        $errors[] = "PIN E-Wallet yang Anda masukkan salah";
    }
    
    if (empty($errors)) {
        // Generate nomor referensi pembayaran
        $reference_number = strtoupper(substr($ewallet_type, 0, 2)) . date('Ymd') . rand(1000, 9999);
        
        // Update status order
        $update_query = "UPDATE orders SET 
                        status = 'menunggu_verifikasi',
                        payment_method_detail = '$ewallet_type',
                        payment_reference = '$reference_number',
                        payment_time = NOW(),
                        updated_at = NOW() 
                        WHERE id = $order_id";
        
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['payment_success'] = true;
            $_SESSION['payment_message'] = "Pembayaran via E-Wallet berhasil diproses!";
            $_SESSION['payment_method'] = 'e-wallet';
            $_SESSION['payment_type'] = $ewallet_type;
            $_SESSION['payment_reference'] = $reference_number;
            $_SESSION['payment_time'] = date('Y-m-d H:i:s');
            $_SESSION['order_id'] = $order_id;
            header('Location: payment_success.php?id=' . $order_id);
            exit();
        } else {
            $error = "Gagal memproses pembayaran: " . mysqli_error($conn);
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Process pembayaran Transfer Bank (langsung tanpa upload bukti)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_transfer'])) {
    $bank_name = mysqli_real_escape_string($conn, $_POST['bank_name']);
    
    // Generate nomor referensi pembayaran
    $reference_number = 'TRF' . date('Ymd') . rand(1000, 9999);
    
    // Update order dengan status sukses
    $update_query = "UPDATE orders SET 
                    status = 'diproses',
                    payment_method_detail = '$bank_name',
                    payment_reference = '$reference_number',
                    payment_time = NOW(),
                    updated_at = NOW() 
                    WHERE id = $order_id";
    
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['payment_success'] = true;
        $_SESSION['payment_message'] = "Pembayaran via Transfer Bank berhasil!";
        $_SESSION['payment_method'] = 'transfer';
        $_SESSION['payment_bank'] = $bank_name;
        $_SESSION['payment_reference'] = $reference_number;
        $_SESSION['payment_time'] = date('Y-m-d H:i:s');
        $_SESSION['order_id'] = $order_id;
        header('Location: payment_success.php?id=' . $order_id);
        exit();
    } else {
        $error = "Gagal memproses pembayaran: " . mysqli_error($conn);
    }
}

// Process upload bukti pembayaran Transfer Bank
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_transfer_proof']) && isset($_FILES['transfer_payment_proof'])) {
    $target_dir = "uploads/bukti_pembayaran/";
    
    // Buat folder jika belum ada
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = time() . '_transfer_' . basename($_FILES['transfer_payment_proof']['name']);
    $target_file = $target_dir . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Validasi file
    $errors = [];
    
    // Cek apakah file gambar
    $check = getimagesize($_FILES['transfer_payment_proof']['tmp_name']);
    if ($check === false) {
        $errors[] = "File bukan gambar";
    }
    
    // Cek ukuran file (max 2MB)
    if ($_FILES['transfer_payment_proof']['size'] > 2 * 1024 * 1024) {
        $errors[] = "Ukuran file maksimal 2MB";
    }
    
    // Izinkan format tertentu
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        $errors[] = "Hanya file JPG, JPEG, PNG & GIF yang diizinkan";
    }
    
    if (empty($errors)) {
        if (move_uploaded_file($_FILES['transfer_payment_proof']['tmp_name'], $target_file)) {
            // Update order dengan bukti pembayaran
            $bank_name = mysqli_real_escape_string($conn, $_POST['bank_name_upload'] ?? 'BCA');
            $update_query = "UPDATE orders SET 
                            bukti_pembayaran = '$file_name',
                            payment_method_detail = '$bank_name',
                            status = 'menunggu_verifikasi',
                            updated_at = NOW() 
                            WHERE id = $order_id";
            
            if (mysqli_query($conn, $update_query)) {
                $_SESSION['payment_success'] = true;
                $_SESSION['payment_message'] = "Bukti transfer berhasil diupload! Menunggu verifikasi.";
                header('Location: orders.php?status=payment_success');
                exit();
            } else {
                $error = "Gagal mengupdate database: " . mysqli_error($conn);
            }
        } else {
            $error = "Gagal mengupload file";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Process upload bukti pembayaran E-Wallet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_ewallet_proof']) && isset($_FILES['ewallet_payment_proof'])) {
    $target_dir = "uploads/bukti_pembayaran/";
    
    // Buat folder jika belum ada
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = time() . '_ewallet_' . basename($_FILES['ewallet_payment_proof']['name']);
    $target_file = $target_dir . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Validasi file
    $errors = [];
    
    // Cek apakah file gambar
    $check = getimagesize($_FILES['ewallet_payment_proof']['tmp_name']);
    if ($check === false) {
        $errors[] = "File bukan gambar";
    }
    
    // Cek ukuran file (max 2MB)
    if ($_FILES['ewallet_payment_proof']['size'] > 2 * 1024 * 1024) {
        $errors[] = "Ukuran file maksimal 2MB";
    }
    
    // Izinkan format tertentu
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        $errors[] = "Hanya file JPG, JPEG, PNG & GIF yang diizinkan";
    }
    
    if (empty($errors)) {
        if (move_uploaded_file($_FILES['ewallet_payment_proof']['tmp_name'], $target_file)) {
            // Update order dengan bukti pembayaran
            $ewallet_type = mysqli_real_escape_string($conn, $_POST['ewallet_type_upload'] ?? 'gopay');
            $update_query = "UPDATE orders SET 
                            bukti_pembayaran = '$file_name',
                            payment_method_detail = '$ewallet_type',
                            status = 'menunggu_verifikasi',
                            updated_at = NOW() 
                            WHERE id = $order_id";
            
            if (mysqli_query($conn, $update_query)) {
                $_SESSION['payment_success'] = true;
                $_SESSION['payment_message'] = "Bukti transfer berhasil diupload! Menunggu verifikasi.";
                header('Location: orders.php?status=payment_success');
                exit();
            } else {
                $error = "Gagal mengupdate database: " . mysqli_error($conn);
            }
        } else {
            $error = "Gagal mengupload file";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

$page_title = "Pembayaran Pesanan";
include 'components/header_pengguna.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Pesanan - MinShop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4a6cf7;
            --primary-dark: #3a5bd9;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --radius: 8px;
        }
        
        body {
            background-color: #f5f7fb;
        }
        
        .payment-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .payment-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .payment-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .payment-header h1 {
            margin: 0;
            font-size: 1.8rem;
        }
        
        .payment-header p {
            margin: 0.5rem 0 0;
            opacity: 0.9;
        }
        
        .order-info {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
        }
        
        .order-number {
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .order-number span {
            color: var(--primary);
        }
        
        .payment-section {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .payment-section h3 {
            color: var(--primary);
            margin-top: 0;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary);
        }
        
        .payment-details {
            background-color: #f0f7ff;
            padding: 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
        }
        
        .payment-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px dashed #ddd;
        }
        
        .payment-row:last-child {
            border-bottom: none;
        }
        
        .payment-row.total {
            font-weight: bold;
            font-size: 1.1rem;
            color: var(--primary);
        }
        
        .bank-account {
            background: white;
            padding: 1rem;
            border-radius: var(--radius);
            margin: 1rem 0;
            border: 1px solid #ddd;
        }
        
        .bank-account p {
            margin: 0.3rem 0;
        }
        
        .copy-button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.3rem 0.8rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }
        
        .copy-button:hover {
            background: var(--primary-dark);
        }
        
        .countdown-timer {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: var(--radius);
            margin: 1rem 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .timer {
            font-weight: bold;
            font-size: 1.2rem;
            font-family: monospace;
        }
        
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: var(--radius);
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin: 1rem 0;
        }
        
        .upload-area:hover {
            border-color: var(--primary);
            background-color: #f0f7ff;
        }
        
        .upload-area i {
            font-size: 3rem;
            color: #999;
            margin-bottom: 1rem;
        }
        
        .file-preview {
            display: none;
            margin: 1rem 0;
            padding: 1rem;
            background: #f5f5f5;
            border-radius: var(--radius);
            text-align: center;
        }
        
        .file-preview img {
            max-width: 200px;
            max-height: 200px;
            border-radius: var(--radius);
            margin-bottom: 0.5rem;
        }
        
        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
            width: 100%;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: var(--success);
            color: white;
            width: 100%;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            text-decoration: none;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .btn-warning {
            background: #ff9800;
            color: white;
        }
        
        .btn-warning:hover {
            background: #e68900;
        }
        
        .alert {
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }
        
        .alert-info {
            background: #e3f2fd;
            color: #0d47a1;
            border-left: 4px solid #0d47a1;
        }
        
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            display: none;
        }
        
        .loading.active {
            display: flex;
        }
        
        .loading-content {
            background: white;
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .loading i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .action-buttons a {
            flex: 1;
            text-align: center;
            padding: 0.8rem;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 500;
        }
        
        .ewallet-option {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: white;
            border-radius: var(--radius);
            margin: 0.5rem 0;
            border: 1px solid #ddd;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .ewallet-option:hover {
            border-color: var(--primary);
            background-color: #f0f7ff;
        }
        
        .ewallet-option.selected {
            border-color: var(--primary);
            background-color: #e6f2ff;
        }
        
        .ewallet-option input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .ewallet-icon {
            font-size: 2rem;
            color: var(--primary);
        }
        
        .ewallet-info {
            flex: 1;
        }
        
        .ewallet-info h4 {
            margin: 0 0 0.2rem;
        }
        
        .ewallet-info p {
            margin: 0;
            font-size: 0.9rem;
            color: #666;
        }
        
        .bank-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .bank-item {
            border: 2px solid #ddd;
            border-radius: var(--radius);
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .bank-item:hover {
            border-color: var(--primary);
            background-color: #f0f7ff;
        }
        
        .bank-item.selected {
            border-color: var(--primary);
            background-color: #e6f2ff;
        }
        
        .bank-item input[type="radio"] {
            display: none;
        }
        
        .bank-item i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .bank-item h4 {
            margin: 0;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: var(--radius);
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 108, 247, 0.1);
        }
        
        .input-group {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: var(--radius);
            overflow: hidden;
        }
        
        .input-group .form-control {
            border: none;
            flex: 1;
        }
        
        .input-group-text {
            background: #f0f0f0;
            padding: 0.8rem 1rem;
            color: #666;
            font-weight: 500;
        }
        
        .payment-summary {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: var(--radius);
            margin: 1rem 0;
        }
        
        .payment-summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .payment-summary-item:last-child {
            border-bottom: none;
        }
        
        .payment-summary-total {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0 0;
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--primary);
            border-top: 2px solid var(--primary);
            margin-top: 0.5rem;
        }
        
        .pin-input {
            font-family: monospace;
            font-size: 1.2rem;
            letter-spacing: 0.5rem;
            text-align: center;
        }
        
        .info-box {
            background: #e3f2fd;
            padding: 1rem;
            border-radius: var(--radius);
            margin: 1rem 0;
            font-size: 0.9rem;
        }
        
        .info-box i {
            color: var(--primary);
            margin-right: 0.5rem;
        }
        
        .nav-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #eee;
            padding-bottom: 0.5rem;
        }
        
        .nav-tab {
            padding: 0.8rem 1.5rem;
            border-radius: var(--radius) var(--radius) 0 0;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            color: #666;
        }
        
        .nav-tab:hover {
            color: var(--primary);
        }
        
        .nav-tab.active {
            color: var(--primary);
            border-bottom: 3px solid var(--primary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* E-Wallet PIN Section */
        .ewallet-pin-section {
            margin-top: 1.5rem;
            padding: 1.5rem;
            background: white;
            border-radius: var(--radius);
            border: 1px solid #ddd;
        }
        
        .ewallet-pin-section h5 {
            margin-top: 0;
            margin-bottom: 1rem;
            color: var(--primary);
        }
        
        .pin-note {
            background: #fff3cd;
            color: #856404;
            padding: 0.8rem;
            border-radius: var(--radius);
            margin: 1rem 0;
            font-size: 0.9rem;
        }
        
        .upload-section {
            background: #f0f7ff;
            padding: 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
        }
        
        .upload-section h5 {
            margin-top: 0;
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="loading" id="loading">
        <div class="loading-content">
            <i class="fas fa-spinner fa-spin"></i>
            <h3>Memproses Pembayaran...</h3>
            <p>Mohon tunggu sebentar</p>
        </div>
    </div>

    <div class="payment-container">
        <!-- Payment Card -->
        <div class="payment-card">
            <div class="payment-header">
                <h1><i class="fas fa-credit-card"></i> Selesaikan Pembayaran</h1>
                <p>Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></p>
            </div>

            <div class="order-info">
                <div class="order-number">
                    Total Pembayaran: <span>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
                </div>
                <div style="padding: 0.3rem 1rem; background: var(--warning); border-radius: 20px; font-size: 0.9rem;">
                    <i class="fas fa-clock"></i> Menunggu Pembayaran
                </div>
            </div>

            <?php if (isset($error)): ?>
            <div class="alert alert-error" style="margin: 1rem;">
                <i class="fas fa-exclamation-triangle"></i>
                <div><?php echo $error; ?></div>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['payment_message']) && !isset($_GET['status'])): ?>
            <div class="alert alert-success" style="margin: 1rem;">
                <i class="fas fa-check-circle"></i>
                <div><?php echo $_SESSION['payment_message']; unset($_SESSION['payment_message']); ?></div>
            </div>
            <?php endif; ?>

            <!-- Navigation Tabs -->
            <div class="nav-tabs" style="padding: 0 1.5rem;">
                <div class="nav-tab active" onclick="showTab('transfer')" id="tabTransfer">
                    <i class="fas fa-university"></i> Transfer Bank
                </div>
                <div class="nav-tab" onclick="showTab('ewallet')" id="tabEwallet">
                    <i class="fas fa-mobile-alt"></i> E-Wallet
                </div>
                <div class="nav-tab" onclick="showTab('cod')" id="tabCod">
                    <i class="fas fa-money-bill-wave"></i> COD
                </div>
            </div>

            <!-- Transfer Bank Tab (Active by default) -->
            <div id="transferTab" class="tab-content active">
                <div class="payment-section">
                    <h3><i class="fas fa-university"></i> Transfer Bank</h3>
                    
                    <!-- Informasi Rekening Tujuan -->
                    <div class="payment-details">
                        <h4 style="margin-top: 0; margin-bottom: 1rem;">Rekening Tujuan</h4>
                        
                        <div class="bank-account">
                            <p><strong>Bank BCA</strong></p>
                            <p>No. Rekening: <strong>1234567890</strong> 
                                <button class="copy-button" onclick="copyToClipboard('1234567890')">
                                    <i class="fas fa-copy"></i> Salin
                                </button>
                            </p>
                            <p>Atas Nama: <strong>MinShop</strong></p>
                        </div>
                        
                        <div class="bank-account">
                            <p><strong>Bank Mandiri</strong></p>
                            <p>No. Rekening: <strong>1234567890</strong> 
                                <button class="copy-button" onclick="copyToClipboard('1234567890')">
                                    <i class="fas fa-copy"></i> Salin
                                </button>
                            </p>
                            <p>Atas Nama: <strong>MinShop</strong></p>
                        </div>
                        
                        <div class="bank-account">
                            <p><strong>Bank BNI</strong></p>
                            <p>No. Rekening: <strong>1234567890</strong> 
                                <button class="copy-button" onclick="copyToClipboard('1234567890')">
                                    <i class="fas fa-copy"></i> Salin
                                </button>
                            </p>
                            <p>Atas Nama: <strong>MinShop</strong></p>
                        </div>
                        
                        <div class="payment-row">
                            <span>Jumlah yang harus ditransfer:</span>
                            <strong>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong>
                        </div>
                        
                        <div class="countdown-timer">
                            <i class="fas fa-hourglass-half"></i>
                            <span>Batas pembayaran: </span>
                            <span class="timer" id="countdown">24:00:00</span>
                        </div>
                    </div>

                    <!-- Form Pembayaran Transfer -->
                    <form method="POST" id="transferForm">
                        <h4 style="margin-bottom: 1rem;">Konfirmasi Pembayaran</h4>
                        
                        <div class="info-box">
                            <i class="fas fa-info-circle"></i>
                            Pilih bank tujuan untuk konfirmasi pembayaran
                        </div>
                        
                        <!-- Pilih Bank -->
                        <div class="form-group">
                            <label>Pilih Bank Tujuan <span style="color: red;">*</span></label>
                            <div class="bank-selector">
                                <div class="bank-item selected" onclick="selectBank('bca')">
                                    <input type="radio" name="bank_name" value="BCA" id="bankBCA" checked hidden>
                                    <i class="fas fa-university"></i>
                                    <h4>BCA</h4>
                                </div>
                                <div class="bank-item" onclick="selectBank('mandiri')">
                                    <input type="radio" name="bank_name" value="Mandiri" id="bankMandiri" hidden>
                                    <i class="fas fa-university"></i>
                                    <h4>Mandiri</h4>
                                </div>
                                <div class="bank-item" onclick="selectBank('bni')">
                                    <input type="radio" name="bank_name" value="BNI" id="bankBNI" hidden>
                                    <i class="fas fa-university"></i>
                                    <h4>BNI</h4>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ringkasan Pembayaran -->
                        <div class="payment-summary">
                            <div class="payment-summary-item">
                                <span>Total Pembayaran</span>
                                <span>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="payment-summary-item">
                                <span>Biaya Transfer</span>
                                <span>Rp 0</span>
                            </div>
                            <div class="payment-summary-total">
                                <span>Total Dibayar</span>
                                <span>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
                            </div>
                        </div>
                        
                        <input type="hidden" name="pay_transfer" value="1">
                        
                        <!-- Opsi Upload Bukti Transfer (di atas Bayar Sekarang) -->
                        <div style="margin-bottom: 1.5rem;">
                            <button type="button" class="btn btn-secondary" onclick="toggleTransferUpload()" style="width: 100%;">
                                <i class="fas fa-upload"></i> Upload Bukti Transfer
                            </button>
                        </div>
                        
                        <!-- Upload Bukti Transfer (Hidden by default) -->
                        <div id="transferUploadSection" style="display: none; margin-bottom: 1.5rem;">
                            <div class="upload-section">
                                <h5><i class="fas fa-upload"></i> Upload Bukti Transfer</h5>
                                
                                <div class="upload-area" id="transferUploadArea">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Klik untuk upload bukti transfer</p>
                                    <p style="font-size: 0.8rem;">Format: JPG, PNG (Maks. 2MB)</p>
                                    <input type="file" name="transfer_payment_proof" id="transferPaymentProof" accept="image/jpeg,image/png" style="display: none;">
                                </div>
                                
                                <div class="file-preview" id="transferFilePreview" style="display: none;">
                                    <img src="" alt="Preview" id="transferPreviewImage" style="max-width: 100%; max-height: 200px;">
                                    <p style="margin: 0.5rem 0 0; color: var(--success);">
                                        <i class="fas fa-check-circle"></i> File siap diupload
                                    </p>
                                </div>
                                
                                <input type="hidden" name="bank_name_upload" id="bankNameUpload" value="BCA">
                                
                                <button type="submit" name="upload_transfer_proof" class="btn btn-primary" style="margin-top: 1rem; width: 100%;" onclick="return confirmTransferUpload()">
                                    <i class="fas fa-upload"></i> Konfirmasi Upload Bukti Transfer
                                </button>
                            </div>
                        </div>
                        
                        <!-- Bayar Sekarang Button -->
                        <button type="submit" class="btn btn-success" onclick="return confirmTransfer()">
                            <i class="fas fa-check-circle"></i> Bayar Sekarang
                        </button>
                    </form>
                </div>
            </div>

            <!-- E-Wallet Tab -->
            <div id="ewalletTab" class="tab-content">
                <div class="payment-section">
                    <h3><i class="fas fa-mobile-alt"></i> E-Wallet</h3>
                    
                    <div class="payment-details">
                        <h4 style="margin-top: 0;">Pembayaran via E-Wallet</h4>
                        
                        <div class="alert alert-info" style="margin-bottom: 1.5rem;">
                            <i class="fas fa-info-circle"></i>
                            <div>Silakan pilih metode E-Wallet dan lakukan pembayaran</div>
                        </div>
                        
                        <!-- Form untuk pembayaran E-Wallet dengan PIN -->
                        <form method="POST" id="ewalletForm" enctype="multipart/form-data">
                            <!-- Pilihan E-Wallet -->
                            <div style="margin: 1.5rem 0;">
                                <div class="ewallet-option selected" onclick="selectEwallet('gopay')">
                                    <input type="radio" name="ewallet_type" value="gopay" id="gopay" checked hidden>
                                    <i class="fab fa-google-pay ewallet-icon"></i>
                                    <div class="ewallet-info">
                                        <h4>GoPay</h4>
                                        <p>Nomor: 081931194443</p>
                                    </div>
                                </div>
                                
                                <div class="ewallet-option" onclick="selectEwallet('ovo')">
                                    <input type="radio" name="ewallet_type" value="ovo" id="ovo" hidden>
                                    <i class="fas fa-mobile-alt ewallet-icon"></i>
                                    <div class="ewallet-info">
                                        <h4>OVO</h4>
                                        <p>Nomor: 081931194443</p>
                                    </div>
                                </div>
                                
                                <div class="ewallet-option" onclick="selectEwallet('dana')">
                                    <input type="radio" name="ewallet_type" value="dana" id="dana" hidden>
                                    <i class="fas fa-wallet ewallet-icon"></i>
                                    <div class="ewallet-info">
                                        <h4>Dana</h4>
                                        <p>Nomor: 081931194443</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bank-account">
                                <p><strong>Nomor Tujuan:</strong> 081931194443 
                                    <button type="button" class="copy-button" onclick="copyToClipboard('081931194443')">
                                        <i class="fas fa-copy"></i> Salin
                                    </button>
                                </p>
                            </div>
                            
                            <div class="payment-row">
                                <span>Jumlah yang harus dibayar:</span>
                                <strong>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong>
                            </div>
                            
                            <div class="countdown-timer">
                                <i class="fas fa-clock"></i>
                                <span>Batas pembayaran: </span>
                                <span class="timer" id="ewalletCountdown">15:00</span>
                            </div>
                            
                            <!-- PIN Section -->
                            
                            <!-- Opsi Upload Bukti Transfer untuk E-Wallet (di atas Bayar Sekarang) -->
                            <div style="margin-bottom: 1.5rem;">
                                <button type="button" class="btn btn-secondary" onclick="toggleEwalletUpload()" style="width: 100%;">
                                    <i class="fas fa-upload"></i> Upload Bukti Transfer
                                </button>
                            </div>
                            
                            <!-- Upload Bukti Transfer E-Wallet (Hidden by default) -->
                            <div id="ewalletUploadSection" style="display: none; margin-bottom: 1.5rem;">
                                <div class="upload-section">
                                    <h5><i class="fas fa-upload"></i> Upload Bukti Transfer E-Wallet</h5>
                                    
                                    <div class="upload-area" id="ewalletUploadArea">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Klik untuk upload bukti transfer</p>
                                        <p style="font-size: 0.8rem;">Format: JPG, PNG (Maks. 2MB)</p>
                                        <input type="file" name="ewallet_payment_proof" id="ewalletPaymentProof" accept="image/jpeg,image/png" style="display: none;">
                                    </div>
                                    
                                    <div class="file-preview" id="ewalletFilePreview" style="display: none;">
                                        <img src="" alt="Preview" id="ewalletPreviewImage" style="max-width: 100%; max-height: 200px;">
                                        <p style="margin: 0.5rem 0 0; color: var(--success);">
                                            <i class="fas fa-check-circle"></i> File siap diupload
                                        </p>
                                    </div>
                                    
                                    <input type="hidden" name="ewallet_type_upload" id="ewalletTypeUpload" value="gopay">
                                    
                                    <button type="submit" name="upload_ewallet_proof" class="btn btn-primary" style="margin-top: 1rem; width: 100%;" onclick="return confirmEwalletUpload()">
                                        <i class="fas fa-upload"></i> Konfirmasi Upload Bukti Transfer
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Hidden inputs -->
                            <input type="hidden" name="ewallet_selected" id="ewalletSelected" value="gopay">
                            <input type="hidden" name="pay_ewallet" value="1">
                            
                            <!-- Bayar Sekarang Button -->
                            <button type="submit" class="btn btn-success" onclick="return confirmEwalletWithPin()" style="margin-top: 0;">
                                <i class="fas fa-check-circle"></i> Bayar dengan E-Wallet
                            </button>
                        </form>
                        
                        <p style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: #666;">
                            <i class="fas fa-lock"></i> Pembayaran aman dan terenkripsi
                        </p>
                    </div>
                </div>
            </div>

            <!-- COD Tab -->
            <div id="codTab" class="tab-content">
                <div class="payment-section">
                    <h3><i class="fas fa-money-bill-wave"></i> Cash on Delivery (COD)</h3>
                    
                    <div class="payment-details">
                        <h4 style="margin-top: 0;">Instruksi COD</h4>
                        
                        <div class="payment-row">
                            <span>Total yang harus dibayar:</span>
                            <strong>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong>
                        </div>
                        
                        <div class="payment-row">
                            <span>Alamat Pengiriman:</span>
                            <span><?php echo htmlspecialchars($order['shipping_address']); ?></span>
                        </div>
                        
                        <div style="background: #e8f5e9; padding: 1rem; border-radius: var(--radius); margin: 1rem 0;">
                            <p style="margin: 0;"><i class="fas fa-info-circle"></i> Instruksi COD:</p>
                            <ol style="margin: 0.5rem 0 0; padding-left: 1.5rem;">
                                <li>Bayar langsung saat kurir mengantarkan pesanan</li>
                                <li>Siapkan uang pas dengan total Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></li>
                                <li>Periksa pesanan sebelum membayar</li>
                            </ol>
                        </div>
                        
                        <a href="orders.php" class="btn btn-primary" style="text-decoration: none; display: block; text-align: center;">
                            <i class="fas fa-list"></i> Lihat Riwayat Pesanan
                        </a>
                    </div>
                </div>
            </div>

            <div class="action-buttons" style="padding: 0 1.5rem 1.5rem;">
                <a href="orders.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Riwayat
                </a>
            </div>
        </div>

        <!-- Informasi Keamanan -->
        <div style="text-align: center; margin-top: 1rem; padding: 1rem; background: white; border-radius: var(--radius); box-shadow: 0 3px 10px rgba(0,0,0,0.05);">
            <p style="margin: 0; color: #666;">
                <i class="fas fa-shield-alt" style="color: var(--success);"></i> 
                Transaksi Anda aman dan terenkripsi
            </p>
            <p style="margin: 0.5rem 0 0; color: #999; font-size: 0.8rem;">
                <i class="fas fa-clock"></i> Layanan customer service 24/7
            </p>
        </div>
    </div>

    <!-- Modal Success (for demo) -->
    <div id="successModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: white; max-width: 500px; width: 90%; border-radius: var(--radius); padding: 2rem; text-align: center;">
            <i class="fas fa-check-circle" style="font-size: 5rem; color: var(--success); margin-bottom: 1rem;"></i>
            <h2 style="color: var(--success); margin-bottom: 1rem;">Pembayaran Berhasil!</h2>
            <p style="margin-bottom: 1.5rem;">Terima kasih, pembayaran Anda telah kami terima.</p>
            
            <div style="background: #f5f5f5; padding: 1rem; border-radius: var(--radius); text-align: left; margin-bottom: 1.5rem;">
                <p><strong>Nomor Order:</strong> #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></p>
                <p><strong>Total Pembayaran:</strong> Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></p>
                <p><strong>Waktu:</strong> <?php echo date('d M Y H:i:s'); ?></p>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button onclick="window.print()" class="btn btn-secondary" style="flex: 1;">
                    <i class="fas fa-print"></i> Cetak
                </button>
                <a href="orders.php" class="btn btn-primary" style="flex: 1; text-decoration: none;">
                    <i class="fas fa-list"></i> Lihat Pesanan
                </a>
            </div>
        </div>
    </div>

    <script>
    // Tab functionality
    window.showTab = function(tabName) {
        // Update tab buttons
        document.getElementById('tabTransfer').classList.remove('active');
        document.getElementById('tabEwallet').classList.remove('active');
        document.getElementById('tabCod').classList.remove('active');
        
        // Update content
        document.getElementById('transferTab').classList.remove('active');
        document.getElementById('ewalletTab').classList.remove('active');
        document.getElementById('codTab').classList.remove('active');
        
        if (tabName === 'transfer') {
            document.getElementById('tabTransfer').classList.add('active');
            document.getElementById('transferTab').classList.add('active');
        } else if (tabName === 'ewallet') {
            document.getElementById('tabEwallet').classList.add('active');
            document.getElementById('ewalletTab').classList.add('active');
        } else if (tabName === 'cod') {
            document.getElementById('tabCod').classList.add('active');
            document.getElementById('codTab').classList.add('active');
        }
    };

    // Countdown Timer untuk Transfer Bank
    function startCountdown(elementId, hours) {
        const endTime = new Date().getTime() + (hours * 60 * 60 * 1000);
        
        function updateTimer() {
            const now = new Date().getTime();
            const distance = endTime - now;

            if (distance < 0) {
                document.getElementById(elementId).innerHTML = "EXPIRED";
                return;
            }

            const h = Math.floor(distance / (1000 * 60 * 60));
            const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const s = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById(elementId).innerHTML = 
                `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        }

        updateTimer();
        setInterval(updateTimer, 1000);
    }

    // Countdown Timer untuk E-Wallet
    function startEwalletCountdown(elementId, minutes) {
        const endTime = new Date().getTime() + (minutes * 60 * 1000);
        
        function updateTimer() {
            const now = new Date().getTime();
            const distance = endTime - now;

            if (distance < 0) {
                document.getElementById(elementId).innerHTML = "EXPIRED";
                return;
            }

            const m = Math.floor((distance % (60 * 60 * 1000)) / (60 * 1000));
            const s = Math.floor((distance % (60 * 1000)) / 1000);

            document.getElementById(elementId).innerHTML = 
                `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        }

        updateTimer();
        setInterval(updateTimer, 1000);
    }

    // Start timers if elements exist
    if (document.getElementById('countdown')) {
        startCountdown('countdown', 24);
    }
    
    if (document.getElementById('ewalletCountdown')) {
        startEwalletCountdown('ewalletCountdown', 15);
    }

    // Copy to clipboard function
    window.copyToClipboard = function(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Nomor berhasil disalin!');
        }, function() {
            alert('Gagal menyalin nomor');
        });
    };

    // Select bank for transfer
    window.selectBank = function(bank) {
        document.querySelectorAll('.bank-item').forEach(item => {
            item.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');
        document.getElementById('bank' + bank.toUpperCase()).checked = true;
        
        // Update hidden input for upload
        document.getElementById('bankNameUpload').value = bank.toUpperCase();
    };

    // Select e-wallet
    window.selectEwallet = function(type) {
        document.getElementById('ewalletSelected').value = type;
        document.getElementById('ewalletTypeUpload').value = type;
        
        document.querySelectorAll('.ewallet-option').forEach(option => {
            option.classList.remove('selected');
        });
        
        event.currentTarget.classList.add('selected');
        document.getElementById(type).checked = true;
    };

    // Confirm transfer payment
    window.confirmTransfer = function() {
        const bank = document.querySelector('input[name="bank_name"]:checked')?.value || 'BCA';
        const total = 'Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>';
        
        const confirmMessage = `Konfirmasi Pembayaran Transfer\n\nBank Tujuan: ${bank}\nJumlah: ${total}\n\nLanjutkan pembayaran?`;
        
        if (confirm(confirmMessage)) {
            document.getElementById('loading').classList.add('active');
            return true;
        }
        return false;
    };

    // Confirm e-wallet payment with PIN
    window.confirmEwalletWithPin = function() {
        const selectedEwallet = document.getElementById('ewalletSelected').value;
        const ewalletPin = document.getElementById('ewallet_pin').value;
        const total = 'Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>';
        
        let ewalletName = '';
        if (selectedEwallet === 'gopay') ewalletName = 'GoPay';
        else if (selectedEwallet === 'ovo') ewalletName = 'OVO';
        else if (selectedEwallet === 'dana') ewalletName = 'Dana';
        
        // Validasi PIN tidak boleh kosong
        if (!ewalletPin || ewalletPin.trim() === '') {
            alert('Harap masukkan PIN E-Wallet Anda');
            document.getElementById('ewallet_pin').focus();
            return false;
        }
        
        // Validasi PIN harus 6 digit angka
        if (!/^[0-9]{6}$/.test(ewalletPin)) {
            alert('PIN harus 6 digit angka');
            document.getElementById('ewallet_pin').focus();
            return false;
        }
        
        // Validasi PIN (untuk demo, PIN yang valid adalah 123456)
        if (ewalletPin !== '123456') {
            alert('PIN yang Anda masukkan salah. Untuk demo gunakan PIN: 123456');
            document.getElementById('ewallet_pin').focus();
            return false;
        }
        
        const confirmMessage = `Konfirmasi pembayaran via ${ewalletName}\n\nNomor: 081931194443\nJumlah: ${total}\nPIN: ••••••\n\nLanjutkan pembayaran?`;
        
        if (confirm(confirmMessage)) {
            document.getElementById('loading').classList.add('active');
            return true;
        }
        return false;
    };

    // Toggle upload section for Transfer
    window.toggleTransferUpload = function() {
        const uploadSection = document.getElementById('transferUploadSection');
        if (uploadSection.style.display === 'none' || uploadSection.style.display === '') {
            uploadSection.style.display = 'block';
        } else {
            uploadSection.style.display = 'none';
        }
    };

    // Toggle upload section for E-Wallet
    window.toggleEwalletUpload = function() {
        const uploadSection = document.getElementById('ewalletUploadSection');
        if (uploadSection.style.display === 'none' || uploadSection.style.display === '') {
            uploadSection.style.display = 'block';
        } else {
            uploadSection.style.display = 'none';
        }
    };

    // Upload area click for Transfer
    document.getElementById('transferUploadArea')?.addEventListener('click', function() {
        document.getElementById('transferPaymentProof').click();
    });

    // Upload area click for E-Wallet
    document.getElementById('ewalletUploadArea')?.addEventListener('click', function() {
        document.getElementById('ewalletPaymentProof').click();
    });

    // File input change for Transfer
    document.getElementById('transferPaymentProof')?.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            
            // Validate file size
            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran file maksimal 2MB');
                this.value = '';
                return;
            }
            
            // Preview image
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('transferPreviewImage').src = e.target.result;
                document.getElementById('transferFilePreview').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    // File input change for E-Wallet
    document.getElementById('ewalletPaymentProof')?.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            
            // Validate file size
            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran file maksimal 2MB');
                this.value = '';
                return;
            }
            
            // Preview image
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('ewalletPreviewImage').src = e.target.result;
                document.getElementById('ewalletFilePreview').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    // Confirm transfer upload
    window.confirmTransferUpload = function() {
        const fileInput = document.getElementById('transferPaymentProof');
        
        if (!fileInput.files || fileInput.files.length === 0) {
            alert('Harap upload bukti transfer terlebih dahulu');
            return false;
        }
        
        const bankName = document.getElementById('bankNameUpload').value;
        const confirmMessage = `Apakah Anda yakin ingin mengupload bukti transfer untuk bank ${bankName}?`;
        
        if (confirm(confirmMessage)) {
            document.getElementById('loading').classList.add('active');
            return true;
        }
        return false;
    };

    // Confirm e-wallet upload
    window.confirmEwalletUpload = function() {
        const fileInput = document.getElementById('ewalletPaymentProof');
        
        if (!fileInput.files || fileInput.files.length === 0) {
            alert('Harap upload bukti transfer terlebih dahulu');
            return false;
        }
        
        const ewalletType = document.getElementById('ewalletTypeUpload').value;
        let ewalletName = '';
        if (ewalletType === 'gopay') ewalletName = 'GoPay';
        else if (ewalletType === 'ovo') ewalletName = 'OVO';
        else if (ewalletType === 'dana') ewalletName = 'Dana';
        
        const confirmMessage = `Apakah Anda yakin ingin mengupload bukti transfer untuk ${ewalletName}?`;
        
        if (confirm(confirmMessage)) {
            document.getElementById('loading').classList.add('active');
            return true;
        }
        return false;
    };

    // Show success modal for demo (you can remove this in production)
    <?php if (isset($_SESSION['payment_success']) && $_SESSION['payment_success'] === true): ?>
    window.addEventListener('load', function() {
        document.getElementById('successModal').style.display = 'flex';
        <?php unset($_SESSION['payment_success']); ?>
    });
    <?php endif; ?>

    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        const modal = document.getElementById('successModal');
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Auto-hide loading
    window.addEventListener('load', function() {
        setTimeout(function() {
            document.getElementById('loading').classList.remove('active');
        }, 3000);
    });

    
    </script>

    <?php include 'components/footer.php'; ?>
</body>
</html>