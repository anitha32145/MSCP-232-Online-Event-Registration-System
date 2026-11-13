<?php
// admin/event_form.php - Add a new event, or edit an existing one
//   /admin/event_form.php         empty form (add)
//   /admin/event_form.php?id=3    form filled with event 3 (edit)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

require_admin();   // only the administrator may see this page

$pageTitle = 'Event Form';
$pageError = '';
$isEdit = false;
$eventId = 0;
$existing = null;
$form = ['name' => '', 'date' => '', 'time' => '', 'venue' => '', 'capacity' => '', 'fee' => '0'];

try {
    $pdo = db();

    // ---------- Is this an edit? Look for an id ----------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rawId = post_str('eventId');
    } else {
        $rawId = isset($_GET['id']) && is_string($_GET['id']) ? trim($_GET['id']) : '';
    }

    if ($rawId !== '') {
        $eventId = filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($eventId === false) {
            set_flash('error', 'That event was not found.');
            redirect('/admin/events.php');
        }

        $stmt = $pdo->prepare(
            "SELECT e.event_id, e.event_name, e.event_date, e.event_time, e.venue, e.capacity, e.fee,
                    (SELECT COUNT(*) FROM registrations r
                      WHERE r.event_id = e.event_id AND r.status = 'Registered') AS registered_count
             FROM events e
             WHERE e.event_id = ?"
        );
        $stmt->execute([$eventId]);
        $existing = $stmt->fetch();

        if (!$existing) {
            set_flash('error', 'That event was not found.');
            redirect('/admin/events.php');
        }

        $isEdit = true;
        $form = [
            'name'     => $existing['event_name'],
            'date'     => $existing['event_date'],
            'time'     => substr($existing['event_time'], 0, 5),   // 10:00:00 becomes 10:00
            'venue'    => $existing['venue'],
            'capacity' => (string) $existing['capacity'],
            'fee'      => (string) $existing['fee'],
        ];
    }

    // ---------- Save the form ----------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Keep what the administrator typed, so it is shown again after an error
        $form = [
            'name'     => post_str('eventName'),
            'date'     => post_str('eventDate'),
            'time'     => post_str('eventTime'),
            'venue'    => post_str('venue'),
            'capacity' => post_str('capacity'),
            'fee'      => post_str('fee'),
        ];

        // Check the date: it must be a real date written as 2026-12-31
        $dateObject = DateTime::createFromFormat('Y-m-d', $form['date']);
        $dateIsValid = $dateObject !== false && $dateObject->format('Y-m-d') === $form['date'];

        // A past date is allowed only when editing and the date was not changed
        $dateUnchanged = $isEdit && $existing !== null && $form['date'] === $existing['event_date'];

        $registered = $isEdit ? (int) $existing['registered_count'] : 0;

        if (!verify_csrf()) {
            $pageError = 'The form could not be verified. Please try again.';
        } elseif (strlen($form['name']) < 3 || strlen($form['name']) > 150) {
            $pageError = 'Event name must be 3 to 150 characters long.';
        } elseif (!$dateIsValid) {
            $pageError = 'Please choose a valid event date.';
        } elseif ($form['date'] < date('Y-m-d') && !$dateUnchanged) {
            $pageError = 'The event date cannot be in the past.';
        } elseif (preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $form['time']) !== 1) {
            $pageError = 'Please choose a valid event time.';
        } elseif (strlen($form['venue']) < 3 || strlen($form['venue']) > 150) {
            $pageError = 'Venue must be 3 to 150 characters long.';
        } elseif (preg_match('/^[0-9]{1,6}$/', $form['capacity']) !== 1
                  || (int) $form['capacity'] < 1
                  || (int) $form['capacity'] > 100000) {
            $pageError = 'Capacity must be a whole number between 1 and 100000.';
        } elseif ((int) $form['capacity'] < $registered) {
            $pageError = 'Capacity cannot be lower than the ' . $registered
                       . ' active registration(s) this event already has.';
        } elseif (preg_match('/^[0-9]{1,8}(\.[0-9]{1,2})?$/', $form['fee']) !== 1) {
            $pageError = 'Fee must be a number with at most 2 decimal places, for example 150 or 99.50. Use 0 for a free event.';
        } else {
            $timeForDatabase = $form['time'] . ':00';
            $capacity = (int) $form['capacity'];
            $fee = number_format((float) $form['fee'], 2, '.', '');

            if ($isEdit) {
                $stmt = $pdo->prepare(
                    'UPDATE events
                     SET event_name = ?, event_date = ?, event_time = ?, venue = ?, capacity = ?, fee = ?
                     WHERE event_id = ?'
                );
                $stmt->execute([$form['name'], $form['date'], $timeForDatabase,
                                $form['venue'], $capacity, $fee, $eventId]);
                set_flash('success', 'Event "' . $form['name'] . '" was updated successfully.');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO events (event_name, event_date, event_time, venue, capacity, fee)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$form['name'], $form['date'], $timeForDatabase,
                                $form['venue'], $capacity, $fee]);
                set_flash('success', 'Event "' . $form['name'] . '" was added successfully.');
            }

            redirect('/admin/events.php');
        }
    }

} catch (PDOException $e) {
    error_log('admin/event_form.php: ' . $e->getMessage());
    $pageError = 'A database problem occurred. The event was not saved. Please try again.';
}

$pageTitle = $isEdit ? 'Edit Event' : 'Add Event';
require __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">

        <div class="mb-3">
            <a href="<?php echo BASE_URL; ?>/admin/events.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Events</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4"><?php echo $isEdit ? 'Edit Event' : 'Add New Event'; ?></h1>

                <form action="" method="post">
                    <?php echo csrf_field(); ?>

                    <?php if ($isEdit): ?>
                        <!-- A hidden id tells the page this is an edit -->
                        <input type="hidden" name="eventId" value="<?php echo (int) $eventId; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="eventName" class="form-label">Event name</label>
                        <input type="text" class="form-control" id="eventName" name="eventName"
                               minlength="3" maxlength="150" value="<?php echo e($form['name']); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="eventDate" class="form-label">Date</label>
                            <input type="date" class="form-control" id="eventDate" name="eventDate"
                                   value="<?php echo e($form['date']); ?>"
                                   <?php if (!$isEdit): ?>min="<?php echo e(date('Y-m-d')); ?>"<?php endif; ?> required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="eventTime" class="form-label">Time</label>
                            <input type="time" class="form-control" id="eventTime" name="eventTime"
                                   value="<?php echo e($form['time']); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="venue" class="form-label">Venue</label>
                        <input type="text" class="form-control" id="venue" name="venue"
                               minlength="3" maxlength="150" value="<?php echo e($form['venue']); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="capacity" class="form-label">Capacity (number of seats)</label>
                            <input type="number" class="form-control" id="capacity" name="capacity"
                                   min="1" max="100000" step="1" value="<?php echo e($form['capacity']); ?>" required>
                            <?php if ($isEdit): ?>
                                <div class="form-text">
                                    This event has <?php echo (int) $existing['registered_count']; ?> active registration(s).
                                    Capacity cannot be lower than that.
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="fee" class="form-label">Fee (&#8377;)</label>
                            <input type="number" class="form-control" id="fee" name="fee"
                                   min="0" max="99999999.99" step="0.01" value="<?php echo e($form['fee']); ?>" required>
                            <div class="form-text">Enter 0 for a free event.</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Save Changes' : 'Add Event'; ?></button>
                        <a href="<?php echo BASE_URL; ?>/admin/events.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>