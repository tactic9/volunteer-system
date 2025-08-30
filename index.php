<?php
include 'header.php';
$stmt = $conn->prepare("SELECT e.*, g.name AS group_name FROM events e LEFT JOIN `groups` g ON e.group_id = g.id WHERE e.status = 'approved' ORDER BY e.created_at DESC LIMIT 10");
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
    <h1><?php echo $lang['welcome'] ?? 'Welcome to Volunteer System'; ?></h1>
    <div class="row">
        <div class="col-md-8">
            <h2><?php echo $lang['upcoming_events'] ?? 'Upcoming Events'; ?></h2>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><?php echo $lang['event'] ?? 'Event'; ?></th>
                            <th><?php echo $lang['location'] ?? 'Location'; ?></th>
                            <th><?php echo $lang['group'] ?? 'Group'; ?></th>
                            <th><?php echo $lang['action'] ?? 'Action'; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['name']); ?></td>
                                <td><?php echo htmlspecialchars($event['location'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($event['group_name'] ?: 'N/A'); ?></td>
                                <td><a href="event_details.php?id=<?php echo $event['id']; ?>" class="btn btn-primary btn-sm"><?php echo $lang['view'] ?? 'View'; ?></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-4">
            <?php if (is_logged_in()): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $lang['quick_actions'] ?? 'Quick Actions'; ?></h5>
                        <a href="create_event.php" class="btn btn-primary w-100 mb-2"><?php echo $lang['create_event'] ?? 'Create Event'; ?></a>
                        <a href="manage_events.php" class="btn btn-secondary w-100"><?php echo $lang['manage_events'] ?? 'Manage Events'; ?></a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $lang['join_us'] ?? 'Join Us'; ?></h5>
                        <p><?php echo $lang['join_message'] ?? 'Sign up or log in to volunteer!'; ?></p>
                        <a href="login.php" class="btn btn-primary w-100 mb-2"><?php echo $lang['login'] ?? 'Login'; ?></a>
                        <a href="register.php" class="btn btn-secondary w-100"><?php echo $lang['register'] ?? 'Register'; ?></a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php include 'footer.php'; ?>