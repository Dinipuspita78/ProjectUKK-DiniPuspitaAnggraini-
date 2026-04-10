<?php
// kurir/update_status.php
session_start();
require_once '../components/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kurir') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $kurir_id = $_SESSION['kurir_id'];
    
    // Cek apakah order milik kurir ini
    $check_query = "SELECT id FROM orders WHERE id = ? AND kurir_id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $kurir_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Order tidak ditemukan']);
        exit();
    }
    
    // Update status
    $update_query = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Jika status diterima, update kurir status menjadi aktif
        if ($status == 'diterima') {
            $update_kurir = "UPDATE kurir SET status = 'aktif' WHERE id = ?";
            $stmt2 = mysqli_prepare($conn, $update_kurir);
            mysqli_stmt_bind_param($stmt2, "i", $kurir_id);
            mysqli_stmt_execute($stmt2);
            
            $_SESSION['status_kurir'] = 'aktif';
        }
        
        echo json_encode(['success' => true, 'message' => 'Status berhasil diupdate']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal update status']);
    }
}
?>