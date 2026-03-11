<?php
require_once __DIR__ . '/../app/init.php';
require_admin_login();

$pdo = get_db();

$roomsStmt = $pdo->query('
    SELECT id, room_number, room_type, capacity, current_occupancy, monthly_rent, status
    FROM rooms
    ORDER BY room_number ASC
');
$rooms = $roomsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rooms - OAHMS</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-header">OAHMS</div>
        <nav class="sidebar-nav">
            <div>
                <div class="nav-section-title">Overview</div>
                <a class="nav-link" href="/public/dashboard.php"><span>Dashboard</span></a>
                <div class="nav-section-title">Management</div>
                <a class="nav-link" href="/public/residents.php"><span>Residents</span></a>
                <a class="nav-link active" href="/public/rooms.php"><span>Rooms</span></a>
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
            <div class="topbar-title">Rooms</div>
            <div class="topbar-user">
                <a class="btn btn-secondary btn-sm" href="/public/logout.php">Logout</a>
            </div>
        </header>
        <section class="content">
            <div class="page-title">
                <h1>Rooms</h1>
                <a href="/public/room_form.php" class="btn btn-sm">Add room</a>
            </div>

            <div class="table-wrapper">
                <div class="table-header">
                    <div>Room list</div>
                </div>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Room #</th>
                        <th>Type</th>
                        <th>Capacity</th>
                        <th>Occupancy</th>
                        <th>Monthly rent</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($rooms): ?>
                        <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td><?= h($room['room_number']) ?></td>
                                <td><?= h($room['room_type']) ?></td>
                                <td><?= (int)$room['capacity'] ?></td>
                                <td><?= (int)$room['current_occupancy'] ?></td>
                                <td>₹<?= number_format((float)$room['monthly_rent'], 2) ?></td>
                                <td>
                                    <?php if ($room['status'] === 'Available'): ?>
                                        <span class="badge badge-success">Available</span>
                                    <?php elseif ($room['status'] === 'Occupied'): ?>
                                        <span class="badge badge-warning">Occupied</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a class="btn btn-secondary btn-sm" href="/public/room_form.php?id=<?= (int)$room['id'] ?>">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No rooms defined yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
<script src="/assets/js/main.js"></script>
</body>
</html>

