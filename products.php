<?php
/**
 * products.php  -  READ (the "SELECT" in CRUD)
 * Lists all products in a table, with search and category filter.
 */
$page_title = 'Products';
$active = 'products';
$admin_only = true;                 // cashiers cannot manage products
require_once 'includes/header.php';

// Read the search box and category filter from the URL (?q=...&category=...)
$q   = trim($_GET['q'] ?? '');
$cat = trim($_GET['category'] ?? '');

// Build the SQL step by step depending on the filters used
$sql    = 'SELECT * FROM products WHERE 1=1';
$types  = '';
$params = [];

if ($q !== '') {
    $sql     .= ' AND (product_name LIKE ? OR barcode LIKE ?)';   // search by name OR item code
    $types   .= 'ss';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($cat !== '') {
    $sql     .= ' AND category = ?';
    $types   .= 's';
    $params[] = $cat;
}
$sql .= ' ORDER BY product_name ASC';

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h3 class="mb-0"><i class="bi bi-box-seam text-brand"></i> Products</h3>
    <a href="product_add.php" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Add Product</a>
</div>

<!-- Search + filter -->
<form method="get" class="row g-2 mb-3">
    <div class="col-md-6">
        <input type="text" name="q" class="form-control" placeholder="Search product name or item code..." value="<?= e($q) ?>">
    </div>
    <div class="col-md-3">
        <select name="category" class="form-select">
            <option value="">All categories</option>
            <?php foreach (categories() as $c): ?>
                <option value="<?= e($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-outline-secondary flex-fill"><i class="bi bi-search"></i> Search</button>
        <a href="products.php" class="btn btn-outline-secondary">Reset</a>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th class="text-end">Price</th>
                    <th class="text-center">Stock</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($products->num_rows === 0): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No products found.</td></tr>
            <?php endif; ?>

            <?php while ($row = $products->fetch_assoc()): ?>
                <tr>
                    <td class="text-muted"><?= e($row['barcode'] ?? '-') ?></td>
                    <td>
                        <strong><?= e($row['product_name']) ?></strong>
                        <?php if ($row['description']): ?>
                            <div class="small text-muted"><?= e($row['description']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge text-bg-secondary"><?= e($row['category']) ?></span></td>
                    <td class="text-end"><?= peso($row['price']) ?></td>
                    <td class="text-center">
                        <?php if ($row['stock'] == 0): ?>
                            <span class="badge text-bg-danger">Out of stock</span>
                        <?php elseif ($row['stock'] <= LOW_STOCK): ?>
                            <span class="badge text-bg-warning"><?= (int)$row['stock'] ?> (low)</span>
                        <?php else: ?>
                            <span class="badge text-bg-success"><?= (int)$row['stock'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="product_edit.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit</a>

                        <!-- Delete is a small POST form with a confirm popup -->
                        <form method="post" action="product_delete.php" class="d-inline"
                              onsubmit="return confirm('Delete this product? This cannot be undone.');">
                            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
