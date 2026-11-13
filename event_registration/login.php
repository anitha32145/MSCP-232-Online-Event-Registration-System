<?php
// login.php - Login for users AND the administrator (one page, two roles)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/validation.php';

// Someone who is already logged in goes straight to their dashboard
if (is_admin_logged_in()) {
    redirect('/admin/dashboard.php');
}
if (is_user_logged_in()) {
    redirect('/user/dashboard.php');
}

const INVALID_LOGIN = 'Invalid login details. Please check and try again.';

$pageTitle = 'Login';
$pageScript = 'login.js';
$pageError = '';
$role = 'user';
$loginId = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Read the form values
    $role     = (post_str('role') === 'admin') ? 'admin' : 'user';
    $loginId  = post_str('loginId');
    $password = post_raw('password');

    // 2. Basic checks
    if (!verify_csrf()) {
        $pageError = 'The form could not be verified. Please try again.';
    } elseif ($loginId === '' || $password === '') {
        $pageError = 'Please enter your login ID and password.';
    } else {
        try {
            $pdo = db();

            if ($role === 'admin') {
                // ---------- Administrator login ----------
                $stmt = $pdo->prepare('SELECT admin_id, username, password FROM admin WHERE username = ?');
                $stmt->execute([$loginId]);
                $admin = $stmt->fetch();

                // Same message for "no such admin" and "wrong password"
                if (!$admin || !password_verify($password, $admin['password'])) {
                    $pageError = INVALID_LOGIN;
                } else {
                    session_regenerate_id(true);   // new session id after login
                    $_SESSION['admin_id'] = (int) $admin['admin_id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    redirect('/admin/dashboard.php');
                }

            } else {
                // ---------- User login ----------
                $email = strtolower($loginId);
                $stmt = $pdo->prepare('SELECT user_id, name, password FROM users WHERE email = ?');
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if (!$user || !password_verify($password, $user['password'])) {
                    $pageError = INVALID_LOGIN;
                } else {
                    // Upgrade the stored hash if PHP's recommended settings have changed
                    if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                        $update = $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?');
                        $update->execute([password_hash($password, PASSWORD_DEFAULT), $user['user_id']]);
                    }

                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $user['user_id'];
                    $_SESSION['user_name'] = $user['name'];
                    redirect('/user/dashboard.php');
                }
            }

        } catch (PDOException $e) {
            error_log('login.php: ' . $e->getMessage());
            $pageError = 'A database problem occurred. Please try again later.';
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-1">Login</h1>
                <p class="text-muted mb-4">Sign in to continue.</p>

                <div id="clientError" class="alert alert-danger d-none" role="alert"></div>

                <form id="loginForm" action="" method="post" novalidate>
                    <?php echo csrf_field(); ?>

                    <div class="mb-3">
                        <span class="form-label d-block">Login as</span>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="role" id="roleUser"
                                   value="user" <?php echo $role === 'user' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="roleUser">User</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="role" id="roleAdmin"
                                   value="admin" <?php echo $role === 'admin' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="roleAdmin">Admin</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="loginId" id="loginIdLabel" class="form-label">Email address</label>
                        <input type="text" class="form-control" id="loginId" name="loginId"
                               maxlength="100" value="<?php echo e($loginId); ?>"
                               placeholder="Enter your email" autocomplete="username">
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password"
                               maxlength="64" placeholder="Enter your password"
                               autocomplete="current-password">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>

                <p id="registerBox" class="text-center mt-3 mb-0">
                    New user? <a href="<?php echo BASE_URL; ?>/register.php">Create an account</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>