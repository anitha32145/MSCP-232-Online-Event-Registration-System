<?php
// admin/report_payments.php - Payment Report
// Contains: payment status, transaction details, amount information
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();   // only the administrator may see this page

$pageTitle = 'Payment Report';
$pageError = '';
$rows = [];
$collected = 0.0;
$toCollect = 0.0;
$counts = ['Paid' => 0, 'Pending' => 0, 'Failed' => 0, 'Free' => 0];

try {
    $sql = 'SELECT p.payment_id, p.registration_id, p.amount, p.payment_status, p.payment_method,
                   r.status AS registration_status, u.name AS user_name, u.email AS user_email,
                   e.event_name
            FROM payments p
            JOIN registrations r ON r.registration_id = p.registration_id
            JOIN users u ON u.user_id = r.user_id
            JOIN events e ON e.event_id = r.event_id
            ORDER BY p.payment_id DESC';

    $rows = db()->query($sql)->fetchAll();

    foreach ($rows as $row) {
        $status = $row['payment_status'];

        if (isset($counts[$status])) {
            $counts[$status]++;
        }

        if ($status === 'Paid') {
            $collected += (float) $row['amount'];
        } elseif ($row['registration_status'] === 'Registered'
                  && ($status === 'Pending' || $status === 'Failed')) {
            // Money still expected: active registrations only
            $toCollect += (float) $row['amount'];
        }
    }

} catch (PDOException $e) {
    error_log('admin/report_payments.php: ' . $e->getMessage());
    $pageError = 'A database problem occurred while preparing the report. Please try again later.';
}

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Payment Report</h1>
    <div class="d-flex gap-2 d-print-none">
        <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="btn btn-outline-secondary btn-sm">All Reports</a>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">Print</button>
    </div>
</div>

<p class="text-muted small">Generated on <?php echo e(date('d M Y, h:i A')); ?></p>

<?php if ($pageError === ''): ?>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-3 d-flex flex-wrap gap-2">
            <span class="badge bg-light text-dark border p-2">Payment records: <?php echo count($rows); ?></span>
            <span class="badge bg-light text-dark border p-2">Paid: <?php echo $counts['Paid']; ?></span>
            <span class="badge bg-light text-dark border p-2">Pending: <?php echo $counts['Pending']; ?></span>
            <span class="badge bg-light text-dark border p-2">Failed: <?php echo $counts['Failed']; ?></span>
            <span class="badge bg-light text-dark border p-2">Free: <?php echo $counts['Free']; ?></span>
            <span class="badge bg-light text-dark border p-2">Total collected: &#8377; <?php echo e(number_format($collected, 2)); ?></span>
            <span class="badge bg-light text-dark border p-2">Still to collect: &#8377; <?php echo e(number_format($toCollect, 2)); ?></span>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <div class="alert alert-info">There are no payment records yet.</div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-striped table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Transaction</th>
                            <th>Reg. No.</th>
                            <th>User</th>
                            <th>Event</th>
                            <th>Amount (&#8377;)</th>
                            <th>Method</th>
                            <th>Payment status</th>
                            <th>Registration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $index => $row): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>TXN-<?php echo (int) $row['payment_id']; ?></td>
                                <td>REG-<?php echo (int) $row['registration_id']; ?></td>
                                <td>
                                    <?php echo e($row['user_name']); ?>
                                    <div class="small text-muted"><?php echo e($row['user_email']); ?></div>
                                </td>
                                <td><?php echo e($row['event_name']); ?></td>
                                <td><?php echo e(number_format((float) $row['amount'], 2)); ?></td>
                                <td><?php echo e($row['payment_method']); ?></td>
                                <td><?php echo e($row['payment_status']); ?></td>
                                <td><?php echo e($row['registration_status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <p class="text-muted small mt-3 mb-0">
            Total collected includes every payment marked Paid. Still to collect counts Pending and Failed payments
            of active registrations only.
        </p>
    <?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>