<?php
/**
 * functions.php
 * Small reusable helpers used by many pages.
 */

// >>> Change these to make the system "yours" <<<
define('STORE_NAME', 'Ange's Friend Grocers');   // shown in navbar, login page, and receipts
define('LOW_STOCK', 5);                 // stock at or below this = "low stock"
define('DISCOUNT_RATE', 0.20);          // Senior / PWD discount = 20%

/** Product categories used in the dropdown and filter. Edit freely. */
function categories(): array
{
    return ['Food', 'Drinks', 'Snacks', 'Household', 'Others'];
}

/** Escape text before printing in HTML (prevents XSS). Use on ALL user data. */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Format a number as Philippine peso, e.g. 1234.5 -> ₱1,234.50 */
function peso($amount): string
{
    return '₱' . number_format((float)$amount, 2);
}

/** Format a database date/time nicely, e.g. Nov 05, 2026 12:30 PM */
function fmt_date(string $datetime): string
{
    return date('M d, Y h:i A', strtotime($datetime));
}

/** Go to another page and stop running this one. */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Save a one-time message (shown on the next page load). $type = success|danger|warning|info */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $message];
}

/** Print the saved message (if any) as a Bootstrap alert, then forget it. */
function flash_show(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return '<div class="alert alert-' . e($f['type']) . ' alert-dismissible fade show" role="alert">'
         . e($f['msg'])
         . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}

// ---------------------------------------------------------------
// LOGIN / ROLES
// The logged-in user is kept in $_SESSION['user'] = [id, name, username, role]
// ---------------------------------------------------------------

/** The logged-in user (array) or null if nobody is logged in. */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** True if the logged-in user is an admin. */
function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

/** Put this at the top of a page: sends visitors to login.php if not logged in. */
function require_login(): void
{
    if (!current_user()) {
        redirect('login.php');
    }
}

/** Same as require_login(), but only admins may stay. Cashiers go back to the cashier screen. */
function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        flash_set('danger', 'Sorry, that page is for admins only.');
        redirect('pos.php');
    }
}

// ---------------------------------------------------------------
// PRODUCTS / CART
// ---------------------------------------------------------------

/**
 * Check the product form input.
 * Returns [cleanData, errorsArray]. Used by both Add and Edit pages.
 */
function validate_product(array $in): array
{
    $data = [
        'product_name' => trim($in['product_name'] ?? ''),
        'barcode'      => trim($in['barcode'] ?? ''),
        'category'     => trim($in['category'] ?? ''),
        'price'        => trim($in['price'] ?? ''),
        'stock'        => trim($in['stock'] ?? ''),
        'description'  => trim($in['description'] ?? ''),
    ];
    $errors = [];

    if ($data['product_name'] === '' || mb_strlen($data['product_name']) > 100) {
        $errors[] = 'Product name is required (max 100 characters).';
    }
    if ($data['barcode'] !== '' && !preg_match('/^[A-Za-z0-9\-]{3,30}$/', $data['barcode'])) {
        $errors[] = 'Item code must be 3-30 letters, numbers, or dashes.';
    }
    if (!in_array($data['category'], categories(), true)) {
        $errors[] = 'Please choose a valid category.';
    }
    if (!is_numeric($data['price']) || (float)$data['price'] < 0) {
        $errors[] = 'Price must be a number, 0 or higher.';
    }
    if (!ctype_digit($data['stock'])) {
        $errors[] = 'Stock must be a whole number, 0 or higher.';
    }
    if (mb_strlen($data['description']) > 255) {
        $errors[] = 'Description is too long (max 255 characters).';
    }
    return [$data, $errors];
}

/**
 * Understand what the cashier typed in the scan box.
 *   "10001"   -> 1 piece of item 10001
 *   "3*10001" -> 3 pieces of item 10001
 * Returns [quantity, code].
 */
function parse_scan(string $input): array
{
    $input = trim($input);
    if (preg_match('/^(\d{1,3})\s*\*\s*(\S+)$/', $input, $m)) {
        return [max(1, (int)$m[1]), $m[2]];
    }
    return [1, $input];
}

/**
 * Read the cart (saved in the session as [product_id => quantity]) and
 * match it with the latest product info from the database.
 * Returns [itemsArray, subtotal].
 */
function get_cart_items(mysqli $conn): array
{
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) {
        return [[], 0.0];
    }

    // Only whole numbers go into the query, so it is safe to join them
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $result = $conn->query("SELECT id, product_name, barcode, price, stock FROM products WHERE id IN ($ids)");

    $items = [];
    $total = 0.0;
    while ($row = $result->fetch_assoc()) {
        $row['qty']      = (int)$cart[$row['id']];
        $row['subtotal'] = $row['qty'] * (float)$row['price'];
        $total += $row['subtotal'];
        $items[] = $row;
    }
    return [$items, $total];
}
