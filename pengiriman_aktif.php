<?php
session_start();
require_once '../components/database.php';

// Cek login kurir
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'kurir') {
    header('Location: login_kurir.php');
    exit();
}

if (!isset($_SESSION['kurir_id'])) {
    header('Location: login_kurir.php?error=session');
    exit();
}

$kurir_id = $_SESSION['kurir_id'];

// Ambil data kurir
$query_kurir = "SELECT * FROM kurir WHERE id = ?";
$stmt = mysqli_prepare($conn, $query_kurir);
mysqli_stmt_bind_param($stmt, "i", $kurir_id);
mysqli_stmt_execute($stmt);
$result_kurir = mysqli_stmt_get_result($stmt);
$kurir = mysqli_fetch_assoc($result_kurir);

// Ambil pesanan yang sedang dikirim
$query = "SELECT 
    o.id,
    o.shipping_address,
    o.total_amount,
    o.created_at,
    o.updated_at,
    o.payment_method,
    o.notes,
    u.nama as nama_pelanggan,
    u.telepon,
    TIMESTAMPDIFF(MINUTE, o.updated_at, NOW()) as menit_pengiriman,
    (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as total_quantity
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.status = 'dikirim' AND o.kurir_id = ?
    ORDER BY o.updated_at DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $kurir_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$page_title = "Pengiriman Aktif";

?>
<?php include '../components/sidebar_kurir.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengiriman Aktif - MinShop</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Style sama dengan menunggu_kurir.php dengan penyesuaian */
        .btn-complete {
            background: #10b981;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }
        
        .btn-complete:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        .delivery-time {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            background: #e0f2fe;
            color: #0369a1;
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        
        
        <div class="main-content">
            <!-- Konten Pengiriman Aktif -->
            <!-- ... (similar structure with menunggu_kurir.php but for active deliveries) ... -->
        </div>
    </div>
</body>
</html>