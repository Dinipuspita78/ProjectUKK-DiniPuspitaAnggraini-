<?php
require_once '../components/database.php';
require_once '../components/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../admin_login.php');
    exit();
}

$action = $_GET['action'] ?? '';
$product_id = $_GET['id'] ?? 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'add') {
        // Add new product
        $nama = clean_input($_POST['nama']);
        $deskripsi = clean_input($_POST['deskripsi']);
        $harga = floatval($_POST['harga']);
        $stok = intval($_POST['stok']);
        $kategori = clean_input($_POST['kategori']);
        
        // Handle image upload
        $gambar = 'project images';
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../project images/';
            $file_extension = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            // Allowed file types
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array(strtolower($file_extension), $allowed_types)) {
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                    $gambar = $new_filename;
                }
            }
        }
        
        $query = "INSERT INTO produk (nama, deskripsi, harga, stok, kategori, gambar) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssdiss", $nama, $deskripsi, $harga, $stok, $kategori, $gambar);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'Produk berhasil ditambahkan!';
            header('Location: produk.php');
            exit();
        }
        
    } elseif ($_POST['action'] === 'edit') {
        // Edit product
        $product_id = intval($_POST['product_id']);
        $nama = clean_input($_POST['nama']);
        $deskripsi = clean_input($_POST['deskripsi']);
        $harga = floatval($_POST['harga']);
        $stok = intval($_POST['stok']);
        $kategori = clean_input($_POST['kategori']);
        
        // Handle image upload
        $gambar = $_POST['current_gambar'];
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../project images/';
            $file_extension = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            // Allowed file types
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array(strtolower($file_extension), $allowed_types)) {
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                    // Delete old image if not default
                    if ($gambar != 'project images') {
                        unlink($upload_dir . $gambar);
                    }
                    $gambar = $new_filename;
                }
            }
        }
        
        $query = "UPDATE produk SET nama = ?, deskripsi = ?, harga = ?, 
                  stok = ?, kategori = ?, gambar = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssdissi", $nama, $deskripsi, $harga, $stok, $kategori, $gambar, $product_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'Produk berhasil diperbarui!';
            header('Location: produk.php');
            exit();
        }
    }
}

// Handle delete
// Handle delete - REVISI DENGAN SOFT DELETE
if ($action === 'delete' && $product_id > 0) {
    // Cek apakah produk ada di order_items (ada transaksi)
    $check_query = "SELECT COUNT(*) as count FROM order_items WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $order_count = mysqli_fetch_assoc($result)['count'];
    
    if ($order_count > 0) {
        // Jika ada transaksi, soft delete (tandai sebagai dihapus)
        // Pertama, tambah kolom is_deleted jika belum ada
        $add_column = "ALTER TABLE produk ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) DEFAULT 0";
        mysqli_query($conn, $add_column);
        
        $query = "UPDATE produk SET stok = 0, is_deleted = 1 WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'Produk ditandai sebagai dihapus (masih ada transaksi terkait). Stok diatur ke 0.';
            header('Location: produk.php');
            exit();
        } else {
            $_SESSION['error'] = 'Gagal menandai produk sebagai dihapus.';
            header('Location: produk.php');
            exit();
        }
    } else {
        // Jika tidak ada transaksi, hard delete (hapus permanen)
        // Get image filename first
        $query = "SELECT gambar FROM produk WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($product = mysqli_fetch_assoc($result)) {
            // Delete image file if not default
            if ($product['gambar'] != 'project images') {
                $image_path = '../project images/' . $product['gambar'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
        }
        
        // Delete from database
        $query = "DELETE FROM produk WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'Produk berhasil dihapus!';
            header('Location: produk.php');
            exit();
        } else {
            $_SESSION['error'] = 'Gagal menghapus produk: ' . mysqli_error($conn);
            header('Location: produk.php');
            exit();
        }
    }

}

// Get product for edit
$product = null;
if ($action === 'edit' && $product_id > 0) {
    $query = "SELECT * FROM produk WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
}

