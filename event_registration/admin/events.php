<?php
// admin/events.php - The administrator's list of ALL events (past and upcoming)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();   // only the administrator may see this page

$pageTitle = 'Manage Events';
$pageError = '';
$events = [];

try {
    $sql = "SELECT e.event_id, e.event_name, e.event_date, e.event_time, e.venue,
                   e.capacity, e.fee, COUNT(r.registration_id) AS registered_count
            FROM events e
            LEFT JOIN registrations r
                   ON r.event_id = e.event_id AND r.status = 'Registered'
            GROUP BY e.event_id, e.event_name, e.event_date, e.event_time,
                     e.venue, e.capacity, e.fee
            ORDER BY e.event_date ASC, e.event_time ASC";

    $events = db()->query($sql)->fetchAll();

} catch (PDOException $e) {
    error_log('admin/events.php: ' . $e->getMessage());
    $pageError = 'A database problem occurred while loading events. Please try again later.';
}

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Manage Events</h1>
    <div class="d-flex gap-2">
        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
        <a href="<?php echo BASE_URL; ?>/admin/event_form.php" class="btn btn-success btn-sm">+ Add New Event</a>
    </div>
</div>

<?php if (empty($events) && $pageError === ''): ?>
    <div class="alert alert-info">
        There are no events yet. Click <b>Add New Event</b> to create the first one.
    </div>
<?php elseif (!empty($events)): ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Event</th>
                        <th>Date and time</th>
                        <th>Venue</th>
                        <th>Fee</th>
                        <th>Registered</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                        <?php
                            $registered = (int) $event['registered_count'];
                            $capacity = (int) $event['capacity'];
                            $seatsLeft = max(0, $capacity - $registered);
                            $isFree = ((float) $event['fee']) == 0.0;
                            $isPast = $event['event_date'] < date('Y-m-d');
                        ?>
                        <tr>
                            <td><?php echo (int) $event['event_id']; ?></td>

                            <td><b><?php echo e($event['event_name']); ?></b></td>

                            <td>
                                <?php echo e(date('d M Y', strtotime($event['event_date']))); ?>
                                <div class="small text-muted"><?php echo e(date('h:i A', strtotime($event['event_time']))); ?></div>
                            </td>

                            <td><?php echo e($event['venue']); ?></td>

                            <td>
                                <?php if ($isFree): ?>
                                    <span class="badge bg-success">Free</span>
                                <?php else: ?>
                                    &#8377; <?php echo e(number_format((float) $event['fee'], 2)); ?>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php echo $registered; ?> / <?php echo $capacity; ?>
                                <div class="small text-muted"><?php echo $seatsLeft; ?> left</div>
                            </td>

                            <td>
                                <?php if ($isPast): ?>
                                    <span class="badge bg-secondary">Completed</span>
                                <?php elseif ($seatsLeft === 0): ?>
                                    <span class="badge bg-danger">Full</span>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark">Open</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <a href="<?php echo BASE_URL; ?>/admin/event_form.php?id=<?php echo (int) $event['event_id']; ?>"
                                       class="btn btn-outline-primary btn-sm">Edit</a>

                                    <?php if ($registered > 0): ?>
                                        <!-- An event with active registrations cannot be deleted -->
                                        <button type="button" class="btn btn-outline-secondary btn-sm" disabled
                                                title="This event has active registrations">Delete</button>
                                    <?php else: ?>
                                        <form action="<?php echo BASE_URL; ?>/admin/event_delete.php" method="post"
                                              onsubmit="return confirm('Delete this event permanently?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="eventId" value="<?php echo (int) $event['event_id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-muted small mt-3 mb-0">
        Events with active registrations cannot be deleted. Capacity cannot be set below the number of active registrations.
    </p>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>