<?php
// midtrans_notification.php
require_once 'components/database.php';
require_once 'components/midtrans.php';

// Terima notifikasi dari Midtrans
$notification = new \Midtrans\Notification();
$transaction = $notification->transaction_status;
$type = $notification->payment_type;
$order_id = $notification->order_id;
$fraud = $notification->fraud_status;

// Extract order ID dari format "ORDER-123-1234567890"
$parts = explode('-', $order_id);
$real_order_id = $parts[1] ?? 0;

// Cari order di database
$query = "SELECT * FROM orders WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $real_order_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($order) {
    if ($transaction == 'capture') {
        if ($type == 'credit_card') {
            if ($fraud == 'challenge') {
                updateOrderStatus($conn, $real_order_id, 'pending_payment', 'fraud_challenge');
            } else {
                updateOrderStatus($conn, $real_order_id, 'paid', 'capture_success');
            }
        }
    } elseif ($transaction == 'settlement') {
        // Pembayaran sukses
        updateOrderStatus($conn, $real_order_id, 'paid', 'settlement');
        
        // Kurangi stok produk
        $items_query = "SELECT * FROM order_items WHERE order_id = ?";
        $stmt = mysqli_prepare($conn, $items_query);
        mysqli_stmt_bind_param($stmt, "i", $real_order_id);
        mysqli_stmt_execute($stmt);
        $items = mysqli_stmt_get_result($stmt);
        
        while ($item = mysqli_fetch_assoc($items)) {
            $update_stock = "UPDATE produk SET stok = stok - ? WHERE id = ?";
            $stmt2 = mysqli_prepare($conn, $update_stock);
            mysqli_stmt_bind_param($stmt2, "ii", $item['quantity'], $item['product_id']);
            mysqli_stmt_execute($stmt2);
        }
        
    } elseif ($transaction == 'pending') {
        updateOrderStatus($conn, $real_order_id, 'pending_payment', 'pending');
    } elseif ($transaction == 'deny') {
        updateOrderStatus($conn, $real_order_id, 'failed', 'deny');
    } elseif ($transaction == 'expire') {
        updateOrderStatus($conn, $real_order_id, 'expired', 'expire');
    } elseif ($transaction == 'cancel') {
        updateOrderStatus($conn, $real_order_id, 'cancelled', 'cancel');
    }
}

function updateOrderStatus($conn, $order_id, $status, $notes = '') {
    $query = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
    mysqli_stmt_execute($stmt);
    
    // Log transaksi
    $log_query = "INSERT INTO payment_logs (order_id, status, notes, created_at) VALUES (?, ?, ?, NOW())";
    $log_stmt = mysqli_prepare($conn, $log_query);
    mysqli_stmt_bind_param($log_stmt, "iss", $order_id, $status, $notes);
    mysqli_stmt_execute($log_stmt);
}

http_response_code(200);
?>