<?php
// ajax/add_to_cart.php
session_start();
require_once '../components/database.php';
header('Content-Type: application/json');

// Cek jika user belum login
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Silakan login terlebih dahulu untuk menambahkan ke keranjang'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data dari request
$data = json_decode(file_get_contents('php://input'), true);
$product_id = intval($data['product_id']);
$quantity = intval($data['quantity']);

// Validasi data
if (!$product_id || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit();
}

// Cek apakah produk ada dan stok cukup
$product_query = mysqli_query($conn, "SELECT * FROM produk WHERE id = $product_id");
if (mysqli_num_rows($product_query) == 0) {
    echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
    exit();
}

$product = mysqli_fetch_assoc($product_query);

if ($product['stok'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi']);
    exit();
}

// Cek apakah produk sudah ada di keranjang
$cart_check = mysqli_query($conn, "SELECT * FROM keranjang WHERE user_id = $user_id AND product_id = $product_id");
if (mysqli_num_rows($cart_check) > 0) {
    // Update quantity
    mysqli_query($conn, "UPDATE keranjang SET quantity = quantity + $quantity WHERE user_id = $user_id AND product_id = $product_id");
} else {
    // Tambah baru
    mysqli_query($conn, "INSERT INTO keranjang (user_id, product_id, quantity) VALUES ($user_id, $product_id, $quantity)");
}

// Hitung total item di keranjang
$count_query = mysqli_query($conn, "SELECT SUM(quantity) as total FROM keranjang WHERE user_id = $user_id");
$count_data = mysqli_fetch_assoc($count_query);
$cart_count = $count_data['total'] ?? 0;

echo json_encode([
    'success' => true,
    'message' => 'Produk berhasil ditambahkan ke keranjang',
    'cart_count' => $cart_count
]);
?>