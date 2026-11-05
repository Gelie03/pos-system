<?php
/**
 * pos.php  -  the "New Sale" (cashier) screen.
 * Top-left: scan box. Below it: product tiles. Right: the cart + payment.
 */
$page_title = 'New Sale';
$active = 'pos';
require_once 'includes/header.php';

// All products for the tiles
$products = $conn->query('SELECT id, product_name, barcode, category, price, stock FROM products ORDER BY product_name ASC');

// Current cart (matched with the latest prices/stock from the database)
[$items, $subtotal] = get_cart_items($conn);
$item_count = array_sum(array_column($items, 'qty'));
?>

<div class="row g-4">

    <!-- ========== LEFT: SCAN BOX + PRODUCT TILES ========== -->
    <div class="col-lg-7">

        <!-- Scan / item code box. A barcode scanner types the code and presses Enter by itself. -->
        <form method="post" action="cart_action.php" class="mb-3">
            <input type="hidden" name="action" value="scan">
            <label for="scan" class="form-label fw-semibold mb-1">
                <i class="bi bi-upc-scan text-brand"></i> Scan or type item code, then press Enter
            </label>
            <div class="input-group input-group-lg">
                <input type="text" name="code" id="scan" class="form-control scan-input"
                       placeholder="e.g. 10001   or   3*10001 (for 3 pieces)"
                       autocomplete="off" autofocus required>
                <button class="btn btn-brand" type="submit"><i class="bi bi-plus-lg"></i> Add</button>
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0"><i class="bi bi-grid text-brand"></i> Products</h5>
        </div>
        <input type="text" id="tileSearch" class="form-control mb-3" placeholder="Search by name or code...">

        <div class="product-scroll">
            <div class="row g-2" id="tiles">
                <?php if ($products->num_rows === 0): ?>
                    <div class="col-12 text-muted">No products yet.</div>
                <?php endif; ?>

                <?php while ($p = $products->fetch_assoc()): $out = ((int)$p['stock'] === 0); ?>
                    <div class="col-6 col-md-4 tile-col"
                         data-name="<?= e(strtolower($p['product_name'] . ' ' . $p['barcode'])) ?>">
                        <form method="post" action="cart_action.php" class="h-100">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="product-tile card h-100 w-100 text-start p-2 <?= $out ? 'out' : '' ?>"
                                    <?= $out ? 'disabled' : '' ?>>
                                <span class="small text-muted"><?= e($p['category']) ?>
                                    <?php if ($p['barcode']): ?> &middot; <?= e($p['barcode']) ?><?php endif; ?>
                                </span>
                                <span class="fw-semibold"><?= e($p['product_name']) ?></span>
                                <span class="text-brand fw-bold"><?= peso($p['price']) ?></span>
                                <span class="small <?= $out ? 'text-danger' : 'text-muted' ?>">
                                    <?= $out ? 'Out of stock' : 'Stock: ' . (int)$p['stock'] ?>
                                </span>
                            </button>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- ========== RIGHT: CART ========== -->
    <div class="col-lg-5">
        <div class="card shadow-sm cart-sticky">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-cart3 text-brand"></i> Current Order
                    <span class="badge text-bg-secondary ms-1"><?= (int)$item_count ?> item<?= $item_count == 1 ? '' : 's' ?></span>
                </strong>
                <?php if ($items): ?>
                    <form method="post" action="cart_action.php" class="d-inline"
                          onsubmit="return confirm('Clear the whole cart?');">
                        <input type="hidden" name="action" value="clear">
                        <button class="btn btn-sm btn-outline-secondary">Clear</button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="card-body p-0 cart-list">
                <?php if (!$items): ?>
                    <p class="text-muted text-center my-4">The cart is empty.<br>Scan an item code or tap a product.</p>
                <?php else: ?>
                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold"><?= e($it['product_name']) ?></div>
                                    <div class="small text-muted"><?= peso($it['price']) ?> each</div>
                                </td>
                                <td class="text-nowrap">
                                    <!-- minus button -->
                                    <form method="post" action="cart_action.php" class="d-inline">
                                        <input type="hidden" name="action" value="dec">
                                        <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                                        <button class="btn btn-sm btn-outline-secondary">&minus;</button>
                                    </form>
                                    <span class="mx-1 fw-bold"><?= (int)$it['qty'] ?></span>
                                    <!-- plus button -->
                                    <form method="post" action="cart_action.php" class="d-inline">
                                        <input type="hidden" name="action" value="inc">
                                        <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                                        <button class="btn btn-sm btn-outline-secondary">+</button>
                                    </form>
                                </td>
                                <td class="text-end"><?= peso($it['subtotal']) ?></td>
                                <td class="pe-3 text-end">
                                    <form method="post" action="cart_action.php" class="d-inline">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                                        <button class="btn btn-sm text-danger" title="Remove"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <?php if ($items): ?>
            <div class="card-footer bg-white">
                <!-- Payment form -> checkout.php saves the sale -->
                <form method="post" action="checkout.php" id="checkoutForm">

                    <!-- Discount -->
                    <div class="row g-2 mb-2">
                        <div class="col-5">
                            <label class="form-label mb-1 small">Discount</label>
                            <select name="discount_type" id="discType" class="form-select form-select-sm">
                                <option value="None">None</option>
                                <option value="Senior">Senior Citizen (20%)</option>
                                <option value="PWD">PWD (20%)</option>
                            </select>
                        </div>
                        <div class="col-7" id="discIdBox" style="display:none">
                            <label class="form-label mb-1 small">Senior / PWD ID no.</label>
                            <input type="text" name="discount_id_no" id="discId" class="form-control form-control-sm" maxlength="30">
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="d-flex justify-content-between"><span>Subtotal</span><span id="subText" data-sub="<?= e($subtotal) ?>"><?= peso($subtotal) ?></span></div>
                    <div class="d-flex justify-content-between text-danger"><span>Discount</span><span id="discText">-₱0.00</span></div>
                    <div class="d-flex justify-content-between fs-3 fw-bold border-top pt-2 mt-1 mb-3">
                        <span>TOTAL</span><span id="totalText"><?= peso($subtotal) ?></span>
                    </div>

                    <div class="mb-2">
                        <label class="form-label mb-1">Payment method</label>
                        <select name="payment_method" id="method" class="form-select">
                            <option value="Cash">Cash</option>
                            <option value="GCash">GCash</option>
                        </select>
                    </div>
                    <div class="mb-2" id="cashBox">
                        <label class="form-label mb-1">Cash received (₱)</label>
                        <input type="number" name="amount_paid" id="paid" class="form-control form-control-lg"
                               min="0" step="0.01" placeholder="0.00">
                        <div class="mt-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="exactBtn">Exact amount</button>
                        </div>
                        <div class="mt-2 fs-5">Change: <strong id="changeText">₱0.00</strong></div>
                    </div>
                    <button type="submit" class="btn btn-brand btn-lg w-100 mt-2">
                        <i class="bi bi-check2-circle"></i> Complete Sale
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// 1) Filter product tiles while typing (name or code)
document.getElementById('tileSearch').addEventListener('input', function () {
    var term = this.value.toLowerCase();
    document.querySelectorAll('.tile-col').forEach(function (col) {
        col.style.display = col.dataset.name.includes(term) ? '' : 'none';
    });
});

