<?php
/**
 * product_edit.php  -  UPDATE (the "UPDATE" in CRUD)
 * Opens ?id=5 -> loads product 5 into the form -> saves changes.
 */
require_once 'includes/db_connect.php';
require_admin();                          // only admins can edit products

$id = (int)($_GET['id'] ?? 0);

// Load the existing product (SELECT)
$stmt = $conn->prepare('SELECT * FROM products WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    flash_set('danger', 'Product not found.');
    redirect('products.php');
}

$errors = [];
$p = [
    'product_name' => $product['product_name'],
    'barcode'      => $product['barcode'] ?? '',
    'category'     => $product['category'],
    'price'        => $product['price'],
    'stock'        => $product['stock'],
    'description'  => $product['description'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$p, $errors] = validate_product($_POST);

    if (!$errors) {
        try {
            $stmt = $conn->prepare(
                'UPDATE products SET product_name = ?, barcode = ?, category = ?, price = ?, stock = ?, description = ? WHERE id = ?'
            );
            $barcode = $p['barcode'] !== '' ? $p['barcode'] : null;
            $price   = (float)$p['price'];
            $stock   = (int)$p['stock'];
            $desc    = $p['description'] !== '' ? $p['description'] : null;
            // "sssdisi" = string, string, string, double, integer, string, integer(id)
            $stmt->bind_param('sssdisi', $p['product_name'], $barcode, $p['category'], $price, $stock, $desc, $id);
            $stmt->execute();

            flash_set('success', 'Product "' . $p['product_name'] . '" was updated.');
            redirect('products.php');
        } catch (mysqli_sql_exception $ex) {
            if ((int)$ex->getCode() === 1062) {
                $errors[] = 'That item code is already used by another product.';
            } else {
                throw $ex;
            }
        }
    }
}

$page_title = 'Edit Product';
$active = 'products';
$submit_label = 'Update Product';
require_once 'includes/header.php';
?>

<h3 class="mb-3"><i class="bi bi-pencil-square text-brand"></i> Edit Product</h3>
<?php require 'includes/product_form.php'; ?>

<?php require_once 'includes/footer.php'; ?>
