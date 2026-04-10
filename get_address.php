<?php
require_once 'components/database.php';
require_once 'components/functions.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID required']);
    exit();
}

$user_id = $_SESSION['user_id'];
$address_id = intval($_GET['id']);

$query = "SELECT * FROM user_addresses WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $address_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$address = mysqli_fetch_assoc($result);

if ($address) {
    echo json_encode([
        'success' => true, 
        'data' => $address
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Alamat tidak ditemukan'
    ]);
}
?>