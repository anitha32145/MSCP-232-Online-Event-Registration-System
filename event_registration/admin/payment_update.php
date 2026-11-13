<?php
// admin/payment_update.php - Saves a payment status change (POST only)
//
// The change and the notification to the user are saved together in ONE
// transaction, and the payment row is locked while it changes.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

require_admin();   // only the administrator may use this page

// Typing the address directly just goes back to the list
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/payments.php');
}

if (!verify_csrf()) {
    set_flash('error', 'The form could not be verified. Please try again.');
    redirect('/admin/payments.php');
}

$paymentId = filter_var(post_str('paymentId'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($paymentId === false) {
    set_flash('error', 'That payment was not found.');
    redirect('/admin/payments.php');
}

// Only these statuses and methods are accepted
$newStatus = post_str('newStatus');
if (!in_array($newStatus, ['Pending', 'Paid', 'Failed'], true)) {
    set_flash('error', 'Please choose a valid payment status.');
    redirect('/admin/payments.php');
}

$method = post_str('method');
if ($newStatus === 'Pending') {
    $method = 'Not Selected';   // a pending payment has no method yet
} elseif (!in_array($method, payment_methods(), true)) {
    set_flash('error', 'Please choose a valid payment method.');
    redirect('/admin/payments.php');
}

try {
    $pdo = db();
    $pdo->beginTransaction();

    // Read and lock the payment together with its registration, user and event
    $stmt = $pdo->prepare(
        'SELECT p.payment_status, p.payment_method, p.amount,
                r.status AS registration_status, r.user_id, e.event_name
         FROM payments p
         JOIN registrations r ON r.registration_id = p.registration_id
         JOIN events e ON e.event_id = r.event_id
         WHERE p.payment_id = ? FOR UPDATE'
    );
    $stmt->execute([$paymentId]);
    $pay = $stmt->fetch();

    if (!$pay) {
        $pdo->rollBack();
        set_flash('error', 'That payment was not found.');
    } elseif ($pay['registration_status'] !== 'Registered') {
        $pdo->rollBack();
        set_flash('error', 'The registration is cancelled, so its payment cannot be changed.');
    } elseif ($pay['payment_status'] === 'Free') {
        $pdo->rollBack();
        set_flash('error', 'This event is free, so its payment cannot be changed.');
    } elseif ($pay['payment_status'] === $newStatus && $pay['payment_method'] === $method) {
        $pdo->rollBack();
        set_flash('error', 'Nothing was changed. The payment already has this status and method.');
    } else {
        // Save the new status and method
        $stmt = $pdo->prepare('UPDATE payments SET payment_status = ?, payment_method = ? WHERE payment_id = ?');
        $stmt->execute([$newStatus, $method, $paymentId]);

        // Tell the user
        $message = 'The payment status for "' . $pay['event_name'] . '" (Rs. '
                 . number_format((float) $pay['amount'], 2) . ') was updated to ' . $newStatus;
        if ($newStatus === 'Paid') {
            $message .= ' using ' . $method . '. Transaction reference: TXN-' . $paymentId;
        }
        $message .= '.';

        $stmt = $pdo->prepare('INSERT INTO notifications (user_id, message, notification_date) VALUES (?, ?, ?)');
        $stmt->execute([(int) $pay['user_id'], $message, date('Y-m-d')]);

        $pdo->commit();
        set_flash('success', 'Payment TXN-' . $paymentId . ' is now ' . $newStatus . '. The user was notified.');
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('admin/payment_update.php: ' . $e->getMessage());
    set_flash('error', 'A database problem occurred. The payment was not changed. Please try again.');
}

redirect('/admin/payments.php');