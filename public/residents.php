<?php
require_once __DIR__ . '/../app/init.php';
require_admin_login();

$pdo = get_db();

$search = trim($_GET['q'] ?? '');
$params = [];
$where = '';

if ($search !== '') {
    $where = "WHERE r.full_name LIKE :q OR r.resident_identifier LIKE :q";
    $params[':q'] = '%' . $search . '%';
}

$sql = "
    SELECT r.id,
           r.resident_identifier,
           r.full_name,
           r.gender,
           r.age,
           r.status,
           r.admission_date,
           rm.room_number
    FROM residents r
    LEFT JOIN rooms rm ON rm.id = r.room_id
    $where
    ORDER BY r.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$residents = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Residents - OAHMS</title>
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
            <div class="topbar-title">Residents</div>
            <div class="topbar-user">
                <a class="btn btn-secondary btn-sm" href="logout.php">Logout</a>
            </div>
        </header>
        <section class="content">
            <div class="page-title">
                <h1>Residents</h1>
                <a href="resident_form.php" class="btn btn-sm">Add resident</a>
            </div>

            <div class="card form-card" style="margin-bottom: 1rem;">
                <form method="get">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="q">Search (name or ID)</label>
                            <input class="form-input" type="text" id="q" name="q" value="<?= h($search) ?>">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-secondary btn-sm" type="submit">Search</button>
                    </div>
                </form>
            </div>

            <div class="table-wrapper">
                <div class="table-header">
                    <div>Resident list</div>
                </div>
                <table class="table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Room</th>
                        <th>Status</th>
                        <th>Admission</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($residents): ?>
                        <?php foreach ($residents as $res): ?>
                            <tr>
                                <td><?= h($res['resident_identifier']) ?></td>
                                <td><?= h($res['full_name']) ?></td>
                                <td><?= h($res['gender']) ?></td>
                                <td><?= (int) $res['age'] ?></td>
                                <td><?= h($res['room_number'] ?? '-') ?></td>
                                <td>
                                    <?php if ($res['status'] === 'Active'): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                    <td><?= h($res['admission_date']) ?></td>
                                <td>
                                    <a class="btn btn-secondary btn-sm" href="resident_view.php?id=<?= (int)$res['id'] ?>">View</a>
                                    <a class="btn btn-secondary btn-sm" href="resident_form.php?id=<?= (int)$res['id'] ?>">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8">No residents found.</td></tr>
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

