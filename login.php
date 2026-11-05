<?php
/**
 * login.php  -  the login page.
 * Checks the username and password against the `users` table.
 * Passwords are stored as hashes, so we use password_verify() to compare.
 */
require_once 'includes/db_connect.php';

// Already logged in? Go straight to the right page.
if (current_user()) {
    redirect(is_admin() ? 'index.php' : 'pos.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare('SELECT id, full_name, username, password_hash, role FROM users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);          // new session id after login (security)
        $_SESSION['user'] = [
            'id'       => (int)$user['id'],
            'name'     => $user['full_name'],
            'username' => $user['username'],
            'role'     => $user['role'],
        ];
        redirect($user['role'] === 'admin' ? 'index.php' : 'pos.php');
    }
    // Same message for wrong username OR wrong password, so nobody can guess usernames
    $error = 'Wrong username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | <?= e(STORE_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-11 col-sm-8 col-md-6 col-lg-4">
                <div class="card shadow border-0 mt-5">
                    <div class="card-header bg-brand text-white text-center py-4">
                        <div class="fs-1"><i class="bi bi-shop"></i></div>
                        <h4 class="mb-0 fw-bold"><?= e(STORE_NAME) ?></h4>
                        <div class="small">Cashier &amp; Admin Login</div>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger py-2"><?= e($error) ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" id="username" name="username" class="form-control form-control-lg"
                                       required autofocus value="<?= e($_POST['username'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" id="password" name="password" class="form-control form-control-lg" required>
                            </div>
                            <button type="submit" class="btn btn-brand btn-lg w-100">
                                <i class="bi bi-box-arrow-in-right"></i> Log in
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
