<?php
session_start();
require_once 'components/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Silakan login terlebih dahulu");
}

echo "<h1>Debug Cart Session</h1>";
echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";

echo "<h2>Isi Session Cart:</h2>";
echo "<pre>";
if (isset($_SESSION['cart'])) {
    print_r($_SESSION['cart']);
} else {
    echo "Session cart kosong";
}
echo "</pre>";

// Hitung total per user
if (isset($_SESSION['cart'])) {
    $user_id = $_SESSION['user_id'];
    $user_items = [];
    $total_quantity = 0;
    
    foreach ($_SESSION['cart'] as $item) {
        if (isset($item['user_id']) && $item['user_id'] == $user_id) {
            $user_items[] = $item;
            $total_quantity += $item['quantity'];
        }
    }
    
    echo "<h2>Item untuk User ID $user_id:</h2>";
    echo "<p>Total item: " . count($user_items) . "</p>";
    echo "<p>Total quantity: $total_quantity</p>";
    echo "<pre>";
    print_r($user_items);
    echo "</pre>";
}

echo "<br><a href='keranjang.php'>Kembali ke Keranjang</a>";
?>