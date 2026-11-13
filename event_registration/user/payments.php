<?php
// user/payments.php - The user's payment status and transaction records (read-only).
// Users cannot change a payment; the administrator updates the status.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_user();   // only logged-in users may see this page

$userId = (int) $_SESSION['user_id'];
$pageTitle = 'Payment Status';
$pageError = '';
$payments = [];
$totalPaid = 0.0;
$totalDue = 0.0;

try {
    // Only the payments of this user's own registrations
    $stmt = db()->prepare(
        'SELECT p.payment_id, p.registration_id, p.amount, p.payment_status, p.payment_method,
                r.status AS registration_status, e.event_name, e.event_date
         FROM payments p
         JOIN registrations r ON r.registration_id = p.registration_id
         JOIN events e ON e.event_id = r.event_id
         WHERE r.user_id = ?
         ORDER BY p.payment_id DESC'
    );
    $stmt->execute([$userId]);
    $payments = $stmt->fetchAll();

    // Add up what has been paid and what is still due (active registrations only)
    foreach ($payments as $pay) {
        if ($pay['payment_status'] === 'Paid') {
            $totalPaid += (float) $pay['amount'];
        } elseif ($pay['registration_status'] === 'Registered'
                  && in_array($pay['payment_status'], ['Pending', 'Failed'], true)) {
            $totalDue += (float) $pay['amount'];
        }
    }

} catch (PDOException $e) {
    error_log('user/payments.php: ' . $e->getMessage());
    $pageError = 'A database problem occurred while loading your payments. Please try again later.';
}

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Payment Status</h1>
    <a href="<?php echo BASE_URL; ?>/user/dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <div class="display-6 text-success">&#8377; <?php echo e(number_format($totalPaid, 2)); ?></div>
                <div class="text-muted">Total paid</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <div class="display-6 text-warning">&#8377; <?php echo e(number_format($totalDue, 2)); ?></div>
                <div class="text-muted">Still to pay</div>
            </div>
        </div>
    </div>
</div>

<?php if (empty($payments) && $pageError === ''): ?>

    <div class="alert alert-info">
        You have no payment records yet. A record is created when you register for an event.
        <a href="<?php echo BASE_URL; ?>/user/events.php" class="alert-link">View events</a>.
    </div>

<?php elseif (!empty($payments)): ?>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Transaction</th>
                        <th>Reg. No.</th>
                        <th>Event</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Payment status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td><b>TXN-<?php echo (int) $pay['payment_id']; ?></b></td>

                            <td>REG-<?php echo (int) $pay['registration_id']; ?></td>

                            <td>
                                <?php echo e($pay['event_name']); ?>
                                <div class="small text-muted">
                                    Event date: <?php echo e(date('d M Y', strtotime($pay['event_date']))); ?>
                                </div>
                            </td>

                            <td>&#8377; <?php echo e(number_format((float) $pay['amount'], 2)); ?></td>

                            <td><?php echo e($pay['payment_method']); ?></td>

                            <td>
                                <?php if ($pay['payment_status'] === 'Paid'): ?>
                                    <span class="badge bg-success">Paid</span>
                                <?php elseif ($pay['payment_status'] === 'Free'): ?>
                                    <span class="badge bg-success">Free</span>
                                <?php elseif ($pay['payment_status'] === 'Failed'): ?>
                                    <span class="badge bg-danger">Failed</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><?php echo e($pay['payment_status']); ?></span>
                                <?php endif; ?>

                                <?php if ($pay['registration_status'] === 'Cancelled'): ?>
                                    <div class="small text-muted">Registration cancelled</div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-muted small mt-3 mb-0">
        Payment status is updated by the administrator after your payment is received.
    </p>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>