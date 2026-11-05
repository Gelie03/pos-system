<?php
/**
 * product_add.php  -  CREATE (the "INSERT" in CRUD)
 * Shows an empty form. When submitted, saves a new row in `products`.
 */
require_once 'includes/db_connect.php';   // load DB first so we can redirect before any HTML
require_admin();                          // only admins can add products

$errors = [];
$p = ['product_name' => '', 'barcode' => '', 'category' => '', 'price' => '', 'stock' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$p, $errors] = validate_product($_POST);   // check the form input

    if (!$errors) {
        try {
            // Prepared statement: ? are placeholders, so user input can never break the SQL
            $stmt = $conn->prepare(
                'INSERT INTO products (product_name, barcode, category, price, stock, description) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $barcode = $p['barcode'] !== '' ? $p['barcode'] : null;
            $price   = (float)$p['price'];
            $stock   = (int)$p['stock'];
            $desc    = $p['description'] !== '' ? $p['description'] : null;
            // "sssdis" = string, string, string, double, integer, string
            $stmt->bind_param('sssdis', $p['product_name'], $barcode, $p['category'], $price, $stock, $desc);
            $stmt->execute();

            flash_set('success', 'Product "' . $p['product_name'] . '" was added.');
            redirect('products.php');
        } catch (mysqli_sql_exception $ex) {
            if ((int)$ex->getCode() === 1062) {      // 1062 = duplicate value (item code already used)
                $errors[] = 'That item code is already used by another product.';
            } else {
                throw $ex;
            }
        }
    }
}

$page_title = 'Add Product';
$active = 'products';
$submit_label = 'Save Product';
require_once 'includes/header.php';
?>

<h3 class="mb-3"><i class="bi bi-plus-circle text-brand"></i> Add Product</h3>
<?php require 'includes/product_form.php'; ?>

<?php require_once 'includes/footer.php'; ?>
