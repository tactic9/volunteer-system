<?php
include 'header.php';
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}
$shift_id = filter_input(INPUT_GET, 'shift_id', FILTER_VALIDATE_INT);
if (!$shift_id) {
    echo "<div class='alert alert-danger'>Invalid shift ID.</div>";
    include 'footer.php';
    exit;
}
$stmt = $conn->prepare("SELECT s.*, e.name AS event_name FROM shifts s JOIN events e ON s.event_id = e.id WHERE s.id = ? AND e.status = 'approved'");
$stmt->bind_param("i", $shift_id);
$stmt->execute();
$shift = $stmt->get_result()->fetch_assoc();
if (!$shift) {
    echo "<div class='alert alert-danger'>Shift not found or event not approved.</div>";
    include 'footer.php';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'])) {
    try {
        $stmt = $conn->prepare("SELECT id FROM signups WHERE shift_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $shift_id, $_SESSION['user_id']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo "<div class='alert alert-warning'>You are already signed up for this shift.</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO signups (shift_id, user_id, signup_time) VALUES (?, ?, NOW())");
            $stmt->bind_param("ii", $shift_id, $_SESSION['user_id']);
            $stmt->execute();
            send_email(
                $_SESSION['user_email'],
                "Shift Signup Confirmation",
                "<p>You have signed up for the shift: {$shift['event_name']} on {$shift['date']} from {$shift['time_start']} to {$shift['time_end']}.</p>"
            );
            echo "<div class='alert alert-success'>Successfully signed up!</div>";
        }
    } catch (Exception $e) {
        error_log("Signup error: " . $e->getMessage());
        echo "<div class='alert alert-danger'>Signup failed. Please try again.</div>";
    }
}
?>
    <h1><?php echo $lang['signup'] ?? 'Sign Up for Shift'; ?></h1>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($shift['event_name']); ?></h5>
            <p><strong><?php echo $lang['date'] ?? 'Date'; ?>:</strong> <?php echo htmlspecialchars($shift['date']); ?></p>
            <p><strong><?php echo $lang['time'] ?? 'Time'; ?>:</strong> <?php echo htmlspecialchars($shift['time_start'] . ' - ' . $shift['time_end']); ?></p>
        </div>
    </div>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <button type="submit" class="btn btn-primary"><?php echo $lang['signup'] ?? 'Sign Up'; ?></button>
        <a href="event_details.php?id=<?php echo $shift['event_id']; ?>" class="btn btn-secondary"><?php echo $lang['back'] ?? 'Back'; ?></a>
    </form>
<?php include 'footer.php'; ?>