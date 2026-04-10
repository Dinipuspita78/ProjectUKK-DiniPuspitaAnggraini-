<?php
session_start();
require_once 'database.php';

// Set header JSON
header('Content-Type: application/json');

// Cek login
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Silakan login terlebih dahulu',
        'code' => 'AUTH_REQUIRED'
    ]);
    exit();
}

// Ambil data dari POST
$product_id = intval($_POST['product_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);
$user_id = $_SESSION['user_id'];

// Debug log
error_log("Tambah Keranjang - User: $user_id, Product: $product_id, Quantity: $quantity");

// Validasi input
if ($product_id <= 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Produk tidak valid',
        'code' => 'INVALID_PRODUCT'
    ]);
    exit();
}

if ($quantity <= 0) {
    $quantity = 1;
}

if ($quantity > 100) {
    $quantity = 100;
}

// DOUBLE PROTECTION: Cek request rate limiting
$request_key = "cart_request_{$user_id}_{$product_id}";
if (isset($_SESSION[$request_key])) {
    $last_request = $_SESSION[$request_key];
    $time_diff = time() - $last_request;
    
    // Blok request yang terlalu cepat (kurang dari 1 detik)
    if ($time_diff < 1) {
        echo json_encode([
            'success' => false, 
            'message' => 'Silakan tunggu sebentar sebelum menambah lagi',
            'code' => 'RATE_LIMITED'
        ]);
        exit();
    }
}

// Simpan timestamp request terakhir
$_SESSION[$request_key] = time();

// Cek produk di database
$query = "SELECT * FROM produk WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Produk tidak ditemukan',
        'code' => 'PRODUCT_NOT_FOUND'
    ]);
    exit();
}

$product = mysqli_fetch_assoc($result);

// Cek stok mencukupi
if ($product['stok'] < $quantity) {
    echo json_encode([
        'success' => false, 
        'message' => 'Stok tidak mencukupi. Stok tersedia: ' . $product['stok'],
        'code' => 'INSUFFICIENT_STOCK',
        'available_stock' => $product['stok']
    ]);
    exit();
}

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// DOUBLE PROTECTION: Lock session untuk menghindari race condition
session_write_close();
session_start();

$item_index = -1;
$cart_item_key = null;

// Cari apakah produk sudah ada di keranjang untuk user ini
foreach ($_SESSION['cart'] as $index => $item) {
    // Pastikan item memiliki struktur yang benar
    if (!isset($item['user_id']) || !isset($item['product_id'])) {
        continue;
    }
    
    if ($item['user_id'] == $user_id && $item['product_id'] == $product_id) {
        $item_index = $index;
        $cart_item_key = "cart_item_{$user_id}_{$product_id}";
        break;
    }
}

// DOUBLE PROTECTION: Cek lock item
if ($cart_item_key && isset($_SESSION[$cart_item_key])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Produk sedang diproses. Silakan tunggu...',
        'code' => 'ITEM_LOCKED'
    ]);
    exit();
}

// Lock item untuk mencegah duplikasi
$cart_item_key = "cart_item_{$user_id}_{$product_id}";
$_SESSION[$cart_item_key] = true;

try {
    // Jika produk sudah ada di keranjang
    if ($item_index !== -1) {
        $new_quantity = $_SESSION['cart'][$item_index]['quantity'] + $quantity;
        
        // Cek stok maksimal
        if ($new_quantity > $product['stok']) {
            echo json_encode([
                'success' => false, 
                'message' => 'Stok tidak mencukupi untuk menambah jumlah. Stok tersedia: ' . $product['stok'],
                'code' => 'EXCEED_STOCK_LIMIT',
                'available_stock' => $product['stok'],
                'current_quantity' => $_SESSION['cart'][$item_index]['quantity']
            ]);
            exit();
        }
        
        $_SESSION['cart'][$item_index]['quantity'] = $new_quantity;
        $_SESSION['cart'][$item_index]['updated_at'] = date('Y-m-d H:i:s');
        
    } else {
        // Tambahkan produk baru ke keranjang
        $cart_item = [
            'user_id' => $user_id,
            'product_id' => $product_id,
            'quantity' => $quantity,
            'product_name' => $product['nama'],
            'product_price' => $product['harga'],
            'product_image' => $product['gambar'],
            'added_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $_SESSION['cart'][] = $cart_item;
    }
    
    // Hitung total item di keranjang untuk user ini
    $cart_count = 0;
    $cart_total = 0;
    
    foreach ($_SESSION['cart'] as $item) {
        if (isset($item['user_id']) && $item['user_id'] == $user_id) {
            $cart_count += $item['quantity'];
            if (isset($item['product_price']) && isset($item['quantity'])) {
                $cart_total += ($item['product_price'] * $item['quantity']);
            }
        }
    }
    
    // Response sukses
    echo json_encode([
        'success' => true,
        'message' => 'Produk berhasil ditambahkan ke keranjang',
        'code' => 'SUCCESS',
        'cart_count' => $cart_count,
        'cart_total' => $cart_total,
        'product_name' => $product['nama'],
        'product_id' => $product_id,
        'quantity_added' => $quantity,
        'total_quantity' => $item_index !== -1 ? $_SESSION['cart'][$item_index]['quantity'] : $quantity
    ]);
    
} catch (Exception $e) {
    // Error handling
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
        'code' => 'SYSTEM_ERROR'
    ]);
} finally {
    // Unlock item
    if (isset($cart_item_key)) {
        unset($_SESSION[$cart_item_key]);
    }
    
    // Cleanup old locks (older than 10 seconds)
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'cart_item_') === 0 || strpos($key, 'cart_request_') === 0) {
            // Simpan hanya lock yang masih relevan
            // (Dalam implementasi lengkap, bisa ditambahkan timestamp checking)
        }
    }
}

// Tutup koneksi database
mysqli_close($conn);
?>