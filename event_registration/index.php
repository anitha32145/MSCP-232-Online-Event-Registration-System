<?php
// index.php - Home page
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
?>

<section class="hero-section text-center">
    <h1 class="display-5">Online Event Registration System</h1>
    <p class="lead mt-3 mb-4">
        Register for seminars, workshops and cultural events online, and keep track of your registrations and payments.
    </p>

    <?php if (is_admin_logged_in()): ?>
        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="btn btn-light btn-lg">Go to Admin Dashboard</a>
    <?php elseif (is_user_logged_in()): ?>
        <a href="<?php echo BASE_URL; ?>/user/dashboard.php" class="btn btn-light btn-lg">Go to My Dashboard</a>
    <?php else: ?>
        <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-light btn-lg me-2">Register Now</a>
        <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-outline-light btn-lg">Login</a>
    <?php endif; ?>
</section>

<div class="row text-center mt-4 g-3">
    <div class="col-md-4">
        <div class="card shadow-sm h-100"><div class="card-body">
            <h2 class="h5">View Events</h2>
            <p class="text-muted mb-0">See available events with date, time, venue, fee and seats.</p>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100"><div class="card-body">
            <h2 class="h5">Register Online</h2>
            <p class="text-muted mb-0">Register for an event and get an instant confirmation.</p>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100"><div class="card-body">
            <h2 class="h5">Track Everything</h2>
            <p class="text-muted mb-0">View your registration history and check your payment status.</p>
        </div></div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>