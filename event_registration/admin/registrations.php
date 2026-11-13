<?php
// admin/registrations.php - The administrator's list of registrations.
// The administrator can view the participants of each event, mark attendance
// and cancel a registration.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();   // only the administrator may see this page

$pageTitle = 'Manage Registrations';
$pageError = '';
$registrations = [];
$events = [];

// Which event is selected? 0 means all events. Anything invalid falls back to 0.
$eventFilter = filter_var($_GET['eventId'] ?? '0', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($eventFilter === false) {
    $eventFilter = 0;
}

try {
    $pdo = db();

    // All events, for the selector
    $events = $pdo->query('SELECT event_id, event_name, event_date FROM events ORDER BY event_date DESC')
                  ->fetchAll();

    // The registrations (all, or only those of the selected event)
    $stmt = $pdo->prepare(
        'SELECT r.registration_id, r.registration_date, r.status, r.attendance_status,
                u.name AS user_name, u.email AS user_email,
                e.event_name, e.event_date,
                p.payment_status
         FROM registrations r
         JOIN users u ON u.user_id = r.user_id
         JOIN events e ON e.event_id = r.event_id
         LEFT JOIN payments p ON p.registration_id = r.registration_id
         WHERE (? = 0 OR r.event_id = ?)
         ORDER BY r.registration_id DESC'
    );
    $stmt->execute([$eventFilter, $eventFilter]);
    $registrations = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('admin/registrations.php: ' . $e->getMessage());
    $pageError = 'A database problem occurred while loading registrations. Please try again later.';
}

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Manage Registrations</h1>
    <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
</div>

<!-- Event selector -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="" method="get" class="row g-2 align-items-end">
            <div class="col-md-8">
                <label for="eventId" class="form-label">Show participants of</label>
                <select class="form-select" id="eventId" name="eventId">
                    <option value="0">All events</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?php echo (int) $event['event_id']; ?>"
                            <?php echo ((int) $event['event_id'] === $eventFilter) ? 'selected' : ''; ?>>
                            <?php echo e($event['event_name']); ?>
                            (<?php echo e(date('d M Y', strtotime($event['event_date']))); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">Show</button>
                <a href="<?php echo BASE_URL; ?>/admin/registrations.php" class="btn btn-outline-secondary flex-fill">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($registrations) && $pageError === ''): ?>

    <div class="alert alert-info">There are no registrations to show.</div>

<?php elseif (!empty($registrations)): ?>

    <p class="text-muted"><?php echo count($registrations); ?> registration(s) found.</p>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Reg. No.</th>
                        <th>User</th>
                        <th>Event</th>
                        <th>Registered on</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Attendance</th>
                        <th>Cancel</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $reg): ?>
                        <?php
                            $isActive = $reg['status'] === 'Registered';

                            // The text of the confirmation popup for the Cancel button
                            $confirmText = 'Cancel registration REG-' . (int) $reg['registration_id']
                                         . '? The user will be notified.';
                            if ($reg['payment_status'] === 'Paid') {
                                $confirmText .= ' The payment was already collected, and any refund must be handled outside this system.';
                            }
                        ?>
                        <tr>
                            <td><b>REG-<?php echo (int) $reg['registration_id']; ?></b></td>

                            <td>
                                <?php echo e($reg['user_name']); ?>
                                <div class="small text-muted"><?php echo e($reg['user_email']); ?></div>
                            </td>

                            <td>
                                <?php echo e($reg['event_name']); ?>
                                <div class="small text-muted">
                                    <?php echo e(date('d M Y', strtotime($reg['event_date']))); ?>
                                    <?php if ($reg['event_date'] < date('Y-m-d')): ?> (completed)<?php endif; ?>
                                </div>
                            </td>

                            <td><?php echo e(date('d M Y', strtotime($reg['registration_date']))); ?></td>

                            <td>
                                <?php if ($isActive): ?>
                                    <span class="badge bg-success">Registered</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Cancelled</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($reg['payment_status'] === 'Paid' || $reg['payment_status'] === 'Free'): ?>
                                    <span class="badge bg-success"><?php echo e($reg['payment_status']); ?></span>
                                <?php elseif ($reg['payment_status'] === 'Failed'): ?>
                                    <span class="badge bg-danger">Failed</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><?php echo e($reg['payment_status'] ?? '-'); ?></span>
                                <?php endif; ?>
                            </td>

                            <!-- Attendance: a small form (active registrations only) -->
                            <td>
                                <?php if ($isActive): ?>
                                    <form action="<?php echo BASE_URL; ?>/admin/registration_action.php" method="post"
                                          class="d-flex gap-1">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="registrationId" value="<?php echo (int) $reg['registration_id']; ?>">
                                        <input type="hidden" name="action" value="attendance">
                                        <input type="hidden" name="eventId" value="<?php echo (int) $eventFilter; ?>">
                                        <select name="attendance" class="form-select form-select-sm" style="min-width:120px">
                                            <?php foreach (['Not Marked', 'Present', 'Absent'] as $value): ?>
                                                <option value="<?php echo e($value); ?>"
                                                    <?php echo $reg['attendance_status'] === $value ? 'selected' : ''; ?>>
                                                    <?php echo e($value); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-outline-primary btn-sm">Save</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">&mdash;</span>
                                <?php endif; ?>
                            </td>

                            <!-- Cancel (active registrations only) -->
                            <td>
                                <?php if ($isActive): ?>
                                    <form action="<?php echo BASE_URL; ?>/admin/registration_action.php" method="post"
                                          onsubmit="return confirm(<?php echo e(json_encode($confirmText)); ?>);">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="registrationId" value="<?php echo (int) $reg['registration_id']; ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="eventId" value="<?php echo (int) $eventFilter; ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Cancel</button>
                                    </form>
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