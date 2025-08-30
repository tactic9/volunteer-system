<?php
include 'header.php';
if (!has_permission('manage_events')) {
    header('Location: login.php');
    exit;
}
$stmt = $conn->prepare("SELECT e.*, g.name AS group_name FROM events e LEFT JOIN `groups` g ON e.group_id = g.id WHERE e.created_by = ? OR ? = 1 ORDER BY e.created_at DESC");
$admin = has_permission('admin_access') ? 1 : 0;
$stmt->bind_param("ii", $_SESSION['user_id'], $admin);
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
    <h1><?php echo $lang['manage_events'] ?? 'Manage Events'; ?></h1>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo $lang['event'] ?? 'Event'; ?></th>
                    <th><?php echo $lang['location'] ?? 'Location'; ?></th>
                    <th><?php echo $lang['group'] ?? 'Group'; ?></th>
                    <th><?php echo $lang['status'] ?? 'Status'; ?></th>
                    <th><?php echo $lang['action'] ?? 'Action'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['name']); ?></td>
                        <td><?php echo htmlspecialchars($event['location'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($event['group_name'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($event['status']); ?></td>
                        <td>
                            <a href="event_details.php?id=<?php echo $event['id']; ?>" class="btn btn-primary btn-sm"><?php echo $lang['view'] ?? 'View'; ?></a>
                            <a href="edit_event.php?id=<?php echo $event['id']; ?>" class="btn btn-warning btn-sm"><?php echo $lang['edit'] ?? 'Edit'; ?></a>
                            <a href="duplicate_event.php?id=<?php echo $event['id']; ?>" class="btn btn-info btn-sm"><?php echo $lang['duplicate'] ?? 'Duplicate'; ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php include 'footer.php'; ?>