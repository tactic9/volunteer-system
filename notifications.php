<?php
include 'header.php';
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}
$stmt = $conn->prepare("SELECT n.*, e.name AS event_name 
                        FROM notifications n 
                        LEFT JOIN events e ON n.event_id = e.id 
                        WHERE n.user_id = ? 
                        ORDER BY n.created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'])) {
    $notification_id = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);
    try {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $_SESSION['user_id']);
        $stmt->execute();
        echo "<div class='alert alert-success'>Notification marked as read.</div>";
    } catch (Exception $e) {
        error_log("Notification update error: " . $e->getMessage());
        echo "<div class='alert alert-danger'>Failed to mark notification as read.</div>";
    }
}
?>
    <h1><?php echo $lang['notifications'] ?? 'Notifications'; ?></h1>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo $lang['message'] ?? 'Message'; ?></th>
                    <th><?php echo $lang['event'] ?? 'Event'; ?></th>
                    <th><?php echo $lang['created_at'] ?? 'Created At'; ?></th>
                    <th><?php echo $lang['action'] ?? 'Action'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notifications as $notification): ?>
                    <tr class="<?php echo $notification['is_read'] ? '' : 'table-primary'; ?>">
                        <td><?php echo htmlspecialchars($notification['message']); ?></td>
                        <td><?php echo htmlspecialchars($notification['event_name'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($notification['created_at']); ?></td>
                        <td>
                            <?php if (!$notification['is_read']): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-primary"><?php echo $lang['mark_read'] ?? 'Mark Read'; ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php include 'footer.php'; ?>