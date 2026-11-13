<?php
// user/dashboard.php - The home page of a logged-in user
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_user();   // only logged-in users may see this page

$userId = (int) $_SESSION['user_id'];
$pageTitle = 'My Dashboard';
$pageError = '';
$user = null;
$notifications = [];

try {
    $pdo = db();

    // The user's details
    $stmt = $pdo->prepare('SELECT name, email, phone FROM users WHERE user_id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    // The account may have been removed by the administrator
    if (!$user) {
        redirect('/logout.php');
    }

    // The 5 newest notifications
    $stmt = $pdo->prepare(
        'SELECT message, notification_date FROM notifications
         WHERE user_id = ? ORDER BY notification_id DESC LIMIT 5'
    );
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('user/dashboard.php: ' . $e->getMessage());
    $pageError = 'A database problem occurred while loading your dashboard. Please try again later.';
}

require __DIR__ . '/../includes/header.php';
?>

<div class="card shadow-sm mb-4">
    <div class="card-body p-4 text-center">
        <h1 class="h3">Welcome, <?php echo e($_SESSION['user_name']); ?></h1>
        <?php if ($user): ?>
            <p class="text-muted mb-0">
                Email: <?php echo e($user['email']); ?>
                &nbsp;|&nbsp;
                Phone: <?php echo e($user['phone']); ?>
            </p>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">View Events</h2>
                <p class="text-muted">See the events you can register for.</p>
                <a href="<?php echo BASE_URL; ?>/user/events.php" class="btn btn-success">View Events</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">My Registrations</h2>
                <p class="text-muted">Your registration history and status.</p>
                <a href="<?php echo BASE_URL; ?>/user/my_registrations.php" class="btn btn-primary">Open History</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Payment Status</h2>
                <p class="text-muted">Check the payment status of your registrations.</p>
                <a href="<?php echo BASE_URL; ?>/user/payments.php" class="btn btn-warning">Check Status</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">My Profile</h2>
                <p class="text-muted">Update your details or password.</p>
                <a href="<?php echo BASE_URL; ?>/user/profile.php" class="btn btn-outline-primary">Open Profile</a>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <h2 class="h5 mb-3">Recent Notifications</h2>
        <?php if (empty($notifications)): ?>
            <p class="text-muted mb-0">You have no notifications yet.</p>
        <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($notifications as $note): ?>
                    <li class="list-group-item px-0">
                        <div><?php echo e($note['message']); ?></div>
                        <small class="text-muted"><?php echo e(date('d M Y', strtotime($note['notification_date']))); ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>