<?php
/**
 * product_form.php
 * The form shared by "Add Product" and "Edit Product" pages.
 * The page that includes this must define:
 *   $p             -> array of current values (product_name, category, price, stock, description)
 *   $errors        -> array of error messages (can be empty)
 *   $submit_label  -> text on the submit button
 */
?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" class="card card-body shadow-sm" novalidate>
    <div class="row g-3">
        <div class="col-md-5">
            <label for="product_name" class="form-label">Product name <span class="text-danger">*</span></label>
            <input type="text" id="product_name" name="product_name" class="form-control"
                   maxlength="100" required value="<?= e($p['product_name']) ?>">
        </div>

        <div class="col-md-3">
            <label for="barcode" class="form-label">Item code / barcode</label>
            <input type="text" id="barcode" name="barcode" class="form-control"
                   maxlength="30" placeholder="e.g. 10013" value="<?= e($p['barcode']) ?>">
        </div>

        <div class="col-md-4">
            <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
            <select id="category" name="category" class="form-select" required>
                <option value="">-- Choose --</option>
                <?php foreach (categories() as $c): ?>
                    <option value="<?= e($c) ?>" <?= $p['category'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label for="price" class="form-label">Price (₱) <span class="text-danger">*</span></label>
            <input type="number" id="price" name="price" class="form-control"
                   min="0" step="0.01" required value="<?= e($p['price']) ?>">
        </div>

        <div class="col-md-6">
            <label for="stock" class="form-label">Stock (pieces) <span class="text-danger">*</span></label>
            <input type="number" id="stock" name="stock" class="form-control"
                   min="0" step="1" required value="<?= e($p['stock']) ?>">
        </div>

        <div class="col-12">
            <label for="description" class="form-label">Description (optional)</label>
            <input type="text" id="description" name="description" class="form-control"
                   maxlength="255" value="<?= e($p['description']) ?>">
        </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg"></i> <?= e($submit_label) ?></button>
        <a href="products.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
