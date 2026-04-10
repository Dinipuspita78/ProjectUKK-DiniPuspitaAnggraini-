<?php
// File: admin/get_category_products.php
require_once '../components/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['category'])) {
    echo json_encode(['success' => false, 'message' => 'Category required']);
    exit();
}

$category = urldecode($_GET['category']);

// Get products in category
$query = "SELECT * FROM produk WHERE kategori = ? ORDER BY nama";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $category);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$products = [];

while ($product = mysqli_fetch_assoc($result)) {
    $products[] = $product;
}

echo json_encode([
    'success' => true,
    'category' => $category,
    'products' => $products
]);
?>