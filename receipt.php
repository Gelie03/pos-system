<?php
/**
 * receipt.php  -  shows one sale as a printable receipt (?id=...)
 */
require_once 'includes/db_connect.php';
require_login();

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare('SELECT * FROM sales WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$sale = $stmt->get_result()->fetch_assoc();

if (!$sale) {
    flash_set('danger', 'Receipt not found.');
    redirect('sales.php');
}

// A cashier may only open receipts of their own sales
if (!is_admin() && (int)$sale['cashier_id'] !== current_user()['id']) {
    flash_set('danger', 'You can only view your own receipts.');
    redirect('sales.php');
}

$stmt = $conn->prepare('SELECT * FROM sale_items WHERE sale_id = ? ORDER BY id ASC');
$stmt->bind_param('i', $id);
$stmt->execute();
$lines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$item_count = array_sum(array_column($lines, 'quantity'));

$page_title = 'Receipt ' . $sale['receipt_no'];
$active = 'sales';
require_once 'includes/header.php';
?>

<div class="no-print text-center mb-3 d-flex justify-content-center gap-2 flex-wrap">
    <button onclick="window.print()" class="btn btn-brand"><i class="bi bi-printer"></i> Print receipt</button>
    <a href="pos.php" class="btn btn-outline-secondary"><i class="bi bi-cart-plus"></i> New sale</a>
    <a href="sales.php" class="btn btn-outline-secondary">All sales</a>
</div>

<div class="receipt card shadow-sm p-4">
    <div class="text-center">
        <h5 class="mb-0"><?= e(STORE_NAME) ?></h5>
        <div class="small">OFFICIAL RECEIPT</div>
    </div>
    <hr>
    <div class="small">
        <div>Receipt #: <strong><?= e($sale['receipt_no']) ?></strong></div>
        <div>Date: <?= e(fmt_date($sale['created_at'])) ?></div>
        <div>Cashier: <?= e($sale['cashier_name'] !== '' ? $sale['cashier_name'] : 'N/A') ?></div>
        <div>Payment: <?= e($sale['payment_method']) ?></div>
    </div>
    <hr>

    <table class="table table-sm table-borderless mb-0 small">
        <thead>
            <tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Amount</th></tr>
        </thead>
        <tbody>
        <?php foreach ($lines as $l): ?>
            <tr>
                <td>
                    <?= e($l['product_name']) ?>
                    <div class="text-muted"><?= peso($l['price']) ?> each</div>
                </td>
                <td class="text-center"><?= (int)$l['quantity'] ?></td>
                <td class="text-end"><?= peso($l['subtotal']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <hr>
    <div class="d-flex justify-content-between small"><span>No. of items</span><span><?= (int)$item_count ?></span></div>
    <div class="d-flex justify-content-between"><span>Subtotal</span><span><?= peso($sale['subtotal']) ?></span></div>

    <?php if ($sale['discount_type'] !== 'None'): ?>
        <div class="d-flex justify-content-between text-danger">
            <span><?= e($sale['discount_type']) ?> discount (<?= (int)round(DISCOUNT_RATE * 100) ?>%)</span>
            <span>-<?= peso($sale['discount_amount']) ?></span>
        </div>
        <div class="small text-muted">ID No.: <?= e($sale['discount_id_no']) ?></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between fw-bold fs-5 mt-1"><span>TOTAL</span><span><?= peso($sale['total_amount']) ?></span></div>
    <div class="d-flex justify-content-between"><span>Paid (<?= e($sale['payment_method']) ?>)</span><span><?= peso($sale['amount_paid']) ?></span></div>
    <div class="d-flex justify-content-between"><span>Change</span><span><?= peso($sale['change_amount']) ?></span></div>
    <hr>
    <div class="text-center small">Salamat po! Balik kayo ulit.</div>
</div>

<?php require_once 'includes/footer.php'; ?>
