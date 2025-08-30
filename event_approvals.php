<?php
include 'header.php';
if (!has_permission('approve_events')) {
    header('Location: login.php');
    exit;
}
$stmt = $conn->prepare("SELECT e.id, e.name, e.created_by, u.first_name, u.last_name FROM events e JOIN users u ON e.created_by = u.id WHERE e.status = 'pending'");
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<div class="container py-4">
    <h1>Event Approvals</h1>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Event Name</th>
                    <th>Created By</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['name']); ?></td>
                        <td><?php echo htmlspecialchars($event['first_name'] . ' ' . $event['last_name']); ?></td>
                        <td>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                <button type="submit" name="approve" class="btn btn-success btn-sm">Approve</button>
                                <button type="submit" name="reject" class="btn btn-danger btn-sm">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'])) {
        try {
            $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
            if ($event_id) {
                if (isset($_POST['approve'])) {
                    $stmt = $conn->prepare("UPDATE events SET status = 'approved' WHERE id = ?");
                    $stmt->bind_param("i", $event_id);
                    $stmt->execute();
                    echo "<div class='alert alert-success mt-3'>Event approved!</div>";
                } elseif (isset($_POST['reject'])) {
                    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
                    $stmt->bind_param("i", $event_id);
                    $stmt->execute();
                    echo "<div class='alert alert-success mt-3'>Event rejected!</div>";
                }
                echo "<script>setTimeout(() => location.reload(), 2000);</script>";
            }
        } catch (Exception $e) {
            error_log("Event approval error: " . $e->getMessage());
            echo "<div class='alert alert-danger mt-3'>Error processing approval.</div>";
        }
    }
    ?>
</div>
<?php include 'footer.php'; ?>