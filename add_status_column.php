<?php
require_once '../components/database.php';

if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Akses ditolak']));
}

// Cek apakah kolom status sudah ada
$check_query = "SHOW COLUMNS FROM pesan_kontak LIKE 'status'";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) > 0) {
    echo json_encode(['success' => false, 'message' => 'Kolom status sudah ada']);
    exit();
}

// Tambahkan kolom status
$alter_query = "ALTER TABLE pesan_kontak ADD COLUMN status ENUM('unread', 'read', 'replied') DEFAULT 'unread' AFTER pesan";

if (mysqli_query($conn, $alter_query)) {
    // Update semua pesan yang ada dengan status 'read' berdasarkan tanggal lama
    $update_query = "UPDATE pesan_kontak SET status = 'read' WHERE tanggal < DATE_SUB(NOW(), INTERVAL 1 DAY)";
    mysqli_query($conn, $update_query);
    
    echo json_encode(['success' => true, 'message' => 'Kolom status berhasil ditambahkan']);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}