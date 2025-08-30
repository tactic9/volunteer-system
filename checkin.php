<?php
include 'header.php';
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}
$shift_id = filter_input(INPUT_GET, 'shift_id', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];
if (!$shift_id) {
    echo "<div class='alert alert-danger'>Invalid shift ID.</div>";
    include 'footer.php';
    exit;
}
// Check if signed up
$stmt = $conn->prepare("SELECT id FROM signups WHERE user_id = ? AND shift_id = ?");
$stmt->bind_param("ii", $user_id, $shift_id);
$stmt->execute();
$signup = $stmt->get_result()->fetch_assoc();
if (!$signup) {
    echo "<div class='alert alert-danger'>Not signed up for this shift.</div>";
    include 'footer.php';
    exit;
}
$signup_id = $signup['id'];
// Check if already checked in
$check_stmt = $conn->prepare("SELECT id, checkin_time, checkout_time FROM checkins WHERE signup_id = ?");
$check_stmt->bind_param("i", $signup_id);
$check_stmt->execute();
$checkin = $check_stmt->get_result()->fetch_assoc();
if ($checkin && $checkin['checkout_time']) {
    echo "<div class='alert alert-danger'>Already checked out.</div>";
    include 'footer.php';
    exit;
}
?>
<div class="container py-4">
    <h1>Check In/Out for Shift</h1>
    <?php if (!$checkin): ?>
        <form method="post" class="mb-3">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <button type="submit" name="checkin" class="btn btn-primary">Check In</button>
        </form>
    <?php else: ?>
        <form method="post" class="mb-3">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <button type="submit" name="checkout" class="btn btn-primary">Check Out</button>
        </form>
    <?php endif; ?>
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'])) {
        try {
            if (isset($_POST['checkin'])) {
                $now = date('Y-m-d H:i:s');
                $insert_stmt = $conn->prepare("INSERT INTO checkins (signup_id, checkin_time) VALUES (?, ?)");
                $insert_stmt->bind_param("is", $signup_id, $now);
                $insert_stmt->execute();
                $user_stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
                $user_stmt->bind_param("i", $user_id);
                $user_stmt->execute();
                $email = $user_stmt->get_result()->fetch_assoc()['email'];
                send_email($email, "Checked In", "You have checked in. <a href='checkin.php?shift_id=$shift_id'>Click to Check Out</a>");
                echo "<div class='alert alert-success'>Checked in successfully!</div>";
            } elseif (isset($_POST['checkout'])) {
                $now = date('Y-m-d H:i:s');
                $hours = (strtotime($now) - strtotime($checkin['checkin_time'])) / 3600;
                $update_stmt = $conn->prepare("UPDATE checkins SET checkout_time = ?, hours = ? WHERE id = ?");
                $update_stmt->bind_param("sdi", $now, $hours, $checkin['id']);
                $update_stmt->execute();
                $user_stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
                $user_stmt->bind_param("i", $user_id);
                $user_stmt->execute();
                $email = $user_stmt->get_result()->fetch_assoc()['email'];
                send_email($email, "Checked Out", "Thank you for your service! You served $hours hours.");
                echo "<div class='alert alert-success'>Checked out! Served $hours hours.</div>";
                echo "<form method='post' class='mt-3'>
                        <input type='hidden' name='csrf_token' value='".generate_csrf_token()."'>
                        <label class='form-label'>Feedback</label>
                        <textarea name='feedback' class='form-control'></textarea>
                        <button type='submit' name='submit_feedback' class='btn btn-secondary mt-2'>Submit Feedback</button>
                      </form>";
            } elseif (isset($_POST['submit_feedback'])) {
                $feedback_stmt = $conn->prepare("INSERT INTO feedback (user_id, shift_id, feedback) VALUES (?, ?, ?)");
                $feedback_stmt->bind_param("iis", $user_id, $shift_id, $_POST['feedback']);
                $feedback_stmt->execute();
                echo "<div class='alert alert-success mt-3'>Feedback submitted!</div>";
            }
        } catch (Exception $e) {
            error_log("Checkin error: " . $e->getMessage());
            echo "<div class='alert alert-danger mt-3'>Error processing request.</div>";
        }
    }
    ?>
</div>
<?php include 'footer.php'; ?>