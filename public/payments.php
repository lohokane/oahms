<?php
require_once __DIR__ . '/../app/init.php';
require_admin_login();

$pdo = get_db();

$stmt = $pdo->query('
    SELECT p.id,
           p.payment_date,
           p.payment_amount,
           p.payment_method,
           r.full_name,
           i.billing_month
    FROM payments p
    JOIN residents r ON r.id = p.resident_id
    JOIN invoices i ON i.id = p.invoice_id
    ORDER BY p.payment_date DESC, p.id DESC
');
$payments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payments - OAHMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-header"><img src="../assets/images/logo.jpg" class="logo_sidebar" alt="Logo" loading="eager" fetchpriority="high" style="border-radius: 10px; width: 100px;">OAHMS</div>
        <nav class="sidebar-nav">
            <div>
                <div class="nav-section-title">Overview</div>
                <a class="nav-link" href="dashboard.php"><span>Dashboard</span></a>
                <div class="nav-section-title">Management</div>
                <a class="nav-link" href="residents.php"><span>Residents</span></a>
                <a class="nav-link" href="invoices.php"><span>Invoices</span></a>
                <a class="nav-link active" href="payments.php"><span>Payments</span></a>
                <div class="nav-section-title">Reports</div>
                <a class="nav-link" href="reports.php"><span>Reports</span></a>
            </div>
        </nav>
        <div class="sidebar-footer">
            Logged in as <?= h($_SESSION['admin_username'] ?? 'Admin') ?>
        </div>
    </aside>
    <main class="main">
        <header class="topbar">
            <div class="topbar-title">Payments</div>
            <div class="topbar-user">
                <a class="btn btn-secondary btn-sm" href="logout.php">Logout</a>
            </div>
        </header>
        <section class="content">
            <div class="page-title">
                <h1>Payments</h1>
                <a href="payment_form.php" class="btn btn-sm">Record payment</a>
            </div>

            <div class="table-wrapper">
                <div class="table-header">
                    <div>Payment history</div>
                </div>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Resident</th>
                        <th>Billing month</th>
                        <th>Amount</th>
                        <th>Method</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($payments): ?>
                        <?php foreach ($payments as $pay): ?>
                            <tr>
                                <td><?= h($pay['payment_date']) ?></td>
                                <td><?= h($pay['full_name']) ?></td>
                                <td><?= h($pay['billing_month']) ?></td>
                                <td>₹<?= number_format((float)$pay['payment_amount'], 2) ?></td>
                                <td><?= h($pay['payment_method']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No payments recorded yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>

