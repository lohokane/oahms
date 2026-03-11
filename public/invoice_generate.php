<?php
require_once __DIR__ . '/../app/init.php';
require_admin_login();

$pdo = get_db();

$targetMonth = $_POST['billing_month'] ?? date('Y-m');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For all active residents with a room, create invoice if not exists for month.
    $stmtRes = $pdo->prepare('
        SELECT r.id AS resident_id, r.full_name, rm.monthly_rent
        FROM residents r
        JOIN rooms rm ON rm.id = r.room_id
        WHERE r.status = "Active"
    ');
    $stmtRes->execute();
    $residents = $stmtRes->fetchAll();

    $created = 0;

    $checkStmt = $pdo->prepare('SELECT id FROM invoices WHERE resident_id = :resident_id AND billing_month = :billing_month');
    $insertStmt = $pdo->prepare('
        INSERT INTO invoices
        (resident_id, billing_month, room_rent, additional_charges, total_amount, payment_status)
        VALUES
        (:resident_id, :billing_month, :room_rent, 0, :total_amount, "PENDING")
    ');

    foreach ($residents as $res) {
        $checkStmt->execute([
            ':resident_id'   => $res['resident_id'],
            ':billing_month' => $targetMonth,
        ]);
        if ($checkStmt->fetch()) {
            continue;
        }

        $rent = (float)$res['monthly_rent'];
        $insertStmt->execute([
            ':resident_id'   => $res['resident_id'],
            ':billing_month' => $targetMonth,
            ':room_rent'     => $rent,
            ':total_amount'  => $rent,
        ]);
        $created++;
    }

    $message = $created . ' invoice(s) generated for ' . htmlspecialchars($targetMonth, ENT_QUOTES, 'UTF-8') . '.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Monthly Invoices - OAHMS</title>
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
                <a class="nav-link" href="rooms.php"><span>Rooms</span></a>
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
            <div class="topbar-title">Generate Monthly Invoices</div>
            <div class="topbar-user">
                <a class="btn btn-secondary btn-sm" href="logout.php">Logout</a>
            </div>
        </header>
        <section class="content">
            <div class="page-title">
                <h1>Generate Monthly Invoices</h1>
                <a href="invoices.php" class="btn btn-secondary btn-sm">Back to list</a>
            </div>

            <div class="card form-card">
                <?php if ($message): ?>
                    <div class="alert alert-success" data-flash><?= $message ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="billing_month">Billing month</label>
                            <input class="form-input" type="month" id="billing_month" name="billing_month"
                                   value="<?= h($targetMonth) ?>" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-sm" type="submit">Generate invoices</button>
                    </div>
                </form>
            </div>
        </section>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>

