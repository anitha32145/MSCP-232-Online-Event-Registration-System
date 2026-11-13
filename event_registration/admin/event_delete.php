<?php
// admin/event_delete.php - Deletes an event (POST only)
//
// An event with ACTIVE registrations cannot be deleted, because the database
// would also erase those users' registrations and payments. The administrator
// must cancel those registrations first.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

require_admin();   // only the administrator may use this page

// Typing the address directly just goes back to the list
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/events.php');
}

if (!verify_csrf()) {
    set_flash('error', 'The form could not be verified. Please try again.');
    redirect('/admin/events.php');
}

$eventId = filter_var(post_str('eventId'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($eventId === false) {
    set_flash('error', 'That event was not found.');
    redirect('/admin/events.php');
}

try {
    $pdo = db();

    // Read the event and count its active registrations
    $stmt = $pdo->prepare(
        "SELECT e.event_name,
                (SELECT COUNT(*) FROM registrations r
                  WHERE r.event_id = e.event_id AND r.status = 'Registered') AS registered_count
         FROM events e
         WHERE e.event_id = ?"
    );
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();

    if (!$event) {
        set_flash('error', 'That event was not found.');
    } elseif ((int) $event['registered_count'] > 0) {
        set_flash('error', '"' . $event['event_name'] . '" has ' . (int) $event['registered_count']
            . ' active registration(s) and cannot be deleted. Cancel those registrations first.');
    } else {
        $stmt = $pdo->prepare('DELETE FROM events WHERE event_id = ?');
        $stmt->execute([$eventId]);
        set_flash('success', 'Event "' . $event['event_name'] . '" was deleted.');
    }

} catch (PDOException $e) {
    error_log('admin/event_delete.php: ' . $e->getMessage());
    set_flash('error', 'A database problem occurred. The event was not deleted. Please try again.');
}

redirect('/admin/events.php');