<?php
require_once __DIR__ . '/../app/init.php';
require_admin_login();

$pdo = get_db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;

// Fetch rooms for dropdown
$roomsStmt = $pdo->query('SELECT id, room_number, capacity, current_occupancy FROM rooms ORDER BY room_number ASC');
$rooms = $roomsStmt->fetchAll();

$resident = [
    'resident_identifier' => '',
    'full_name'           => '',
    'gender'              => 'Male',
    'age'                 => '',
    'contact_number'      => '',
    'emergency_contact'   => '',
    'address'             => '',
    'admission_date'      => date('Y-m-d'),
    'room_id'             => null,
    'status'              => 'Active',
];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM residents WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $data = $stmt->fetch();
    if (!$data) {
        redirect('/public/residents.php');
    }
    $resident = $data;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resident['resident_identifier'] = trim($_POST['resident_identifier'] ?? '');
    $resident['full_name'] = trim($_POST['full_name'] ?? '');
    $resident['gender'] = $_POST['gender'] ?? 'Male';
    $resident['age'] = (int) ($_POST['age'] ?? 0);
    $resident['contact_number'] = trim($_POST['contact_number'] ?? '');
    $resident['emergency_contact'] = trim($_POST['emergency_contact'] ?? '');
    $resident['address'] = trim($_POST['address'] ?? '');
    $resident['admission_date'] = $_POST['admission_date'] ?? date('Y-m-d');
    $resident['room_id'] = $_POST['room_id'] !== '' ? (int) $_POST['room_id'] : null;
    $resident['status'] = $_POST['status'] ?? 'Active';

    if ($resident['resident_identifier'] === '' || $resident['full_name'] === '') {
        $error = 'Resident ID and full name are required.';
    } elseif ($resident['age'] <= 0) {
        $error = 'Please enter a valid age.';
    } else {
        if ($isEdit) {
            $sql = 'UPDATE residents
                    SET resident_identifier = :resident_identifier,
                        full_name = :full_name,
                        gender = :gender,
                        age = :age,
                        contact_number = :contact_number,
                        emergency_contact = :emergency_contact,
                        address = :address,
                        admission_date = :admission_date,
                        room_id = :room_id,
                        status = :status
                    WHERE id = :id';
        } else {
            $sql = 'INSERT INTO residents
                    (resident_identifier, full_name, gender, age, contact_number, emergency_contact, address, admission_date, room_id, status)
                    VALUES
                    (:resident_identifier, :full_name, :gender, :age, :contact_number, :emergency_contact, :address, :admission_date, :room_id, :status)';
        }

        $stmt = $pdo->prepare($sql);
        $params = [
            ':resident_identifier' => $resident['resident_identifier'],
            ':full_name'           => $resident['full_name'],
            ':gender'              => $resident['gender'],
            ':age'                 => $resident['age'],
            ':contact_number'      => $resident['contact_number'],
            ':emergency_contact'   => $resident['emergency_contact'],
            ':address'             => $resident['address'],
            ':admission_date'      => $resident['admission_date'],
            ':room_id'             => $resident['room_id'],
            ':status'              => $resident['status'],
        ];

        if ($isEdit) {
            $params[':id'] = $id;
        }

        try {
            $stmt->execute($params);
            if (!$isEdit) {
                $id = (int) $pdo->lastInsertId();
                $isEdit = true;
            }
            $success = 'Resident saved successfully.';
        } catch (PDOException $e) {
            $error = 'Error saving resident. Please ensure the Resident ID is unique.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $isEdit ? 'Edit Resident' : 'Add Resident' ?> - OAHMS</title>
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
            <div class="topbar-title"><?= $isEdit ? 'Edit Resident' : 'Add Resident' ?></div>
            <div class="topbar-user">
                <a class="btn btn-secondary btn-sm" href="/public/logout.php">Logout</a>
            </div>
        </header>
        <section class="content">
            <div class="page-title">
                <h1><?= $isEdit ? 'Edit Resident' : 'Add Resident' ?></h1>
                <a href="/public/residents.php" class="btn btn-secondary btn-sm">Back to list</a>
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
                            <label class="form-label" for="resident_identifier">Resident ID</label>
                            <input class="form-input" type="text" id="resident_identifier" name="resident_identifier"
                                   value="<?= h($resident['resident_identifier']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="full_name">Full name</label>
                            <input class="form-input" type="text" id="full_name" name="full_name"
                                   value="<?= h($resident['full_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="gender">Gender</label>
                            <select class="form-select" id="gender" name="gender">
                                <option value="Male" <?= $resident['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $resident['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= $resident['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="age">Age</label>
                            <input class="form-input" type="number" id="age" name="age" min="1"
                                   value="<?= h((string)$resident['age']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="contact_number">Contact number</label>
                            <input class="form-input" type="text" id="contact_number" name="contact_number"
                                   value="<?= h($resident['contact_number']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="emergency_contact">Emergency contact</label>
                            <input class="form-input" type="text" id="emergency_contact" name="emergency_contact"
                                   value="<?= h($resident['emergency_contact']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="admission_date">Admission date</label>
                            <input class="form-input" type="date" id="admission_date" name="admission_date"
                                   value="<?= h($resident['admission_date']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="room_id">Assigned room</label>
                            <select class="form-select" id="room_id" name="room_id">
                                <option value="">-- None --</option>
                                <?php foreach ($rooms as $room): ?>
                                    <?php
                                    $label = $room['room_number'] . ' (' . $room['current_occupancy'] . '/' . $room['capacity'] . ')';
                                    ?>
                                    <option value="<?= (int)$room['id'] ?>" <?= (int)$resident['room_id'] === (int)$room['id'] ? 'selected' : '' ?>>
                                        <?= h($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="Active" <?= $resident['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Inactive" <?= $resident['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label class="form-label" for="address">Address</label>
                        <textarea class="form-textarea" id="address" name="address"><?= h($resident['address']) ?></textarea>
                    </div>
                    <div class="form-actions">
                        <a href="/public/residents.php" class="btn btn-secondary btn-sm">Cancel</a>
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

