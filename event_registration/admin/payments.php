<?php
// admin/payments.php - The administrator's list of all payments.
// The administrator records the payment method and updates the payment status here.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();   // only the administrator may see this page

$pageTitle = 'Manage Payments';
$pageError = '';
$payments = [];
$totalPaid = 0.0;
$totalDue = 0.0;
$methods = payment_methods();

try {
    $sql = 'SELECT p.payment_id, p.registration_id, p.amount, p.payment_status, p.payment_method,
                   r.status AS registration_status, u.name AS user_name, u.email AS user_email,
                   e.event_name
            FROM payments p
            JOIN registrations r ON r.registration_id = p.registration_id
            JOIN users u ON u.user_id = r.user_id
            JOIN events e ON e.event_id = r.event_id
            ORDER BY p.payment_id DESC';

    $payments = db()->query($sql)->fetchAll();

    // Add up what has been collected and what is still due (active registrations only)
    foreach ($payments as $pay) {
        if ($pay['payment_status'] === 'Paid') {
            $totalPaid += (float) $pay['amount'];
        } elseif ($pay['registration_status'] === 'Registered'
                  && in_array($pay['payment_status'], ['Pending', 'Failed'], true)) {
            $totalDue += (float) $pay['amount'];
        }
    }

} catch (PDOException $e) {
    error_log('admin/payments.php: ' . $e->getMessage());
    $pageError = 'A database problem occurred while loading payments. Please try again later.';
}

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Manage Payments</h1>
    <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <div class="display-6 text-success">&#8377; <?php echo e(number_format($totalPaid, 2)); ?></div>
                <div class="text-muted">Total collected</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <div class="display-6 text-warning">&#8377; <?php echo e(number_format($totalDue, 2)); ?></div>
                <div class="text-muted">Still to be collected</div>
            </div>
        </div>
    </div>
</div>

<?php if (empty($payments) && $pageError === ''): ?>

    <div class="alert alert-info">There are no payment records yet. A record is created when a user registers for an event.</div>

<?php elseif (!empty($payments)): ?>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Transaction</th>
                        <th>Reg. No.</th>
                        <th>User</th>
                        <th>Event</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $pay): ?>
                        <?php
                            // The status can be changed only for active registrations with a real (non-free) payment
                            $editable = $pay['registration_status'] === 'Registered'
                                        && $pay['payment_status'] !== 'Free';
                        ?>
                        <tr>
                            <td><b>TXN-<?php echo (int) $pay['payment_id']; ?></b></td>

                            <td>
                                REG-<?php echo (int) $pay['registration_id']; ?>
                                <?php if ($pay['registration_status'] === 'Cancelled'): ?>
                                    <div class="small text-muted">Cancelled</div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php echo e($pay['user_name']); ?>
                                <div class="small text-muted"><?php echo e($pay['user_email']); ?></div>
                            </td>

                            <td><?php echo e($pay['event_name']); ?></td>

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
                            </td>

                            <td>
                                <?php if ($editable): ?>
                                    <form action="<?php echo BASE_URL; ?>/admin/payment_update.php" method="post"
                                          class="d-flex flex-wrap gap-1"
                                          onsubmit="return confirm('Update this payment? The user will be notified.');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="paymentId" value="<?php echo (int) $pay['payment_id']; ?>">

                                        <select name="newStatus" class="form-select form-select-sm" style="width:auto">
                                            <?php foreach (['Pending', 'Paid', 'Failed'] as $status): ?>
                                                <option value="<?php echo e($status); ?>"
                                                    <?php echo $pay['payment_status'] === $status ? 'selected' : ''; ?>>
                                                    <?php echo e($status); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <select name="method" class="form-select form-select-sm" style="width:auto">
                                            <?php foreach ($methods as $method): ?>
                                                <?php
                                                    // Select the current method; if there is none yet, default to Cash
                                                    $isCurrent = $pay['payment_method'] === $method
                                                        || (!in_array($pay['payment_method'], $methods, true) && $method === 'Cash');
                                                ?>
                                                <option value="<?php echo e($method); ?>" <?php echo $isCurrent ? 'selected' : ''; ?>>
                                                    <?php echo e($method); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <button type="submit" class="btn btn-outline-primary btn-sm">Update</button>
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

    <p class="text-muted small mt-3 mb-0">
        The method is saved when the status is Paid or Failed. Choosing Pending clears the method.
        Free payments and payments of cancelled registrations cannot be changed.
    </p>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>