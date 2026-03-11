<?php
require_once __DIR__ . '/../app/init.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        if (login_admin($username, $password)) {
            redirect('dashboard.php');
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - OAHMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-page">
<div class="login-card">
    <h1 class="login-title">OAHMS Admin</h1>
    <p class="login-subtitle">Sign in to manage residents, rooms and billing.</p>

    <?php if ($error): ?>
        <div class="alert alert-error" data-flash><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input class="form-input" type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input class="form-input" type="password" id="password" name="password" required>
            </div>
        </div>
        <div class="form-actions" style="margin-top: 1.5rem;">
            <button class="btn" type="submit">Login</button>
        </div>
    </form>

    <div class="login-footer">
        OAHMS &mdash; Old Age Home Management System
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>

