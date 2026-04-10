<?php
session_start();
require_once '../components/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Pesanan Menunggu</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #1e293b; padding: 40px; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 16px; padding: 30px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        h1 { color: #1e293b; display: flex; align-items: center; gap: 10px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #4a6cf7; color: white; text-decoration: none; border-radius: 8px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1><i class='fas fa-tools' style='color: #ffc107;'></i> Fix Pesanan Menunggu</h1>";

// CEK PESANAN BERMASALAH
$query = "SELECT id, kurir_id FROM orders WHERE status = 'menunggu_kurir' AND kurir_id IS NOT NULL";
$result = mysqli_query($conn, $query);
$jumlah_error = mysqli_num_rows($result);

echo "<div class='" . ($jumlah_error > 0 ? 'warning' : 'success') . "'>";
echo "<strong>📊 Status Saat Ini:</strong><br>";

if ($jumlah_error > 0) {
    echo "Ditemukan <strong>$jumlah_error</strong> pesanan dengan status 'menunggu_kurir' tapi sudah punya kurir_id!<br><br>";
    echo "<strong>🛠️ Memperbaiki...</strong><br>";
    
    // TAMPILKAN DATA YANG AKAN DIFIX
    mysqli_data_seek($result, 0);
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- Order #{$row['id']}: kurir_id = {$row['kurir_id']} → akan direset ke NULL<br>";
    }
    
    // RESET kurir_id KE NULL
    $reset = "UPDATE orders SET kurir_id = NULL, updated_at = NOW() WHERE status = 'menunggu_kurir' AND kurir_id IS NOT NULL";
    if (mysqli_query($conn, $reset)) {
        echo "<br><strong style='color: #28a745;'>✅ BERHASIL!</strong> Semua pesanan menunggu direset.";
    }
} else {
    echo "✅ Semua pesanan menunggu sudah benar (kurir_id = NULL)";
}
echo "</div>";

// TAMPILKAN JUMLAH SEKARANG
$cek_sekarang = "SELECT COUNT(*) as total FROM orders WHERE status = 'menunggu_kurir' AND kurir_id IS NULL";
$result_sekarang = mysqli_query($conn, $cek_sekarang);
$data_sekarang = mysqli_fetch_assoc($result_sekarang);

echo "<div style='margin-top: 30px; padding: 20px; background: #eef2ff; border-radius: 8px;'>";
echo "<strong>📋 Hasil Akhir:</strong><br>";
echo "Total pesanan menunggu yang siap diambil: <strong style='font-size: 24px; color: #4a6cf7;'>{$data_sekarang['total']}</strong>";
echo "</div>";

echo "<a href='pesanan.php?status=menunggu_kurir' class='btn'><i class='fas fa-arrow-left'></i> Kembali ke Pesanan</a>";
echo "</div></body></html>";

mysqli_close($conn);
?>