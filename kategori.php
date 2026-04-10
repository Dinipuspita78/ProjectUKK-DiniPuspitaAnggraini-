<?php
require_once '../components/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../admin_login.php');
    exit();
}

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $category_name = clean_input($_POST['category_name']);
        $category_icon = clean_input($_POST['category_icon']);
        
        // Check if category exists
        $check_query = "SELECT COUNT(*) as total FROM produk WHERE kategori = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "s", $category_name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $exists = mysqli_fetch_assoc($result)['total'];
        
        if ($exists > 0) {
            $_SESSION['error'] = 'Kategori sudah ada!';
        } else {
            $_SESSION['success'] = 'Kategori berhasil ditambahkan!';
        }
        
    } elseif (isset($_POST['update_category'])) {
        $old_category = clean_input($_POST['old_category']);
        $new_category = clean_input($_POST['new_category']);
        $new_icon = clean_input($_POST['new_icon']);
        
        // Update products with this category
        $update_query = "UPDATE produk SET kategori = ? WHERE kategori = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ss", $new_category, $old_category);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'Kategori berhasil diperbarui!';
        } else {
            $_SESSION['error'] = 'Gagal memperbarui kategori!';
        }
        
    } elseif (isset($_POST['delete_category'])) {
        $category_to_delete = clean_input($_POST['category_to_delete']);
        
        // Check if category has products
        $check_query = "SELECT COUNT(*) as total FROM produk WHERE kategori = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "s", $category_to_delete);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $product_count = mysqli_fetch_assoc($result)['total'];
        
        if ($product_count > 0) {
            $_SESSION['error'] = 'Tidak dapat menghapus kategori yang memiliki produk!';
        } else {
            $_SESSION['success'] = 'Kategori berhasil dihapus!';
        }
    }
    
    header('Location: kategori.php');
    exit();
}

// Get all categories with statistics
$categories_query = "SELECT 
    kategori,
    COUNT(*) as total_products,
    SUM(CASE WHEN stok > 0 THEN 1 ELSE 0 END) as in_stock,
    SUM(CASE WHEN stok = 0 THEN 1 ELSE 0 END) as out_of_stock,
    MIN(harga) as min_price,
    MAX(harga) as max_price,
    AVG(harga) as avg_price
    FROM produk 
    GROUP BY kategori 
    ORDER BY total_products DESC";
$categories_result = mysqli_query($conn, $categories_query);

// Get all products count
$total_products = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as total FROM produk"))['total'];

