<?php
require_once '../components/database.php';

if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../admin_login.php');
    exit();
}

// Ambil semua pesan
$query = "SELECT * FROM pesan_kontak ORDER BY tanggal DESC";
$result = mysqli_query($conn, $query);

// Set header untuk download file CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=pesan_kontak_' . date('Y-m-d') . '.csv');

// Buat output stream
$output = fopen('php://output', 'w');

// Header CSV
fputcsv($output, ['ID', 'Nama', 'Email', 'Telepon', 'Subjek', 'Pesan', 'Tanggal', 'Status']);

// Data CSV
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['id'],
        $row['nama'],
        $row['email'],
        $row['telepon'],
        $row['subjek'],
        $row['pesan'],
        $row['tanggal'],
        $row['status']
    ]);
}

fclose($output);
exit();