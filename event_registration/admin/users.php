<?php
// admin/users.php - The administrator's list of registered users.
// The password is never loaded or shown.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();   // only the administrator may see this page

$pageTitle = 'Manage Users';
$pageError = '';
$users = [];

try {
    // Each user with the number of active and total registrations
    $sql = "SELECT u.user_id, u.name, u.email, u.phone,
                   COUNT(r.registration_id) AS total_regs,
                   COALESCE(SUM(CASE WHEN r.status = 'Registered' THEN 1 ELSE 0 END), 0) AS active_regs
            FROM users u
            LEFT JOIN registrations r ON r.user_id = u.user_id
            GROUP BY u.user_id, u.name, u.email, u.phone
            ORDER BY u.user_id DESC";

    $users = db()->query($sql)->fetchAll();

} catch (PDOException $e) {
    error_log('admin/users.php: ' . $e->getMessage());
    $pageError = 'A database problem occurred while loading users. Please try again later.';
}

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Manage Users</h1>
    <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
</div>

<?php if (empty($users) && $pageError === ''): ?>

    <div class="alert alert-info">No users have registered yet.</div>

<?php elseif (!empty($users)): ?>

    <p class="text-muted"><?php echo count($users); ?> user(s) found.</p>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Registrations</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <?php $activeRegs = (int) $user['active_regs']; ?>
                        <tr>
                            <td><?php echo (int) $user['user_id']; ?></td>
                            <td><b><?php echo e($user['name']); ?></b></td>
                            <td><?php echo e($user['email']); ?></td>
                            <td><?php echo e($user['phone']); ?></td>
                            <td>
                                <span class="badge bg-success"><?php echo $activeRegs; ?> active</span>
                                <span class="small text-muted"><?php echo (int) $user['total_regs']; ?> in total</span>
                            </td>
                            <td>
                                <?php if ($activeRegs === 0): ?>
                                    <form action="<?php echo BASE_URL; ?>/admin/user_delete.php" method="post"
                                          onsubmit="return confirm('Delete this user permanently? Their cancelled registrations and notifications will also be removed.');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="userId" value="<?php echo (int) $user['user_id']; ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                    </form>
                                <?php else: ?>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" disabled
                                            title="This user has active registrations">Delete</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-muted small mt-3 mb-0">
        A user with active registrations cannot be deleted. Cancel those registrations first under Registrations.
    </p>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>