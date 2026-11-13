<?php
// user/event_details.php - Full details of one event, with the Register button
// Address: /user/event_details.php?id=3
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_user();   // only logged-in users may see this page

$userId = (int) $_SESSION['user_id'];
$pageTitle = 'Event Details';
$pageError = '';
$event = null;
$myRegistration = null;

// The id must be a whole number of 1 or more; anything else means "not found"
$eventId = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($eventId === false) {
    $pageError = 'That event was not found.';
} else {
    try {
        $pdo = db();

        $stmt = $pdo->prepare(
            "SELECT e.event_id, e.event_name, e.event_date, e.event_time, e.venue,
                    e.capacity, e.fee,
                    (SELECT COUNT(*) FROM registrations r
                      WHERE r.event_id = e.event_id AND r.status = 'Registered') AS registered_count
             FROM events e
             WHERE e.event_id = ?"
        );
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();

        if (!$event) {
            $pageError = 'That event was not found.';
        } else {
            // Is this user registered for the event (or was, before an admin cancelled)?
            $stmt = $pdo->prepare(
                'SELECT registration_id, status FROM registrations WHERE user_id = ? AND event_id = ?'
            );
            $stmt->execute([$userId, $eventId]);
            $myRegistration = $stmt->fetch() ?: null;
        }

    } catch (PDOException $e) {
        error_log('user/event_details.php: ' . $e->getMessage());
        $pageError = 'A database problem occurred while loading the event. Please try again later.';
    }
}

// Work out the values shown on the page
$seatsLeft = 0;
$isFree = false;
$isPast = false;
if ($event) {
    $seatsLeft = max(0, (int) $event['capacity'] - (int) $event['registered_count']);
    $isFree = ((float) $event['fee']) == 0.0;
    $isPast = $event['event_date'] < date('Y-m-d');
}

require __DIR__ . '/../includes/header.php';
?>

<div class="mb-3">
    <a href="<?php echo BASE_URL; ?>/user/events.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Events</a>
</div>

<?php if (!$event): ?>

    <div class="card shadow-sm">
        <div class="card-body p-4 text-center">
            <h1 class="h4">Event not available</h1>
            <p class="text-muted">The event you are looking for does not exist or was removed.</p>
            <a href="<?php echo BASE_URL; ?>/user/events.php" class="btn btn-primary">See all events</a>
        </div>
    </div>

<?php else: ?>

    <div class="card shadow-sm">
        <div class="card-body p-4">

            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <h1 class="h3 mb-0"><?php echo e($event['event_name']); ?></h1>
                <?php if ($isPast): ?>
                    <span class="badge bg-secondary fs-6">Completed</span>
                <?php elseif ($seatsLeft === 0): ?>
                    <span class="badge bg-danger fs-6">Full</span>
                <?php else: ?>
                    <span class="badge bg-success fs-6">Open for registration</span>
                <?php endif; ?>
            </div>

            <table class="table table-bordered mb-4">
                <tr>
                    <th style="width:30%">Date</th>
                    <td><?php echo e(date('l, d F Y', strtotime($event['event_date']))); ?></td>
                </tr>
                <tr>
                    <th>Time</th>
                    <td><?php echo e(date('h:i A', strtotime($event['event_time']))); ?></td>
                </tr>
                <tr>
                    <th>Venue</th>
                    <td><?php echo e($event['venue']); ?></td>
                </tr>
                <tr>
                    <th>Registration fee</th>
                    <td>
                        <?php if ($isFree): ?>
                            Free
                        <?php else: ?>
                            &#8377; <?php echo e(number_format((float) $event['fee'], 2)); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Total capacity</th>
                    <td><?php echo (int) $event['capacity']; ?></td>
                </tr>
                <tr>
                    <th>Seats left</th>
                    <td><?php echo $seatsLeft; ?></td>
                </tr>
            </table>

            <?php if ($myRegistration && $myRegistration['status'] === 'Registered'): ?>

                <!-- 1. Already registered -->
                <div class="alert alert-success mb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>You are registered for this event (REG-<?php echo (int) $myRegistration['registration_id']; ?>).</span>
                    <a href="<?php echo BASE_URL; ?>/user/registration_confirmation.php?id=<?php echo (int) $myRegistration['registration_id']; ?>"
                       class="btn btn-success btn-sm">View Confirmation</a>
                </div>

            <?php elseif ($isPast): ?>

                <!-- 2. The event already happened -->
                <button type="button" class="btn btn-secondary" disabled>Event completed</button>

            <?php elseif ($seatsLeft === 0): ?>

                <!-- 3. No seats left -->
                <button type="button" class="btn btn-danger" disabled>Event full</button>

            <?php else: ?>

                <!-- 4. The user can register -->
                <?php if ($myRegistration): ?>
                    <div class="alert alert-info">
                        Your earlier registration for this event was cancelled. You can register again.
                    </div>
                <?php endif; ?>

                <form action="<?php echo BASE_URL; ?>/user/register_event.php" method="post"
                      onsubmit="return confirm('Register for this event?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="eventId" value="<?php echo (int) $event['event_id']; ?>">
                    <button type="submit" class="btn btn-primary">
                        Register<?php if (!$isFree): ?> (Fee &#8377; <?php echo e(number_format((float) $event['fee'], 2)); ?>)<?php endif; ?>
                    </button>
                </form>

            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>