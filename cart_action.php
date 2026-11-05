<?php
/**
 * cart_action.php
 * Handles the buttons on the New Sale page: scan, add, inc (+1), dec (-1), remove, clear.
 * The cart is stored in the SESSION as: $_SESSION['cart'] = [ product_id => quantity ]
 * (It lives in the server session, not in the database, until checkout.)
 */
require_once 'includes/db_connect.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pos.php');
}

$action = $_POST['action'] ?? '';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($action === 'clear') {
    $_SESSION['cart'] = [];
    flash_set('info', 'Cart cleared.');
    redirect('pos.php');
}

// ---------- SCAN: the cashier typed or scanned an item code ----------
if ($action === 'scan') {
    [$qty, $code] = parse_scan($_POST['code'] ?? '');      // "3*10001" -> [3, "10001"]

    if ($code === '') {
        redirect('pos.php');
    }

    $stmt = $conn->prepare('SELECT id, product_name, stock FROM products WHERE barcode = ?');
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();

    if (!$found) {
        flash_set('danger', 'Item code "' . $code . '" was not found.');
        redirect('pos.php');
    }

    $pid     = (int)$found['id'];
    $current = (int)($_SESSION['cart'][$pid] ?? 0);

    if ($current + $qty > (int)$found['stock']) {
        flash_set('warning', 'Not enough stock for "' . $found['product_name'] . '" (only ' . (int)$found['stock'] . ' left).');
    } else {
        $_SESSION['cart'][$pid] = $current + $qty;
    }
    redirect('pos.php');
}

// ---------- add / inc / dec / remove: these use the product id ----------
$id = (int)($_POST['id'] ?? 0);

// We need the product to check its stock
$stmt = $conn->prepare('SELECT product_name, stock FROM products WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    unset($_SESSION['cart'][$id]);
    flash_set('warning', 'That product no longer exists.');
    redirect('pos.php');
}

$current = (int)($_SESSION['cart'][$id] ?? 0);

switch ($action) {
    case 'add':
    case 'inc':
        if ($current + 1 > (int)$product['stock']) {
            flash_set('warning', 'Not enough stock for "' . $product['product_name'] . '" (only ' . (int)$product['stock'] . ' left).');
        } else {
            $_SESSION['cart'][$id] = $current + 1;
        }
        break;

    case 'dec':
        if ($current <= 1) {
            unset($_SESSION['cart'][$id]);      // going below 1 removes the item
        } else {
            $_SESSION['cart'][$id] = $current - 1;
        }
        break;

    case 'remove':
        unset($_SESSION['cart'][$id]);
        break;
}

redirect('pos.php');
