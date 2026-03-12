<?php
require_once __DIR__ . '/../app/init.php';
require_admin_login();

$pdo = get_db();

$month = $_GET['month'] ?? date('Y-m');

// Monthly revenue (date range to avoid collation issues)
try {
    $start = new DateTime($month . '-01');
} catch (Exception $e) {
    $start = new DateTime(date('Y-m') . '-01');
    $month = $start->format('Y-m');
}
$end = (clone $start)->modify('+1 month');

$stmtRev = $pdo->prepare('
    SELECT IFNULL(SUM(payment_amount), 0) AS total
    FROM payments
    WHERE payment_date >= :start AND payment_date < :end
');
$stmtRev->execute([
    ':start' => $start->format('Y-m-d'),
    ':end'   => $end->format('Y-m-d'),
]);
$monthlyRevenue = (float)$stmtRev->fetch()['total'];

// Pending payments (invoices)
$pendingInvoices = $pdo->query('
    SELECT i.id, i.billing_month, i.total_amount, i.payment_status,
           r.full_name, r.room_number, r.bed_number
    FROM invoices i
    JOIN residents r ON r.id = i.resident_id
    WHERE i.payment_status = "PENDING"
    ORDER BY i.billing_month DESC, r.full_name ASC
')->fetchAll();

// Resident status summary
$activeResidents = (int)$pdo->query('SELECT COUNT(*) AS c FROM residents WHERE status = "Active"')->fetch()['c'];
$deceasedResidents = (int)$pdo->query('SELECT COUNT(*) AS c FROM residents WHERE status = "Deceased"')->fetch()['c'];
$dischargedResidents = (int)$pdo->query('SELECT COUNT(*) AS c FROM residents WHERE status = "Discharged"')->fetch()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - OAHMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-header"><img src="../assets/images/logo.jpg" class="logo_sidebar" alt="Logo" loading="eager" fetchpriority="high"></div>
        <nav class="sidebar-nav">
            <div>
                <div class="nav-section-title">Overview</div>
                <a class="nav-link" href="dashboard.php"><span>Dashboard</span></a>
                <div class="nav-section-title">Management</div>
                <a class="nav-link" href="residents.php"><span>Residents</span></a>
                <a class="nav-link" href="invoices.php"><span>Invoices</span></a>
                <a class="nav-link" href="payments.php"><span>Payments</span></a>
                <div class="nav-section-title">Reports</div>
                <a class="nav-link active" href="reports.php"><span>Reports</span></a>
            </div>
        </nav>
        <div class="sidebar-footer">
            Logged in as <?= h($_SESSION['admin_username'] ?? 'Admin') ?>
        </div>
    </aside>
    <main class="main">
        <header class="topbar">
            <div class="topbar-title">Reports</div>
            <div class="topbar-user">
                <a class="btn btn-secondary btn-sm" href="logout.php">Logout</a>
            </div>
        </header>
        <section class="content">
            <div class="page-title">
                <h1>Reports</h1>
            </div>

            <div class="card form-card" style="margin-bottom: 1.25rem;">
                <form method="get">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="month">Revenue month</label>
                            <input class="form-input" type="month" id="month" name="month" value="<?= h($month) ?>">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-secondary btn-sm" type="submit">Update</button>
                    </div>
                </form>
            </div>

            <div class="grid">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Revenue for <?= h($month) ?></div>
                    </div>
                    <div class="card-value">₹<?= number_format($monthlyRevenue, 2) ?></div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Active residents</div>
                    </div>
                    <div class="card-value"><?= number_format($activeResidents) ?></div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Deceased / Discharged</div>
                    </div>
                    <div class="card-value">
                        <?= number_format($deceasedResidents) ?> / <?= number_format($dischargedResidents) ?>
                    </div>
                </div>
            </div>

            <div class="table-wrapper" style="margin-top: 1.5rem;">
                <div class="table-header">
                    <div>Pending payments</div>
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
                    <?php if ($pendingInvoices): ?>
                        <?php foreach ($pendingInvoices as $inv): ?>
                            <tr>
                                <td>
                                    <?= h($inv['full_name']) ?>
                                    <?php if (!empty($inv['room_number']) || !empty($inv['bed_number'])): ?>
                                        <span class="card-pill">Room <?= h($inv['room_number'] ?? '-') ?> / Bed <?= h($inv['bed_number'] ?? '-') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= h($inv['billing_month']) ?></td>
                                <td>₹<?= number_format((float)$inv['total_amount'], 2) ?></td>
                                <td><span class="badge badge-warning"><?= h($inv['payment_status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">No pending invoices.</td></tr>
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

