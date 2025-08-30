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
$stmt = $conn->prepare("SELECT c.id, c.checkin_time, c.checkout_time, c.hours, c.manual_adjust, u.first_name, u.last_name
                        FROM checkins c
                        JOIN users u ON c.user_id = u.id
                        WHERE c.event_id = ?
                        ORDER BY c.checkin_time");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$checkins = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
    <h1><?php echo $lang['checkin_list'] ?? 'Check-In List'; ?>: <?php echo htmlspecialchars($event['name']); ?></h1>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo $lang['volunteer'] ?? 'Volunteer'; ?></th>
                    <th><?php echo $lang['checkin_time'] ?? 'Check-In Time'; ?></th>
                    <th><?php echo $lang['checkout_time'] ?? 'Check-Out Time'; ?></th>
                    <th><?php echo $lang['hours'] ?? 'Hours'; ?></th>
                    <th><?php echo $lang['manual_adjust'] ?? 'Adjustment'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checkins as $checkin): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($checkin['first_name'] . ' ' . $checkin['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($checkin['checkin_time']); ?></td>
                        <td><?php echo htmlspecialchars($checkin['checkout_time'] ?: 'N/A'); ?></td>
                        <td><?php echo number_format($checkin['hours'], 2); ?></td>
                        <td><?php echo number_format($checkin['manual_adjust'] ?? 0, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <a href="event_details.php?id=<?php echo $event_id; ?>" class="btn btn-secondary"><?php echo $lang['back'] ?? 'Back'; ?></a>
<?php include 'footer.php'; ?>