-- Buat database
CREATE DATABASE IF NOT EXISTS petshop_db;
USE petshop_db;

-- Tabel admin
CREATE TABLE admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel kategori
CREATE TABLE kategori (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel produk
CREATE TABLE produk (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kategori_id INT,
    nama_produk VARCHAR(200) NOT NULL,
    deskripsi TEXT,
    harga DECIMAL(10,2) NOT NULL,
    stok INT DEFAULT 0,
    gambar VARCHAR(255),
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL
);

-- Tabel pesan kontak
CREATE TABLE pesan_kontak (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subjek VARCHAR(200),
    pesan TEXT NOT NULL,
    tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dibaca BOOLEAN DEFAULT FALSE
);
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,  -- total_amount sesuai kode Anda
    shipping_address TEXT,
    payment_method VARCHAR(50),
    notes TEXT,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
-- Buat database
CREATE DATABASE IF NOT EXISTS petshop_db;
USE petshop_db;

-- Tabel users (pengguna)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);   

-- Tabel categories (kategori produk)
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel products (produk)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    category_id INT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Tabel orders (pesanan)
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel order_items (item dalam pesanan)
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Tabel carts (keranjang belanja)
CREATE TABLE carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Tabel messages (pesan dari form kontak)
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert data admin default
INSERT INTO users (username, email, password, full_name, role) 
VALUES ('admin', 'admin@petshop.com', '$2y$10$YourHashedPasswordHere', 'Administrator', 'admin');

-- Insert beberapa kategori
INSERT INTO categories (name, description) VALUES
('Makanan', 'Berbagai macam makanan untuk hewan peliharaan'),
('Aksesoris', 'Aksesoris dan perlengkapan hewan peliharaan'),
('Obat & Vitamin', 'Obat-obatan dan vitamin untuk kesehatan hewan'),
('Mainan', 'Mainan untuk hewan peliharaan'),
('Perawatan', 'Produk perawatan dan kebersihan hewan');

-- Insert beberapa produk
INSERT INTO products (name, description, price, stock, category_id, image) VALUES
('Royal Canin Kitten', 'Makanan kering untuk anak kucing usia 1-12 bulan', 125000, 50, 1, 'royal_canin.jpg'),
('Whiskas Adult 1+', 'Makanan kucing dewasa dengan rasa ikan tuna', 75000, 100, 1, 'whiskas.jpg'),
('Pedigree Adult', 'Makanan anjing dewasa dengan formula lengkap', 90000, 80, 1, 'pedigree.jpg'),
('Kandang Kucing Medium', 'Kandang kucing ukuran medium dengan 2 tingkat', 350000, 15, 2, 'kandang.jpg'),
('Tali Anjing Adjustable', 'Tali anjing adjustable dengan pegangan nyaman', 85000, 40, 2, 'tali_anjing.jpg'),
('Vitamin Kucing', 'Vitamin lengkap untuk kesehatan kucing', 65000, 60, 3, 'vitamin.jpg'),
('Bola Mainan Kucing', 'Bola mainan dengan bulu dan bunyi', 25000, 120, 4, 'bola_mainan.jpg'),
('Shampoo Anjing', 'Shampoo untuk anjing dengan formula hypoallergenic', 55000, 70, 5, 'shampoo.jpg');

-- Insert admin default (password: admin123)
INSERT INTO admin (username, password, nama_lengkap, email) 
VALUES ('admin', '$2y$10$YourHashHere', 'Admin PetShop', 'admin@petshop.com');

-- Insert kategori default
INSERT INTO kategori (nama_kategori, deskripsi) VALUES
('Makanan Anjing', 'Berbagai jenis makanan untuk anjing'),
('Makanan Kucing', 'Berbagai jenis makanan untuk kucing'),
('Aksesoris', 'Aksesoris hewan peliharaan'),
('Perlengkapan', 'Perlengkapan perawatan hewan'),
('Kesehatan', 'Produk kesehatan hewan');

-- Insert produk contoh
INSERT INTO produk (kategori_id, nama_produk, deskripsi, harga, stok, gambar, is_featured) VALUES
(1, 'Royal Canin Maxi Adult', 'Makanan anjing dewasa ras besar, 15kg', 650000, 25, 'dog-food-1.jpg', 1),
(2, 'Whiskas 1+ Tahun', 'Makanan kucing dewasa, rasa tuna, 1.2kg', 85000, 50, 'cat-food-1.jpg', 1),
(3, 'Kalung Anjing Premium', 'Kalung kulit dengan name tag', 120000, 30, 'accessory-1.jpg', 1),
(4, 'Kandang Kucing Portable', 'Kandang kucing lipat untuk perjalanan', 350000, 15, 'cage-1.jpg', 0),
(5, 'Shampoo Anjing Antikutu', 'Shampoo khusus anjing dengan formula antikutu', 75000, 40, 'shampoo-1.jpg', 1);