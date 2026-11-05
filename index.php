<?php
/**
 * index.php  -  Dashboard: quick numbers about the store.
 */
$page_title = 'Dashboard';
$active = 'dashboard';
$admin_only = true;                 // the dashboard is for admins only
require_once 'includes/header.php';

// LOW_STOCK is a number constant, so it is safe to put in the query
$total_products = $conn->query('SELECT COUNT(*) AS c FROM products')->fetch_assoc()['c'];
$low_count      = $conn->query('SELECT COUNT(*) AS c FROM products WHERE stock <= ' . LOW_STOCK)->fetch_assoc()['c'];
$today          = $conn->query('SELECT COUNT(*) AS c, COALESCE(SUM(total_amount),0) AS s
                                FROM sales WHERE DATE(created_at) = CURDATE()')->fetch_assoc();

// Top 5 best-selling products (by quantity sold)
$best = $conn->query('SELECT product_name, SUM(quantity) AS qty, SUM(subtotal) AS amount
                      FROM sale_items GROUP BY product_name ORDER BY qty DESC LIMIT 5');

// Products that need restocking
$low_list = $conn->query('SELECT id, product_name, stock FROM products
                          WHERE stock <= ' . LOW_STOCK . ' ORDER BY stock ASC, product_name ASC LIMIT 6');

// Last 5 sales
$recent = $conn->query('SELECT id, receipt_no, total_amount, payment_method, created_at
                        FROM sales ORDER BY id DESC LIMIT 5');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-speedometer2 text-brand"></i> Dashboard</h3>
    <a href="pos.php" class="btn btn-brand"><i class="bi bi-cart-plus"></i> Start a sale</a>
</div>

<!-- Stat cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card shadow-sm"><div class="card-body">
            <div class="text-muted small">Today's sales</div>
            <div class="stat-value"><?= peso($today['s']) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card shadow-sm"><div class="card-body">
            <div class="text-muted small">Transactions today</div>
            <div class="stat-value"><?= (int)$today['c'] ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card shadow-sm"><div class="card-body">
            <div class="text-muted small">Total products</div>
            <div class="stat-value"><?= (int)$total_products ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card shadow-sm" style="border-left-color:#dc3545"><div class="card-body">
            <div class="text-muted small">Low / out of stock</div>
            <div class="stat-value text-danger"><?= (int)$low_count ?></div>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <!-- Best sellers -->
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-bold"><i class="bi bi-trophy text-brand"></i> Best sellers</div>
            <ul class="list-group list-group-flush">
                <?php if ($best->num_rows === 0): ?>
                    <li class="list-group-item text-muted">No sales yet.</li>
                <?php endif; ?>
                <?php while ($b = $best->fetch_assoc()): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= e($b['product_name']) ?></span>
                        <span class="badge text-bg-success"><?= (int)$b['qty'] ?> sold</span>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>

    <!-- Low stock -->
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-bold"><i class="bi bi-exclamation-triangle text-danger"></i> Needs restocking</div>
            <ul class="list-group list-group-flush">
                <?php if ($low_list->num_rows === 0): ?>
                    <li class="list-group-item text-muted">All products have enough stock.</li>
                <?php endif; ?>
                <?php while ($l = $low_list->fetch_assoc()): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="product_edit.php?id=<?= (int)$l['id'] ?>" class="text-decoration-none"><?= e($l['product_name']) ?></a>
                        <span class="badge text-bg-<?= (int)$l['stock'] === 0 ? 'danger' : 'warning' ?>">
                            <?= (int)$l['stock'] === 0 ? 'Out' : (int)$l['stock'] . ' left' ?>
                        </span>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>

    <!-- Recent sales -->
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-bold"><i class="bi bi-clock-history text-brand"></i> Recent sales</div>
            <ul class="list-group list-group-flush">
                <?php if ($recent->num_rows === 0): ?>
                    <li class="list-group-item text-muted">No sales yet.</li>
                <?php endif; ?>
                <?php while ($r = $recent->fetch_assoc()): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="receipt.php?id=<?= (int)$r['id'] ?>" class="text-decoration-none"><?= e($r['receipt_no']) ?></a>
                        <span><?= peso($r['total_amount']) ?></span>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
