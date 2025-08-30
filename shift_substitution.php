<?php
include 'header.php';
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}
$signup_id = filter_input(INPUT_GET, 'signup_id', FILTER_VALIDATE_INT);
if (!$signup_id) {
    echo "<div class='alert alert-danger'>Invalid signup ID.</div>";
    include 'footer.php';
    exit;
}
$stmt = $conn->prepare("SELECT s.id, s.shift_id, s.user_id, e.name AS event_name, sh.date, sh.time_start, sh.time_end
                        FROM signups s
                        JOIN shifts sh ON s.shift_id = sh.id
                        JOIN events e ON sh.event_id = e.id
                        WHERE s.id = ? AND s.user_id = ?");
$stmt->bind_param("ii", $signup_id, $_SESSION['user_id']);
$stmt->execute();
$signup = $stmt->get_result()->fetch_assoc();
if (!$signup) {
    echo "<div class='alert alert-danger'>Signup not found or not yours.</div>";
    include 'footer.php';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'])) {
    $new_user_id = filter_input(INPUT_POST, 'new_user_id', FILTER_VALIDATE_INT);
    try {
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->bind_param("i", $new_user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows == 0) {
            echo "<div class='alert alert-danger'>Invalid user selected.</div>";
        } else {
            $stmt = $conn->prepare("UPDATE signups SET user_id = ?, substitution_requested = NOW() WHERE id = ?");
            $stmt->bind_param("ii", $new_user_id, $signup_id);
            $stmt->execute();
            send_email(
                $_SESSION['user_email'],
                "Shift Substitution Request",
                "<p>Your substitution request for {$signup['event_name']} on {$signup['date']} has been submitted.</p>"
            );
            echo "<div class='alert alert-success'>Substitution requested! Awaiting approval.</div>";
        }
    } catch (Exception $e) {
        error_log("Substitution error: " . $e->getMessage());
        echo "<div class='alert alert-danger'>Failed to request substitution.</div>";
    }
}
?>
    <h1><?php echo $lang['shift_substitution'] ?? 'Request Shift Substitution'; ?></h1>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($signup['event_name']); ?></h5>
            <p><strong><?php echo $lang['date'] ?? 'Date'; ?>:</strong> <?php echo htmlspecialchars($signup['date']); ?></p>
            <p><strong><?php echo $lang['time'] ?? 'Time'; ?>:</strong> <?php echo htmlspecialchars($signup['time_start'] . ' - ' . $signup['time_end']); ?></p>
        </div>
    </div>
    <form method="post" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <div class="col-md-6">
            <label class="form-label"><?php echo $lang['new_volunteer'] ?? 'New Volunteer'; ?></label>
            <select name="new_user_id" class="form-select" required>
                <option value=""><?php echo $lang['select_user'] ?? 'Select User'; ?></option>
                <?php
                $stmt = $conn->prepare("SELECT id, first_name, last_name FROM users WHERE id != ? ORDER BY first_name");
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                foreach ($users as $user) {
                    echo "<option value='{$user['id']}'>" . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-6 align-self-end">
            <button type="submit" class="btn btn-primary"><?php echo $lang['request_substitution'] ?? 'Request Substitution'; ?></button>
            <a href="event_details.php?id=<?php echo $signup['event_id']; ?>" class="btn btn-secondary"><?php echo $lang['back'] ?? 'Back'; ?></a>
        </div>
    </form>
<?php include 'footer.php'; ?>