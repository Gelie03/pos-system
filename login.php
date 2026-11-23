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
    <link rel="icon" type="image/svg+xml" href="assets/logo.svg">
    <!-- Remembers light/dark mode (see assets/theme.js) -->
    <script src="assets/theme.js"></script>
</head>
<body class="login-page">

    <!-- Light / dark mode switch -->
    <button type="button" class="btn btn-sm btn-outline-secondary position-fixed top-0 end-0 m-3"
            data-theme-toggle title="Switch to dark mode">
        <i class="bi bi-moon-stars-fill"></i>
    </button>

    <div class="container py-4">
        <div class="card login-card shadow-lg">
            <div class="row g-0">

                <!-- Left side: logo and store name -->
                <div class="col-md-5 login-side p-4 p-md-5 d-flex flex-column justify-content-center text-center text-md-start">
                    <img src="assets/logo.svg" alt="" class="login-logo mb-3 mx-auto mx-md-0">
                    <h3 class="fw-bold mb-1"><?= e(STORE_NAME) ?></h3>
                    <p class="mb-3 opacity-75">Your friendly neighborhood minimart</p>
                    <ul class="list-unstyled login-points small mb-0 d-none d-md-block">
                        <li><i class="bi bi-upc-scan"></i> Fast item-code scanning</li>
                        <li><i class="bi bi-percent"></i> Senior / PWD discounts</li>
                        <li><i class="bi bi-receipt"></i> Printable receipts</li>
                    </ul>
                </div>

                <!-- Right side: the login form -->
                <div class="col-md-7">
                    <div class="card-body p-4 p-md-5">
                        <h4 class="fw-bold mb-1">Welcome back</h4>
                        <p class="text-body-secondary mb-4">Log in to start your shift.</p>

                        <?php if ($error): ?>
                            <div class="alert alert-danger py-2"><?= e($error) ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" id="username" name="username" class="form-control"
                                           required autofocus value="<?= e($_POST['username'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" id="password" name="password" class="form-control" required>
                                </div>
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
