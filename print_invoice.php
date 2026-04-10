<?php
require_once '../components/database.php';

if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../admin_login.php');
    exit();
}

$order_id = intval($_GET['id'] ?? 0);

if ($order_id == 0) {
    header('Location: pesanan.php');
    exit();
}

// Get order details
$query = "SELECT o.*, u.nama as customer_name, u.email, u.telepon 
          FROM orders o 
          JOIN pengguna u ON o.user_id = u.id 
          WHERE o.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    die("Pesanan tidak ditemukan!");
}

// Get order items
$items_query = "SELECT dp.*, pr.nama 
                FROM detail_pesanan dp 
                JOIN produk pr ON dp.produk_id = pr.id 
                WHERE dp.pesanan_id = ?";
$stmt_items = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($stmt_items, "i", $order_id);
mysqli_stmt_execute($stmt_items);
$items_result = mysqli_stmt_get_result($stmt_items);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border: 1px solid #ddd;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #4a90e2;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-info h1 {
            color: #4a90e2;
            margin: 0;
        }
        .invoice-info h2 {
            color: #333;
            margin: 0;
        }
        .customer-info, .order-info {
            margin-bottom: 30px;
        }
        .section-title {
            color: #4a90e2;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .info-item {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background: #4a90e2;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .total-section {
            margin-top: 30px;
            text-align: right;
        }
        .total-row {
            margin-bottom: 10px;
        }
        .final-total {
            font-size: 1.2em;
            font-weight: bold;
            color: #4a90e2;
            border-top: 2px solid #4a90e2;
            padding-top: 10px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #666;
            font-size: 0.9em;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
            .invoice-container {
                border: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div class="company-info">
                <h1>MinShop Petshop</h1>
                <p>Jl. MinShop No. 123, Jakarta</p>
                <p>Telp: (021) 1234-5678</p>
                <p>Email: info@minshop.com</p>
            </div>
            <div class="invoice-info">
                <h2>INVOICE</h2>
                <p><strong>No: </strong>#<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></p>
                <p><strong>Tanggal: </strong><?php echo date('d/m/Y'); ?></p>
            </div>
        </div>

        <div class="customer-info">
            <h3 class="section-title">Informasi Pelanggan</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Nama</div>
                    <div><?php echo htmlspecialchars($order['customer_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div><?php echo htmlspecialchars($order['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Telepon</div>
                    <div><?php echo htmlspecialchars($order['telepon'] ?? '-'); ?></div>
                </div>
            </div>
        </div>

        <div class="order-info">
            <h3 class="section-title">Detail Pesanan</h3>
            <table>
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Harga</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total = 0;
                    while ($item = mysqli_fetch_assoc($items_result)): 
                        $subtotal = $item['harga'] * $item['jumlah'];
                        $total += $subtotal;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['nama']); ?></td>
                        <td>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                        <td><?php echo $item['jumlah']; ?></td>
                        <td>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <div class="total-section">
                <div class="total-row">
                    <span>Subtotal: </span>
                    <span>Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                </div>
                <div class="total-row">
                    <span>Ongkos Kirim: </span>
                    <span>Rp <?php echo number_format($order['shipping_cost'] ?? 5000, 0, ',', '.'); ?></span>
                </div>
                <div class="total-row final-total">
                    <span>Total: </span>
                    <span>Rp <?php echo number_format(($order['total_amount'] ?? $total + 5000), 0, ',', '.'); ?></span>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Terima kasih telah berbelanja di MinShop Petshop!</p>
            <p>Invoice ini sah dan dapat digunakan sebagai bukti pembayaran</p>
            <p>Hubungi customer service kami jika ada pertanyaan</p>
        </div>

        <div class="no-print" style="margin-top: 30px; text-align: center;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #4a90e2; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Cetak Invoice
            </button>
            <button onclick="window.close()" style="padding: 10px 20px; background: #666; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                Tutup
            </button>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>