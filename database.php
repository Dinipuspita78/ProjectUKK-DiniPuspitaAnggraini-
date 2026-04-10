<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$host = "localhost";
$username = "root";
$password = "";
$database = "petshp_db";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Buat database dan tabel jika belum ada
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if (mysqli_query($conn, $sql)) {
    mysqli_select_db($conn, $database);
    
    // Tabel users - PERBAIKI SINI
    $users_table = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        telepon VARCHAR(20),
        alamat TEXT,
        role ENUM('user', 'admin', 'kurir') DEFAULT 'user', -- PERBAIKAN: 'admin','kurir' menjadi 'admin', 'kurir'
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    // Tabel produk
    $produk_table = "CREATE TABLE IF NOT EXISTS produk (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(200) NOT NULL,
        deskripsi TEXT,
        harga DECIMAL(10,2) NOT NULL,
        stok INT DEFAULT 0,
        kategori VARCHAR(50),
        gambar VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    // Tabel pesanan - TAMBAHKAN kolom kurir_id
    $pesanan_table = "CREATE TABLE IF NOT EXISTS pesanan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        kurir_id INT NULL, -- TAMBAHKAN: untuk mencatat kurir yang mengantar
        total_harga DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'diproses', 'dikirim', 'selesai') DEFAULT 'pending',
        alamat_pengiriman TEXT,
        catatan_kurir TEXT, -- TAMBAHKAN: catatan dari kurir
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (kurir_id) REFERENCES users(id) ON DELETE SET NULL -- TAMBAHKAN foreign key
    )";
    
    // Tabel detail_pesanan
    $detail_pesanan_table = "CREATE TABLE IF NOT EXISTS detail_pesanan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pesanan_id INT,
        produk_id INT,
        jumlah INT,
        harga DECIMAL(10,2),
        FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE,
        FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE
    )";
    
    // Tabel keranjang
    $keranjang_table = "CREATE TABLE IF NOT EXISTS keranjang (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        produk_id INT,
        jumlah INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE
    )";
    
    // Tabel orders (untuk sistem internasional)
    $orders_table = "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        total_amount DECIMAL(10,2) NOT NULL,
        shipping_address TEXT,
        payment_method VARCHAR(50),
        notes TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    // Tabel order_items
    $order_items_table = "CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES produk(id)
    )";
    
    // Tabel tracking pengiriman
    $tracking_table = "CREATE TABLE IF NOT EXISTS pengiriman_tracking (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pesanan_id INT,
        kurir_id INT,
        status VARCHAR(50) NOT NULL,
        lokasi VARCHAR(255),
        catatan TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE,
        FOREIGN KEY (kurir_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    // Tabel kendaraan kurir (optional)
    $kendaraan_table = "CREATE TABLE IF NOT EXISTS kendaraan_kurir (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kurir_id INT UNIQUE,
        jenis_kendaraan VARCHAR(50),
        plat_nomor VARCHAR(20),
        kapasitas INT,
        status ENUM('tersedia', 'sibuk', 'perbaikan') DEFAULT 'tersedia',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (kurir_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $activity_table = "CREATE TABLE IF NOT EXISTS admin_activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT,
        activity_type VARCHAR(50) NOT NULL,
        description TEXT NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    function logAdminActivity($admin_id, $activity_type, $description) {
        global $conn;
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $query = "INSERT INTO admin_activity_log (admin_id, activity_type, description, ip_address, user_agent) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "issss", $admin_id, $activity_type, $description, $ip_address, $user_agent);
        mysqli_stmt_execute($stmt);
    }
    
    $tables = [
        $users_table, 
        $produk_table, 
        $pesanan_table, 
        $detail_pesanan_table, 
        $keranjang_table,
        $orders_table,
        $order_items_table,
        $tracking_table,
        $kendaraan_table,
        $activity_table
    ];
    
    foreach ($tables as $table) {
        if (!mysqli_query($conn, $table)) {
            // Hanya tampilkan error jika bukan "table already exists"
            if (strpos(mysqli_error($conn), 'already exists') === false) {
                error_log("Error creating table: " . mysqli_error($conn));
            }
        }
    }
    
    // Buat admin default jika belum ada
    $check_admin = mysqli_query($conn, "SELECT * FROM users WHERE email='admin@petshop.com'");
    if (mysqli_num_rows($check_admin) == 0) {
        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
        mysqli_query($conn, "INSERT INTO users (nama, email, password, role) VALUES ('Admin', 'admin@petshop.com', '$hashed_password', 'admin')");
    }
    
    // Buat user default untuk testing
    $check_user = mysqli_query($conn, "SELECT * FROM users WHERE email='user@example.com'");
    if (mysqli_num_rows($check_user) == 0) {
        $hashed_password = password_hash('user123', PASSWORD_DEFAULT);
        mysqli_query($conn, "INSERT INTO users (nama, email, password, role) VALUES ('User Demo', 'user@example.com', '$hashed_password', 'user')");
    }
    
    // Buat kurir default untuk testing
    $check_kurir = mysqli_query($conn, "SELECT * FROM users WHERE email='kurir@petshop.com'");
    if (mysqli_num_rows($check_kurir) == 0) {
        $hashed_password = password_hash('kurir123', PASSWORD_DEFAULT);
        mysqli_query($conn, "INSERT INTO users (nama, email, password, telepon, alamat, role) VALUES 
            ('Kurir Demo', 'kurir@petshop.com', '$hashed_password', '081234567890', 'Jl. Pengiriman No. 123', 'kurir')");
    }
    
} else {
    die("Error creating database: " . mysqli_error($conn));
}
?>