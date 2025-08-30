<?php
include 'header.php';
if (!has_permission('manage_events')) {
    header('Location: login.php');
    exit;
}
$signup_id = filter_input(INPUT_GET, 'signup_id', FILTER_VALIDATE_INT);
if (!$signup_id) {
    echo "<div class='alert alert-danger'>Invalid signup ID.</div>";
    include 'footer.php';
    exit;
}
$stmt = $conn->prepare("SELECT s.*, e.name AS event_name, sh.date, sh.time_start, sh.time_end, u.first_name, u.last_name
                        FROM signups s
                        JOIN shifts sh ON s.shift_id = sh.id
                        JOIN events e ON sh.event_id = e.id
                        JOIN users u ON s.user_id = u.id
                        WHERE s.id = ? AND s.substitution_requested IS NOT NULL");
$stmt->bind_param("i", $signup_id);
$stmt->execute();
$signup = $stmt->get_result()->fetch_assoc();
if (!$signup) {
    echo "<div class='alert alert-danger'>Substitution request not found.</div>";
    include 'footer.php';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'])) {
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
    try {
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE signups SET substitution_approved = NOW(), substitution_requested = NULL WHERE id = ?");
            $stmt->bind_param("i", $signup_id);
            $stmt->execute();
            send_email(
                $signup['email'],
                "Substitution Request Approved",
                "<p>Your substitution request for {$signup['event_name']} on {$signup['date']} has been approved.</p>"
            );
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, event_id, message, created_at) VALUES (?, ?, ?, NOW())");
            $message = "Your substitution for {$signup['event_name']} was approved.";
            $stmt->bind_param("iis", $signup['user_id'], $signup['event_id'], $message);
            $stmt->execute();
            echo "<div class='alert alert-success'>Substitution approved!</div>";
        } else {
            $stmt = $conn->prepare("UPDATE signups SET substitution_requested = NULL WHERE id = ?");
            $stmt->bind_param("i", $signup_id);
            $stmt->execute();
            send_email(
                $signup['email'],
                "Substitution Request Denied",
                "<p>Your substitution request for {$signup['event_name']} on {$signup['date']} was denied.</p>"
            );
            echo "<div class='alert alert-danger'>Substitution denied.</div>";
        }
    } catch (Exception $e) {
        error_log("Substitution approval error: " . $e->getMessage());
        echo "<div class='alert alert-danger'>Failed to process substitution.</div>";
    }
}
?>
    <h1><?php echo $lang['approve_substitution'] ?? 'Approve Substitution'; ?></h1>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($signup['event_name']); ?></h5>
            <p><strong><?php echo $lang['volunteer'] ?? 'Volunteer'; ?>:</strong> <?php echo htmlspecialchars($signup['first_name'] . ' ' . $signup['last_name']); ?></p>
            <p><strong><?php echo $lang['date'] ?? 'Date'; ?>:</strong> <?php echo htmlspecialchars($signup['date']); ?></p>
            <p><strong><?php echo $lang['time'] ?? 'Time'; ?>:</strong> <?php echo htmlspecialchars($signup['time_start'] . ' - ' . $signup['time_end']); ?></p>
        </div>
    </div>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <button type="submit" name="action" value="approve" class="btn btn-success"><?php echo $lang['approve'] ?? 'Approve'; ?></button>
        <button type="submit" name="action" value="deny" class="btn btn-danger"><?php echo $lang['deny'] ?? 'Deny'; ?></button>
        <a href="event_approvals.php" class="btn btn-secondary"><?php echo $lang['back'] ?? 'Back'; ?></a>
    </form>
<?php include 'footer.php'; ?>