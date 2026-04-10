<?php
// File: admin/get_message_detail.php
require_once '../components/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Message ID required']);
    exit();
}

$message_id = intval($_GET['id']);

// Get message details
$query = "SELECT * FROM pesan_kontak WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $message_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$message = mysqli_fetch_assoc($result);

if (!$message) {
    echo json_encode(['success' => false, 'message' => 'Message not found']);
    exit();
}

// Mark as read when viewed
if (!$message['dibaca']) {
    $update_query = "UPDATE pesan_kontak SET dibaca = TRUE WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "i", $message_id);
    mysqli_stmt_execute($stmt);
}

echo json_encode([
    'success' => true,
    'message' => $message
]);
?>