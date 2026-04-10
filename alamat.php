<?php
require_once 'components/database.php';
require_once 'components/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle address actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_address'])) {
        $label = clean_input($_POST['label']);
        $nama_penerima = clean_input($_POST['nama_penerima']);
        $telepon = clean_input($_POST['telepon']);
        $alamat = clean_input($_POST['alamat']);
        $kota = clean_input($_POST['kota']);
        $provinsi = clean_input($_POST['provinsi']);
        $kode_pos = clean_input($_POST['kode_pos']);
        $utama = isset($_POST['utama']) ? 1 : 0;
        
        // If this is main address, unset others and update users table
        if ($utama) {
            // Unset semua alamat utama lainnya
            $update_query = "UPDATE user_addresses SET utama = 0 WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            
            // Update alamat utama di tabel users
            $update_user_query = "UPDATE users SET alamat = ? WHERE id = ?";
            $stmt_user = mysqli_prepare($conn, $update_user_query);
            mysqli_stmt_bind_param($stmt_user, "si", $alamat, $user_id);
            mysqli_stmt_execute($stmt_user);
        }
        
        $insert_query = "INSERT INTO user_addresses (user_id, label, nama_penerima, telepon, alamat, kota, provinsi, kode_pos, utama) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "isssssssi", $user_id, $label, $nama_penerima, $telepon, $alamat, $kota, $provinsi, $kode_pos, $utama);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Alamat berhasil ditambahkan!';
        } else {
            $error = 'Gagal menambahkan alamat!';
        }
        
    } elseif (isset($_POST['update_address'])) {
        $address_id = intval($_POST['address_id']);
        $label = clean_input($_POST['label']);
        $nama_penerima = clean_input($_POST['nama_penerima']);
        $telepon = clean_input($_POST['telepon']);
        $alamat = clean_input($_POST['alamat']);
        $kota = clean_input($_POST['kota']);
        $provinsi = clean_input($_POST['provinsi']);
        $kode_pos = clean_input($_POST['kode_pos']);
        $utama = isset($_POST['utama']) ? 1 : 0;
        
        // If this is main address, unset others and update users table
        if ($utama) {
            // Unset semua alamat utama lainnya
            $update_query = "UPDATE user_addresses SET utama = 0 WHERE user_id = ? AND id != ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "ii", $user_id, $address_id);
            mysqli_stmt_execute($stmt);
            
            // Update alamat utama di tabel users
            $update_user_query = "UPDATE users SET alamat = ? WHERE id = ?";
            $stmt_user = mysqli_prepare($conn, $update_user_query);
            mysqli_stmt_bind_param($stmt_user, "si", $alamat, $user_id);
            mysqli_stmt_execute($stmt_user);
        }
        
        $update_query = "UPDATE user_addresses SET label = ?, nama_penerima = ?, telepon = ?, alamat = ?, kota = ?, provinsi = ?, kode_pos = ?, utama = ? 
                        WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "sssssssiii", $label, $nama_penerima, $telepon, $alamat, $kota, $provinsi, $kode_pos, $utama, $address_id, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Alamat berhasil diperbarui!';
        } else {
            $error = 'Gagal memperbarui alamat!';
        }
        
    } elseif (isset($_POST['delete_address'])) {
        $address_id = intval($_POST['address_id']);
        
        // Cek apakah alamat yang akan dihapus adalah alamat utama
        $check_query = "SELECT utama FROM user_addresses WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "ii", $address_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $address_data = mysqli_fetch_assoc($result);
        
        if ($address_data && $address_data['utama'] == 1) {
            // Cari alamat lain untuk dijadikan utama
            $find_new_main_query = "SELECT id FROM user_addresses WHERE user_id = ? AND id != ? ORDER BY created_at DESC LIMIT 1";
            $stmt_find = mysqli_prepare($conn, $find_new_main_query);
            mysqli_stmt_bind_param($stmt_find, "ii", $user_id, $address_id);
            mysqli_stmt_execute($stmt_find);
            $find_result = mysqli_stmt_get_result($stmt_find);
            $new_main = mysqli_fetch_assoc($find_result);
            
            if ($new_main) {
                // Update alamat baru sebagai utama
                $update_main_query = "UPDATE user_addresses SET utama = 1 WHERE id = ?";
                $stmt_update = mysqli_prepare($conn, $update_main_query);
                mysqli_stmt_bind_param($stmt_update, "i", $new_main['id']);
                mysqli_stmt_execute($stmt_update);
                
                // Update alamat di tabel users
                $get_address_query = "SELECT alamat FROM user_addresses WHERE id = ?";
                $stmt_get = mysqli_prepare($conn, $get_address_query);
                mysqli_stmt_bind_param($stmt_get, "i", $new_main['id']);
                mysqli_stmt_execute($stmt_get);
                $address_result = mysqli_stmt_get_result($stmt_get);
                $new_address_data = mysqli_fetch_assoc($address_result);
                
                if ($new_address_data) {
                    $update_user_query = "UPDATE users SET alamat = ? WHERE id = ?";
                    $stmt_user = mysqli_prepare($conn, $update_user_query);
                    mysqli_stmt_bind_param($stmt_user, "si", $new_address_data['alamat'], $user_id);
                    mysqli_stmt_execute($stmt_user);
                }
            } else {
                // Tidak ada alamat lain, kosongkan alamat di users
                $update_user_query = "UPDATE users SET alamat = NULL WHERE id = ?";
                $stmt_user = mysqli_prepare($conn, $update_user_query);
                mysqli_stmt_bind_param($stmt_user, "i", $user_id);
                mysqli_stmt_execute($stmt_user);
            }
        }
        
        $delete_query = "DELETE FROM user_addresses WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "ii", $address_id, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Alamat berhasil dihapus!';
        } else {
            $error = 'Gagal menghapus alamat!';
        }
        
    } elseif (isset($_POST['set_main_address'])) {
        $address_id = intval($_POST['address_id']);
        
        // Dapatkan alamat yang akan dijadikan utama
        $get_address_query = "SELECT alamat FROM user_addresses WHERE id = ? AND user_id = ?";
        $stmt_get = mysqli_prepare($conn, $get_address_query);
        mysqli_stmt_bind_param($stmt_get, "ii", $address_id, $user_id);
        mysqli_stmt_execute($stmt_get);
        $address_result = mysqli_stmt_get_result($stmt_get);
        $address_data = mysqli_fetch_assoc($address_result);

        if ($address_data) {
            // Unset semua alamat utama lainnya
            $update_query = "UPDATE user_addresses SET utama = 0 WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            
            // Set alamat baru sebagai utama
            $update_query = "UPDATE user_addresses SET utama = 1 WHERE id = ? AND user_id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "ii", $address_id, $user_id);
            mysqli_stmt_execute($stmt);
            
            // Update alamat utama di tabel users
            $update_user_query = "UPDATE users SET alamat = ? WHERE id = ?";
            $stmt_user = mysqli_prepare($conn, $update_user_query);
            mysqli_stmt_bind_param($stmt_user, "si", $address_data['alamat'], $user_id);
            mysqli_stmt_execute($stmt_user);
            
            $success = 'Alamat utama berhasil diubah!';
        } else {
            $error = 'Gagal mengubah alamat utama!';
        }
    }
    
    // Refresh page
    header('Location: alamat.php');
    exit();
}

