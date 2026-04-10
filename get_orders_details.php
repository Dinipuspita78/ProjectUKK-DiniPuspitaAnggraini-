<?php
require_once '../components/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit();
}

$order_id = intval($_GET['id']);

// Pertama, cek struktur tabel
$check_columns = mysqli_query($conn, "SHOW COLUMNS FROM pesanan");
$pesanan_columns = [];
while ($col = mysqli_fetch_assoc($check_columns)) {
    $pesanan_columns[] = $col['Field'];
}

// Bangun query dinamis
$select_fields = "p.*";
if (in_array('alamat_pengiriman', $pesanan_columns)) {
    $select_fields .= ", p.alamat_pengiriman as shipping_address";
} elseif (in_array('shipping_address', $pesanan_columns)) {
    $select_fields .= ", p.shipping_address";
}

// Cek tabel users
$check_users = mysqli_query($conn, "SHOW COLUMNS FROM users");
$users_columns = [];
while ($col = mysqli_fetch_assoc($check_users)) {
    $users_columns[] = $col['Field'];
}

$user_name_field = 'username';
if (in_array('nama', $users_columns)) {
    $user_name_field = 'nama';
}

// Get order details
$order_query = "SELECT 
    $select_fields,
    u.$user_name_field as customer_name,
    u.email as customer_email
FROM pesanan p
LEFT JOIN users u ON p.user_id = u.id
WHERE p.id = ?";

$stmt = mysqli_prepare($conn, $order_query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit();
}

// Get order items - cek tabel detail_pesanan
$check_detail = mysqli_query($conn, "SHOW TABLES LIKE 'detail_pesanan'");
if (mysqli_num_rows($check_detail) > 0) {
    $items_query = "SELECT dp.*, pr.nama, pr.harga 
                   FROM detail_pesanan dp 
                   LEFT JOIN produk pr ON dp.product_id = pr.id 
                   WHERE dp.order_id = ?";
} else {
    // Coba tabel lain
    $items_query = "SELECT * FROM order_items WHERE order_id = ?";
}

$stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);
$items = [];

while ($item = mysqli_fetch_assoc($items_result)) {
    $items[] = $item;
}

echo json_encode([
    'success' => true,
    'order' => $order,
    'items' => $items
]);
?>