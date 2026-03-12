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

// Invoices for this resident
$stmtInv = $pdo->prepare('
    SELECT i.*
    FROM invoices i
    WHERE i.resident_id = :resident_id
    ORDER BY i.billing_month DESC, i.created_at DESC
');
$stmtInv->execute([':resident_id' => $id]);
$invoices = $stmtInv->fetchAll();

// Documents for this resident
$stmtDocs = $pdo->prepare('
    SELECT document_path, document_name, created_at
    FROM residents_documents
    WHERE resident_id = :resident_id
    ORDER BY created_at DESC
');
$stmtDocs->execute([':resident_id' => $id]);
$documents = $stmtDocs->fetchAll();
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
                    <div class="form-group">
                        <div class="form-label">Photo</div>
                        <div>
                            <?php if (!empty($resident['photo_path'])): ?>
                                <img src="../<?= h($resident['photo_path']) ?>" alt="Resident photo" style="max-width: 160px; border-radius: 0.5rem;">
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Documents</div>
                        <div>
                            <?php if ($documents): ?>
                                <?php foreach ($documents as $doc): ?>
                                    <?php
                                    $docName = $doc['document_name'] ?: basename($doc['document_path']);
                                    $ext = strtolower(pathinfo($doc['document_path'], PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                                    ?>
                                    <div style="margin-bottom:0.5rem;">
                                        <?php if ($isImage): ?>
                                            <img src="../<?= h($doc['document_path']) ?>" alt="Resident document" style="max-width: 120px; border-radius: 0.5rem; display:block; margin-bottom:0.2rem;">
                                        <?php endif; ?>
                                        <a href="../<?= h($doc['document_path']) ?>" target="_blank"><?= h($docName) ?></a>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-wrapper" style="margin-top: 1.5rem;">
                <div class="table-header">
                    <div>Invoices</div>
                </div>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Billing month</th>
                        <th>Room rent</th>
                        <th>Additional</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($invoices): ?>
                        <?php foreach ($invoices as $inv): ?>
                            <tr>
                                <td><?= h($inv['billing_month']) ?></td>
                                <td>₹<?= number_format((float)$inv['room_rent'], 2) ?></td>
                                <td>₹<?= number_format((float)$inv['additional_charges'], 2) ?></td>
                                <td>₹<?= number_format((float)$inv['total_amount'], 2) ?></td>
                                <td>
                                    <?php if ($inv['payment_status'] === 'PAID'): ?>
                                        <span class="badge badge-success">Paid</span>
                                    <?php elseif ($inv['payment_status'] === 'PENDING'): ?>
                                        <span class="badge badge-warning">Pending</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Partial</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a class="btn btn-sm" href="payment_form.php?invoice_id=<?= (int)$inv['id'] ?>">Pay</a>
                                    <a class="btn btn-secondary btn-sm" href="invoice_form.php?id=<?= (int)$inv['id'] ?>">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No invoices for this resident.</td></tr>
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

