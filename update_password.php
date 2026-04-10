<?php
require_once '../components/database.php';

if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = $_SESSION['user_id'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon'] ?? '');
    
    $query = "UPDATE users SET nama = '$nama', email = '$email', telepon = '$telepon' WHERE id = '$admin_id'";
    
    if (mysqli_query($conn, $query)) {
        // Update session
        $_SESSION['nama'] = $nama;
        $_SESSION['email'] = $email;
        
        // Log activity
        $log_query = "INSERT INTO admin_log (admin_id, action, description) VALUES ('$admin_id', 'update', 'Admin mengupdate profil')";
        mysqli_query($conn, $log_query);
        
        header('Location: profil_admin.php?success=1');
    } else {
        header('Location: profil_admin.php?error=' . urlencode(mysqli_error($conn)));
    }
}