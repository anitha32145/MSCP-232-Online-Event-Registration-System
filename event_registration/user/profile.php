<?php
// user/profile.php - Profile management: update details and change password
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

require_user();   // only logged-in users may see this page

$userId = (int) $_SESSION['user_id'];
$pageTitle = 'My Profile';
$pageScript = 'validation.js';
$pageError = '';
$user = null;
$form = ['name' => '', 'email' => '', 'phone' => ''];   // the values shown in the details form

try {
    $pdo = db();

    // Load the user's current details
    $stmt = $pdo->prepare('SELECT user_id, name, email, phone, password FROM users WHERE user_id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    // The account may have been removed by the administrator
    if (!$user) {
        redirect('/logout.php');
    }

    $form = ['name' => $user['name'], 'email' => $user['email'], 'phone' => $user['phone']];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $action = post_str('action');

        if (!verify_csrf()) {
            $pageError = 'The form could not be verified. Please try again.';

        } elseif ($action === 'details') {
            // ---------- Update name, email and phone ----------
            $name  = post_str('name');
            $email = strtolower(post_str('email'));
            $phone = post_str('phone');
            $form = ['name' => $name, 'email' => $email, 'phone' => $phone];   // keep what was typed

            if (!is_valid_name($name)) {
                $pageError = 'Please enter a valid name (2 to 100 letters, spaces, dots, apostrophes or hyphens).';
            } elseif (!is_valid_email($email)) {
                $pageError = 'Please enter a valid email address.';
            } elseif (!is_valid_phone($phone)) {
                $pageError = 'Please enter a valid 10-digit mobile number starting with 6, 7, 8 or 9.';
            } else {
                // The email must not belong to a different user
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND user_id <> ?');
                $stmt->execute([$email, $userId]);

                if ((int) $stmt->fetchColumn() > 0) {
                    $pageError = 'This email address is already used by another account.';
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE user_id = ?');
                    $stmt->execute([$name, $email, $phone, $userId]);

                    $_SESSION['user_name'] = $name;   // the menu bar shows the new name
                    set_flash('success', 'Profile updated successfully.');
                    redirect('/user/profile.php');
                }
            }

        } elseif ($action === 'password') {
            // ---------- Change password ----------
            $currentPassword = post_raw('currentPassword');
            $newPassword     = post_raw('newPassword');
            $confirmPassword = post_raw('confirmNewPassword');

            if ($currentPassword === '') {
                $pageError = 'Please enter your current password.';
            } elseif (!is_valid_password($newPassword)) {
                $pageError = password_rules();
            } elseif ($newPassword !== $confirmPassword) {
                $pageError = 'The new password and its confirmation do not match.';
            } elseif ($newPassword === $currentPassword) {
                $pageError = 'The new password must be different from the current password.';
            } elseif (!password_verify($currentPassword, $user['password'])) {
                $pageError = 'Your current password is incorrect.';
            } else {
                $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?');
                $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);

                session_regenerate_id(true);   // new session id after a security change
                set_flash('success', 'Password changed successfully.');
                redirect('/user/profile.php');
            }

        } else {
            $pageError = 'Unknown action.';
        }
    }

} catch (PDOException $e) {
    // Error code 23000 means "duplicate value" (the email was taken at the same moment)
    if ($e->getCode() === '23000') {
        $pageError = 'This email address is already used by another account.';
    } else {
        error_log('user/profile.php: ' . $e->getMessage());
        $pageError = 'A database problem occurred. Please try again later.';
    }
}

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">My Profile</h1>
    <a href="<?php echo BASE_URL; ?>/user/dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
</div>

<?php if ($user): ?>
<div class="row g-4">

    <!-- Card 1: personal details -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Personal Details</h2>

                <form id="profileForm" action="" method="post" novalidate>
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="details">

                    <div class="mb-3">
                        <label for="pName" class="form-label">Full name</label>
                        <input type="text" class="form-control" id="pName" name="name"
                               maxlength="100" value="<?php echo e($form['name']); ?>" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="pEmail" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="pEmail" name="email"
                               maxlength="100" value="<?php echo e($form['email']); ?>" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-4">
                        <label for="pPhone" class="form-label">Mobile number</label>
                        <input type="text" class="form-control" id="pPhone" name="phone"
                               maxlength="10" inputmode="numeric" value="<?php echo e($form['phone']); ?>" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Card 2: change password -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Change Password</h2>

                <form id="passwordForm" action="" method="post" novalidate>
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="password">

                    <div class="mb-3">
                        <label for="currentPassword" class="form-label">Current password</label>
                        <input type="password" class="form-control" id="currentPassword"
                               name="currentPassword" maxlength="64" autocomplete="current-password">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="newPassword" class="form-label">New password</label>
                        <input type="password" class="form-control" id="newPassword"
                               name="newPassword" maxlength="64" autocomplete="new-password">
                        <div class="form-text">8 to 64 characters with an uppercase letter, a lowercase letter and a digit.</div>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-4">
                        <label for="confirmNewPassword" class="form-label">Confirm new password</label>
                        <input type="password" class="form-control" id="confirmNewPassword"
                               name="confirmNewPassword" maxlength="64" autocomplete="new-password">
                        <div class="invalid-feedback"></div>
                    </div>

                    <button type="submit" class="btn btn-warning">Change Password</button>
                </form>
            </div>
        </div>
    </div>

</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>