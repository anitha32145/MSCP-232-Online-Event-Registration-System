<?php
// admin/report_attendance.php - Attendance Report
// Contains: the participant list of each event, with the attendance status.
// Only ACTIVE registrations are listed (cancelled people are not participants).
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();   // only the administrator may see this page

$pageTitle = 'Attendance Report';
$pageError = '';
$rows = [];
$events = [];
$present = 0;
$absent = 0;
$notMarked = 0;

// Which event is selected? 0 means all events. Anything invalid falls back to 0.
$eventFilter = filter_var($_GET['eventId'] ?? '0', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($eventFilter === false) {
    $eventFilter = 0;
}

try {
    $pdo = db();

    // All events, for the selector
    $events = $pdo->query('SELECT event_id, event_name, event_date FROM events ORDER BY event_date DESC')
                  ->fetchAll();

    $stmt = $pdo->prepare(
        "SELECT e.event_name, e.event_date, r.registration_id, r.attendance_status,
                u.name, u.email, u.phone
         FROM registrations r
         JOIN events e ON e.event_id = r.event_id
         JOIN users u ON u.user_id = r.user_id
         WHERE r.status = 'Registered' AND (? = 0 OR r.event_id = ?)
         ORDER BY e.event_date ASC, e.event_name ASC, u.name ASC"
    );
    $stmt->execute([$eventFilter, $eventFilter]);
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        if ($row['attendance_status'] === 'Present') {
            $present++;
        } elseif ($row['attendance_status'] === 'Absent') {
            $absent++;
        } else {
            $notMarked++;
        }
    }

} catch (PDOException $e) {
    error_log('admin/report_attendance.php: ' . $e->getMessage());
    $pageError = 'A database problem occurred while preparing the report. Please try again later.';
}

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Attendance Report</h1>
    <div class="d-flex gap-2 d-print-none">
        <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="btn btn-outline-secondary btn-sm">All Reports</a>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">Print</button>
    </div>
</div>

<p class="text-muted small">Generated on <?php echo e(date('d M Y, h:i A')); ?></p>

<!-- Event selector -->
<div class="card shadow-sm mb-3 d-print-none">
    <div class="card-body">
        <form action="" method="get" class="row g-2 align-items-end">
            <div class="col-md-8">
                <label for="eventId" class="form-label">Show participants of</label>
                <select class="form-select" id="eventId" name="eventId">
                    <option value="0">All events</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?php echo (int) $event['event_id']; ?>"
                            <?php echo ((int) $event['event_id'] === $eventFilter) ? 'selected' : ''; ?>>
                            <?php echo e($event['event_name']); ?>
                            (<?php echo e(date('d M Y', strtotime($event['event_date']))); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">Show</button>
                <a href="<?php echo BASE_URL; ?>/admin/report_attendance.php" class="btn btn-outline-secondary flex-fill">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if ($pageError === ''): ?>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-3 d-flex flex-wrap gap-2">
            <span class="badge bg-light text-dark border p-2">Participants: <?php echo count($rows); ?></span>
            <span class="badge bg-light text-dark border p-2">Present: <?php echo $present; ?></span>
            <span class="badge bg-light text-dark border p-2">Absent: <?php echo $absent; ?></span>
            <span class="badge bg-light text-dark border p-2">Not marked: <?php echo $notMarked; ?></span>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <div class="alert alert-info">There are no participants to show.</div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-striped table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Event</th>
                            <th>Event date</th>
                            <th>Reg. No.</th>
                            <th>Participant</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Attendance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $index => $row): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo e($row['event_name']); ?></td>
                                <td><?php echo e(date('d M Y', strtotime($row['event_date']))); ?></td>
                                <td>REG-<?php echo (int) $row['registration_id']; ?></td>
                                <td><?php echo e($row['name']); ?></td>
                                <td><?php echo e($row['email']); ?></td>
                                <td><?php echo e($row['phone']); ?></td>
                                <td><?php echo e($row['attendance_status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>