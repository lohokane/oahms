<?php
require_once __DIR__ . '/../app/init.php';
require_admin_login();

$pdo = get_db();

// Create / edit invoice
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

// Residents for dropdown
$resStmt = $pdo->query("SELECT id, full_name, monthly_fee FROM residents WHERE status = 'Active' ORDER BY full_name ASC");
$residents = $resStmt->fetchAll();

$data = [
    'resident_id'        => '',
    'billing_month'      => date('Y-m'),
    'room_rent'          => 0,
    'additional_charges' => 0,
    'total_amount'       => 0,
    'payment_status'     => 'PENDING',
    'notes'              => '',
];

$error = '';
$success = '';

if ($isEdit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        redirect('invoices.php');
    }
    $data['resident_id'] = (int)$row['resident_id'];
    $data['billing_month'] = $row['billing_month'];
    $data['room_rent'] = (float)$row['room_rent'];
    $data['additional_charges'] = (float)$row['additional_charges'];
    $data['total_amount'] = (float)$row['total_amount'];
    $data['payment_status'] = $row['payment_status'];
    $data['notes'] = $row['notes'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['resident_id'] = (int) ($_POST['resident_id'] ?? 0);
    $data['billing_month'] = $_POST['billing_month'] ?? date('Y-m');
    $data['room_rent'] = (float) ($_POST['room_rent'] ?? 0);
    $data['additional_charges'] = (float) ($_POST['additional_charges'] ?? 0);
    $data['total_amount'] = $data['room_rent'] + $data['additional_charges'];
    $data['payment_status'] = $_POST['payment_status'] ?? 'PENDING';
    $data['notes'] = trim($_POST['notes'] ?? '');

    if ($data['resident_id'] <= 0) {
        $error = 'Please select a resident.';
    } elseif ($data['billing_month'] === '') {
        $error = 'Please select a billing month.';
    } elseif ($data['total_amount'] <= 0) {
        $error = 'Total amount must be greater than zero.';
    } else {
        try {
            if ($isEdit) {
                $stmt = $pdo->prepare('
                    UPDATE invoices
                    SET resident_id = :resident_id,
                        billing_month = :billing_month,
                        room_rent = :room_rent,
                        additional_charges = :additional_charges,
                        total_amount = :total_amount,
                        payment_status = :payment_status,
                        notes = :notes
                    WHERE id = :id
                ');
                $stmt->execute([
                    ':resident_id'        => $data['resident_id'],
                    ':billing_month'      => $data['billing_month'],
                    ':room_rent'          => $data['room_rent'],
                    ':additional_charges' => $data['additional_charges'],
                    ':total_amount'       => $data['total_amount'],
                    ':payment_status'     => $data['payment_status'],
                    ':notes'              => $data['notes'],
                    ':id'                 => $id,
                ]);
                $success = 'Invoice updated successfully.';
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO invoices
                    (resident_id, billing_month, room_rent, additional_charges, total_amount, payment_status, notes)
                    VALUES
                    (:resident_id, :billing_month, :room_rent, :additional_charges, :total_amount, :payment_status, :notes)
                ');
                $stmt->execute([
                    ':resident_id'        => $data['resident_id'],
                    ':billing_month'      => $data['billing_month'],
                    ':room_rent'          => $data['room_rent'],
                    ':additional_charges' => $data['additional_charges'],
                    ':total_amount'       => $data['total_amount'],
                    ':payment_status'     => $data['payment_status'],
                    ':notes'              => $data['notes'],
                ]);
                $success = 'Invoice created successfully.';
            }
        } catch (PDOException $e) {
            $error = $isEdit
                ? 'Error updating invoice.'
                : 'Error creating invoice. There may already be an invoice for this resident and month.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $isEdit ? 'Edit Invoice' : 'New Invoice' ?> - OAHMS</title>
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
            <div class="topbar-title">New Invoice</div>
            <div class="topbar-user">
                <a class="btn btn-secondary btn-sm" href="logout.php">Logout</a>
            </div>
        </header>
        <section class="content">
            <div class="page-title">
                <h1><?= $isEdit ? 'Edit Invoice' : 'New Invoice' ?></h1>
                <a href="invoices.php" class="btn btn-secondary btn-sm">Back to list</a>
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
                            <label class="form-label" for="resident_id">Resident</label>
                            <select class="form-select" id="resident_id" name="resident_id" required>
                                <option value="">-- Select resident --</option>
                                <?php foreach ($residents as $res): ?>
                                    <option value="<?= (int)$res['id'] ?>"
                                            data-monthly-fee="<?= h((string)$res['monthly_fee']) ?>"
                                            <?= (int)$data['resident_id'] === (int)$res['id'] ? 'selected' : '' ?>>
                                        <?= h($res['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="billing_month">Billing month</label>
                            <input class="form-input" type="month" id="billing_month" name="billing_month"
                                   value="<?= h($data['billing_month']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="room_rent">Room rent</label>
                            <input class="form-input" type="number" step="0.01" min="0" id="room_rent" name="room_rent"
                                   value="<?= h((string)$data['room_rent']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="additional_charges">Additional charges</label>
                            <input class="form-input" type="number" step="0.01" min="0" id="additional_charges" name="additional_charges"
                                   value="<?= h((string)$data['additional_charges']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="payment_status">Initial status</label>
                            <select class="form-select" id="payment_status" name="payment_status">
                                <option value="PENDING" <?= $data['payment_status'] === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                                <option value="PAID" <?= $data['payment_status'] === 'PAID' ? 'selected' : '' ?>>Paid</option>
                                <option value="PARTIAL" <?= $data['payment_status'] === 'PARTIAL' ? 'selected' : '' ?>>Partial</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea class="form-textarea" id="notes" name="notes"><?= h($data['notes']) ?></textarea>
                    </div>
                    <div class="form-actions">
                        <a href="invoices.php" class="btn btn-secondary btn-sm">Cancel</a>
                        <button class="btn btn-sm" type="submit"><?= $isEdit ? 'Save changes' : 'Create invoice' ?></button>
                    </div>
                </form>
            </div>
        </section>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>

