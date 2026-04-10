<?php
session_start();
require_once '../components/database.php';

// Cek login kurir
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'kurir') {
    header('Location:login_kurir.php');
    exit();
}

if (!isset($_SESSION['kurir_id'])) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT id FROM kurir WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $_SESSION['kurir_id'] = $row['id'];
        $kurir_id = $row['id'];
    } else {
        header('Location:login_kurir.php?error=not_found');
        exit();
    }
} else {
    $kurir_id = $_SESSION['kurir_id'];
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    $_SESSION['error'] = 'ID pesanan tidak valid!';
    header('Location:menunggu_kurir.php');
    exit();
}
  
// Mulai transaction
mysqli_begin_transaction($conn);

try {
    // Cek apakah pesanan masih tersedia
    $check_query = "SELECT id, status FROM orders WHERE id = ? FOR UPDATE";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($result);
    
    if (!$order) {
        throw new Exception('Pesanan tidak ditemukan!');
    }
    
    if ($order['status'] != 'menunggu_kurir') {
        throw new Exception('Pesanan sudah diambil kurir lain!');
    }
    
    // Update pesanan: assign ke kurir dan ubah status jadi 'dikirim'
    $update_query = "UPDATE orders SET kurir_id = ?, status = 'dikirim', updated_at = NOW() 
                    WHERE id = ? AND status = 'menunggu_kurir'";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "ii", $kurir_id, $order_id);
    
    if (!mysqli_stmt_execute($stmt) || mysqli_affected_rows($conn) == 0) {
        throw new Exception('Gagal mengambil pesanan!');
    }
    
    // Catat log aktivitas kurir
    $log_query = "INSERT INTO kurir_aktivitas (kurir_id, order_id, aktivitas, created_at) 
                  VALUES (?, ?, 'mengambil_pesanan', NOW())";
    $stmt = mysqli_prepare($conn, $log_query);
    mysqli_stmt_bind_param($stmt, "ii", $kurir_id, $order_id);
    mysqli_stmt_execute($stmt);
    
    // Commit transaction
    mysqli_commit($conn);
    
    $_SESSION['success'] = 'Pesanan berhasil diambil! Silakan lakukan pengiriman.';
    header('Location:pengiriman_aktif.php');
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = 'Gagal mengambil pesanan: ' . $e->getMessage();
    header('Location:menunggu_kurir.php');
}
exit();
?>