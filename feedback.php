<?php
include 'header.php';
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
if (!$event_id) {
    echo "<div class='alert alert-danger'>Invalid event ID.</div>";
    include 'footer.php';
    exit;
}
$stmt = $conn->prepare("SELECT name FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
if (!$event) {
    echo "<div class='alert alert-danger'>Event not found.</div>";
    include 'footer.php';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'])) {
    $feedback = filter_input(INPUT_POST, 'feedback', FILTER_SANITIZE_STRING);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    try {
        $stmt = $conn->prepare("INSERT INTO feedback (event_id, user_id, feedback, rating, submitted_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iisi", $event_id, $_SESSION['user_id'], $feedback, $rating);
        $stmt->execute();
        send_email(
            $_SESSION['user_email'],
            "Feedback Submitted",
            "<p>Thank you for your feedback on {$event['name']}. We appreciate your input!</p>"
        );
        echo "<div class='alert alert-success'>Feedback submitted successfully!</div>";
    } catch (Exception $e) {
        error_log("Feedback error: " . $e->getMessage());
        echo "<div class='alert alert-danger'>Failed to submit feedback.</div>";
    }
}
?>
    <h1><?php echo $lang['submit_feedback'] ?? 'Submit Feedback'; ?>: <?php echo htmlspecialchars($event['name']); ?></h1>
    <form method="post" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <div class="col-12">
            <label class="form-label"><?php echo $lang['feedback'] ?? 'Feedback'; ?></label>
            <textarea name="feedback" class="form-control" rows="5" required></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label"><?php echo $lang['rating'] ?? 'Rating (1-5)'; ?></label>
            <select name="rating" class="form-select" required>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
            </select>
        </div>
        <div class="col-md-6 align-self-end">
            <button type="submit" class="btn btn-primary"><?php echo $lang['submit'] ?? 'Submit'; ?></button>
            <a href="event_details.php?id=<?php echo $event_id; ?>" class="btn btn-secondary"><?php echo $lang['back'] ?? 'Back'; ?></a>
        </div>
    </form>
<?php include 'footer.php'; ?>