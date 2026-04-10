<?php
require_once '../components/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../admin_login.php');
    exit();
}

$user_id = intval($_GET['id'] ?? 0);

if ($user_id == 0) {
    header('Location: pengguna.php');
    exit();
}

// Get user data
$query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    header('Location: pengguna.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = clean_input($_POST['nama']);
    $email = clean_input($_POST['email']);
    $telepon = clean_input($_POST['telepon']);
    $alamat = clean_input($_POST['alamat']);
    $role = clean_input($_POST['role']);
    
    // Check if email is changed and exists
    if ($email != $user['email']) {
        $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $_SESSION['error'] = 'Email sudah digunakan oleh pengguna lain!';
            header("Location: edit_user.php?id=$user_id");
            exit();
        }
    }
    
    // Handle password change if provided
    if (!empty($_POST['password'])) {
        if ($_POST['password'] !== $_POST['confirm_password']) {
            $_SESSION['error'] = 'Password tidak cocok!';
            header("Location: edit_user.php?id=$user_id");
            exit();
        }
        
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $update_query = "UPDATE users SET nama = ?, email = ?, telepon = ?, alamat = ?, role = ?, password = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ssssssi", $nama, $email, $telepon, $alamat, $role, $hashed_password, $user_id);
    } else {
        $update_query = "UPDATE users SET nama = ?, email = ?, telepon = ?, alamat = ?, role = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "sssssi", $nama, $email, $telepon, $alamat, $role, $user_id);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = 'Data pengguna berhasil diperbarui!';
        header('Location: pengguna.php');
        exit();
    } else {
        $_SESSION['error'] = 'Gagal memperbarui data pengguna!';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pengguna - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    .edit-user-page {
        padding: 20px;
    }
    
    .back-button {
        margin-bottom: 20px;
    }
    
    .user-header {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border);
    }
    
    .user-avatar-large {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 2rem;
    }
    
    .edit-form-container {
        background: white;
        padding: 30px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
    }
    
    .form-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 30px;
        border-bottom: 1px solid var(--border);
        padding-bottom: 10px;
    }
    
    .tab-button {
        padding: 10px 20px;
        background: none;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        font-weight: 500;
        color: var(--text);
    }
    
    .tab-button.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .password-note {
        background: #f8f9fa;
        padding: 15px;
        border-radius: var(--radius);
        margin-bottom: 20px;
        font-size: 0.9rem;
        color: var(--text);
    }
    
    .password-note i {
        color: var(--warning);
        margin-right: 8px;
    }
    </style>
</head>
<body class="admin-body">
    <?php include '../components/sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="edit-user-page">
            <!-- Back Button -->
            <div class="back-button">
                <a href="pengguna.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar Pengguna
                </a>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
            <?php endif; ?>
            
            <!-- User Header -->
            <div class="user-header">
                <div class="user-avatar-large">
                    <?php echo strtoupper(substr($user['nama'], 0, 2)); ?>
                </div>
                <div>
                    <h1>Edit Pengguna: <?php echo $user['nama']; ?></h1>
                    <p>ID: #<?php echo str_pad($user['id'], 3, '0', STR_PAD_LEFT); ?> | 
                       Role: <span class="role-badge role-<?php echo $user['role']; ?>">
                           <?php echo $user['role'] == 'admin' ? 'Admin' : 'Pelanggan'; ?>
                       </span>
                    </p>
                </div>
            </div>
            
            <!-- Edit Form -->
            <div class="edit-form-container">
                <div class="form-tabs">
                    <button type="button" class="tab-button active" onclick="showTab('profile')">
                        <i class="fas fa-user"></i> Profil
                    </button>
                    <button type="button" class="tab-button" onclick="showTab('password')">
                        <i class="fas fa-lock"></i> Password
                    </button>
                </div>
                
                <form method="POST" action="" id="editUserForm">
                    <!-- Profile Tab -->
                    <div id="profileTab" class="tab-content active">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nama">Nama Lengkap *</label>
                                <input type="text" id="nama" name="nama" class="form-control" required
                                       value="<?php echo htmlspecialchars($user['nama']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" class="form-control" required
                                       value="<?php echo htmlspecialchars($user['email']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="telepon">Telepon</label>
                                <input type="tel" id="telepon" name="telepon" class="form-control"
                                       value="<?php echo htmlspecialchars($user['telepon'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="role">Role *</label>
                                <select id="role" name="role" class="form-control" required>
                                    <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>Pelanggan</option>
                                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="alamat">Alamat</label>
                            <textarea id="alamat" name="alamat" class="form-control" rows="4"><?php echo htmlspecialchars($user['alamat'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Password Tab -->
                    <div id="passwordTab" class="tab-content">
                        <div class="password-note">
                            <i class="fas fa-info-circle"></i>
                            Kosongkan field password jika tidak ingin mengubah password.
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Password Baru</label>
                                <input type="password" id="password" name="password" class="form-control"
                                       placeholder="Kosongkan jika tidak diubah">
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Konfirmasi Password Baru</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                       placeholder="Kosongkan jika tidak diubah">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                        <a href="pengguna.php" class="btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <script>
    function showTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Remove active class from all buttons
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
        });
        
        // Show selected tab
        document.getElementById(tabName + 'Tab').classList.add('active');
        
        // Activate selected button
        event.target.classList.add('active');
    }
    
    // Form validation
    document.getElementById('editUserForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (password && password.length < 6) {
            e.preventDefault();
            alert('Password minimal 6 karakter!');
            return false;
        }
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Password tidak cocok!');
            return false;
        }
        
        return true;
    });
    </script>
</body>
</html>