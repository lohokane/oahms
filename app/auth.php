<?php
// Authentication helpers for OAHMS

declare(strict_types=1);

/**
 * Attempt to log in an admin user.
 *
 * @param string $username
 * @param string $password
 * @return bool
 */
function login_admin(string $username, string $password): bool
{
    $pdo = get_db();

    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $admin = $stmt->fetch();

    if (!$admin) {
        return false;
    }

    $stored = (string)($admin['password_hash'] ?? '');

    // Normal path: stored hash created by password_hash().
    $ok = password_verify($password, $stored);

    // Backward-compatible path: if a plain password was stored by mistake,
    // allow login once and upgrade to a secure hash.
    if (!$ok && $stored !== '' && hash_equals($stored, $password)) {
        $ok = true;
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $upd = $pdo->prepare('UPDATE admins SET password_hash = :hash WHERE id = :id');
        $upd->execute([
            ':hash' => $newHash,
            ':id'   => (int)$admin['id'],
        ]);
    }

    if (!$ok) {
        return false;
    }

    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];

    return true;
}

/**
 * Log out current admin.
 *
 * @return void
 */
function logout_admin(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * Check if admin is logged in.
 *
 * @return bool
 */
function is_admin_logged_in(): bool
{
    return isset($_SESSION['admin_id']);
}

/**
 * Require admin to be logged in, otherwise redirect to login.
 *
 * @return void
 */
function require_admin_login(): void
{
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

