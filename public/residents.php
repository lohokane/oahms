<?php
require_once __DIR__ . '/../app/init.php';
require_admin_login();

$pdo = get_db();

$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$roomFilter = trim($_GET['room'] ?? '');
$sort = $_GET['sort'] ?? 'created_at';
$dir = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$params = [];
$whereParts = [];

if ($search !== '') {
    $whereParts[] = "(r.full_name LIKE :q1 OR r.room_number LIKE :q2 OR r.guardian_phone LIKE :q3)";
    $params[':q1'] = '%' . $search . '%';
    $params[':q2'] = '%' . $search . '%';
    $params[':q3'] = '%' . $search . '%';
}

if ($statusFilter !== '') {
    $whereParts[] = 'r.status = :status';
    $params[':status'] = $statusFilter;
}

if ($roomFilter !== '') {
    $whereParts[] = 'r.room_number = :room';
    $params[':room'] = $roomFilter;
}

$where = $whereParts ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

$sortMap = [
    'name' => 'r.full_name',
    'room' => 'r.room_number',
    'joining_date' => 'r.joining_date',
    'dob' => 'r.date_of_birth',
    'monthly_fee' => 'r.monthly_fee',
    'status' => 'r.status',
    'created_at' => 'r.created_at',
];
$sortCol = $sortMap[$sort] ?? $sortMap['created_at'];

$sql = "
    SELECT r.id,
           r.full_name,
           r.date_of_birth,
           r.gender,
           r.room_number,
           r.bed_number,
           r.guardian_name,
           r.guardian_phone,
           r.alternate_contact_number,
           r.monthly_fee,
           r.status,
           r.joining_date
    FROM residents r
    $where
    ORDER BY $sortCol $dir
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$residents = $stmt->fetchAll();

function sort_link(string $label, string $key, string $currentSort, string $currentDir, string $search): string
{
    $nextDir = 'asc';
    if ($currentSort === $key && strtoupper($currentDir) === 'ASC') {
        $nextDir = 'desc';
    }
    $qs = http_build_query(array_filter([
        'q' => $search,
        'status' => $_GET['status'] ?? '',
        'room' => $_GET['room'] ?? '',
        'sort' => $key,
        'dir' => $nextDir,
    ], static fn($v) => $v !== ''));
    return '<a href="residents.php?' . h($qs) . '">' . h($label) . '</a>';
}
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
            <img src="../assets/images/logo.jpg" class="logo_sidebar" alt="Logo" loading="eager" fetchpriority="high" style="border-radius: 10px; width: 100px;">OAHMS
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
                            <label class="form-label" for="q">Search (name / room / guardian phone)</label>
                            <input class="form-input" type="text" id="q" name="q" value="<?= h($search) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Any</option>
                                <option value="Active" <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Deceased" <?= $statusFilter === 'Deceased' ? 'selected' : '' ?>>Deceased</option>
                                <option value="Discharged" <?= $statusFilter === 'Discharged' ? 'selected' : '' ?>>Discharged</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="room">Room number</label>
                            <input class="form-input" type="text" id="room" name="room" value="<?= h($roomFilter) ?>">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-secondary btn-sm" type="submit">Search</button>
                        <a class="btn btn-secondary btn-sm" href="residents.php">Reset</a>
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
                        <th><?= sort_link('Name', 'name', (string)$sort, (string)$dir, $search) ?></th>
                        <th><?= sort_link('Room', 'room', (string)$sort, (string)$dir, $search) ?></th>
                        <th>Bed</th>
                        <th>Gender</th>
                        <th><?= sort_link('Monthly fee', 'monthly_fee', (string)$sort, (string)$dir, $search) ?></th>
                        <th><?= sort_link('Status', 'status', (string)$sort, (string)$dir, $search) ?></th>
                        <th><?= sort_link('Date of birth', 'dob', (string)$sort, (string)$dir, $search) ?></th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($residents): ?>
                        <?php foreach ($residents as $res): ?>
                            <tr>
                                <td><?= h($res['full_name']) ?></td>
                                <td><?= h($res['room_number'] ?? '-') ?></td>
                                <td><?= h($res['bed_number'] ?? '-') ?></td>
                                <td><?= h($res['gender']) ?></td>
                                <td>₹<?= number_format((float)$res['monthly_fee'], 2) ?></td>
                                <td>
                                    <?php if ($res['status'] === 'Active'): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php elseif ($res['status'] === 'Deceased'): ?>
                                        <span class="badge badge-danger">Deceased</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Discharged</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= h($res['date_of_birth'] ?? '-') ?></td>
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

