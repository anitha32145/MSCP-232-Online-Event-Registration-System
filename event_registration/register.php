<?php
// register.php - User registration
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/validation.php';

// Someone who is already logged in has no reason to register
if (is_user_logged_in() || is_admin_logged_in()) {
    redirect('/index.php');
}

$pageTitle = 'Register';
$pageScript = 'validation.js';
$pageError = '';
$old = ['name' => '', 'email' => '', 'phone' => ''];   // keeps what the person typed

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Read the form values
    $name            = post_str('name');
    $email           = strtolower(post_str('email'));
    $phone           = post_str('phone');
    $password        = post_raw('password');
    $confirmPassword = post_raw('confirmPassword');
    $old = ['name' => $name, 'email' => $email, 'phone' => $phone];

    // 2. Validate on the server
    if (!verify_csrf()) {
        $pageError = 'The form could not be verified. Please try again.';
    } elseif (!is_valid_name($name)) {
        $pageError = 'Please enter a valid name (2 to 100 letters, spaces, dots, apostrophes or hyphens).';
    } elseif (!is_valid_email($email)) {
        $pageError = 'Please enter a valid email address.';
    } elseif (!is_valid_phone($phone)) {
        $pageError = 'Please enter a valid 10-digit mobile number starting with 6, 7, 8 or 9.';
    } elseif (!is_valid_password($password)) {
        $pageError = password_rules();
    } elseif ($password !== $confirmPassword) {
        $pageError = 'The two passwords do not match.';
    } else {
        try {
            $pdo = db();

            // 3. Is the email already registered?
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $stmt->execute([$email]);

            if ((int) $stmt->fetchColumn() > 0) {
                $pageError = 'This email address is already registered. Please login or use another email.';
            } else {
                // 4. Hash the password and save the user
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)');
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $phone]);

                // 5. Success: show a message on the login page
                set_flash('success', 'Registration successful. Please login with your email and password.');
                redirect('/login.php');
            }

        } catch (PDOException $e) {
            // Error code 23000 means "duplicate value" (two people registering the same email at once)
            if ($e->getCode() === '23000') {
                $pageError = 'This email address is already registered. Please login or use another email.';
            } else {
                error_log('register.php: ' . $e->getMessage());
                $pageError = 'A database problem occurred. Please try again later.';
            }
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-1">Create your account</h1>
                <p class="text-muted mb-4">Register to view and join events.</p>

                <form id="registerForm" action="" method="post" novalidate>
                    <?php echo csrf_field(); ?>

                    <div class="mb-3">
                        <label for="name" class="form-label">Full name</label>
                        <input type="text" class="form-control" id="name" name="name"
                               maxlength="100" value="<?php echo e($old['name']); ?>" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email"
                               maxlength="100" value="<?php echo e($old['email']); ?>" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Mobile number</label>
                        <input type="text" class="form-control" id="phone" name="phone"
                               maxlength="10" inputmode="numeric" value="<?php echo e($old['phone']); ?>" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password"
                               maxlength="64" autocomplete="new-password" required>
                        <div class="form-text">8 to 64 characters with an uppercase letter, a lowercase letter and a digit.</div>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-4">
                        <label for="confirmPassword" class="form-label">Confirm password</label>
                        <input type="password" class="form-control" id="confirmPassword"
                               name="confirmPassword" maxlength="64" autocomplete="new-password" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>

                <p class="text-center mt-3 mb-0">
                    Already have an account? <a href="<?php echo BASE_URL; ?>/login.php">Login here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>