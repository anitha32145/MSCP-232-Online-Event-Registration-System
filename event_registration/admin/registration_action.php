<?php
// admin/registration_action.php - Handles the buttons on the registrations page (POST only)
//   action = cancel       cancels the registration and notifies the user
//   action = attendance   sets Present, Absent or Not Marked (active registrations only)
// After the work the administrator returns to the list (same event selected).
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

require_admin();   // only the administrator may use this page

// Typing the address directly just goes back to the list
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/registrations.php');
}

// The page to return to: the same event selection the administrator was viewing
$eventFilter = filter_var(post_str('eventId'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
$returnPath = '/admin/registrations.php' . (($eventFilter !== false && $eventFilter > 0)
    ? '?eventId=' . $eventFilter : '');

if (!verify_csrf()) {
    set_flash('error', 'The form could not be verified. Please try again.');
    redirect($returnPath);
}

$registrationId = filter_var(post_str('registrationId'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($registrationId === false) {
    set_flash('error', 'That registration was not found.');
    redirect($returnPath);
}

$action = post_str('action');

try {
    $pdo = db();

    if ($action === 'cancel') {
        // ---------- Cancel the registration ----------
        // The cancel and the notification are saved together (one transaction)
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'SELECT r.user_id, r.status, e.event_name
             FROM registrations r
             JOIN events e ON e.event_id = r.event_id
             WHERE r.registration_id = ? FOR UPDATE'
        );
        $stmt->execute([$registrationId]);
        $reg = $stmt->fetch();

        if (!$reg) {
            $pdo->rollBack();
            set_flash('error', 'That registration was not found.');
        } elseif ($reg['status'] !== 'Registered') {
            $pdo->rollBack();
            set_flash('error', 'This registration is already cancelled.');
        } else {
            $stmt = $pdo->prepare("UPDATE registrations SET status = 'Cancelled' WHERE registration_id = ?");
            $stmt->execute([$registrationId]);

            $stmt = $pdo->prepare(
                'INSERT INTO notifications (user_id, message, notification_date) VALUES (?, ?, ?)'
            );
            $stmt->execute([
                (int) $reg['user_id'],
                'Your registration for "' . $reg['event_name'] . '" was cancelled by the administrator.',
                date('Y-m-d'),
            ]);

            $pdo->commit();
            set_flash('success', 'Registration REG-' . $registrationId . ' was cancelled and the user was notified.');
        }

    } elseif ($action === 'attendance') {
        // ---------- Mark attendance ----------
        $attendance = post_str('attendance');

        if (!in_array($attendance, ['Present', 'Absent', 'Not Marked'], true)) {
            set_flash('error', 'Please choose a valid attendance value.');
        } else {
            $stmt = $pdo->prepare('SELECT status FROM registrations WHERE registration_id = ?');
            $stmt->execute([$registrationId]);
            $status = $stmt->fetchColumn();

            if ($status === false) {
                set_flash('error', 'That registration was not found.');
            } elseif ($status !== 'Registered') {
                set_flash('error', 'Attendance can only be marked for active registrations.');
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE registrations SET attendance_status = ?
                     WHERE registration_id = ? AND status = 'Registered'"
                );
                $stmt->execute([$attendance, $registrationId]);
                set_flash('success', 'Attendance for REG-' . $registrationId . ' is now: ' . $attendance . '.');
            }
        }

    } else {
        set_flash('error', 'Unknown action.');
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('admin/registration_action.php: ' . $e->getMessage());
    set_flash('error', 'A database problem occurred. Nothing was changed. Please try again.');
}

redirect($returnPath);