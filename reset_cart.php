<?php
session_start();

// Reset hanya cart untuk user yang login
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    if (isset($_SESSION['cart'])) {
        // Hapus hanya item milik user ini
        foreach ($_SESSION['cart'] as $key => $item) {
            if (isset($item['user_id']) && $item['user_id'] == $user_id) {
                unset($_SESSION['cart'][$key]);
            }
        }
        
        // Re-index array
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        
        echo "Keranjang untuk user ID $user_id telah direset!";
    } else {
        echo "Keranjang sudah kosong";
    }
} else {
    echo "Silakan login terlebih dahulu";
}

header('Refresh: 2; URL=keranjang.php');
?>