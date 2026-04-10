<?php
session_start();
require_once '../components/database.php';

// Cek login kurir
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'kurir') {
    header('Location: login_kurir.php');
    exit();
}

if (!isset($_SESSION['kurir_id'])) {
    header('Location: login_kurir.php?error=session');
    exit();
}

$kurir_id = $_SESSION['kurir_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    header('Location: dashboard.php?error=invalid_id');
    exit();
}

// Update status jadi 'selesai'
$query = "UPDATE orders SET status = 'selesai' WHERE id = ? AND kurir_id = ? AND status = 'dikirim'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $kurir_id);

if (mysqli_stmt_execute($stmt) && mysqli_affected_rows($conn) > 0) {
    $_SESSION['success'] = 'Pesanan berhasil diselesaikan! Terima kasih.';
    header('Location: dashboard.php?success=completed');
} else {
    $_SESSION['error'] = 'Gagal menyelesaikan pesanan.';
    header('Location: dashboard.php?error=failed_complete');
}
exit();
?>