// 2) Live discount, total and change computation
(function () {
    var subEl = document.getElementById('subText');
    if (!subEl) return;                           // cart is empty, nothing to compute

    var subtotal = parseFloat(subEl.dataset.sub);
    var discType = document.getElementById('discType');
    var discBox  = document.getElementById('discIdBox');
    var discId   = document.getElementById('discId');
    var discText = document.getElementById('discText');
    var totalEl  = document.getElementById('totalText');
    var paid     = document.getElementById('paid');
    var change   = document.getElementById('changeText');
    var method   = document.getElementById('method');
    var cash     = document.getElementById('cashBox');
    var RATE     = <?= json_encode(DISCOUNT_RATE) ?>;
    var total    = subtotal;

    function peso(n) { return '₱' + n.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }

    function update() {
        var hasDisc = discType.value !== 'None';
        var disc    = hasDisc ? Math.round(subtotal * RATE * 100) / 100 : 0;
        total       = subtotal - disc;

        discText.textContent = '-' + peso(disc);
        totalEl.textContent  = peso(total);
        discBox.style.display = hasDisc ? '' : 'none';
        discId.required = hasDisc;                // ID number is required for Senior/PWD

        var diff = (parseFloat(paid.value) || 0) - total;
        change.textContent = diff >= 0 ? peso(diff) : 'Not enough';
        change.className   = diff >= 0 ? 'text-success' : 'text-danger';
    }

    discType.addEventListener('change', update);
    paid.addEventListener('input', update);
    document.getElementById('exactBtn').addEventListener('click', function () {
        paid.value = total.toFixed(2); update();
    });
    method.addEventListener('change', function () {
        cash.style.display = (method.value === 'GCash') ? 'none' : '';   // GCash pays the exact total
    });
    update();
})();
</script>

<?php require_once 'includes/footer.php'; ?>
