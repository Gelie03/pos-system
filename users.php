<?php
/**
 * users.php  -  ADMIN ONLY. Add and remove cashier/admin accounts.
 * (A second small CRUD module: Create, Read, Delete.)
 */
require_once 'includes/db_connect.php';
require_admin();

$errors = [];
$form   = ['full_name' => '', 'username' => '', 'role' => 'cashier'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ---------- DELETE a user ----------
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === current_user()['id']) {
            flash_set('warning', 'You cannot delete the account you are using.');
        } else {
            $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            flash_set('success', 'User deleted.');
        }
        redirect('users.php');
    }

    // ---------- ADD a user ----------
    if ($action === 'add') {
        $form['full_name'] = trim($_POST['full_name'] ?? '');
        $form['username']  = trim($_POST['username'] ?? '');
        $form['role']      = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'cashier';
        $password          = $_POST['password'] ?? '';

        if ($form['full_name'] === '' || mb_strlen($form['full_name']) > 100) {
            $errors[] = 'Full name is required (max 100 characters).';
        }
        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $form['username'])) {
            $errors[] = 'Username must be 3-30 letters, numbers, or underscores.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        if (!$errors) {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);   // never store the plain password
                $stmt = $conn->prepare('INSERT INTO users (full_name, username, password_hash, role) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('ssss', $form['full_name'], $form['username'], $hash, $form['role']);
                $stmt->execute();
                flash_set('success', 'User "' . $form['username'] . '" was created.');
                redirect('users.php');
            } catch (mysqli_sql_exception $ex) {
                if ((int)$ex->getCode() === 1062) {        // 1062 = duplicate value
                    $errors[] = 'That username is already taken.';
                } else {
                    throw $ex;
                }
            }
        }
    }
}

$users = $conn->query('SELECT id, full_name, username, role, created_at FROM users ORDER BY role ASC, username ASC');

$page_title = 'Users';
$active = 'users';
require_once 'includes/header.php';
?>

<h3 class="mb-3"><i class="bi bi-people text-brand"></i> Users</h3>

<div class="row g-4">
    <!-- Add user form -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold">Add a user</div>
            <div class="card-body">
                <?php if ($errors): ?>
                    <div class="alert alert-danger py-2"><ul class="mb-0 ps-3">
                        <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
                    </ul></div>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-2">
                        <label class="form-label mb-1">Full name</label>
                        <input type="text" name="full_name" class="form-control" maxlength="100" required value="<?= e($form['full_name']) ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label mb-1">Username</label>
                        <input type="text" name="username" class="form-control" maxlength="30" required value="<?= e($form['username']) ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label mb-1">Password (min 6)</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label mb-1">Role</label>
                        <select name="role" class="form-select">
                            <option value="cashier" <?= $form['role'] === 'cashier' ? 'selected' : '' ?>>Cashier (sales only)</option>
                            <option value="admin" <?= $form['role'] === 'admin' ? 'selected' : '' ?>>Admin (everything)</option>
                        </select>
                    </div>
                    <button class="btn btn-brand w-100"><i class="bi bi-person-plus"></i> Create user</button>
                </form>
            </div>
        </div>
    </div>

    <!-- User list -->
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Username</th><th>Role</th><th>Created</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php while ($u = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($u['full_name']) ?></td>
                            <td><?= e($u['username']) ?></td>
                            <td><span class="badge text-bg-<?= $u['role'] === 'admin' ? 'danger' : 'secondary' ?>"><?= e(ucfirst($u['role'])) ?></span></td>
                            <td class="small text-muted"><?= e(fmt_date($u['created_at'])) ?></td>
                            <td class="text-end">
                                <?php if ((int)$u['id'] === current_user()['id']): ?>
                                    <span class="small text-muted">(you)</span>
                                <?php else: ?>
                                    <form method="post" class="d-inline"
                                          onsubmit="return confirm('Delete this user?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
