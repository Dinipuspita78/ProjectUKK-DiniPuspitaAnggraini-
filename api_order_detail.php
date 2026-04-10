<?php
session_start();
require_once '../components/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Cek apakah user adalah admin
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] == 'admin';

if ($is_admin) {
    // Admin bisa melihat semua pesanan
    $order_query = mysqli_query($conn, "SELECT o.*, u.nama as customer_name, u.email as customer_email 
                                        FROM orders o 
                                        JOIN users u ON o.user_id = u.id 
                                        WHERE o.id = $order_id");
} else {
    // User hanya bisa melihat pesanannya sendiri
    $order_query = mysqli_query($conn, "SELECT o.*, u.nama as customer_name, u.email as customer_email 
                                        FROM orders o 
                                        JOIN users u ON o.user_id = u.id 
                                        WHERE o.id = $order_id AND o.user_id = $user_id");
}

$order = mysqli_fetch_assoc($order_query);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
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

// Format response
$response = [
    'success' => true,
    'order' => [
        'id' => $order['id'],
        'customer_name' => $order['customer_name'],
        'customer_email' => $order['customer_email'],
        'total_amount' => $order['total_amount'],
        'status' => $order['status'],
        'payment_method' => $order['payment_method'],
        'payment_method_detail' => $order['payment_method_detail'],
        'payment_reference' => $order['payment_reference'],
        'bukti_pembayaran' => $order['bukti_pembayaran'],
        'shipping_address' => $order['shipping_address'],
        'notes' => $order['notes'],
        'created_at' => $order['created_at'],
        'updated_at' => $order['updated_at']
    ],
    'items' => $items
];

echo json_encode($response);
?>