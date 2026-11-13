<?php
// user/register_event.php - Handles the "Register" button (POST only)
//
// The user id always comes from the session, never from the form, so nobody
// can register another person. Registering runs as ONE database transaction
// that locks the event row, so two people cannot take the last seat together.
// After the work is done the page REDIRECTS, so refreshing the browser cannot
// register twice.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

require_user();   // only logged-in users may use this page

// Typing the address directly just goes back to the events list
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/user/events.php');
}

if (!verify_csrf()) {
    set_flash('error', 'The form could not be verified. Please try again.');
    redirect('/user/events.php');
}

$eventId = filter_var(post_str('eventId'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($eventId === false) {
    set_flash('error', 'Invalid event selected.');
    redirect('/user/events.php');
}

$userId = (int) $_SESSION['user_id'];

// Registers the user for the event.
// Returns an array with a 'status' (success, not_found, past, full or already)
// and, for success, the 'registration_id'.
function register_for_event(PDO $pdo, int $userId, int $eventId): array
{
    $pdo->beginTransaction();

    try {
        // Step 1: lock the event row and read its details
        $stmt = $pdo->prepare(
            'SELECT event_name, event_date, capacity, fee FROM events WHERE event_id = ? FOR UPDATE'
        );
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();

        if (!$event) {
            $pdo->rollBack();
            return ['status' => 'not_found'];
        }

        // Step 2: no registration for events that already happened
        if ($event['event_date'] < date('Y-m-d')) {
            $pdo->rollBack();
            return ['status' => 'past'];
        }

        // Step 3: has this user registered for this event before?
        $stmt = $pdo->prepare(
            'SELECT registration_id, status FROM registrations
             WHERE user_id = ? AND event_id = ? FOR UPDATE'
        );
        $stmt->execute([$userId, $eventId]);
        $existing = $stmt->fetch();

        if ($existing && $existing['status'] === 'Registered') {
            $pdo->rollBack();
            return ['status' => 'already'];
        }

        // Step 4: are seats left?
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM registrations WHERE event_id = ? AND status = 'Registered'"
        );
        $stmt->execute([$eventId]);
        $taken = (int) $stmt->fetchColumn();

        if ($taken >= (int) $event['capacity']) {
            $pdo->rollBack();
            return ['status' => 'full'];
        }

        // Step 5: save the registration (a new row, or re-activate a cancelled one)
        $today = date('Y-m-d');

        if ($existing) {
            $registrationId = (int) $existing['registration_id'];
            $stmt = $pdo->prepare(
                "UPDATE registrations
                 SET status = 'Registered', attendance_status = 'Not Marked', registration_date = ?
                 WHERE registration_id = ?"
            );
            $stmt->execute([$today, $registrationId]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO registrations (user_id, event_id, registration_date, status, attendance_status)
                 VALUES (?, ?, ?, 'Registered', 'Not Marked')"
            );
            $stmt->execute([$userId, $eventId, $today]);
            $registrationId = (int) $pdo->lastInsertId();
        }

        // Step 6: save the payment record (Free, or Pending until the administrator updates it)
        $fee = number_format((float) $event['fee'], 2, '.', '');
        $isFree = ((float) $fee) == 0.0;
        $payStatus = $isFree ? 'Free' : 'Pending';
        $payMethod = $isFree ? 'Not Required' : 'Not Selected';

        $stmt = $pdo->prepare(
            'SELECT payment_id, payment_status FROM payments WHERE registration_id = ? FOR UPDATE'
        );
        $stmt->execute([$registrationId]);
        $payment = $stmt->fetch();

        if (!$payment) {
            $stmt = $pdo->prepare(
                'INSERT INTO payments (registration_id, amount, payment_status, payment_method)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$registrationId, $fee, $payStatus, $payMethod]);
        } elseif ($payment['payment_status'] !== 'Paid') {
            // An old unpaid record is refreshed. A payment already collected is left alone.
            $stmt = $pdo->prepare(
                'UPDATE payments SET amount = ?, payment_status = ?, payment_method = ?
                 WHERE payment_id = ?'
            );
            $stmt->execute([$fee, $payStatus, $payMethod, $payment['payment_id']]);
        }

        // Step 7: save a notification for the user
        $message = 'You are registered for "' . $event['event_name'] . '" (event date: '
                 . date('d M Y', strtotime($event['event_date'])) . ').';
        if (!$isFree) {
            $message .= ' A fee of Rs. ' . number_format((float) $fee, 2) . ' is pending.';
        }

        $stmt = $pdo->prepare(
            'INSERT INTO notifications (user_id, message, notification_date) VALUES (?, ?, ?)'
        );
        $stmt->execute([$userId, $message, $today]);

        // Step 8: everything worked, save all changes together
        $pdo->commit();
        return ['status' => 'success', 'registration_id' => $registrationId];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

try {
    $result = register_for_event(db(), $userId, $eventId);

    switch ($result['status']) {
        case 'success':
            set_flash('success', 'Registration successful!');
            redirect('/user/registration_confirmation.php?id=' . $result['registration_id']);
            break;

        case 'already':
            set_flash('error', 'You are already registered for this event.');
            redirect('/user/event_details.php?id=' . $eventId);
            break;

        case 'full':
            set_flash('error', 'Sorry, this event is full. No seats are left.');
            redirect('/user/event_details.php?id=' . $eventId);
            break;

        case 'past':
            set_flash('error', 'Registration is closed because this event has already taken place.');
            redirect('/user/event_details.php?id=' . $eventId);
            break;

        default:
            set_flash('error', 'That event was not found.');
            redirect('/user/events.php');
            break;
    }

} catch (PDOException $e) {
    // Error code 23000 means "duplicate value": the same registration was sent twice at once
    if ($e->getCode() === '23000') {
        set_flash('error', 'You are already registered for this event.');
    } else {
        error_log('user/register_event.php: ' . $e->getMessage());
        set_flash('error', 'A database problem occurred. Your registration was not saved. Please try again.');
    }
    redirect('/user/event_details.php?id=' . $eventId);
}