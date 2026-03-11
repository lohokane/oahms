<?php
require_once __DIR__ . '/../app/init.php';
require_admin_login();

$pdo = get_db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;

$room = [
    'room_number'       => '',
    'room_type'         => 'Single',
    'capacity'          => 1,
    'current_occupancy' => 0,
    'monthly_rent'      => 0,
    'status'            => 'Available',
];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM rooms WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $data = $stmt->fetch();
    if (!$data) {
        redirect('/public/rooms.php');
    }
    $room = $data;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room['room_number'] = trim($_POST['room_number'] ?? '');
    $room['room_type'] = $_POST['room_type'] ?? 'Single';
    $room['capacity'] = (int) ($_POST['capacity'] ?? 1);
    $room['current_occupancy'] = (int) ($_POST['current_occupancy'] ?? 0);
    $room['monthly_rent'] = (float) ($_POST['monthly_rent'] ?? 0);
    $room['status'] = $_POST['status'] ?? 'Available';

    if ($room['room_number'] === '') {
        $error = 'Room number is required.';
    } elseif ($room['capacity'] <= 0) {
        $error = 'Capacity must be at least 1.';
    } elseif ($room['current_occupancy'] < 0 || $room['current_occupancy'] > $room['capacity']) {
        $error = 'Occupancy must be between 0 and capacity.';
    } elseif ($room['monthly_rent'] < 0) {
        $error = 'Monthly rent cannot be negative.';
    } else {
        if ($isEdit) {
            $sql = 'UPDATE rooms
                    SET room_number = :room_number,
                        room_type = :room_type,
                        capacity = :capacity,
                        current_occupancy = :current_occupancy,
                        monthly_rent = :monthly_rent,
                        status = :status
                    WHERE id = :id';
        } else {
            $sql = 'INSERT INTO rooms
                    (room_number, room_type, capacity, current_occupancy, monthly_rent, status)
                    VALUES
                    (:room_number, :room_type, :capacity, :current_occupancy, :monthly_rent, :status)';
        }

        $stmt = $pdo->prepare($sql);
        $params = [
            ':room_number'       => $room['room_number'],
            ':room_type'         => $room['room_type'],
            ':capacity'          => $room['capacity'],
            ':current_occupancy' => $room['current_occupancy'],
            ':monthly_rent'      => $room['monthly_rent'],
            ':status'            => $room['status'],
        ];
        if ($isEdit) {
            $params[':id'] = $id;
        }

        try {
            $stmt->execute($params);
            if (!$isEdit) {
                $id = (int)$pdo->lastInsertId();
                $isEdit = true;
            }
            $success = 'Room saved successfully.';
        } catch (PDOException $e) {
            $error = 'Error saving room. Please ensure the room number is unique.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $isEdit ? 'Edit Room' : 'Add Room' ?> - OAHMS</title>
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
            <div class="topbar-title"><?= $isEdit ? 'Edit Room' : 'Add Room' ?></div>
            <div class="topbar-user">
                <a class="btn btn-secondary btn-sm" href="/public/logout.php">Logout</a>
            </div>
        </header>
        <section class="content">
            <div class="page-title">
                <h1><?= $isEdit ? 'Edit Room' : 'Add Room' ?></h1>
                <a href="/public/rooms.php" class="btn btn-secondary btn-sm">Back to list</a>
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
                            <label class="form-label" for="room_number">Room number</label>
                            <input class="form-input" type="text" id="room_number" name="room_number"
                                   value="<?= h($room['room_number']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="room_type">Room type</label>
                            <select class="form-select" id="room_type" name="room_type">
                                <option value="Single" <?= $room['room_type'] === 'Single' ? 'selected' : '' ?>>Single</option>
                                <option value="Double" <?= $room['room_type'] === 'Double' ? 'selected' : '' ?>>Double</option>
                                <option value="Shared" <?= $room['room_type'] === 'Shared' ? 'selected' : '' ?>>Shared</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="capacity">Capacity</label>
                            <input class="form-input" type="number" id="capacity" name="capacity" min="1"
                                   value="<?= h((string)$room['capacity']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="current_occupancy">Current occupancy</label>
                            <input class="form-input" type="number" id="current_occupancy" name="current_occupancy" min="0"
                                   value="<?= h((string)$room['current_occupancy']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="monthly_rent">Monthly rent</label>
                            <input class="form-input" type="number" step="0.01" min="0" id="monthly_rent" name="monthly_rent"
                                   value="<?= h((string)$room['monthly_rent']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="Available" <?= $room['status'] === 'Available' ? 'selected' : '' ?>>Available</option>
                                <option value="Occupied" <?= $room['status'] === 'Occupied' ? 'selected' : '' ?>>Occupied</option>
                                <option value="Inactive" <?= $room['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <a href="/public/rooms.php" class="btn btn-secondary btn-sm">Cancel</a>
                        <button class="btn btn-sm" type="submit">Save</button>
                    </div>
                </form>
            </div>
        </section>
    </main>
</div>
<script src="/assets/js/main.js"></script>
</body>
</html>

