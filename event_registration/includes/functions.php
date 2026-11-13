<?php
// includes/functions.php
// ------------------------------------------------------------
// Shared helper functions used by every page.
// This file also starts the session (PHP's way of remembering a
// logged-in person between pages).

// The address of the project in the browser
define('BASE_URL', '/event_registration');


// All dates and times in this project use Indian Standard Time
date_default_timezone_set('Asia/Kolkata');


// Developer switch. Keep it false for the demo and the final submission.
// Set it to true only while fixing a problem, so PHP errors show on the page.
// When it is false, errors are written to C:\xampp\apache\logs\error.log instead.
define('APP_DEBUG', false);

ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// A person is logged out after 30 minutes without activity
define('SESSION_TIMEOUT', 1800);

// ---------- Start the session once, with safer cookie settings ----------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,     // JavaScript in the browser cannot read the session cookie
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ---------- Log out after a long period of inactivity ----------
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['flash_error'] = 'Your session expired. Please login again.';
}
$_SESSION['last_activity'] = time();

// ---------- Output and navigation helpers ----------

// Makes text safe to print inside a web page (stops HTML and script injection).
// Use it on every value that comes from the database or from a form.
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Sends the browser to another page of this project, then stops the script.
// Example: redirect('/login.php');
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

// ---------- One-time messages ----------
// A page stores a message, then redirects. The next page shows it once.
// Types used in this project: "success" and "error".

function set_flash(string $type, string $message): void
{
    $_SESSION['flash_' . $type] = $message;
}

function get_flash(string $type): ?string
{
    $key = 'flash_' . $type;

    if (isset($_SESSION[$key])) {
        $message = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $message;
    }
    return null;
}

// ---------- Login state and role checks ----------
// After login the session holds:
//   a normal user:   user_id, user_name
//   the admin:       admin_id, admin_username

function is_user_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function is_admin_logged_in(): bool
{
    return isset($_SESSION['admin_id']);
}

// Put require_user() at the top of every page only a user may see
function require_user(): void
{
    if (!is_user_logged_in()) {
        set_flash('error', 'Please login to continue.');
        redirect('/login.php');
    }
}

// Put require_admin() at the top of every page only the administrator may see
function require_admin(): void
{
    if (!is_admin_logged_in()) {
        set_flash('error', 'Please login as administrator to continue.');
        redirect('/login.php');
    }
}

// ---------- Form protection (CSRF) ----------
// Every form that changes data includes csrf_field(). The page that receives
// the form calls verify_csrf(). This blocks forms sent from other websites.

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Prints the hidden field inside a form: <?php echo csrf_field(); 
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// Returns true when the submitted form carries the correct token
function verify_csrf(): bool
{
    $sent = $_POST['csrf_token'] ?? '';

    return is_string($sent)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $sent);
}


// ---------- Payment methods ----------
// The only payment methods the administrator can record
function payment_methods(): array
{
    return ['Cash', 'UPI', 'Credit Card', 'Debit Card', 'Net Banking', 'Bank Transfer'];
}