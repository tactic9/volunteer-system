<?php
include 'header.php';
if (!has_permission('admin_access')) {
    header('Location: login.php');
    exit;
}
// Fetch summary stats
$stmt = $conn->prepare("SELECT COUNT(*) as event_count FROM events WHERE status = 'pending'");
$stmt->execute();
$pending_events = $stmt->get_result()->fetch_assoc()['event_count'];
$stmt = $conn->prepare("SELECT COUNT(*) as user_count FROM users");
$stmt->execute();
$user_count = $stmt->get_result()->fetch_assoc()['user_count'];
$stmt = $conn->prepare("SELECT SUM(hours + COALESCE(manual_adjust, 0)) as total_hours FROM checkins");
$stmt->execute();
$total_hours = $stmt->get_result()->fetch_assoc()['total_hours'];
?>
<div class="container py-4">
    <h1 class="mb-4"><?php echo $lang['admin_dashboard'] ?? 'Admin Dashboard'; ?></h1>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Pending Events</h5>
                    <p class="card-text"><?php echo $pending_events; ?></p>
                    <a href="event_approvals.php" class="btn btn-primary">Review</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <p class="card-text"><?php echo $user_count; ?></p>
                    <a href="manage_members.php" class="btn btn-primary">Manage</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Hours</h5>
                    <p class="card-text"><?php echo number_format($total_hours ?? 0, 2); ?></p>
                    <a href="reports.php" class="btn btn-primary">View Reports</a>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-4">
        <h3>Quick Actions</h3>
        <ul class="list-group">
            <li class="list-group-item"><a href="create_event.php">Create Event</a></li>
            <li class="list-group-item"><a href="manage_groups.php">Manage Groups</a></li>
            <li class="list-group-item"><a href="manage_roles.php">Manage Roles</a></li>
        </ul>
    </div>
</div>
<?php include 'footer.php'; ?>