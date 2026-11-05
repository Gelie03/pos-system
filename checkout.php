<?php
/**
 * checkout.php
 * Saves the sale: creates a row in `sales`, rows in `sale_items`,
 * and lowers the stock in `products`.
 *
 * It uses a DATABASE TRANSACTION: either ALL steps succeed (commit)
 * or NOTHING is saved (rollback). This prevents half-saved sales.
 */
require_once 'includes/db_connect.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pos.php');
}

/** Our own error type for "customer-facing" problems like not enough stock */
class CheckoutException extends Exception {}

[$items, $subtotal] = get_cart_items($conn);

if (!$items) {
    flash_set('warning', 'The cart is empty.');
    redirect('pos.php');
}

// ---- Discount (Senior / PWD) ----
$discount_type = $_POST['discount_type'] ?? 'None';
if (!in_array($discount_type, ['None', 'Senior', 'PWD'], true)) {
    $discount_type = 'None';
}
$discount_id = trim($_POST['discount_id_no'] ?? '');

if ($discount_type !== 'None' && ($discount_id === '' || mb_strlen($discount_id) > 30)) {
    flash_set('danger', 'Please enter the ' . $discount_type . ' ID number (max 30 characters).');
    redirect('pos.php');
}
$discount_amount = ($discount_type === 'None') ? 0.0 : round($subtotal * DISCOUNT_RATE, 2);
$discount_id     = ($discount_type === 'None') ? null : $discount_id;

// The amount the customer must pay
$total = round($subtotal - $discount_amount, 2);

// ---- Payment details ----
$method = ($_POST['payment_method'] ?? 'Cash') === 'GCash' ? 'GCash' : 'Cash';
$paid   = ($method === 'GCash') ? $total : (float)($_POST['amount_paid'] ?? 0);

if ($paid + 0.001 < $total) {   // tiny allowance for decimal rounding
    flash_set('danger', 'Payment is less than the total (' . peso($total) . ').');
    redirect('pos.php');
}
$change = round($paid - $total, 2);

$cashier_id   = current_user()['id'];
$cashier_name = current_user()['name'];

try {
    $conn->begin_transaction();

    // 1) Create the sale (receipt number is filled in after we know the id)
    $stmt = $conn->prepare(
        'INSERT INTO sales (subtotal, discount_type, discount_id_no, discount_amount, total_amount,
                            amount_paid, change_amount, payment_method, cashier_id, cashier_name)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    // d=double, s=string, i=integer  ->  d s s d d d d s i s
    $stmt->bind_param('dssddddsis',
        $subtotal, $discount_type, $discount_id, $discount_amount, $total,
        $paid, $change, $method, $cashier_id, $cashier_name
    );
    $stmt->execute();
    $sale_id = $conn->insert_id;

    // Prepared statements reused for every item in the cart
    $lock   = $conn->prepare('SELECT stock FROM products WHERE id = ? FOR UPDATE'); // locks the row
    $insert = $conn->prepare(
        'INSERT INTO sale_items (sale_id, product_id, product_name, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $reduce = $conn->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');

    foreach ($items as $it) {
        $pid = (int)$it['id'];
        $qty = (int)$it['qty'];

        // 2) Re-check stock right now (someone else may have bought it meanwhile)
        $lock->bind_param('i', $pid);
        $lock->execute();
        $row = $lock->get_result()->fetch_assoc();
        if (!$row || (int)$row['stock'] < $qty) {
            throw new CheckoutException('Not enough stock for "' . $it['product_name'] . '". Please update the cart.');
        }

        // 3) Save the item line
        $price = (float)$it['price'];
        $line  = (float)$it['subtotal'];
        $insert->bind_param('iisdid', $sale_id, $pid, $it['product_name'], $price, $qty, $line);
        $insert->execute();

        // 4) Reduce the stock
        $reduce->bind_param('ii', $qty, $pid);
        $reduce->execute();
    }

    // 5) Give the sale a readable receipt number, e.g. R20261105-0007
    $receipt_no = sprintf('R%s-%04d', date('Ymd'), $sale_id);
    $stmt = $conn->prepare('UPDATE sales SET receipt_no = ? WHERE id = ?');
    $stmt->bind_param('si', $receipt_no, $sale_id);
    $stmt->execute();

    $conn->commit();               // everything worked -> save permanently
    $_SESSION['cart'] = [];        // empty the cart
    redirect('receipt.php?id=' . $sale_id);

} catch (CheckoutException $e) {
    $conn->rollback();             // undo everything
    flash_set('danger', $e->getMessage());
    redirect('pos.php');
} catch (Throwable $e) {
    $conn->rollback();
    flash_set('danger', 'Something went wrong while saving the sale. Please try again.');
    redirect('pos.php');
}
