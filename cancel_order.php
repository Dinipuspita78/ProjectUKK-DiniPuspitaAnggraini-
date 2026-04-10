<?php
require_once '../components/database.php';

if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Akses ditolak']));
}

if (!isset($_POST['order_id'])) {
    exit(json_encode(['success' => false, 'message' => 'Order ID tidak valid']));
}

$order_id = intval($_POST['order_id']);

// Cek apakah pesanan bisa dibatalkan
$query = "SELECT status FROM orders WHERE id = ? AND status NOT IN ('delivered', 'cancelled')";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    exit(json_encode(['success' => false, 'message' => 'Pesanan tidak dapat dibatalkan']));
}

// Update status menjadi cancelled
$update_query = "UPDATE orders SET status = 'cancelled', catatan = CONCAT(catatan, '\n[Dibatalkan oleh admin]') WHERE id = ?";
$stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($stmt, "i", $order_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Pesanan berhasil dibatalkan']);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}