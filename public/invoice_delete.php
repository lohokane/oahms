<?php
require_once __DIR__ . '/../app/init.php';
require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('invoices.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    redirect('invoices.php');
}

$pdo = get_db();

try {
    $stmt = $pdo->prepare('DELETE FROM invoices WHERE id = :id');
    $stmt->execute([':id' => $id]);
} catch (PDOException $e) {
    // Ignore and return to list; UI will still work.
}

redirect('invoices.php');

