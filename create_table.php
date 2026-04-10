<?php
require_once 'components/database.php';

$sql = "CREATE TABLE IF NOT EXISTS pesan_kontak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telepon VARCHAR(20) NOT NULL,
    subjek VARCHAR(255) NOT NULL,
    pesan TEXT NOT NULL,
    tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('unread', 'read', 'replied') DEFAULT 'unread'
)";

if (mysqli_query($conn, $sql)) {
    echo "Tabel pesan_kontak berhasil dibuat/diperbarui!<br>";
    
    // Cek apakah kolom subjek ada, jika tidak tambahkan
    $check = mysqli_query($conn, "SHOW COLUMNS FROM pesan_kontak LIKE 'subjek'");
    if (mysqli_num_rows($check) == 0) {
        $alter_sql = "ALTER TABLE pesan_kontak ADD COLUMN subjek VARCHAR(255) AFTER telepon";
        if (mysqli_query($conn, $alter_sql)) {
            echo "Kolom 'subjek' berhasil ditambahkan!";
        } else {
            echo "Error menambahkan kolom: " . mysqli_error($conn);
        }
    } else {
        echo "Kolom 'subjek' sudah ada.";
    }
} else {
    echo "Error: " . mysqli_error($conn);
}
?>