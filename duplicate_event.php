<?php
include 'header.php';
if (!has_permission('manage_events')) {
    header('Location: login.php');
    exit;
}
$event_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$event_id) {
    echo "<div class='alert alert-danger'>Invalid event ID.</div>";
    include 'footer.php';
    exit;
}
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
if (!$event) {
    echo "<div class='alert alert-danger'>Event not found.</div>";
    include 'footer.php';
    exit;
}
$shifts_stmt = $conn->prepare("SELECT * FROM shifts WHERE event_id = ?");
$shifts_stmt->bind_param("i", $event_id);
$shifts_stmt->execute();
$shifts = $shifts_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<div class="container py-4">
    <h1>Duplicate Event</h1>
    <form method="post" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <div class="col-md-6">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($event['name']); ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($event['location']); ?>">
        </div>
        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"><?php echo htmlspecialchars($event['description']); ?></textarea>
        </div>
        <div id="shifts">
            <h3>Shifts</h3>
            <?php foreach ($shifts as $key => $shift): ?>
                <div class="shift row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="date" name="shift_date[]" class="form-control" value="<?php echo htmlspecialchars($shift['date']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Start Time</label>
                        <input type="time" name="shift_start[]" class="form-control" value="<?php echo htmlspecialchars($shift['time_start']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">End Time</label>
                        <input type="time" name="shift_end[]" class="form-control" value="<?php echo htmlspecialchars($shift['time_end']); ?>" required>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="col-12">
            <button type="button" onclick="addShift()" class="btn btn-secondary">Add Shift</button>
            <button type="submit" class="btn btn-primary">Duplicate</button>
        </div>
    </form>
    <script>
    function addShift() {
        var shifts = document.getElementById('shifts');
        var count = shifts.getElementsByClassName('shift').length;
        var div = document.createElement('div');
        div.className = 'shift row g-3 mb-3';
        div.innerHTML = `
            <div class="col-md-4">
                <label class="form-label">Date</label>
                <input type="date" name="shift_date[]" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Start Time</label>
                <input type="time" name="shift_start[]" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">End Time</label>
                <input type="time" name="shift_end[]" class="form-control" required>
            </div>
        `;
        shifts.appendChild(div);
    }
    </script>
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'])) {
        try {
            $status = has_permission('approve_events') ? 'approved' : 'pending';
            $group_id = $event['group_id'] ? (int)$event['group_id'] : null;
            $stmt = $conn->prepare("INSERT INTO events (name, description, location, lat, lng, radius, group_id, status, coordinator_id, type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssddiisisi", $_POST['name'], $_POST['description'], $_POST['location'], $event['lat'], $event['lng'], $event['radius'], $group_id, $status, $event['coordinator_id'], $event['type'], $_SESSION['user_id']);
            $stmt->execute();
            $new_event_id = $conn->insert_id;
            foreach ($_POST['shift_date'] as $key => $date) {
                $start = $_POST['shift_start'][$key];
                $end = $_POST['shift_end'][$key];
                $shift_stmt = $conn->prepare("INSERT INTO shifts (event_id, `date`, time_start, time_end) VALUES (?, ?, ?, ?)");
                $shift_stmt->bind_param("isss", $new_event_id, $date, $start, $end);
                $shift_stmt->execute();
            }
            echo "<div class='alert alert-success mt-3'>Event duplicated successfully!</div>";
        } catch (Exception $e) {
            error_log("Duplicate event error: " . $e->getMessage());
            echo "<div class='alert alert-danger mt-3'>Error duplicating event.</div>";
        }
    }
    ?>
</div>
<?php include 'footer.php'; ?>