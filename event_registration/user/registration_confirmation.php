<?php
// user/registration_confirmation.php - Confirmation of one registration
// Address: /user/registration_confirmation.php?id=5
//
// The query only returns the registration if it belongs to the logged-in user,
// so users cannot view each other's confirmations by changing the number.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_user();   // only logged-in users may see this page

$userId = (int) $_SESSION['user_id'];

$registrationId = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($registrationId === false) {
    set_flash('error', 'That registration was not found.');
    redirect('/user/my_registrations.php');
}

try {
    $stmt = db()->prepare(
        'SELECT r.registration_id, r.event_id, r.registration_date, r.status, r.attendance_status,
                e.event_name, e.event_date, e.event_time, e.venue,
                p.amount, p.payment_status
         FROM registrations r
         JOIN events e ON e.event_id = r.event_id
         LEFT JOIN payments p ON p.registration_id = r.registration_id
         WHERE r.registration_id = ? AND r.user_id = ?'
    );
    $stmt->execute([$registrationId, $userId]);
    $reg = $stmt->fetch();

} catch (PDOException $e) {
    error_log('user/registration_confirmation.php: ' . $e->getMessage());
    set_flash('error', 'A database problem occurred while loading the confirmation. Please try again.');
    redirect('/user/my_registrations.php');
}

if (!$reg) {
    set_flash('error', 'That registration was not found.');
    redirect('/user/my_registrations.php');
}

$pageTitle = 'Registration Confirmation';
$pageError = '';
require __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">

                <div class="text-center mb-4">
                    <h1 class="h3 text-success mb-1">Registration Confirmed</h1>
                    <p class="text-muted mb-0">Please keep your registration number for reference.</p>
                </div>

                <table class="table table-bordered mb-4">
                    <tr>
                        <th style="width:35%">Registration number</th>
                        <td><b>REG-<?php echo (int) $reg['registration_id']; ?></b></td>
                    </tr>
                    <tr>
                        <th>Event</th>
                        <td><?php echo e($reg['event_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Date</th>
                        <td><?php echo e(date('l, d F Y', strtotime($reg['event_date']))); ?></td>
                    </tr>
                    <tr>
                        <th>Time</th>
                        <td><?php echo e(date('h:i A', strtotime($reg['event_time']))); ?></td>
                    </tr>
                    <tr>
                        <th>Venue</th>
                        <td><?php echo e($reg['venue']); ?></td>
                    </tr>
                    <tr>
                        <th>Registered on</th>
                        <td><?php echo e(date('d M Y', strtotime($reg['registration_date']))); ?></td>
                    </tr>
                    <tr>
                        <th>Registration status</th>
                        <td>
                            <?php if ($reg['status'] === 'Registered'): ?>
                                <span class="badge bg-success">Registered</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?php echo e($reg['status']); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Payment</th>
                        <td>
                            <?php if ($reg['payment_status'] === 'Free'): ?>
                                <span class="badge bg-success">No payment required</span>
                            <?php elseif ($reg['payment_status'] === 'Paid'): ?>
                                <span class="badge bg-success">Paid</span>
                                &#8377; <?php echo e(number_format((float) $reg['amount'], 2)); ?>
                            <?php elseif ($reg['payment_status'] === 'Failed'): ?>
                                <span class="badge bg-danger">Failed</span>
                                &#8377; <?php echo e(number_format((float) $reg['amount'], 2)); ?>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><?php echo e($reg['payment_status'] ?? 'Not available'); ?></span>
                                <?php if ($reg['amount'] !== null): ?>
                                    &#8377; <?php echo e(number_format((float) $reg['amount'], 2)); ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <?php if ($reg['payment_status'] === 'Pending' || $reg['payment_status'] === 'Failed'): ?>
                    <div class="alert alert-warning">
                        Your fee of <b>&#8377; <?php echo e(number_format((float) $reg['amount'], 2)); ?></b>
                        is not yet paid. The administrator updates the payment status once your payment is received.
                        You can check it any time under <b>Payment Status</b>.
                    </div>
                <?php endif; ?>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?php echo BASE_URL; ?>/user/event_details.php?id=<?php echo (int) $reg['event_id']; ?>"
                       class="btn btn-outline-primary">View Event</a>
                    <a href="<?php echo BASE_URL; ?>/user/my_registrations.php" class="btn btn-primary">My Registrations</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>