<?php
include 'header.php';
if (!has_permission('manage_events')) {
    header('Location: login.php');
    exit;
}
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
if (!$event_id) {
    echo "<div class='alert alert-danger'>Invalid event ID.</div>";
    include 'footer.php';
    exit;
}
$stmt = $conn->prepare("SELECT e.name FROM events e WHERE e.id = ? AND (e.created_by = ? OR ? = 1)");
$admin = has_permission('admin_access') ? 1 : 0;
$stmt->bind_param("iii", $event_id, $_SESSION['user_id'], $admin);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
if (!$event) {
    echo "<div class='alert alert-danger'>Event not found or unauthorized.</div>";
    include 'footer.php';
    exit;
}
$stmt = $conn->prepare("SELECT s.id, s.date, s.time_start, s.time_end, u.first_name, u.last_name, su.id AS signup_id
                        FROM shifts s
                        LEFT JOIN signups su ON s.id = su.shift_id
                        LEFT JOIN users u ON su.user_id = u.id
                        WHERE s.event_id = ?
                        ORDER BY s.date, s.time_start");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$signups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
    <h1><?php echo $lang['event_signups'] ?? 'Event Signups'; ?>: <?php echo htmlspecialchars($event['name']); ?></h1>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo $lang['shift_date'] ?? 'Shift Date'; ?></th>
                    <th><?php echo $lang['time'] ?? 'Time'; ?></th>
                    <th><?php echo $lang['volunteer'] ?? 'Volunteer'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($signups as $signup): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($signup['date']); ?></td>
                        <td><?php echo htmlspecialchars($signup['time_start'] . ' - ' . $signup['time_end']); ?></td>
                        <td><?php echo htmlspecialchars($signup['first_name'] . ' ' . $signup['last_name'] ?: 'None'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <a href="event_details.php?id=<?php echo $event_id; ?>" class="btn btn-secondary"><?php echo $lang['back'] ?? 'Back'; ?></a>
<?php include 'footer.php'; ?>