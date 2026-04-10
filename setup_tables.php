<?php
require_once 'components/database.php';

echo "<h2>Setup Database Tables</h2>";

// 1. Tabel orders
$sql_orders = "CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_address TEXT,
    payment_method VARCHAR(50),
    notes TEXT,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if (mysqli_query($conn, $sql_orders)) {
    echo "<p style='color:green;'>✓ Tabel 'orders' berhasil dibuat/ditemukan</p>";
} else {
    echo "<p style='color:red;'>✗ Error creating orders table: " . mysqli_error($conn) . "</p>";
}

// 2. Tabel order_items
$sql_order_items = "CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES produk(id)
)";

if (mysqli_query($conn, $sql_order_items)) {
    echo "<p style='color:green;'>✓ Tabel 'order_items' berhasil dibuat/ditemukan</p>";
} else {
    echo "<p style='color:red;'>✗ Error creating order_items table: " . mysqli_error($conn) . "</p>";
}

// 3. Cek struktur tabel
echo "<h3>Struktur Tabel Orders:</h3>";
$result = mysqli_query($conn, "DESCRIBE orders");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Test insert
echo "<h3>Test Insert Data:</h3>";
$test_query = "INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, status) 
               VALUES (1, 10000, 'Test Address', 'transfer', 'pending')";
if (mysqli_query($conn, $test_query)) {
    echo "<p style='color:green;'>✓ Test insert berhasil</p>";
    
    // Hapus test data
    mysqli_query($conn, "DELETE FROM orders WHERE shipping_address = 'Test Address'");
} else {
    echo "<p style='color:red;'>✗ Test insert gagal: " . mysqli_error($conn) . "</p>";
}

echo "<hr>";
echo "<a href='checkout.php'>Kembali ke Checkout</a>";
?>