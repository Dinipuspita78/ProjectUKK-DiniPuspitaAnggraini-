<?php
// File: components/breadcrumb.php
function generateBreadcrumb($category = null) {
    $breadcrumbs = [
        ['label' => 'Home', 'url' => 'home.php']
    ];
    
    if ($category) {
        $breadcrumbs[] = ['label' => 'Kategori', 'url' => 'kategori.php'];
        $breadcrumbs[] = ['label' => $category, 'url' => 'kategori.php?kategori=' . urlencode($category)];
    } else {
        $breadcrumbs[] = ['label' => 'Kategori', 'url' => 'kategori.php', 'active' => true];
    }
    
    return $breadcrumbs;
}
?>

<?php if (isset($breadcrumbs)): ?>
<div class="category-breadcrumb">
    <ul class="breadcrumb-list">
        <?php foreach ($breadcrumbs as $index => $crumb): ?>
        <li class="breadcrumb-item <?php echo isset($crumb['active']) ? 'active' : ''; ?>">
            <?php if (isset($crumb['url']) && !isset($crumb['active'])): ?>
            <a href="<?php echo $crumb['url']; ?>">
                <?php if ($index == 0): ?>
                <i class="fas fa-home"></i>
                <?php endif; ?>
                <?php echo $crumb['label']; ?>
            </a>
            <?php else: ?>
            <span>
                <?php echo $crumb['label']; ?>
            </span>
            <?php endif; ?>
            
            <?php if ($index < count($breadcrumbs) - 1): ?>
            <span class="breadcrumb-separator">/</span>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>