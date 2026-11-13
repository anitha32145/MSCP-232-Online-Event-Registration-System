<?php
// user/events.php - Events a user can register for
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_user();   // only logged-in users may see this page

$pageTitle = 'Available Events';
$pageError = '';
$events = [];

try {
    // Each event with the number of active registrations (used to work out seats left).
    // Only events dated today or later are shown.
    $sql = "SELECT e.event_id, e.event_name, e.event_date, e.event_time, e.venue,
                   e.capacity, e.fee, COUNT(r.registration_id) AS registered_count
            FROM events e
            LEFT JOIN registrations r
                   ON r.event_id = e.event_id AND r.status = 'Registered'
            WHERE e.event_date >= CURDATE()
            GROUP BY e.event_id, e.event_name, e.event_date, e.event_time,
                     e.venue, e.capacity, e.fee
            ORDER BY e.event_date ASC, e.event_time ASC";

    $events = db()->query($sql)->fetchAll();

} catch (PDOException $e) {
    error_log('user/events.php: ' . $e->getMessage());
    $pageError = 'A database problem occurred while loading events. Please try again later.';
}

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Available Events</h1>
    <a href="<?php echo BASE_URL; ?>/user/dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
</div>

<?php if (empty($events) && $pageError === ''): ?>
    <div class="alert alert-info">There are no upcoming events at the moment. Please check again later.</div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($events as $event): ?>
            <?php
                $seatsLeft = max(0, (int) $event['capacity'] - (int) $event['registered_count']);
                $isFree = ((float) $event['fee']) == 0.0;
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">

                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h2 class="h5 mb-0"><?php echo e($event['event_name']); ?></h2>
                            <?php if ($isFree): ?>
                                <span class="badge bg-success">Free</span>
                            <?php else: ?>
                                <span class="badge bg-primary">&#8377; <?php echo e(number_format((float) $event['fee'], 2)); ?></span>
                            <?php endif; ?>
                        </div>

                        <p class="mb-1"><b>Date:</b> <?php echo e(date('d M Y', strtotime($event['event_date']))); ?></p>
                        <p class="mb-1"><b>Time:</b> <?php echo e(date('h:i A', strtotime($event['event_time']))); ?></p>
                        <p class="mb-2"><b>Venue:</b> <?php echo e($event['venue']); ?></p>

                        <p class="mb-3">
                            <?php if ($seatsLeft === 0): ?>
                                <span class="badge bg-danger">Full</span>
                            <?php else: ?>
                                <span class="badge bg-info text-dark">
                                    <?php echo $seatsLeft; ?> of <?php echo (int) $event['capacity']; ?> seats left
                                </span>
                            <?php endif; ?>
                        </p>

                        <a href="<?php echo BASE_URL; ?>/user/event_details.php?id=<?php echo (int) $event['event_id']; ?>"
                           class="btn btn-outline-primary mt-auto">View Details</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>