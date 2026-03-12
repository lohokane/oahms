<?php
require_once __DIR__ . '/../app/init.php';
require_admin_login();

$pdo = get_db();

// Optional deep-link: ?invoice_id=123
$prefillInvoiceId = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;

// Fetch pending/partial invoices for dropdown
$invStmt = $pdo->query('
    SELECT i.id,
           i.billing_month,
           i.total_amount,
           i.additional_charges,
           i.notes,
           i.payment_status,
           r.full_name,
           r.room_number,
           r.bed_number
    FROM invoices i
    JOIN residents r ON r.id = i.resident_id
    WHERE i.payment_status IN ("PENDING", "PARTIAL")
    ORDER BY i.billing_month DESC, r.full_name ASC
');
$invoices = $invStmt->fetchAll();

$data = [
    'invoice_id'     => '',
    'payment_date'   => date('Y-m-d'),
    'payment_amount' => 0,
    'payment_method' => '',
    'notes'          => '',
];

$error = '';
$success = '';

if ($prefillInvoiceId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $data['invoice_id'] = $prefillInvoiceId;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['invoice_id'] = (int) ($_POST['invoice_id'] ?? 0);
    $data['payment_date'] = $_POST['payment_date'] ?? date('Y-m-d');
    $data['payment_amount'] = (float) ($_POST['payment_amount'] ?? 0);
    $data['payment_method'] = trim($_POST['payment_method'] ?? '');
    $data['notes'] = trim($_POST['notes'] ?? '');

    if ($data['invoice_id'] <= 0) {
        $error = 'Please select an invoice.';
    } elseif ($data['payment_amount'] <= 0) {
        $error = 'Payment amount must be greater than zero.';
    } else {
        // Load invoice to get resident_id and totals
        $stmtInv = $pdo->prepare('SELECT id, resident_id, total_amount FROM invoices WHERE id = :id');
        $stmtInv->execute([':id' => $data['invoice_id']]);
        $invoice = $stmtInv->fetch();
        if (!$invoice) {
            $error = 'Invoice not found.';
        } else {
            $pdo->beginTransaction();
            try {
                // Insert payment
                $stmtPay = $pdo->prepare('
                    INSERT INTO payments
                    (invoice_id, resident_id, payment_date, payment_amount, payment_method, notes)
                    VALUES
                    (:invoice_id, :resident_id, :payment_date, :payment_amount, :payment_method, :notes)
                ');
                $stmtPay->execute([
                    ':invoice_id'     => $invoice['id'],
                    ':resident_id'    => $invoice['resident_id'],
                    ':payment_date'   => $data['payment_date'],
                    ':payment_amount' => $data['payment_amount'],
                    ':payment_method' => $data['payment_method'],
                    ':notes'          => $data['notes'],
                ]);

                // Calculate total paid so far
                $stmtSum = $pdo->prepare('SELECT IFNULL(SUM(payment_amount), 0) AS paid FROM payments WHERE invoice_id = :invoice_id');
                $stmtSum->execute([':invoice_id' => $invoice['id']]);
                $paid = (float)$stmtSum->fetch()['paid'];

                $newStatus = 'PARTIAL';
                if ($paid <= 0) {
                    $newStatus = 'PENDING';
                } elseif ($paid >= (float)$invoice['total_amount']) {
                    $newStatus = 'PAID';
                }

                $stmtUpd = $pdo->prepare('UPDATE invoices SET payment_status = :status WHERE id = :id');
                $stmtUpd->execute([
                    ':status' => $newStatus,
                    ':id'     => $invoice['id'],
                ]);

                $pdo->commit();
                $success = 'Payment recorded successfully.';
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Error recording payment.';
            }
        }
    }
}

// Prefill suggested payment amount (remaining due) when invoice is selected via deep-link
$invoiceDue = null;
if ((int)$data['invoice_id'] > 0) {
    $stmtInv = $pdo->prepare('SELECT total_amount FROM invoices WHERE id = :id');
    $stmtInv->execute([':id' => (int)$data['invoice_id']]);
    $invRow = $stmtInv->fetch();
    if ($invRow) {
        $stmtSum = $pdo->prepare('SELECT IFNULL(SUM(payment_amount), 0) AS paid FROM payments WHERE invoice_id = :invoice_id');
        $stmtSum->execute([':invoice_id' => (int)$data['invoice_id']]);
        $paid = (float)($stmtSum->fetch()['paid'] ?? 0);
        $invoiceDue = max(0, (float)$invRow['total_amount'] - $paid);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && (float)$data['payment_amount'] <= 0) {
            $data['payment_amount'] = $invoiceDue;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Record Payment - OAHMS</title>
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
            <div class="topbar-title">Record Payment</div>
            <div class="topbar-user">
                <a class="btn btn-secondary btn-sm" href="logout.php">Logout</a>
            </div>
        </header>
        <section class="content">
            <div class="page-title">
                <h1>Record Payment</h1>
                <a href="payments.php" class="btn btn-secondary btn-sm">Back to list</a>
            </div>

            <div class="card form-card">
                <?php if ($error): ?>
                    <div class="alert alert-error" data-flash><?= h($error) ?></div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success" data-flash><?= h($success) ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="invoice_id">Invoice</label>
                            <select class="form-select" id="invoice_id" name="invoice_id" required>
                                <option value="">-- Select invoice --</option>
                                <?php foreach ($invoices as $inv): ?>
                                    <?php
                                    $loc = '';
                                    if (!empty($inv['room_number']) || !empty($inv['bed_number'])) {
                                        $loc = ' - Room ' . ($inv['room_number'] ?? '-') . '/Bed ' . ($inv['bed_number'] ?? '-');
                                    }
                                    $label = $inv['full_name'] . $loc . ' - ' .
                                        $inv['billing_month'] . ' - ₹' . number_format((float)$inv['total_amount'], 2) .
                                        ' [' . $inv['payment_status'] . ']';
                                    ?>
                                    <option value="<?= (int)$inv['id'] ?>" <?= (int)$data['invoice_id'] === (int)$inv['id'] ? 'selected' : '' ?>>
                                        <?= h($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($invoiceDue !== null): ?>
                                <div class="form-label" style="margin-top: 0.35rem;">Due: ₹<?= number_format((float)$invoiceDue, 2) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="payment_date">Payment date</label>
                            <input class="form-input" type="date" id="payment_date" name="payment_date"
                                   value="<?= h($data['payment_date']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="payment_amount">Amount</label>
                            <input class="form-input" type="number" step="0.01" min="0" id="payment_amount" name="payment_amount"
                                   value="<?= h((string)$data['payment_amount']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="payment_method">Payment method</label>
                            <input class="form-input" type="text" id="payment_method" name="payment_method"
                                   value="<?= h($data['payment_method']) ?>">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea class="form-textarea" id="notes" name="notes"><?= h($data['notes']) ?></textarea>
                    </div>
                    <div class="form-actions">
                        <a href="payments.php" class="btn btn-secondary btn-sm">Cancel</a>
                        <button class="btn btn-sm" type="submit">Save payment</button>
                    </div>
                </form>
            </div>
        </section>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>