// Get user addresses
$query = "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY utama DESC, created_at DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$address_result = mysqli_stmt_get_result($stmt);
$addresses = mysqli_fetch_all($address_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Alamat - MinShop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .address-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .address-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .address-header h1 {
            color: #333;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-add-address {
            background: linear-gradient(135deg, #4361ee 0%, #3a56d4 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-add-address:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .address-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .address-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 2px solid #e9ecef;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .address-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .address-card.utama {
            border-color: #4361ee;
            border-width: 3px;
        }
        
        .address-label {
            display: inline-block;
            background: #4361ee;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .address-card.utama .address-label {
            background: #f72585;
        }
        
        .address-info h3 {
            margin: 10px 0;
            color: #333;
            font-size: 1.2rem;
        }
        
        .address-info p {
            margin: 8px 0;
            color: #666;
            line-height: 1.5;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        
        .address-info p i {
            margin-top: 3px;
            flex-shrink: 0;
        }
        
        .address-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .btn-action {
            flex: 1;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .btn-edit {
            background: #e9ecef;
            color: #333;
        }
        
        .btn-edit:hover {
            background: #dee2e6;
        }
        
        .btn-delete {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn-delete:hover {
            background: #f5c6cb;
        }
        
        .btn-set-main {
            background: #d4edda;
            color: #155724;
        }
        
        .btn-set-main:hover {
            background: #c3e6cb;
        }
        
        .no-address {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-top: 20px;
        }
        
        .no-address i {
            font-size: 64px;
            color: #e9ecef;
            margin-bottom: 20px;
        }
        
        .no-address h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.5rem;
        }
        
        .no-address p {
            color: #666;
            margin-bottom: 30px;
            font-size: 1rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h3 {
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            line-height: 1;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .text-muted {
            color: #6c757d;
            font-size: 0.875rem;
            margin-top: 5px;
            display: block;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .btn-submit {
            flex: 1;
            background: #4361ee;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            background: #3a56d4;
        }
        
        .btn-cancel {
            flex: 1;
            background: #e9ecef;
            color: #333;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-cancel:hover {
            background: #dee2e6;
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 1100;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 400px;
        }
        
        .notification.success {
            background: #4cc9f0;
        }
        
        .notification.error {
            background: #f72585;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @media (max-width: 768px) {
            .address-grid {
                grid-template-columns: 1fr;
            }
            
            .address-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .address-actions {
                flex-direction: column;
            }
            
            .modal-content {
                padding: 20px;
                max-height: 95vh;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/header_pengguna.php'; ?>
    
    <div class="address-container">
        <div class="address-header">
            <h1><i class="fas fa-map-marker-alt"></i> Alamat Saya</h1>
            <button class="btn-add-address" onclick="showAddModal()">
                <i class="fas fa-plus"></i> Tambah Alamat Baru
            </button>
        </div>
        
        <?php if (empty($addresses)): ?>
        <div class="no-address">
            <i class="fas fa-map-marked-alt"></i>
            <h3>Belum Ada Alamat</h3>
            <p>Tambahkan alamat pengiriman untuk memudahkan berbelanja</p>
            <button class="btn-add-address" onclick="showAddModal()">
                <i class="fas fa-plus"></i> Tambah Alamat Pertama
            </button>
        </div>
        <?php else: ?>
        <div class="address-grid">
            <?php foreach ($addresses as $address): ?>
            <div class="address-card <?php echo $address['utama'] ? 'utama' : ''; ?>">
                <span class="address-label">
                    <?php echo htmlspecialchars($address['label']); ?>
                    <?php if ($address['utama']): ?>
                    <i class="fas fa-star" style="margin-left: 5px;"></i>
                    <?php endif; ?>
                </span>
                
                <div class="address-info">
                    <h3><?php echo htmlspecialchars($address['nama_penerima']); ?></h3>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($address['telepon']); ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($address['alamat']); ?></p>
                    <p><i class="fas fa-city"></i> <?php echo htmlspecialchars($address['kota']); ?>, <?php echo htmlspecialchars($address['provinsi']); ?> - <?php echo htmlspecialchars($address['kode_pos']); ?></p>
                </div>
                
                <div class="address-actions">
                    <button class="btn-action btn-edit" onclick="showEditModal(<?php echo $address['id']; ?>)">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    
                    <?php if (!$address['utama']): ?>
                    <form method="POST" style="display: inline; flex: 1;">
                        <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                        <button type="submit" name="set_main_address" class="btn-action btn-set-main">
                            <i class="fas fa-star"></i> Jadikan Utama
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <form method="POST" style="display: inline; flex: 1;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus alamat ini?');">
                        <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                        <button type="submit" name="delete_address" class="btn-action btn-delete">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Add Address Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Tambah Alamat Baru</h3>
                <button class="modal-close" onclick="closeAddModal()">&times;</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label for="label">Label Alamat *</label>
                    <input type="text" id="label" name="label" class="form-control" placeholder="Contoh: Rumah, Kantor, Kos" required>
                </div>
                
                <div class="form-group">
                    <label for="nama_penerima">Nama Penerima *</label>
                    <input type="text" id="nama_penerima" name="nama_penerima" class="form-control" placeholder="Nama lengkap penerima" required>
                </div>
                
                <div class="form-group">
                    <label for="telepon">Nomor Telepon *</label>
                    <input type="tel" id="telepon" name="telepon" class="form-control" placeholder="0812-3456-7890" required>
                </div>
                
                <div class="form-group">
                    <label for="alamat">Alamat Lengkap *</label>
                    <textarea id="alamat" name="alamat" class="form-control" rows="3" placeholder="Jl. Contoh No. 123, RT/RW, Kelurahan" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="kota">Kota/Kabupaten *</label>
                    <input type="text" id="kota" name="kota" class="form-control" placeholder="Kota/Kabupaten" required>
                </div>
                
                <div class="form-group">
                    <label for="provinsi">Provinsi *</label>
                    <input type="text" id="provinsi" name="provinsi" class="form-control" placeholder="Provinsi" required>
                </div>
                
                <div class="form-group">
                    <label for="kode_pos">Kode Pos *</label>
                    <input type="text" id="kode_pos" name="kode_pos" class="form-control" placeholder="12345" required>
                </div>
                
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="utama" id="utama" class="form-check-input">
                        Jadikan sebagai alamat utama
                    </label>
                    <span class="text-muted">Alamat utama akan ditampilkan di profil Anda</span>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeAddModal()">Batal</button>
                    <button type="submit" name="add_address" class="btn-submit">Simpan Alamat</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Address Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Alamat</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="address_id" id="edit_address_id">
                <input type="hidden" name="update_address" value="1">
                
                <div class="form-group">
                    <label for="edit_label">Label Alamat *</label>
                    <input type="text" id="edit_label" name="label" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_nama_penerima">Nama Penerima *</label>
                    <input type="text" id="edit_nama_penerima" name="nama_penerima" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_telepon">Nomor Telepon *</label>
                    <input type="tel" id="edit_telepon" name="telepon" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_alamat">Alamat Lengkap *</label>
                    <textarea id="edit_alamat" name="alamat" class="form-control" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_kota">Kota/Kabupaten *</label>
                    <input type="text" id="edit_kota" name="kota" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_provinsi">Provinsi *</label>
                    <input type="text" id="edit_provinsi" name="provinsi" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_kode_pos">Kode Pos *</label>
                    <input type="text" id="edit_kode_pos" name="kode_pos" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="utama" id="edit_utama" class="form-check-input">
                        Jadikan sebagai alamat utama
                    </label>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Batal</button>
                    <button type="submit" class="btn-submit">Update Alamat</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Modal functions
        function showAddModal() {
            document.getElementById('addModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
            document.body.style.overflow = 'auto';
            document.getElementById('addModal').querySelector('form').reset();
        }
        
        function showEditModal(addressId) {
            // Create a simple form for editing (simplified version)
            // In production, you should use AJAX to fetch the address data
            alert('Fitur edit alamat sedang dalam pengembangan. Untuk saat ini, silakan hapus dan buat alamat baru.');
            
            // Uncomment this code when you implement AJAX:
            /*
            fetch('get_address.php?id=' + addressId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_address_id').value = data.id;
                        document.getElementById('edit_label').value = data.label;
                        document.getElementById('edit_nama_penerima').value = data.nama_penerima;
                        document.getElementById('edit_telepon').value = data.telepon;
                        document.getElementById('edit_alamat').value = data.alamat;
                        document.getElementById('edit_kota').value = data.kota;
                        document.getElementById('edit_provinsi').value = data.provinsi;
                        document.getElementById('edit_kode_pos').value = data.kode_pos;
                        document.getElementById('edit_utama').checked = data.utama == 1;
                        
                        document.getElementById('editModal').classList.add('active');
                        document.body.style.overflow = 'hidden';
                    } else {
                        alert('Gagal memuat data alamat');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memuat data');
                });
            */
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.classList.remove('active');
                    document.body.style.overflow = 'auto';
                }
            });
        }
        
        // Auto-remove notifications after 5 seconds
        setTimeout(() => {
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(notification => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            });
        }, 5000);
        
        // Prevent form submission when pressing Enter in non-submit inputs
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && e.target.type !== 'submit') {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>