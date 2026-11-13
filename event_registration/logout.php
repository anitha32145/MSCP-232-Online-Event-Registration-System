<?php
// logout.php - Ends the session of a user or the administrator.
// The "Logout" link in the menu bar opens this page.
require_once __DIR__ . '/includes/functions.php';

// Remove everything stored in the session
$_SESSION = [];

// Remove the session cookie from the browser
if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'],
              $cookie['secure'], $cookie['httponly']);
}

session_destroy();

// Start a fresh session only to carry the confirmation message
session_start();
session_regenerate_id(true);
$_SESSION['flash_success'] = 'You have been logged out successfully.';

redirect('/login.php');