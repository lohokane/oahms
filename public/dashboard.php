<?php
require_once __DIR__ . '/../app/init.php';
require_admin_login();

$pdo = get_db();

// Summary cards
$totalResidents = (int) $pdo->query('SELECT COUNT(*) AS c FROM residents')->fetch()['c'] ?? 0;
$activeResidents = (int) $pdo->query("SELECT COUNT(*) AS c FROM residents WHERE status = 'Active'")->fetch()['c'] ?? 0;
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

// Upcoming birthdays (next 30 days)
// Use numeric month-day (MMDD) comparisons to avoid collation issues on some MariaDB setups.
$upcomingBirthdays = [];
try {
    $start = new DateTime('today');
    $end = (clone $start)->modify('+30 days');

    $startKey = (int)$start->format('md'); // e.g. 0312
    $endKey = (int)$end->format('md');

    if ($startKey <= $endKey) {
        $sqlBirthdays = "
            SELECT full_name, date_of_birth, room_number, bed_number
            FROM residents
            WHERE status = 'Active'
              AND date_of_birth IS NOT NULL
              AND ((MONTH(date_of_birth) * 100) + DAY(date_of_birth)) BETWEEN :start_key AND :end_key
            ORDER BY ((MONTH(date_of_birth) * 100) + DAY(date_of_birth)) ASC, full_name ASC
        ";
        $paramsBirth = [':start_key' => $startKey, ':end_key' => $endKey];
    } else {
        // Window wraps year end; two ranges
        $sqlBirthdays = "
            SELECT full_name, date_of_birth, room_number, bed_number
            FROM residents
            WHERE status = 'Active'
              AND date_of_birth IS NOT NULL
              AND (
                ((MONTH(date_of_birth) * 100) + DAY(date_of_birth)) >= :start_key
                OR ((MONTH(date_of_birth) * 100) + DAY(date_of_birth)) <= :end_key
              )
            ORDER BY ((MONTH(date_of_birth) * 100) + DAY(date_of_birth)) ASC, full_name ASC
        ";
        $paramsBirth = [':start_key' => $startKey, ':end_key' => $endKey];
    }

    $stmtBirth = $pdo->prepare($sqlBirthdays);
    $stmtBirth->execute($paramsBirth);
    $upcomingBirthdays = $stmtBirth->fetchAll();
} catch (Exception $e) {
    $upcomingBirthdays = [];
}
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
                        <img src="../assets/images/logo.jpg" class="logo" alt="Logo" loading="eager" fetchpriority="high" width="100px">
        </div>
        <nav class="sidebar-nav">
            <div>
                <div class="nav-section-title">Overview</div>
                <a class="nav-link active" href="dashboard.php"><span>Dashboard</span></a>
                <div class="nav-section-title">Management</div>
                <a class="nav-link" href="residents.php"><span>Residents</span></a>
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
                        <div class="card-title">Active residents</div>
                    </div>
                    <div class="card-value"><?= number_format($activeResidents) ?></div>
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

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Upcoming birthdays (next 30 days)</div>
                    </div>
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Resident</th>
                            <th>Date of birth</th>
                            <th>Room / Bed</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($upcomingBirthdays): ?>
                            <?php foreach ($upcomingBirthdays as $b): ?>
                                <tr>
                                    <td><?= h($b['full_name']) ?></td>
                                    <td><?= h($b['date_of_birth']) ?></td>
                                    <td>
                                        <?php if (!empty($b['room_number']) || !empty($b['bed_number'])): ?>
                                            Room <?= h($b['room_number'] ?? '-') ?> / Bed <?= h($b['bed_number'] ?? '-') ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3">No upcoming birthdays.</td></tr>
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

