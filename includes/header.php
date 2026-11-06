<?php
/**
 * header.php  -  top part shared by every page (navbar + Bootstrap).
 * Before including it, a page can set:
 *   $page_title  (browser tab title)
 *   $active      (which menu link is highlighted)
 *   $admin_only  (true = only admins may open this page)
 *
 * It also makes sure the visitor is logged in.
 */
require_once __DIR__ . '/db_connect.php';

require_login();                       // not logged in? -> login.php
if (!empty($admin_only)) {
    require_admin();                   // cashiers cannot open admin pages
}

$page_title = $page_title ?? 'POS';
$active     = $active ?? '';
$me         = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?> | <?= e(STORE_NAME) ?></title>
    <!-- Bootstrap 5 (CSS framework required by the project) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>

<!-- Navigation bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-brand no-print">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= is_admin() ? 'index.php' : 'pos.php' ?>">
            <i class="bi bi-shop"></i> <?= e(STORE_NAME) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <?php if (is_admin()): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $active === 'dashboard' ? 'active' : '' ?>" href="index.php">
                        <i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?= $active === 'pos' ? 'active' : '' ?>" href="pos.php">
                        <i class="bi bi-cart-check"></i> New Sale</a>
                </li>
                <?php if (is_admin()): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $active === 'products' ? 'active' : '' ?>" href="products.php">
                        <i class="bi bi-box-seam"></i> Products</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?= $active === 'sales' ? 'active' : '' ?>" href="sales.php">
                        <i class="bi bi-receipt"></i> Sales</a>
                </li>
                <?php if (is_admin()): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $active === 'users' ? 'active' : '' ?>" href="users.php">
                        <i class="bi bi-people"></i> Users</a>
                </li>
                <?php endif; ?>
                <li class="nav-item ms-lg-3 text-white-50 small">
                    <i class="bi bi-person-circle"></i> <?= e($me['name']) ?> (<?= e($me['role']) ?>)
                </li>
                <li class="nav-item ms-lg-2">
                    <a class="btn btn-sm btn-outline-light" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">
<?= flash_show() ?>
