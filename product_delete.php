<?php
/**
 * product_delete.php  -  DELETE (the "DELETE" in CRUD)
 * Only accepts POST (a button click in a form), never a plain link,
 * so a product cannot be deleted by accident or by a crawler.
 */
require_once 'includes/db_connect.php';
require_admin();                       // only admins can delete products

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('products.php');
}

$id = (int)($_POST['id'] ?? 0);

$stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    // Also remove it from the cart if it was there
    unset($_SESSION['cart'][$id]);
    flash_set('success', 'Product deleted.');
} else {
    flash_set('warning', 'Product not found.');
}
redirect('products.php');