// Get unique categories count
$unique_categories = mysqli_num_rows($categories_result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kategori - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    .category-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        text-align: center;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-icon {
        font-size: 2.5rem;
        color: var(--primary);
        margin-bottom: 15px;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 5px;
        color: var(--dark);
    }
    
    .stat-label {
        font-size: 0.9rem;
        color: var(--text);
    }
    
    .category-grid {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 30px;
    }
    
    .category-sidebar {
        background: white;
        padding: 25px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        height: fit-content;
    }
    
    .category-form {
        display: grid;
        gap: 20px;
    }
    
    .form-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        border-bottom: 1px solid var(--border);
        padding-bottom: 10px;
    }
    
    .form-tab {
        padding: 10px 20px;
        background: none;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        font-weight: 500;
        color: var(--text);
    }
    
    .form-tab.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .icon-preview {
        text-align: center;
        margin-bottom: 20px;
    }
    
    .icon-display {
        width: 80px;
        height: 80px;
        background: var(--primary);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto 15px;
    }
    
    .icons-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        max-height: 200px;
        overflow-y: auto;
        padding: 10px;
        background: #f8f9fa;
        border-radius: var(--radius);
    }
    
    .icon-option {
        width: 40px;
        height: 40px;
        background: white;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    
    .icon-option:hover {
        background: var(--primary);
        color: white;
    }
    
    .icon-option.selected {
        background: var(--primary);
        color: white;
        border-color: var(--secondary);
    }
    
    .category-table-container {
        background: white;
        padding: 25px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
    }
    
    .category-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    
    .category-table th,
    .category-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid var(--border);
    }
    
    .category-table th {
        background-color: var(--primary);
        color: white;
        font-weight: 600;
    }
    
    .category-table tr:hover {
        background-color: rgba(109, 157, 197, 0.05);
    }
    
    .category-icon-cell {
        width: 60px;
        text-align: center;
    }
    
    .category-icon {
        width: 40px;
        height: 40px;
        background: var(--primary);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    
    .product-count {
        display: inline-block;
        background: var(--light);
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--primary);
    }
    
    .price-range {
        font-size: 0.9rem;
        color: var(--text);
    }
    
    .stock-info {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .in-stock {
        color: var(--success);
        font-weight: 600;
    }
    
    .out-of-stock {
        color: var(--danger);
        font-weight: 600;
    }
    
    .category-actions {
        display: flex;
        gap: 10px;
    }
    
    .btn-small {
        padding: 5px 10px;
        font-size: 0.85rem;
    }
    
    .no-categories {
        text-align: center;
        padding: 40px;
        color: var(--text);
    }
    
    .no-categories i {
        font-size: 3rem;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    @media (max-width: 1024px) {
        .category-grid {
            grid-template-columns: 1fr;
        }
        
        .category-sidebar {
            order: 2;
        }
        
        .category-table-container {
            order: 1;
        }
    }
    
    @media (max-width: 768px) {
        .category-stats {
            grid-template-columns: 1fr;
        }
        
        .category-table {
            display: block;
            overflow-x: auto;
        }
        
        .icons-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    
    .modal-content {
        background-color: white;
        margin: 10% auto;
        padding: 30px;
        border-radius: var(--radius);
        width: 90%;
        max-width: 500px;
        position: relative;
    }
    
    .close-modal {
        position: absolute;
        right: 20px;
        top: 20px;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--text);
    }
    
    .warning-message {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        color: #856404;
        padding: 15px;
        border-radius: var(--radius);
        margin-bottom: 20px;
    }
    
    .warning-message i {
        margin-right: 8px;
    }
    </style>
</head>
<body class="admin-body">
    <?php include '../components/sidebar.php'; ?>
    
    <main class="admin-main">
        <header class="admin-header">
            <h1><i class="fas fa-tags"></i> Manajemen Kategori</h1>
            <div class="admin-actions">
                <button class="btn-primary" onclick="showAddCategoryModal()">
                    <i class="fas fa-plus"></i> Tambah Kategori
                </button>
            </div>
        </header>
        
        <section class="admin-content">
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="category-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-number"><?php echo $unique_categories; ?></div>
                    <div class="stat-label">Total Kategori</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_products; ?></div>
                    <div class="stat-label">Total Produk</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number">
                        <?php 
                        $in_stock = mysqli_fetch_assoc(mysqli_query($conn, 
                            "SELECT COUNT(*) as total FROM produk WHERE stok > 0"))['total'];
                        echo $in_stock;
                        ?>
                    </div>
                    <div class="stat-label">Produk Tersedia</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-number">
                        <?php 
                        $out_of_stock = mysqli_fetch_assoc(mysqli_query($conn, 
                            "SELECT COUNT(*) as total FROM produk WHERE stok = 0"))['total'];
                        echo $out_of_stock;
                        ?>
                    </div>
                    <div class="stat-label">Produk Habis</div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="category-grid">
                <!-- Sidebar Form -->
                <div class="category-sidebar">
                    <h2><i class="fas fa-cogs"></i> Kelola Kategori</h2>
                    
                    <div class="form-tabs">
                        <button class="form-tab active" onclick="showFormTab('add')">
                            <i class="fas fa-plus"></i> Tambah
                        </button>
                        <button class="form-tab" onclick="showFormTab('edit')">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="form-tab" onclick="showFormTab('delete')">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </div>
                    
                    <!-- Add Category Form -->
                    <div id="addTab" class="tab-content active">
                        <form method="POST" action="" id="addCategoryForm">
                            <input type="hidden" name="add_category" value="1">
                            
                            <div class="form-group">
                                <label for="category_name">Nama Kategori *</label>
                                <input type="text" id="category_name" name="category_name" 
                                       class="form-control" required placeholder="Contoh: Makanan Kucing">
                            </div>
                            
                            <div class="form-group">
                                <label for="category_icon">Icon Kategori</label>
                                <div class="icon-preview">
                                    <div class="icon-display">
                                        <i id="selectedIcon" class="fas fa-box"></i>
                                    </div>
                                    <p>Pilih icon untuk kategori</p>
                                </div>
                                
                                <div class="icons-grid" id="iconsGrid">
                                    <!-- Icons will be populated by JavaScript -->
                                </div>
                                <input type="hidden" id="category_icon" name="category_icon" value="fas fa-box">
                            </div>
                            
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Tambah Kategori
                            </button>
                        </form>
                    </div>
                    
                    <!-- Edit Category Form -->
                    <div id="editTab" class="tab-content">
                        <form method="POST" action="" id="editCategoryForm">
                            <input type="hidden" name="update_category" value="1">
                            
                            <div class="form-group">
                                <label for="old_category">Pilih Kategori *</label>
                                <select id="old_category" name="old_category" class="form-control" required 
                                        onchange="loadCategoryData(this.value)">
                                    <option value="">Pilih kategori...</option>
                                    <?php 
                                    mysqli_data_seek($categories_result, 0);
                                    while ($cat = mysqli_fetch_assoc($categories_result)): 
                                    ?>
                                    <option value="<?php echo htmlspecialchars($cat['kategori']); ?>">
                                        <?php echo $cat['kategori']; ?> (<?php echo $cat['total_products']; ?> produk)
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_category">Nama Baru *</label>
                                <input type="text" id="new_category" name="new_category" 
                                       class="form-control" required placeholder="Nama kategori baru">
                            </div>
                            
                            <div class="form-group">
                                <label for="new_icon">Icon Baru</label>
                                <div class="icon-preview">
                                    <div class="icon-display">
                                        <i id="selectedEditIcon" class="fas fa-box"></i>
                                    </div>
                                    <p>Pilih icon baru</p>
                                </div>
                                
                                <div class="icons-grid" id="editIconsGrid">
                                    <!-- Icons will be populated by JavaScript -->
                                </div>
                                <input type="hidden" id="new_icon" name="new_icon" value="fas fa-box">
                            </div>
                            
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Update Kategori
                            </button>
                        </form>
                    </div>
                    
                    <!-- Delete Category Form -->
                    <div id="deleteTab" class="tab-content">
                        <div class="warning-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Perhatian:</strong> Hapus kategori hanya jika tidak ada produk di dalamnya.
                        </div>
                        
                        <form method="POST" action="" id="deleteCategoryForm">
                            <input type="hidden" name="delete_category" value="1">
                            
                            <div class="form-group">
                                <label for="category_to_delete">Pilih Kategori *</label>
                                <select id="category_to_delete" name="category_to_delete" class="form-control" required>
                                    <option value="">Pilih kategori...</option>
                                    <?php 
                                    mysqli_data_seek($categories_result, 0);
                                    while ($cat = mysqli_fetch_assoc($categories_result)): 
                                        if ($cat['total_products'] == 0):
                                    ?>
                                    <option value="<?php echo htmlspecialchars($cat['kategori']); ?>">
                                        <?php echo $cat['kategori']; ?> (0 produk)
                                    </option>
                                    <?php 
                                        endif;
                                    endwhile; 
                                    ?>
                                </select>
                                <small>Hanya kategori tanpa produk yang bisa dihapus</small>
                            </div>
                            
                            <button type="submit" class="btn-danger" onclick="return confirm('Yakin ingin menghapus kategori ini?')">
                                <i class="fas fa-trash"></i> Hapus Kategori
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Categories Table -->
                <div class="category-table-container">
                    <h2><i class="fas fa-list"></i> Daftar Kategori</h2>
                    <p>Total <?php echo $unique_categories; ?> kategori dengan <?php echo $total_products; ?> produk</p>
                    
                    <?php if (mysqli_num_rows($categories_result) > 0): ?>
                    <div class="table-responsive">
                        <table class="category-table">
                            <thead>
                                <tr>
                                    <th>Icon</th>
                                    <th>Kategori</th>
                                    <th>Produk</th>
                                    <th>Harga</th>
                                    <th>Stok</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                mysqli_data_seek($categories_result, 0);
                                while ($category = mysqli_fetch_assoc($categories_result)): 
                                    $icon = getCategoryIcon($category['kategori']);
                                ?>
                                <tr>
                                    <td class="category-icon-cell">
                                        <div class="category-icon">
                                            <i class="<?php echo $icon; ?>"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo $category['kategori']; ?></strong>
                                    </td>
                                    <td>
                                        <span class="product-count"><?php echo $category['total_products']; ?> produk</span>
                                    </td>
                                    <td>
                                        <div class="price-range">
                                            Rp <?php echo number_format($category['min_price'], 0, ',', '.'); ?> - 
                                            Rp <?php echo number_format($category['max_price'], 0, ',', '.'); ?>
                                        </div>
                                        <small>Rata-rata: Rp <?php echo number_format($category['avg_price'], 0, ',', '.'); ?></small>
                                    </td>
                                    <td>
                                        <div class="stock-info">
                                            <span class="in-stock"><?php echo $category['in_stock']; ?> tersedia</span>
                                            <span class="out-of-stock"><?php echo $category['out_of_stock']; ?> habis</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="category-actions">
                                            <button class="btn-action btn-edit" 
                                                    onclick="editCategory('<?php echo htmlspecialchars($category['kategori']); ?>', '<?php echo $icon; ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-action btn-view" 
                                                    onclick="viewProducts('<?php echo htmlspecialchars($category['kategori']); ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="no-categories">
                        <i class="fas fa-tags"></i>
                        <h3>Tidak Ada Kategori</h3>
                        <p>Belum ada kategori yang dibuat</p>
                        <button class="btn-primary" onclick="showAddCategoryModal()">
                            <i class="fas fa-plus"></i> Tambah Kategori Pertama
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
    
    <!-- Products Modal -->
    <div id="productsModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2>Produk dalam Kategori: <span id="modalCategoryName"></span></h2>
            <div id="productsList">
                <!-- Products will be loaded via AJAX -->
            </div>
        </div>
    </div>
    
    <script>
    // Available icons for categories
    const availableIcons = [
        'fas fa-cat', 'fas fa-dog', 'fas fa-paw', 'fas fa-bone',
        'fas fa-home', 'fas fa-bed', 'fas fa-gamepad', 'fas fa-ball',
        'fas fa-shower', 'fas fa-bath', 'fas fa-pills', 'fas fa-heart',
        'fas fa-tshirt', 'fas fa-mitten', 'fas fa-utensils', 'fas fa-bowl-food',
        'fas fa-box', 'fas fa-cube', 'fas fa-cubes', 'fas fa-tag',
        'fas fa-tags', 'fas fa-store', 'fas fa-shopping-cart', 'fas fa-gift',
        'fas fa-star', 'fas fa-crown', 'fas fa-gem', 'fas fa-award'
    ];
    
    // Initialize icons grid
    function initializeIconsGrid(gridId, iconFieldId, displayIconId) {
        const grid = document.getElementById(gridId);
        grid.innerHTML = '';
        
        availableIcons.forEach(icon => {
            const iconOption = document.createElement('div');
            iconOption.className = 'icon-option';
            iconOption.innerHTML = `<i class="${icon}"></i>`;
            iconOption.onclick = function() {
                // Remove selected class from all icons
                grid.querySelectorAll('.icon-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                // Add selected class to clicked icon
                this.classList.add('selected');
                
                // Update hidden field
                document.getElementById(iconFieldId).value = icon;
                
                // Update display icon
                document.getElementById(displayIconId).className = icon;
            };
            grid.appendChild(iconOption);
        });
        
        // Select first icon by default
        if (grid.children.length > 0) {
            grid.children[0].click();
        }
    }
    
    // Tab switching
    function showFormTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Remove active class from all buttons
        document.querySelectorAll('.form-tab').forEach(button => {
            button.classList.remove('active');
        });
        
        // Show selected tab
        document.getElementById(tabName + 'Tab').classList.add('active');
        
        // Activate selected button
        event.target.classList.add('active');
    }
    
    // Load category data for editing
    function loadCategoryData(categoryName) {
        if (!categoryName) return;
        
        // Set the new category name field
        document.getElementById('new_category').value = categoryName;
        
        // You would typically fetch icon from database here
        // For now, we'll use a default
        const icon = getIconForCategory(categoryName);
        document.getElementById('new_icon').value = icon;
        document.getElementById('selectedEditIcon').className = icon;
        
        // Update icon selection in grid
        const editGrid = document.getElementById('editIconsGrid');
        editGrid.querySelectorAll('.icon-option').forEach(opt => {
            const iconClass = opt.querySelector('i').className;
            opt.classList.toggle('selected', iconClass === icon);
        });
    }
    
    // Get icon for category
    function getIconForCategory(categoryName) {
        const iconMap = {
            'Makanan Kucing': 'fas fa-cat',
            'Makanan Anjing': 'fas fa-dog',
            'Aksesoris': 'fas fa-paw',
            'Mainan': 'fas fa-gamepad',
            'Perawatan': 'fas fa-shower',
            'Vitamin': 'fas fa-pills',
            'Kandang': 'fas fa-home',
            'Pasir Kucing': 'fas fa-box',
            'Peralatan': 'fas fa-tools',
            'Pakaian': 'fas fa-tshirt'
        };
        
        return iconMap[categoryName] || 'fas fa-box';
    }
    
    // Edit category
    function editCategory(categoryName, currentIcon) {
        // Switch to edit tab
        showFormTab('edit');
        
        // Set form values
        document.getElementById('old_category').value = categoryName;
        document.getElementById('new_category').value = categoryName;
        document.getElementById('new_icon').value = currentIcon;
        document.getElementById('selectedEditIcon').className = currentIcon;
        
        // Scroll to form
        document.querySelector('.category-sidebar').scrollIntoView({ behavior: 'smooth' });
        
        // Update icon selection
        const editGrid = document.getElementById('editIconsGrid');
        editGrid.querySelectorAll('.icon-option').forEach(opt => {
            const iconClass = opt.querySelector('i').className;
            opt.classList.toggle('selected', iconClass === currentIcon);
        });
    }
    
    // View products in category
    function viewProducts(categoryName) {
        fetch(`get_category_products.php?category=${encodeURIComponent(categoryName)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const products = data.products;
                    
                    let html = `
                        <div class="modal-products">
                            <div class="modal-stats">
                                <p><strong>Total Produk:</strong> ${products.length}</p>
                            </div>
                            
                            <div class="products-list">
                    `;
                    
                    if (products.length > 0) {
                        products.forEach(product => {
                            html += `
                                <div class="modal-product-item">
                                    <img src="../project images/produk/${product.gambar || 'default.jpg'}" 
                                         alt="${product.nama}" class="modal-product-img">
                                    <div class="modal-product-info">
                                        <h4>${product.nama}</h4>
                                        <p><strong>Harga:</strong> Rp ${parseInt(product.harga).toLocaleString('id-ID')}</p>
                                        <p><strong>Stok:</strong> ${product.stok}</p>
                                        <p><strong>Status:</strong> ${product.stok > 0 ? '<span class="in-stock">Tersedia</span>' : '<span class="out-of-stock">Habis</span>'}</p>
                                    </div>
                                    <div class="modal-product-actions">
                                        <a href="produk.php?action=edit&id=${product.id}" class="btn-action btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="../user/detail_produk.php?id=${product.id}" target="_blank" class="btn-action btn-view">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        html += `
                            <div class="no-products">
                                <p>Tidak ada produk dalam kategori ini</p>
                            </div>
                        `;
                    }
                    
                    html += `
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('modalCategoryName').textContent = categoryName;
                    document.getElementById('productsList').innerHTML = html;
                    document.getElementById('productsModal').style.display = 'block';
                }
            });
    }
    
    // Close modal
    function closeModal() {
        document.getElementById('productsModal').style.display = 'none';
    }
    
    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
        initializeIconsGrid('iconsGrid', 'category_icon', 'selectedIcon');
        initializeIconsGrid('editIconsGrid', 'new_icon', 'selectedEditIcon');
        
        // Form validation
        document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
            const categoryName = document.getElementById('category_name').value.trim();
            
            if (categoryName.length < 2) {
                e.preventDefault();
                alert('Nama kategori minimal 2 karakter');
                return false;
            }
            
            return true;
        });
        
        document.getElementById('editCategoryForm').addEventListener('submit', function(e) {
            const oldCategory = document.getElementById('old_category').value;
            const newCategory = document.getElementById('new_category').value.trim();
            
            if (!oldCategory) {
                e.preventDefault();
                alert('Pilih kategori yang akan diedit');
                return false;
            }
            
            if (newCategory.length < 2) {
                e.preventDefault();
                alert('Nama kategori baru minimal 2 karakter');
                return false;
            }
            
            return true;
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('productsModal')) {
                closeModal();
            }
        };
    });
    
    // CSS for modal products
    const modalStyles = document.createElement('style');
    modalStyles.textContent = `
    .modal-products {
        max-height: 60vh;
        overflow-y: auto;
        padding-right: 10px;
    }
    
    .modal-stats {
        background: #f8f9fa;
        padding: 15px;
        border-radius: var(--radius);
        margin-bottom: 20px;
    }
    
    .modal-product-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        border-bottom: 1px solid var(--border);
        background: white;
        border-radius: var(--radius);
        margin-bottom: 10px;
    }
    
    .modal-product-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    
    .modal-product-img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .modal-product-info {
        flex: 1;
    }
    
    .modal-product-info h4 {
        margin: 0 0 5px 0;
        color: var(--dark);
    }
    
    .modal-product-info p {
        margin: 2px 0;
        font-size: 0.9rem;
        color: var(--text);
    }
    
    .modal-product-actions {
        display: flex;
        gap: 5px;
    }
    
    .no-products {
        text-align: center;
        padding: 30px;
        color: var(--text);
    }
    
    /* Scrollbar styling */
    .modal-products::-webkit-scrollbar {
        width: 8px;
    }
    
    .modal-products::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    .modal-products::-webkit-scrollbar-thumb {
        background: var(--primary);
        border-radius: 4px;
    }
    
    .modal-products::-webkit-scrollbar-thumb:hover {
        background: var(--secondary);
    }
    `;
    document.head.appendChild(modalStyles);
    </script>
    
    <?php
    function getCategoryIcon($category_name) {
        $icons = [
            'Makanan Kucing' => 'fas fa-cat',
            'Makanan Anjing' => 'fas fa-dog',
            'Aksesoris' => 'fas fa-paw',
            'Mainan' => 'fas fa-gamepad',
            'Perawatan' => 'fas fa-shower',
            'Vitamin' => 'fas fa-pills',
            'Kandang' => 'fas fa-home',
            'Pasir Kucing' => 'fas fa-box',
            'Peralatan' => 'fas fa-tools',
            'Pakaian' => 'fas fa-tshirt'
        ];
        
        return $icons[$category_name] ?? 'fas fa-box';
    }
    ?>
</body>
</html>