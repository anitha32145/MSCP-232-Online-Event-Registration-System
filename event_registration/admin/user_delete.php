<?php
// admin/user_delete.php - Deletes a user (POST only)
//
// A user with ACTIVE registrations cannot be deleted. When a user is deleted,
// the database also removes their cancelled registrations, payments and
// notifications (ON DELETE CASCADE).
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

require_admin();   // only the administrator may use this page

// Typing the address directly just goes back to the list
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/users.php');
}

if (!verify_csrf()) {
    set_flash('error', 'The form could not be verified. Please try again.');
    redirect('/admin/users.php');
}

$userId = filter_var(post_str('userId'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($userId === false) {
    set_flash('error', 'That user was not found.');
    redirect('/admin/users.php');
}

try {
    $pdo = db();

    // Read the user and count the active registrations
    $stmt = $pdo->prepare(
        "SELECT u.name,
                (SELECT COUNT(*) FROM registrations r
                  WHERE r.user_id = u.user_id AND r.status = 'Registered') AS active_regs
         FROM users u
         WHERE u.user_id = ?"
    );
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        set_flash('error', 'That user was not found.');
    } elseif ((int) $user['active_regs'] > 0) {
        set_flash('error', $user['name'] . ' has ' . (int) $user['active_regs']
            . ' active registration(s) and cannot be deleted. Cancel them first.');
    } else {
        $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = ?');
        $stmt->execute([$userId]);
        set_flash('success', 'User "' . $user['name'] . '" was deleted.');
    }

} catch (PDOException $e) {
    error_log('admin/user_delete.php: ' . $e->getMessage());
    set_flash('error', 'A database problem occurred. The user was not deleted. Please try again.');
}

redirect('/admin/users.php');