// Get all products
$products_query = "SELECT * FROM produk ORDER BY created_at DESC";
$products_result = mysqli_query($conn, $products_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">
    <?php include '../components/sidebar.php'; ?>
    
    <main class="admin-main">
        <header class="admin-header">
            <h1>
                <i class="fas fa-box"></i> 
                <?php echo $action ? ucfirst($action) . ' Produk' : 'Manajemen Produk'; ?>
            </h1>
            <div class="admin-actions">
                <a href="produk.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </header>
        
        <section class="admin-content">
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Product Form -->
            <div class="card">
                <form method="POST" action="" enctype="multipart/form-data" class="product-form">
                    <input type="hidden" name="action" value="<?php echo $action; ?>">
                    <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                    <input type="hidden" name="current_gambar" value="<?php echo $product['gambar']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nama">Nama Produk *</label>
                            <input type="text" id="nama" name="nama" class="form-control" required
                                   value="<?php echo $product['nama'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="kategori">Kategori *</label>
                            <select id="kategori" name="kategori" class="form-control" required>
                                <option value="">Pilih Kategori</option>
                                <option value="Makanan Kucing" <?php echo ($product['kategori'] ?? '') == 'Makanan Kucing' ? 'selected' : ''; ?>>Makanan Kucing</option>
                                <option value="Makanan Anjing" <?php echo ($product['kategori'] ?? '') == 'Makanan Anjing' ? 'selected' : ''; ?>>Makanan Anjing</option>
                                <option value="Aksesoris" <?php echo ($product['kategori'] ?? '') == 'Aksesoris' ? 'selected' : ''; ?>>Aksesoris</option>
                                <option value="Mainan" <?php echo ($product['kategori'] ?? '') == 'Mainan' ? 'selected' : ''; ?>>Mainan</option>
                                <option value="Perawatan" <?php echo ($product['kategori'] ?? '') == 'Perawatan' ? 'selected' : ''; ?>>Perawatan</option>
                                <option value="Vitamin" <?php echo ($product['kategori'] ?? '') == 'Vitamin' ? 'selected' : ''; ?>>Vitamin</option>
                                <option value="Kandang" <?php echo ($product['kategori'] ?? '') == 'Kandang' ? 'selected' : ''; ?>>Kandang</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="harga">Harga (Rp) *</label>
                            <input type="number" id="harga" name="harga" class="form-control" required
                                   min="0" step="100" value="<?php echo $product['harga'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="stok">Stok *</label>
                            <input type="number" id="stok" name="stok" class="form-control" required
                                   min="0" value="<?php echo $product['stok'] ?? 0; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="deskripsi">Deskripsi Produk</label>
                        <textarea id="deskripsi" name="deskripsi" class="form-control" rows="5"
                                  placeholder="Deskripsi lengkap produk..."><?php echo $product['deskripsi'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="gambar">Gambar Produk</label>
                        <div class="upload-area" id="uploadArea">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Klik untuk upload gambar (max 2MB)</p>
                            <input type="file" id="gambar" name="gambar" accept="image/*" 
                                   style="display: none;" onchange="previewImage(this)">
                        </div>
                        
                        <?php if (isset($product['gambar'])): ?>
                        <div class="current-image">
                            <p>Gambar saat ini:</p>
                            <img src="../project images/<?php echo $product['gambar']; ?>" 
                                 alt="Current Image" style="max-width: 200px;">
                        </div>
                        <?php endif; ?>
                        
                        <div id="imagePreview" style="margin-top: 15px;"></div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Simpan Produk
                        </button>
                        <a href="produk.php" class="btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
            
            <?php else: ?>
            <!-- Product List -->
            <div class="card">
                <div class="card-header">
                    <h2>Daftar Produk</h2>
                    <a href="produk.php?action=add" class="btn-primary">
                        <i class="fas fa-plus"></i> Tambah Produk
                    </a>
                </div>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Gambar</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($prod = mysqli_fetch_assoc($products_result)): ?>
                            <tr>
                                <td>#<?php echo str_pad($prod['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <img src="../project images/<?php echo $prod['gambar']; ?>" 
                                         alt="<?php echo $prod['nama']; ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                </td>
                                <td><?php echo $prod['nama']; ?></td>
                                <td>
                                    <span class="category-badge"><?php echo $prod['kategori']; ?></span>
                                </td>
                                <td>Rp <?php echo number_format($prod['harga'], 0, ',', '.'); ?></td>
                                <td>
                                    <?php if ($prod['stok'] > 0): ?>
                                    <span class="stock-in"><?php echo $prod['stok']; ?></span>
                                    <?php else: ?>
                                    <span class="stock-out">Habis</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($prod['created_at'])); ?></td>
                                <td>
                                    <a href="produk.php?action=edit&id=<?php echo $prod['id']; ?>" 
                                       class="btn-action btn-edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="produk.php?action=delete&id=<?php echo $prod['id']; ?>" 
                                       class="btn-action btn-delete"
                                       onclick="return confirm('Hapus produk ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="../detail_produk.php?id=<?php echo $prod['id']; ?>" 
                                       target="_blank" class="btn-action btn-view">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </section>
    </main>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Image upload preview
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('gambar');
        const imagePreview = document.getElementById('imagePreview');
        
        if (uploadArea) {
            uploadArea.addEventListener('click', function() {
                fileInput.click();
            });
        }
    });
    
    function previewImage(input) {
        const imagePreview = document.getElementById('imagePreview');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                imagePreview.innerHTML = `
                    <div class="preview-container">
                        <img src="${e.target.result}" 
                             style="max-width: 200px; max-height: 200px; border-radius: var(--radius);">
                        <button type="button" onclick="removePreview()" class="btn-remove-preview">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    function removePreview() {
        const imagePreview = document.getElementById('imagePreview');
        const fileInput = document.getElementById('gambar');
        
        imagePreview.innerHTML = '';
        fileInput.value = '';
    }
    </script>
    
    <style>
    .category-badge {
        background-color: var(--secondary);
        color: white;
        padding: 3px 10px;
        border-radius: 15px;
        font-size: 0.85rem;
    }
    
    .stock-in {
        color: var(--success);
        font-weight: 600;
    }
    
    .stock-out {
        color: var(--danger);
        font-weight: 600;
    }
    
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .preview-container {
        position: relative;
        display: inline-block;
    }
    
    .btn-remove-preview {
        position: absolute;
        top: -10px;
        right: -10px;
        background: var(--danger);
        color: white;
        border: none;
        border-radius: 50%;
        width: 25px;
        height: 25px;
        cursor: pointer;
    }
    
    .upload-area {
        border: 2px dashed var(--border);
        border-radius: var(--radius);
        padding: 30px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .upload-area:hover {
        border-color: var(--primary);
        background-color: rgba(109, 157, 197, 0.05);
    }
    
    .upload-area i {
        font-size: 2.5rem;
        color: var(--primary);
        margin-bottom: 10px;
    }
    
    .upload-area p {
        margin: 0;
        color: var(--text);
    }
    </style>
</body>
</html>