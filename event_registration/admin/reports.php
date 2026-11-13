<?php
// admin/reports.php - The report menu: one card for each report
require_once __DIR__ . '/../includes/functions.php';

require_admin();   // only the administrator may see this page

$pageTitle = 'Reports';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Reports</h1>
    <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
</div>

<p class="text-muted">Choose a report. Each report can be printed from its own page.</p>

<div class="row g-3">

    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">User Registration Report</h2>
                <p class="text-muted">Every registration with the user's contact details, event, registration date and status.</p>
                <a href="<?php echo BASE_URL; ?>/admin/report_users.php" class="btn btn-primary">Open Report</a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Event-wise Registration Report</h2>
                <p class="text-muted">For each event: capacity, number of participants, cancellations and seats left.</p>
                <a href="<?php echo BASE_URL; ?>/admin/report_events.php" class="btn btn-primary">Open Report</a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Payment Report</h2>
                <p class="text-muted">All payments with transaction reference, amount, method and status.</p>
                <a href="<?php echo BASE_URL; ?>/admin/report_payments.php" class="btn btn-primary">Open Report</a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Attendance Report</h2>
                <p class="text-muted">The participant list of each event with Present, Absent or Not Marked.</p>
                <a href="<?php echo BASE_URL; ?>/admin/report_attendance.php" class="btn btn-primary">Open Report</a>
            </div>
        </div>
    </div>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>