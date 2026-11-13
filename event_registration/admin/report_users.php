<?php
// admin/report_users.php - User Registration Report
// Contains: user details, registration date, contact information
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();   // only the administrator may see this page

$pageTitle = 'User Registration Report';
$pageError = '';
$rows = [];
$active = 0;
$cancelled = 0;
$userIds = [];

try {
    $sql = 'SELECT r.registration_id, r.registration_date, r.status,
                   u.user_id, u.name, u.email, u.phone, e.event_name
            FROM registrations r
            JOIN users u ON u.user_id = r.user_id
            JOIN events e ON e.event_id = r.event_id
            ORDER BY r.registration_date DESC, r.registration_id DESC';

    $rows = db()->query($sql)->fetchAll();

    // Work out the summary numbers
    foreach ($rows as $row) {
        if ($row['status'] === 'Registered') {
            $active++;
        } else {
            $cancelled++;
        }
        $userIds[(int) $row['user_id']] = true;
    }

} catch (PDOException $e) {
    error_log('admin/report_users.php: ' . $e->getMessage());
    $pageError = 'A database problem occurred while preparing the report. Please try again later.';
}

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">User Registration Report</h1>
    <div class="d-flex gap-2 d-print-none">
        <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="btn btn-outline-secondary btn-sm">All Reports</a>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">Print</button>
    </div>
</div>

<p class="text-muted small">Generated on <?php echo e(date('d M Y, h:i A')); ?></p>

<?php if ($pageError === ''): ?>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-3 d-flex flex-wrap gap-2">
            <span class="badge bg-light text-dark border p-2">Total registrations: <?php echo count($rows); ?></span>
            <span class="badge bg-light text-dark border p-2">Active: <?php echo $active; ?></span>
            <span class="badge bg-light text-dark border p-2">Cancelled: <?php echo $cancelled; ?></span>
            <span class="badge bg-light text-dark border p-2">Different users: <?php echo count($userIds); ?></span>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <div class="alert alert-info">There are no registrations yet.</div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-striped table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Reg. No.</th>
                            <th>User name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Event</th>
                            <th>Registered on</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $index => $row): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>REG-<?php echo (int) $row['registration_id']; ?></td>
                                <td><?php echo e($row['name']); ?></td>
                                <td><?php echo e($row['email']); ?></td>
                                <td><?php echo e($row['phone']); ?></td>
                                <td><?php echo e($row['event_name']); ?></td>
                                <td><?php echo e(date('d M Y', strtotime($row['registration_date']))); ?></td>
                                <td><?php echo e($row['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>