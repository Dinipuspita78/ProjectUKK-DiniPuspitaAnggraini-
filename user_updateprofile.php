<?php
require_once '../components/database.php';
require_once '../components/functions.php';

if (!isset($_SESSION)) {
    session_start();
}

// Cek login user
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_updateprofile'])) {
    // Clean input data
    $nama = clean_input($_POST['nama']);
    $email = clean_input($_POST['email']);
    $telepon = clean_input($_POST['telepon'] ?? '');
    $alamat = clean_input($_POST['alamat'] ?? '');
    
    // Validasi
    if (empty($nama) || empty($email)) {
        header('Location: profil.php?error=' . urlencode('Nama dan email wajib diisi'));
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: profil.php?error=' . urlencode('Format email tidak valid'));
        exit();
    }
    
    // Cek email jika berubah
    if ($email !== $_SESSION['email']) {
        $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            header('Location: profil.php?error=' . urlencode('Email sudah digunakan oleh pengguna lain'));
            exit();
        }
    }
    
    // Update profile menggunakan prepared statement
    $update_query = "UPDATE pengguna SET 
                    nama = ?, 
                    email = ?, 
                    telepon = ?, 
                    alamat = ?,
                    updated_at = NOW()
                    WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "ssssi", $nama, $email, $telepon, $alamat, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Update session
        $_SESSION['nama'] = $nama;
        $_SESSION['email'] = $email;
        $_SESSION['telepon'] = $telepon;
        
        // Simpan log perubahan (opsional)
        $log_query = "INSERT INTO user_logs (user_id, action, details) 
                     VALUES (?, 'update_profile', ?)";
        $stmt_log = mysqli_prepare($conn, $log_query);
        $details = "Mengupdate profil: nama, email, telepon, alamat";
        mysqli_stmt_bind_param($stmt_log, "is", $user_id, $details);
        mysqli_stmt_execute($stmt_log);
        
        header('Location: profil.php?success=' . urlencode('Profil berhasil diperbarui'));
        exit();
    } else {
        header('Location: profil.php?error=' . urlencode('Gagal memperbarui profil'));
        exit();
    }
}

// Redirect jika akses langsung
header('Location: profil.php');
exit();
?>