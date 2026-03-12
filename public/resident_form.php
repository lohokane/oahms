<?php
require_once __DIR__ . '/../app/init.php';
require_admin_login();

$pdo = get_db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;

$resident = [
    'full_name'                 => '',
    'date_of_birth'             => null,
    'gender'                    => 'Male',
    'room_number'               => '',
    'bed_number'                => '',
    'guardian_name'             => '',
    'guardian_phone'            => '',
    'alternate_contact_number'  => '',
    'monthly_fee'               => 0,
    'joining_date'              => date('Y-m-d'),
    'status'                    => 'Active',
    'photo_path'                => null,
    'document_path'             => null,
    'document_name'             => null,
];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM residents WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $data = $stmt->fetch();
    if (!$data) {
        redirect('residents.php');
    }
    $resident = $data;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resident['full_name'] = trim($_POST['full_name'] ?? '');
    $resident['date_of_birth'] = $_POST['date_of_birth'] !== '' ? $_POST['date_of_birth'] : null;
    $resident['gender'] = $_POST['gender'] ?? 'Male';
    $resident['room_number'] = trim($_POST['room_number'] ?? '');
    $resident['bed_number'] = trim($_POST['bed_number'] ?? '');
    $resident['guardian_name'] = trim($_POST['guardian_name'] ?? '');
    $resident['guardian_phone'] = trim($_POST['guardian_phone'] ?? '');
    $resident['alternate_contact_number'] = trim($_POST['alternate_contact_number'] ?? '');
    $resident['monthly_fee'] = (float) ($_POST['monthly_fee'] ?? 0);
    $resident['joining_date'] = $_POST['joining_date'] ?? date('Y-m-d');
    $resident['status'] = $_POST['status'] ?? 'Active';

    if ($resident['full_name'] === '') {
        $error = 'Full name is required.';
    } elseif ($resident['monthly_fee'] < 0) {
        $error = 'Monthly fee cannot be negative.';
    } else {
        if ($isEdit) {
            $sql = 'UPDATE residents
                    SET full_name = :full_name,
                        date_of_birth = :date_of_birth,
                        gender = :gender,
                        room_number = :room_number,
                        bed_number = :bed_number,
                        guardian_name = :guardian_name,
                        guardian_phone = :guardian_phone,
                        alternate_contact_number = :alternate_contact_number,
                        monthly_fee = :monthly_fee,
                        joining_date = :joining_date,
                        status = :status
                    WHERE id = :id';
        } else {
            $sql = 'INSERT INTO residents
                    (full_name, date_of_birth, gender, room_number, bed_number, guardian_name, guardian_phone, alternate_contact_number, monthly_fee, joining_date, status)
                    VALUES
                    (:full_name, :date_of_birth, :gender, :room_number, :bed_number, :guardian_name, :guardian_phone, :alternate_contact_number, :monthly_fee, :joining_date, :status)';
        }

        $stmt = $pdo->prepare($sql);
        $params = [
            ':full_name'           => $resident['full_name'],
            ':date_of_birth'       => $resident['date_of_birth'],
            ':gender'              => $resident['gender'],
            ':room_number'         => $resident['room_number'] !== '' ? $resident['room_number'] : null,
            ':bed_number'          => $resident['bed_number'] !== '' ? $resident['bed_number'] : null,
            ':guardian_name'       => $resident['guardian_name'] !== '' ? $resident['guardian_name'] : null,
            ':guardian_phone'      => $resident['guardian_phone'] !== '' ? $resident['guardian_phone'] : null,
            ':alternate_contact_number' => $resident['alternate_contact_number'] !== '' ? $resident['alternate_contact_number'] : null,
            ':monthly_fee'         => $resident['monthly_fee'],
            ':joining_date'        => $resident['joining_date'],
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

            // Handle uploads
            $photoMaxSize = 2 * 1024 * 1024;        // 2MB
            $docMaxSize   = 5 * 1024 * 1024;        // 5MB each
            $maxDocs      = 10;

            // Photo (single)
            if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                if ($_FILES['photo']['size'] > $photoMaxSize) {
                    $error = 'Photo must be 2MB or smaller.';
                } else {
                    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $dir = dirname(__DIR__) . '/assets/images/residents';
                    if (!is_dir($dir)) {
                        mkdir($dir, 0775, true);
                    }
                    $filename = 'resident_' . $id . '_' . time() . ($ext ? '.' . $ext : '');
                    $dest = $dir . '/' . $filename;
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                        $photoPath = 'assets/images/residents/' . $filename;
                        $upd = $pdo->prepare('UPDATE residents SET photo_path = :p WHERE id = :id');
                        $upd->execute([':p' => $photoPath, ':id' => $id]);
                        $resident['photo_path'] = $photoPath;
                    }
                }
            }

            // Multiple documents (up to 10 per resident, 5MB each)
            if (!empty($_FILES['documents']['name']) && !$error) {
                $names = $_FILES['documents']['name'];
                $tmpNames = $_FILES['documents']['tmp_name'];
                $sizes = $_FILES['documents']['size'];
                $errors = $_FILES['documents']['error'];

                // Count existing docs
                $stmtCount = $pdo->prepare('SELECT COUNT(*) AS c FROM residents_documents WHERE resident_id = :id');
                $stmtCount->execute([':id' => $id]);
                $existing = (int)$stmtCount->fetch()['c'];

                $toUpload = [];
                foreach ($names as $idx => $origName) {
                    if ($origName === '' || $errors[$idx] !== UPLOAD_ERR_OK) {
                        continue;
                    }
                    if ($sizes[$idx] > $docMaxSize) {
                        $error = 'Each document must be 5MB or smaller.';
                        break;
                    }
                    $toUpload[] = $idx;
                }

                if (!$error) {
                    if ($existing + count($toUpload) > $maxDocs) {
                        $error = 'Maximum 10 documents allowed per resident.';
                    } else {
                        $dir = dirname(__DIR__) . '/assets/documents/residents';
                        if (!is_dir($dir)) {
                            mkdir($dir, 0775, true);
                        }
                        $insertStmt = $pdo->prepare('
                            INSERT INTO residents_documents (resident_id, document_path, document_name)
                            VALUES (:resident_id, :path, :name)
                        ');
                        foreach ($toUpload as $idx) {
                            $ext = pathinfo($names[$idx], PATHINFO_EXTENSION);
                            $filename = 'resident_doc_' . $id . '_' . time() . '_' . $idx . ($ext ? '.' . $ext : '');
                            $dest = $dir . '/' . $filename;
                            if (move_uploaded_file($tmpNames[$idx], $dest)) {
                                $relPath = 'assets/documents/residents/' . $filename;
                                $insertStmt->execute([
                                    ':resident_id' => $id,
                                    ':path'        => $relPath,
                                    ':name'        => $names[$idx],
                                ]);
                            }
                        }
                    }
                }
            }

            if (!$error) {
                $success = 'Resident saved successfully.';
            }
        } catch (PDOException $e) {
            $error = 'Error saving resident.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $isEdit ? 'Edit Resident' : 'Add Resident' ?> - OAHMS</title>
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
            <div class="topbar-title"><?= $isEdit ? 'Edit Resident' : 'Add Resident' ?></div>
            <div class="topbar-user">
                <a class="btn btn-secondary btn-sm" href="logout.php">Logout</a>
            </div>
        </header>
        <section class="content">
            <div class="page-title">
                <h1><?= $isEdit ? 'Edit Resident' : 'Add Resident' ?></h1>
                <a href="residents.php" class="btn btn-secondary btn-sm">Back to list</a>
            </div>

            <div class="card form-card">
                <?php if ($error): ?>
                    <div class="alert alert-error" data-flash><?= h($error) ?></div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success" data-flash><?= h($success) ?></div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="full_name">Full name</label>
                            <input class="form-input" type="text" id="full_name" name="full_name"
                                   value="<?= h($resident['full_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="date_of_birth">Date of birth</label>
                            <input class="form-input" type="date" id="date_of_birth" name="date_of_birth"
                                   value="<?= h($resident['date_of_birth'] ? (string)$resident['date_of_birth'] : '') ?>">
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
                            <label class="form-label" for="room_number">Room number</label>
                            <input class="form-input" type="text" id="room_number" name="room_number"
                                   value="<?= h($resident['room_number'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="bed_number">Bed number</label>
                            <input class="form-input" type="text" id="bed_number" name="bed_number"
                                   value="<?= h($resident['bed_number'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="guardian_name">Guardian name</label>
                            <input class="form-input" type="text" id="guardian_name" name="guardian_name"
                                   value="<?= h($resident['guardian_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="guardian_phone">Guardian phone</label>
                            <input class="form-input" type="text" id="guardian_phone" name="guardian_phone"
                                   value="<?= h($resident['guardian_phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="alternate_contact_number">Alternate contact</label>
                            <input class="form-input" type="text" id="alternate_contact_number" name="alternate_contact_number"
                                   value="<?= h($resident['alternate_contact_number'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="monthly_fee">Monthly fee</label>
                            <input class="form-input" type="number" step="0.01" min="0" id="monthly_fee" name="monthly_fee"
                                   value="<?= h((string)$resident['monthly_fee']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="joining_date">Joining date</label>
                            <input class="form-input" type="date" id="joining_date" name="joining_date"
                                   value="<?= h((string)$resident['joining_date']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="Active" <?= $resident['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Deceased" <?= $resident['status'] === 'Deceased' ? 'selected' : '' ?>>Deceased</option>
                                <option value="Discharged" <?= $resident['status'] === 'Discharged' ? 'selected' : '' ?>>Discharged</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="photo">Resident photo</label>
                            <input class="form-input" type="file" id="photo" name="photo" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="documents">Resident documents (max 10, 5MB each)</label>
                            <input class="form-input" type="file" id="documents" name="documents[]" multiple>
                        </div>
                    </div>
                    <div class="form-actions">
                        <a href="residents.php" class="btn btn-secondary btn-sm">Cancel</a>
                        <button class="btn btn-sm" type="submit">Save</button>
                    </div>
                </form>
            </div>
        </section>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>

