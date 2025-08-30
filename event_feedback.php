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
$stmt = $conn->prepare("SELECT name FROM events WHERE id = ? AND (created_by = ? OR ? = 1)");
$admin = has_permission('admin_access') ? 1 : 0;
$stmt->bind_param("iii", $event_id, $_SESSION['user_id'], $admin);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
if (!$event) {
    echo "<div class='alert alert-danger'>Event not found or unauthorized.</div>";
    include 'footer.php';
    exit;
}
$stmt = $conn->prepare("SELECT f.*, u.first_name, u.last_name 
                        FROM feedback f 
                        JOIN users u ON f.user_id = u.id 
                        WHERE f.event_id = ? 
                        ORDER BY f.submitted_at DESC");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$feedbacks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
    <h1><?php echo $lang['event_feedback'] ?? 'Event Feedback'; ?>: <?php echo htmlspecialchars($event['name']); ?></h1>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo $lang['volunteer'] ?? 'Volunteer'; ?></th>
                    <th><?php echo $lang['feedback'] ?? 'Feedback'; ?></th>
                    <th><?php echo $lang['rating'] ?? 'Rating'; ?></th>
                    <th><?php echo $lang['submitted_at'] ?? 'Submitted At'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($feedbacks as $feedback): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($feedback['first_name'] . ' ' . $feedback['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($feedback['feedback']); ?></td>
                        <td><?php echo htmlspecialchars($feedback['rating']); ?></td>
                        <td><?php echo htmlspecialchars($feedback['submitted_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <a href="event_details.php?id=<?php echo $event_id; ?>" class="btn btn-secondary"><?php echo $lang['back'] ?? 'Back'; ?></a>
<?php include 'footer.php'; ?>