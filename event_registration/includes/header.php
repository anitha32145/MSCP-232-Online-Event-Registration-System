<?php
// includes/header.php
// ------------------------------------------------------------
// The top of every page: <head>, the menu bar and the message boxes.
//
// A page can set these variables BEFORE including this file:
//   $pageTitle  the title shown in the browser tab
//   $pageError  an error message to show at the top of the page
require_once __DIR__ . '/functions.php';

// Logged-in pages are private: tell the browser not to keep a copy of them
if (is_user_logged_in() || is_admin_logged_in()) {
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
}

$pageTitle = $pageTitle ?? 'Online Event Registration System';

$flashSuccess = get_flash('success');
$flashError   = get_flash('error');

// Colours depend on who is logged in, so the administrator and a user
// can never be confused with each other
if (is_admin_logged_in()) {
    $roleClass = 'role-admin';
    $navClass  = 'navbar-admin';
} elseif (is_user_logged_in()) {
    $roleClass = 'role-user';
    $navClass  = 'navbar-user';
} else {
    $roleClass = 'role-guest';
    $navClass  = 'navbar-guest';
}

// The file name of the page being shown, for example "events.php"
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');

// Builds one menu link. The link is highlighted when the current page is the
// link's own page, or one of the related pages listed in $also.
// Example: $navItem('/admin/events.php', 'Events', ['event_form.php'])
$navItem = function (string $path, string $label, array $also = []) use ($currentPage): string {
    $pages = array_merge([basename($path)], $also);
    $isActive = in_array($currentPage, $pages, true);

    return '<li class="nav-item"><a class="nav-link' . ($isActive ? ' active' : '') . '"'
         . ($isActive ? ' aria-current="page"' : '')
         . ' href="' . BASE_URL . $path . '">' . e($label) . '</a></li>';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($pageTitle); ?> | Event Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo (int) @filemtime(__DIR__ . '/../assets/css/style.css'); ?>" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100 <?php echo e($roleClass); ?>">

<nav class="navbar navbar-expand-lg navbar-dark <?php echo e($navClass); ?>">
    <div class="container">

        <!-- Left: project name (a link) and the role label (NOT a link) -->
        <div class="d-flex align-items-center">
            <a class="navbar-brand me-2" href="<?php echo BASE_URL; ?>/index.php">Event Registration</a>
            <?php if (is_admin_logged_in()): ?>
                <span class="badge bg-danger role-badge">ADMIN</span>
            <?php elseif (is_user_logged_in()): ?>
                <span class="badge bg-light role-badge role-badge-user">USER</span>
            <?php endif; ?>
        </div>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNav" aria-controls="mainNav"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">

            <!-- Centre: the menu links -->
                        <ul class="navbar-nav <?php echo (is_admin_logged_in() || is_user_logged_in()) ? 'mx-auto' : 'ms-auto'; ?>">
                <?php echo $navItem('/index.php', 'Home'); ?>

                <?php if (is_admin_logged_in()): ?>
                    <?php echo $navItem('/admin/dashboard.php', 'Dashboard'); ?>
                    <?php echo $navItem('/admin/events.php', 'Events', ['event_form.php']); ?>
                    <?php echo $navItem('/admin/registrations.php', 'Registrations'); ?>
                    <?php echo $navItem('/admin/payments.php', 'Payments'); ?>
                    <?php echo $navItem('/admin/users.php', 'Users'); ?>
                    <?php echo $navItem('/admin/reports.php', 'Reports',
                        ['report_users.php', 'report_events.php', 'report_payments.php', 'report_attendance.php']); ?>

                <?php elseif (is_user_logged_in()): ?>
                    <?php echo $navItem('/user/dashboard.php', 'Dashboard'); ?>
                    <?php echo $navItem('/user/events.php', 'Events', ['event_details.php']); ?>
                    <?php echo $navItem('/user/my_registrations.php', 'My Registrations', ['registration_confirmation.php']); ?>
                    <?php echo $navItem('/user/payments.php', 'Payment Status'); ?>
                    <?php echo $navItem('/user/profile.php', 'Profile'); ?>

                <?php else: ?>
                    <?php echo $navItem('/login.php', 'Login'); ?>
                    <?php echo $navItem('/register.php', 'Register'); ?>
                <?php endif; ?>
            </ul>

            <!-- Right: who is logged in, and the Logout button -->
            <?php if (is_admin_logged_in() || is_user_logged_in()): ?>
                <ul class="navbar-nav align-items-lg-center">
                    <li class="nav-item">
                        <span class="navbar-text nav-username me-lg-3">
                            <?php echo e(is_admin_logged_in() ? $_SESSION['admin_username'] : $_SESSION['user_name']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light btn-sm px-3" href="<?php echo BASE_URL; ?>/logout.php">Logout</a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container flex-grow-1 py-4">

<?php if ($flashSuccess !== null): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo e($flashSuccess); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($flashError !== null): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo e($flashError); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($pageError)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo e($pageError); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>