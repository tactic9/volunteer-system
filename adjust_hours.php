<?php
include 'header.php';
if (!has_permission('adjust_hours')) {
    header('Location: login.php');
    exit;
}
$checkin_id = filter_input(INPUT_GET, 'checkin_id', FILTER_VALIDATE_INT);
if (!$checkin_id) {
    echo "<div class='alert alert-danger'>Invalid check-in ID.</div>";
    include 'footer.php';
    exit;
}
?>
<div class="container py-4">
    <h1>Adjust Hours</h1>
    <form method="post" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <div class="col-md-6">
            <label class="form-label">Manual Adjust (hours)</label>
            <input type="number" step="0.1" name="manual_adjust" class="form-control" required>
        </div>
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'])) {
        $manual_adjust = filter_input(INPUT_POST, 'manual_adjust', FILTER_VALIDATE_FLOAT);
        if ($manual_adjust !== false) {
            try {
                $stmt = $conn->prepare("UPDATE checkins SET manual_adjust = ? WHERE id = ?");
                $stmt->bind_param("di", $manual_adjust, $checkin_id);
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success mt-3'>Hours adjusted successfully!</div>";
                } else {
                    throw new Exception("Database error");
                }
            } catch (Exception $e) {
                error_log("Adjust hours error: " . $e->getMessage());
                echo "<div class='alert alert-danger mt-3'>Error adjusting hours.</div>";
            }
        } else {
            echo "<div class='alert alert-danger mt-3'>Invalid adjustment value.</div>";
        }
    }
    ?>
</div>
<?php include 'footer.php'; ?>