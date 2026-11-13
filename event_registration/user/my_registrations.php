<?php
// user/my_registrations.php - Registration history of the logged-in user,
// with the registration status, payment status and attendance of each one
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_user();   // only logged-in users may see this page

$userId = (int) $_SESSION['user_id'];
$pageTitle = 'My Registrations';
$pageError = '';
$registrations = [];

try {
    $stmt = db()->prepare(
        'SELECT r.registration_id, r.event_id, r.registration_date, r.status, r.attendance_status,
                e.event_name, e.event_date, e.venue,
                p.amount, p.payment_status
         FROM registrations r
         JOIN events e ON e.event_id = r.event_id
         LEFT JOIN payments p ON p.registration_id = r.registration_id
         WHERE r.user_id = ?
         ORDER BY r.registration_date DESC, r.registration_id DESC'
    );
    $stmt->execute([$userId]);
    $registrations = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('user/my_registrations.php: ' . $e->getMessage());
    $pageError = 'A database problem occurred while loading your registrations. Please try again later.';
}

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">My Registrations</h1>
    <a href="<?php echo BASE_URL; ?>/user/dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
</div>

<?php if (empty($registrations) && $pageError === ''): ?>

    <div class="alert alert-info">
        You have not registered for any event yet.
        <a href="<?php echo BASE_URL; ?>/user/events.php" class="alert-link">View events</a> to get started.
    </div>

<?php elseif (!empty($registrations)): ?>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Reg. No.</th>
                        <th>Event</th>
                        <th>Event date</th>
                        <th>Registered on</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Attendance</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $reg): ?>
                        <tr>
                            <td><b>REG-<?php echo (int) $reg['registration_id']; ?></b></td>

                            <td>
                                <a href="<?php echo BASE_URL; ?>/user/event_details.php?id=<?php echo (int) $reg['event_id']; ?>"
                                   class="text-decoration-none"><?php echo e($reg['event_name']); ?></a>
                                <div class="small text-muted"><?php echo e($reg['venue']); ?></div>
                            </td>

                            <td><?php echo e(date('d M Y', strtotime($reg['event_date']))); ?></td>
                            <td><?php echo e(date('d M Y', strtotime($reg['registration_date']))); ?></td>

                            <td>
                                <?php if ($reg['status'] === 'Registered'): ?>
                                    <span class="badge bg-success">Registered</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Cancelled</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($reg['payment_status'] === 'Free'): ?>
                                    <span class="badge bg-success">Free</span>
                                <?php elseif ($reg['payment_status'] === 'Paid'): ?>
                                    <span class="badge bg-success">Paid</span>
                                <?php elseif ($reg['payment_status'] === 'Failed'): ?>
                                    <span class="badge bg-danger">Failed</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><?php echo e($reg['payment_status'] ?? '-'); ?></span>
                                <?php endif; ?>
                                <?php if ($reg['amount'] !== null && $reg['payment_status'] !== 'Free'): ?>
                                    <div class="small text-muted">&#8377; <?php echo e(number_format((float) $reg['amount'], 2)); ?></div>
                                <?php endif; ?>
                            </td>

                            <td><?php echo e($reg['attendance_status']); ?></td>

                            <td>
                                <?php if ($reg['status'] === 'Registered'): ?>
                                    <a href="<?php echo BASE_URL; ?>/user/registration_confirmation.php?id=<?php echo (int) $reg['registration_id']; ?>"
                                       class="btn btn-outline-primary btn-sm">View</a>
                                <?php else: ?>
                                    <span class="text-muted">&mdash;</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>