<?php
require_once '../components/database.php';

if (!isset($_SESSION)) {
    session_start();
}

echo "<h1>Debug Detail Pesanan</h1>";

// Cek session
echo "<h3>1. Cek Session:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Tidak ada') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'Tidak ada') . "<br>";

// Cek koneksi database
echo "<h3>2. Cek Koneksi Database:</h3>";
if ($conn) {
    echo "Koneksi database OK<br>";
} else {
    echo "Koneksi database GAGAL<br>";
}

// Cek parameter ID
$order_id = intval($_GET['id'] ?? 0);
echo "<h3>3. Parameter ID:</h3>";
echo "Order ID dari URL: " . $order_id . "<br>";

// Cek semua tabel di database
echo "<h3>4. Cek Tabel Database:</h3>";
$tables_query = "SHOW TABLES";
$tables_result = mysqli_query($conn, $tables_query);
echo "Tabel yang tersedia:<br>";
while ($row = mysqli_fetch_array($tables_result)) {
    echo "- " . $row[0] . "<br>";
}

// Cek struktur tabel orders jika ada
echo "<h3>5. Cek Struktur Tabel orders:</h3>";
$structure_query = "DESCRIBE orders";
$structure_result = mysqli_query($conn, $structure_query);
if ($structure_result && mysqli_num_rows($structure_result) > 0) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($structure_result)) {
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
} else {
    echo "Tabel orders tidak ditemukan atau error: " . mysqli_error($conn) . "<br>";
}

// Cek data pesanan dengan ID tersebut
if ($order_id > 0) {
    echo "<h3>6. Cek Data Pesanan dengan ID $order_id:</h3>";
    $check_query = "SELECT * FROM orders WHERE id = $order_id";
    $check_result = mysqli_query($conn, $check_query);
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $order = mysqli_fetch_assoc($check_result);
        echo "Data ditemukan:<br>";
        echo "<pre>";
        print_r($order);
        echo "</pre>";
    } else {
        echo "Tidak ada data dengan ID $order_id<br>";
        echo "Error: " . mysqli_error($conn) . "<br>";
    }
}

// Cek tabel pengguna
echo "<h3>7. Cek Tabel pengguna:</h3>";
$users_query = "DESCRIBE pengguna";
$users_result = mysqli_query($conn, $users_query);
if ($users_result && mysqli_num_rows($users_result) > 0) {
    echo "Tabel pengguna OK<br>";
} else {
    echo "Tabel pengguna tidak ditemukan<br>";
}

// Cek join antara orders dan pengguna
if ($order_id > 0) {
    echo "<h3>8. Cek JOIN orders dan pengguna:</h3>";
    $join_query = "SELECT o.*, u.nama, u.email FROM orders o JOIN pengguna u ON o.user_id = u.id WHERE o.id = $order_id";
    $join_result = mysqli_query($conn, $join_query);
    if ($join_result) {
        if (mysqli_num_rows($join_result) > 0) {
            $join_data = mysqli_fetch_assoc($join_result);
            echo "JOIN berhasil:<br>";
            echo "<pre>";
            print_r($join_data);
            echo "</pre>";
        } else {
            echo "JOIN tidak menghasilkan data<br>";
            echo "Error: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "Error JOIN: " . mysqli_error($conn) . "<br>";
    }
}

echo "<hr>";
echo "<h2>Link Testing:</h2>";
echo "<a href='pesanan_detail.php?id=1'>Test dengan ID 1</a><br>";
echo "<a href='pesanan_detail.php?id=2'>Test dengan ID 2</a><br>";
echo "<a href='pesanan_detail.php?id=3'>Test dengan ID 3</a><br>";
?>