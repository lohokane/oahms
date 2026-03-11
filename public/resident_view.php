<?php
require_once __DIR__ . '/../app/init.php';
require_admin_login();

$pdo = get_db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    redirect('/public/residents.php');
}

$stmt = $pdo->prepare('
    SELECT r.*,
           rm.room_number,
           rm.room_type
    FROM residents r
    LEFT JOIN rooms rm ON rm.id = r.room_id
    WHERE r.id = :id
');
$stmt->execute([':id' => $id]);
$resident = $stmt->fetch();

if (!$resident) {
    redirect('/public/residents.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resident Details - OAHMS</title>
    <link rel="stylesheet" href="/assets/css/style.css">
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
                <a class="nav-link" href="/public/dashboard.php"><span>Dashboard</span></a>
                <div class="nav-section-title">Management</div>
                <a class="nav-link active" href="/public/residents.php"><span>Residents</span></a>
                <a class="nav-link" href="/public/rooms.php"><span>Rooms</span></a>
                <a class="nav-link" href="/public/invoices.php"><span>Invoices</span></a>
                <a class="nav-link" href="/public/payments.php"><span>Payments</span></a>
                <div class="nav-section-title">Reports</div>
                <a class="nav-link" href="/public/reports.php"><span>Reports</span></a>
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
                <a class="btn btn-secondary btn-sm" href="/public/logout.php">Logout</a>
            </div>
        </header>
        <section class="content">
            <div class="page-title">
                <h1><?= h($resident['full_name']) ?></h1>
                <div>
                    <a href="/public/resident_form.php?id=<?= (int)$resident['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <a href="/public/residents.php" class="btn btn-secondary btn-sm">Back to list</a>
                </div>
            </div>

            <div class="card form-card">
                <div class="form-grid">
                    <div class="form-group">
                        <div class="form-label">Resident ID</div>
                        <div><?= h($resident['resident_identifier']) ?></div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Gender</div>
                        <div><?= h($resident['gender']) ?></div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Age</div>
                        <div><?= (int)$resident['age'] ?></div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Status</div>
                        <div><?= h($resident['status']) ?></div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Contact number</div>
                        <div><?= h($resident['contact_number']) ?></div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Emergency contact</div>
                        <div><?= h($resident['emergency_contact']) ?></div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Admission date</div>
                        <div><?= h($resident['admission_date']) ?></div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Room</div>
                        <div>
                            <?= $resident['room_number']
                                ? h($resident['room_number'] . ' (' . $resident['room_type'] . ')')
                                : '-' ?>
                        </div>
                    </div>
                </div>
                <div class="form-group" style="margin-top: 1rem;">
                    <div class="form-label">Address</div>
                    <div><?= nl2br(h($resident['address'])) ?></div>
                </div>
            </div>
        </section>
    </main>
</div>
<script src="/assets/js/main.js"></script>
</body>
</html>

