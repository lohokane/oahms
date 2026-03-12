<?php
require_once __DIR__ . '/../app/init.php';
require_admin_login();

$pdo = get_db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    redirect('residents.php');
}

$stmt = $pdo->prepare('SELECT * FROM residents WHERE id = :id');
$stmt->execute([':id' => $id]);
$resident = $stmt->fetch();

if (!$resident) {
    redirect('residents.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resident Details - OAHMS</title>
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
                <a class="nav-link" href="dashboard.php"><span>Dashboard</span></a>
                <div class="nav-section-title">Management</div>
                <a class="nav-link active" href="residents.php"><span>Residents</span></a>
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
            <div class="topbar-title">Resident details</div>
            <div class="topbar-user">
                <a class="btn btn-secondary btn-sm" href="logout.php">Logout</a>
            </div>
        </header>
        <section class="content">
            <div class="page-title">
                <h1><?= h($resident['full_name']) ?></h1>
                <div>
                    <a href="resident_form.php?id=<?= (int)$resident['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <a href="residents.php" class="btn btn-secondary btn-sm">Back to list</a>
                </div>
            </div>

            <div class="card form-card">
                <div class="form-grid">
                    <div class="form-group">
                        <div class="form-label">Full name</div>
                        <div><?= h($resident['full_name']) ?></div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Gender</div>
                        <div><?= h($resident['gender']) ?></div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Date of birth</div>
                        <div><?= h($resident['date_of_birth'] ?? '-') ?></div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Status</div>
                        <div><?= h($resident['status']) ?></div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Room number</div>
                        <div><?= h($resident['room_number'] ?? '-') ?></div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Bed number</div>
                        <div><?= h($resident['bed_number'] ?? '-') ?></div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Guardian name</div>
                        <div><?= h($resident['guardian_name'] ?? '-') ?></div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Guardian phone</div>
                        <div><?= h($resident['guardian_phone'] ?? '-') ?></div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Alternate contact</div>
                        <div><?= h($resident['alternate_contact_number'] ?? '-') ?></div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Monthly fee</div>
                        <div>₹<?= number_format((float)($resident['monthly_fee'] ?? 0), 2) ?></div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Joining date</div>
                        <div><?= h($resident['joining_date'] ?? '-') ?></div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>

