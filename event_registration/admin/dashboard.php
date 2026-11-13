<?php
// admin/dashboard.php - The home page of the administrator
require_once __DIR__ . '/../includes/functions.php';

require_admin();   // only the administrator may see this page

$pageTitle = 'Admin Dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="card shadow-sm mb-4">
        <div class="card-body p-4 text-center">
        <h1 class="h3">Administrator Dashboard</h1>
        <p class="text-muted mb-0">
            Welcome, <b><?php echo e($_SESSION['admin_username']); ?></b>. Choose what you want to manage.
        </p>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Manage Events</h2>
                <p class="text-muted">Add, update and delete events, and manage their capacity.</p>
                <a href="<?php echo BASE_URL; ?>/admin/events.php" class="btn btn-primary">Open</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Manage Registrations</h2>
                <p class="text-muted">View participants and manage their registrations.</p>
                <a href="<?php echo BASE_URL; ?>/admin/registrations.php" class="btn btn-primary">Open</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Manage Payments</h2>
                <p class="text-muted">Track payments and update the payment status.</p>
                <a href="<?php echo BASE_URL; ?>/admin/payments.php" class="btn btn-primary">Open</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Manage Users</h2>
                <p class="text-muted">View and manage user information.</p>
                <a href="<?php echo BASE_URL; ?>/admin/users.php" class="btn btn-primary">Open</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Reports</h2>
                <p class="text-muted">User, event-wise, payment and attendance reports.</p>
                <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="btn btn-primary">Open</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>