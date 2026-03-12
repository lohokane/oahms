<?php
require_once __DIR__ . '/../app/init.php';
require_admin_login();

$pdo = get_db();

$month = trim($_GET['month'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = [];
$params = [];

if ($month !== '') {
    $where[] = 'i.billing_month = :month';
    $params[':month'] = $month;
}
if ($status !== '') {
    $where[] = 'i.payment_status = :status';
    $params[':status'] = $status;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT i.id,
           i.billing_month,
           i.room_rent,
           i.additional_charges,
           i.total_amount,
           i.payment_status,
           i.notes,
           r.full_name,
           r.room_number,
           r.bed_number
    FROM invoices i
    JOIN residents r ON r.id = i.resident_id
    $whereSql
    ORDER BY i.billing_month DESC, r.full_name ASC
");
$stmt->execute($params);
$invoices = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoices - OAHMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-header">OAHMS</div>
        <nav class="sidebar-nav">
            <div>
                <div class="nav-section-title">Overview</div>
                <a class="nav-link" href="dashboard.php"><span>Dashboard</span></a>
                <div class="nav-section-title">Management</div>
                <a class="nav-link" href="residents.php"><span>Residents</span></a>
                <a class="nav-link active" href="invoices.php"><span>Invoices</span></a>
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
            <div class="topbar-title">Invoices</div>
            <div class="topbar-user">
                <a class="btn btn-secondary btn-sm" href="logout.php">Logout</a>
            </div>
        </header>
        <section class="content">
            <div class="page-title">
                <h1>Invoices</h1>
                <div>
                    <a href="invoice_form.php" class="btn btn-sm">New invoice</a>
                    <a href="invoice_generate.php" class="btn btn-secondary btn-sm">Generate monthly</a>
                </div>
            </div>

            <div class="card form-card" style="margin-bottom: 1rem;">
                <form method="get">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="month">Billing month (YYYY-MM)</label>
                            <input class="form-input" type="month" id="month" name="month" value="<?= h($month) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Any</option>
                                <option value="PENDING" <?= $status === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                                <option value="PAID" <?= $status === 'PAID' ? 'selected' : '' ?>>Paid</option>
                                <option value="PARTIAL" <?= $status === 'PARTIAL' ? 'selected' : '' ?>>Partial</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-secondary btn-sm" type="submit">Filter</button>
                    </div>
                </form>
            </div>

            <div class="table-wrapper">
                <div class="table-header">
                    <div>Invoice list</div>
                </div>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Resident</th>
                        <th>Billing month</th>
                        <th>Room rent</th>
                        <th>Additional</th>
                        <th>Total</th>
                        <th>Notes</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($invoices): ?>
                        <?php foreach ($invoices as $inv): ?>
                            <tr>
                                <td>
                                    <a href="payment_form.php?invoice_id=<?= (int)$inv['id'] ?>">
                                        <?= h($inv['full_name']) ?>
                                    </a>
                                    <?php if (!empty($inv['room_number']) || !empty($inv['bed_number'])): ?>
                                        <span class="card-pill">Room <?= h($inv['room_number'] ?? '-') ?> / Bed <?= h($inv['bed_number'] ?? '-') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= h($inv['billing_month']) ?></td>
                                <td>₹<?= number_format((float)$inv['room_rent'], 2) ?></td>
                                <td>₹<?= number_format((float)$inv['additional_charges'], 2) ?></td>
                                <td>₹<?= number_format((float)$inv['total_amount'], 2) ?></td>
                                <td><?= h($inv['notes'] ?? '') ?></td>
                                <td>
                                    <?php if ($inv['payment_status'] === 'PAID'): ?>
                                        <span class="badge badge-success">Paid</span>
                                    <?php elseif ($inv['payment_status'] === 'PENDING'): ?>
                                        <span class="badge badge-warning">Pending</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Partial</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a class="btn btn-secondary btn-sm" href="invoice_form.php?id=<?= (int)$inv['id'] ?>">Edit</a>
                                    <form method="post" action="invoice_delete.php" style="display:inline;" onsubmit="return confirm('Delete this invoice?');">
                                        <input type="hidden" name="id" value="<?= (int)$inv['id'] ?>">
                                        <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8">No invoices found.</td></tr>
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

