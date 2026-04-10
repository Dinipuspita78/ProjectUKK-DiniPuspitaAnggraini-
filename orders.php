<?php
session_start();
require 'components/database.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo '<p class="empty">Please login to see your orders</p>';
} else {
    $user_id = $_SESSION['user_id'];
    
    $conn = mysqli_connect($host, $username, $password, $database);
    
    $stmt = $conn->prepare("SELECT * FROM `orders` WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    include 'components/header_pengguna.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - MinShop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
      /* styles.css - Tambahkan di file style.css atau buat file order.css khusus */

/* ===== VARIABLES ===== */
:root {
    --primary: #2c3e50;
    --secondary: #3498db;
    --accent: #e74c3c;
    --light: #ecf0f1;
    --dark: #2c3e50;
    --success: #2ecc71;
    --warning: #f39c12;
    --danger: #e74c3c;
    --border-radius: 8px;
    --box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    --transition: all 0.3s ease;
}

/* ===== ORDER CONTAINER ===== */
.orders-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.orders-header {
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--primary);
}

.orders-header h1 {
    color: var(--primary);
    font-size: 2.2rem;
    margin-bottom: 0.5rem;
}

.orders-header p {
    color: #666;
    font-size: 1.1rem;
}

/* ===== ORDER BOX STYLING ===== */
.box {
    background: white;
    border-radius: var(--border-radius);
    padding: 1.8rem;
    margin-bottom: 1.8rem;
    box-shadow: var(--box-shadow);
    border-left: 4px solid var(--secondary);
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.box:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

/* Status Badge */
.box::before {
    content: attr(data-status);
    position: absolute;
    top: 15px;
    right: -30px;
    background: var(--secondary);
    color: white;
    padding: 0.3rem 3rem;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    transform: rotate(45deg);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.box[data-status="shipped"]::before {
    background: var(--success);
}

.box[data-status="processing"]::before {
    background: var(--warning);
}

.box[data-status="pending"]::before {
    background: #95a5a6;
}

.box[data-status="cancelled"]::before {
    background: var(--danger);
}

/* Order Header */
.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.order-id {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
}

.order-date {
    color: #666;
    font-size: 0.9rem;
    background: #f8f9fa;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
}

/* Order Info Grid */
.order-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.2rem;
    margin-bottom: 1.5rem;
}

.info-group {
    margin-bottom: 0.5rem;
}

.info-label {
    display: block;
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 0.2rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.info-value {
    display: block;
    font-size: 1rem;
    color: var(--dark);
    font-weight: 500;
    padding: 0.5rem 0;
    border-bottom: 1px dashed #eee;
}

/* Total Amount Styling */
.total-amount {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    padding: 1rem 1.5rem;
    border-radius: var(--border-radius);
    margin-top: 1rem;
    border-left: 4px solid var(--secondary);
}

.total-amount .info-label {
    color: var(--primary);
    font-size: 1rem;
}

.total-amount .info-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--accent);
    border-bottom: none;
}

/* Address Box */
.address-box {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: var(--border-radius);
    margin: 1rem 0;
    border-left: 3px solid #95a5a6;
}

/* Status Styling */
.status-badge {
    display: inline-block;
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-shipped {
    background-color: #d5f4e6;
    color: #27ae60;
}

.status-processing {
    background-color: #fef9e7;
    color: #f39c12;
}

.status-pending {
    background-color: #f2f3f4;
    color: #7f8c8d;
}

/* Empty State */
.empty {
    text-align: center;
    padding: 4rem 2rem;
    color: #666;
    font-size: 1.2rem;
    background: #f8f9fa;
    border-radius: var(--border-radius);
    margin: 2rem 0;
}

.empty i {
    font-size: 3rem;
    color: #bdc3c7;
    margin-bottom: 1rem;
    display: block;
}

/* ===== RESPONSIVE DESIGN ===== */
@media (max-width: 768px) {
    .order-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .order-info-grid {
        grid-template-columns: 1fr;
    }
    
    .box {
        padding: 1.2rem;
    }
    
    .orders-header h1 {
        font-size: 1.8rem;
    }
}

@media (max-width: 480px) {
    .box::before {
        font-size: 0.7rem;
        padding: 0.2rem 2.5rem;
        right: -25px;
        top: 10px;
    }
    
    .total-amount .info-value {
        font-size: 1.5rem;
    }
}

/* Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.box {
    animation: fadeIn 0.5s ease forwards;
}
        /* Tambahkan CSS di atas di sini atau simpan di file terpisah */
    </style>
</head>
<body>

<section class="orders-container">
    <div class="orders-header">
        <h1><i class="fas fa-history"></i> Riwayat Pesanan</h1>
        <p>Lihat dan kelola semua pesanan Anda di MinShop</p>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <?php while($fetch_orders = $result->fetch_assoc()): 
            $statusClass = '';
            switch(strtolower($fetch_orders['status'])) {
                case 'shipped':
                    $statusClass = 'status-shipped';
                    break;
                case 'processing':
                    $statusClass = 'status-processing';
                    break;
                case 'pending':
                    $statusClass = 'status-pending';
                    break;
                default:
                    $statusClass = 'status-pending';
            }
        ?>
        <div class="box" data-status="<?= htmlspecialchars($fetch_orders['status']) ?>">
            <div class="order-header">
                <div class="order-id">
                    <i class="fas fa-receipt"></i> Order #<?= htmlspecialchars($fetch_orders['id']) ?>
                </div>
                <div class="order-date">
                    <i class="far fa-calendar-alt"></i> 
                    <?= date('d M Y', strtotime($fetch_orders['created_at'])) ?>
                </div>
            </div>
            
            <div class="order-info-grid">
                <div class="info-group">
                    <span class="info-label">ID Pengguna</span>
                    <span class="info-value"><?= htmlspecialchars($fetch_orders['user_id']) ?></span>
                </div>
                
                <div class="info-group">
                    <span class="info-label">Metode Pembayaran</span>
                    <span class="info-value">
                        <i class="fas fa-credit-card"></i> 
                        <?= htmlspecialchars($fetch_orders['payment_method']) ?>
                    </span>
                </div>
                
                <div class="info-group">
                    <span class="info-label">Status</span>
                    <span class="status-badge <?= $statusClass ?>">
                        <i class="fas fa-circle"></i> 
                        <?= htmlspecialchars($fetch_orders['status']) ?>
                    </span>
                </div>
                
                <div class="info-group">
                    <span class="info-label">Terakhir Diperbarui</span>
                    <span class="info-value">
                        <i class="far fa-clock"></i> 
                        <?= date('d M Y H:i', strtotime($fetch_orders['updated_at'])) ?>
                    </span>
                </div>
            </div>
            
            <div class="address-box">
                <span class="info-label">Alamat Pengiriman</span>
                <span class="info-value">
                    <i class="fas fa-map-marker-alt"></i> 
                    <?= htmlspecialchars($fetch_orders['shipping_address']) ?>
                </span>
            </div>
            
            <?php if (!empty($fetch_orders['notes'])): ?>
            <div class="info-group">
                <span class="info-label">Catatan</span>
                <span class="info-value">
                    <i class="fas fa-sticky-note"></i> 
                    <?= htmlspecialchars($fetch_orders['notes']) ?>
                </span>
            </div>
            <?php endif; ?>
            
            <div class="total-amount">
                <span class="info-label">Total Pembayaran</span>
                <span class="info-value">
                    Rp <?= number_format($fetch_orders['total_amount'], 0, ',', '.') ?>
                </span>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty">
            <i class="fas fa-shopping-cart"></i>
            <p>Tidak ada pesanan!</p>
            <a href="products.php" class="btn" style="margin-top: 1rem;">Mulai Belanja</a>
        </div>
    <?php endif; ?>
</section>

<?php
    $stmt->close();
    $conn->close();
}
?>

<!-- footer section starts  -->
<?php include 'components/footer.php'; ?>
<!-- footer section ends -->

<!-- custom js file link  -->
<script src="js/script.js"></script>
</body>
</html>