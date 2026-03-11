<?php
require_once __DIR__ . '/../app/init.php';
require_admin_login();

$pdo = get_db();

// Summary cards
$totalResidents = (int) $pdo->query('SELECT COUNT(*) AS c FROM residents')->fetch()['c'] ?? 0;
$availableRooms = (int) $pdo->query('SELECT COUNT(*) AS c FROM rooms WHERE current_occupancy < capacity')->fetch()['c'] ?? 0;
$pendingInvoices = (int) $pdo->query("SELECT COUNT(*) AS c FROM invoices WHERE payment_status = 'PENDING'")->fetch()['c'] ?? 0;

$stmtMonth = $pdo->query("SELECT IFNULL(SUM(payment_amount), 0) AS total FROM payments WHERE DATE_FORMAT(payment_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");
$paymentsThisMonth = (float) ($stmtMonth->fetch()['total'] ?? 0);

// Recent activity (latest invoices and payments)
$recentInvoices = $pdo->query('
    SELECT i.id, r.full_name, i.total_amount, i.billing_month, i.payment_status
    FROM invoices i
    JOIN residents r ON r.id = i.resident_id
    ORDER BY i.created_at DESC
    LIMIT 5
')->fetchAll();

$recentPayments = $pdo->query('
    SELECT p.id, r.full_name, p.payment_amount, p.payment_date, p.payment_method
    FROM payments p
    JOIN residents r ON r.id = p.resident_id
    ORDER BY p.payment_date DESC
    LIMIT 5
')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - OAHMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            OAHMS
        </div>
        <nav class="sidebar-nav">
            <div>
                <div class="nav-section-title">Overview</div>
                <a class="nav-link active" href="dashboard.php"><span>Dashboard</span></a>
                <div class="nav-section-title">Management</div>
                <a class="nav-link" href="residents.php"><span>Residents</span></a>
                <a class="nav-link" href="rooms.php"><span>Rooms</span></a>
                <a class="nav-link" href="invoices.php"><span>Invoices</span></a>
                <a class="nav-link" href="payments.php"><span>Payments</span></a>
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
            <div class="topbar-title">Dashboard</div>
            <div class="topbar-user">
                <a class="btn btn-secondary btn-sm" href="logout.php">Logout</a>
            </div>
        </header>
        <section class="content">
            <div class="page-title">
                <h1>Welcome back</h1>
            </div>

            <div class="grid">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Total residents</div>
                    </div>
                    <div class="card-value"><?= number_format($totalResidents) ?></div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Available rooms</div>
                    </div>
                    <div class="card-value"><?= number_format($availableRooms) ?></div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Pending invoices</div>
                        <span class="card-pill">Billing</span>
                    </div>
                    <div class="card-value"><?= number_format($pendingInvoices) ?></div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Payments this month</div>
                        <span class="card-pill">Revenue</span>
                    </div>
                    <div class="card-value">₹<?= number_format($paymentsThisMonth, 2) ?></div>
                </div>
            </div>

            <div class="grid" style="margin-top: 1.5rem;">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Recent invoices</div>
                    </div>
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Resident</th>
                            <th>Billing month</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($recentInvoices): ?>
                            <?php foreach ($recentInvoices as $inv): ?>
                                <tr>
                                    <td><?= h($inv['full_name']) ?></td>
                                    <td><?= h($inv['billing_month']) ?></td>
                                    <td>₹<?= number_format((float)$inv['total_amount'], 2) ?></td>
                                    <td>
                                        <?php if ($inv['payment_status'] === 'PAID'): ?>
                                            <span class="badge badge-success">Paid</span>
                                        <?php elseif ($inv['payment_status'] === 'PENDING'): ?>
                                            <span class="badge badge-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger"><?= h($inv['payment_status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4">No invoices yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Recent payments</div>
                    </div>
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Resident</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($recentPayments): ?>
                            <?php foreach ($recentPayments as $pay): ?>
                                <tr>
                                    <td><?= h($pay['full_name']) ?></td>
                                    <td><?= h($pay['payment_date']) ?></td>
                                    <td>₹<?= number_format((float)$pay['payment_amount'], 2) ?></td>
                                    <td><?= h($pay['payment_method']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4">No payments recorded yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>

