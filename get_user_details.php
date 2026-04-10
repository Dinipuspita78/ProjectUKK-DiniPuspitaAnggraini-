<?php
// File: admin/get_user_details.php
require_once '../components/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit();
}

$user_id = intval($_GET['id']);

// Get user details
$query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

// Get user statistics
$stats_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(total_harga) as total_spent,
    MAX(created_at) as last_order_date
    FROM pesanan 
    WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$stats_result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($stats_result);

// Get recent orders
$orders_query = "SELECT * FROM pesanan 
                 WHERE user_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT 5";
$stmt = mysqli_prepare($conn, $orders_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);
$recent_orders = [];

while ($order = mysqli_fetch_assoc($orders_result)) {
    $recent_orders[] = $order;
}

echo json_encode([
    'success' => true,
    'user' => $user,
    'stats' => $stats,
    'recent_orders' => $recent_orders
]);
?>