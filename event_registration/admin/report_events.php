<?php
// admin/report_events.php - Event-wise Registration Report
// Contains: event details, participant count, registration status
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();   // only the administrator may see this page

$pageTitle = 'Event-wise Registration Report';
$pageError = '';
$rows = [];
$totalActive = 0;
$totalCancelled = 0;
$totalCapacity = 0;

try {
    // One row per event, with the number of active and cancelled registrations
    $sql = "SELECT e.event_id, e.event_name, e.event_date, e.venue, e.capacity,
                   COALESCE(SUM(CASE WHEN r.status = 'Registered' THEN 1 ELSE 0 END), 0) AS active_count,
                   COALESCE(SUM(CASE WHEN r.status = 'Cancelled' THEN 1 ELSE 0 END), 0) AS cancelled_count
            FROM events e
            LEFT JOIN registrations r ON r.event_id = e.event_id
            GROUP BY e.event_id, e.event_name, e.event_date, e.venue, e.capacity
            ORDER BY e.event_date ASC, e.event_id ASC";

    $rows = db()->query($sql)->fetchAll();

    foreach ($rows as $row) {
        $totalActive += (int) $row['active_count'];
        $totalCancelled += (int) $row['cancelled_count'];
        $totalCapacity += (int) $row['capacity'];
    }

} catch (PDOException $e) {
    error_log('admin/report_events.php: ' . $e->getMessage());
    $pageError = 'A database problem occurred while preparing the report. Please try again later.';
}

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Event-wise Registration Report</h1>
    <div class="d-flex gap-2 d-print-none">
        <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="btn btn-outline-secondary btn-sm">All Reports</a>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">Print</button>
    </div>
</div>

<p class="text-muted small">Generated on <?php echo e(date('d M Y, h:i A')); ?></p>

<?php if ($pageError === ''): ?>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-3 d-flex flex-wrap gap-2">
            <span class="badge bg-light text-dark border p-2">Events: <?php echo count($rows); ?></span>
            <span class="badge bg-light text-dark border p-2">Active registrations: <?php echo $totalActive; ?></span>
            <span class="badge bg-light text-dark border p-2">Cancelled registrations: <?php echo $totalCancelled; ?></span>
            <span class="badge bg-light text-dark border p-2">Total capacity: <?php echo $totalCapacity; ?></span>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <div class="alert alert-info">There are no events yet.</div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-striped table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Event ID</th>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Venue</th>
                            <th>Capacity</th>
                            <th>Participants</th>
                            <th>Cancelled</th>
                            <th>Seats left</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <?php
                                $capacity = (int) $row['capacity'];
                                $participants = (int) $row['active_count'];
                                $seatsLeft = max(0, $capacity - $participants);

                                if ($row['event_date'] < date('Y-m-d')) {
                                    $status = 'Completed';
                                } elseif ($seatsLeft === 0) {
                                    $status = 'Full';
                                } else {
                                    $status = 'Open';
                                }
                            ?>
                            <tr>
                                <td><?php echo (int) $row['event_id']; ?></td>
                                <td><?php echo e($row['event_name']); ?></td>
                                <td><?php echo e(date('d M Y', strtotime($row['event_date']))); ?></td>
                                <td><?php echo e($row['venue']); ?></td>
                                <td><?php echo $capacity; ?></td>
                                <td><?php echo $participants; ?></td>
                                <td><?php echo (int) $row['cancelled_count']; ?></td>
                                <td><?php echo $seatsLeft; ?></td>
                                <td><?php echo e($status); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>