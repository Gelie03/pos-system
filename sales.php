<?php
/**
 * sales.php  -  sales history with an optional date filter and totals.
 * Admins see all sales. Cashiers only see their own.
 */
$page_title = 'Sales';
$active = 'sales';
require_once 'includes/header.php';

// Optional date range (?from=2026-11-01&to=2026-11-05)
$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';

// Accept only real dates in YYYY-MM-DD format
$valid = function ($d) { return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d); };
if (!$valid($from)) $from = '';
if (!$valid($to))   $to = '';

$sql    = 'SELECT s.*, (SELECT COALESCE(SUM(quantity),0) FROM sale_items WHERE sale_id = s.id) AS items_count
           FROM sales s WHERE 1=1';
$types  = '';
$params = [];
if (!is_admin()) {                       // a cashier only sees their own sales
    $sql .= ' AND s.cashier_id = ?'; $types .= 'i'; $params[] = current_user()['id'];
}
if ($from !== '') { $sql .= ' AND DATE(s.created_at) >= ?'; $types .= 's'; $params[] = $from; }
if ($to   !== '') { $sql .= ' AND DATE(s.created_at) <= ?'; $types .= 's'; $params[] = $to; }
$sql .= ' ORDER BY s.id DESC';

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$rows = $stmt->get_result();

// Load rows into an array so we can also compute the total
$sales = [];
$grand = 0.0;
while ($r = $rows->fetch_assoc()) {
    $sales[] = $r;
    $grand  += (float)$r['total_amount'];
}
?>

<h3 class="mb-3"><i class="bi bi-receipt text-brand"></i> Sales History
    <?php if (!is_admin()): ?><small class="text-muted fs-6">(your sales)</small><?php endif; ?>
</h3>

<form method="get" class="row g-2 mb-3 align-items-end">
    <div class="col-6 col-md-3">
        <label class="form-label mb-1">From</label>
        <input type="date" name="from" class="form-control" value="<?= e($from) ?>">
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label mb-1">To</label>
        <input type="date" name="to" class="form-control" value="<?= e($to) ?>">
    </div>
    <div class="col-12 col-md-4 d-flex gap-2">
        <button class="btn btn-outline-secondary"><i class="bi bi-funnel"></i> Filter</button>
        <a href="sales.php" class="btn btn-outline-secondary">Reset</a>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Receipt #</th>
                    <th>Date</th>
                    <th>Cashier</th>
                    <th class="text-center">Items</th>
                    <th>Payment</th>
                    <th class="text-end">Discount</th>
                    <th class="text-end">Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$sales): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No sales yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($sales as $s): ?>
                <tr>
                    <td><strong><?= e($s['receipt_no']) ?></strong></td>
                    <td><?= e(fmt_date($s['created_at'])) ?></td>
                    <td><?= e($s['cashier_name'] !== '' ? $s['cashier_name'] : 'N/A') ?></td>
                    <td class="text-center"><?= (int)$s['items_count'] ?></td>
                    <td><span class="badge text-bg-<?= $s['payment_method'] === 'GCash' ? 'primary' : 'secondary' ?>"><?= e($s['payment_method']) ?></span></td>
                    <td class="text-end">
                        <?php if ($s['discount_type'] !== 'None'): ?>
                            <span class="text-danger">-<?= peso($s['discount_amount']) ?></span>
                            <span class="badge text-bg-warning"><?= e($s['discount_type']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><?= peso($s['total_amount']) ?></td>
                    <td class="text-end"><a href="receipt.php?id=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <?php if ($sales): ?>
            <tfoot class="table-light">
                <tr>
                    <th colspan="6" class="text-end">Total sales (<?= count($sales) ?> transactions)</th>
                    <th class="text-end"><?= peso($grand) ?></th>
                    <th></th>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
