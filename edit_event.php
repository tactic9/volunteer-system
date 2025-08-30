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
    <h1>Edit Event</h1>
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
        <div class="col-md-4">
            <label class="form-label">Latitude</label>
            <input type="number" step="0.000001" name="lat" class="form-control" value="<?php echo $event['lat']; ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Longitude</label>
            <input type="number" step="0.000001" name="lng" class="form-control" value="<?php echo $event['lng']; ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Radius (meters)</label>
            <input type="number" name="radius" class="form-control" value="<?php echo $event['radius']; ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Group (Optional)</label>
            <select name="group_id" class="form-control">
                <option value="">None</option>
                <?php
                $stmt = $conn->prepare("SELECT id, name FROM `groups`");
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $selected = $event['group_id'] == $row['id'] ? 'selected' : '';
                    echo "<option value='{$row['id']}' $selected>{$row['name']}</option>";
                }
                ?>
            </select>
        </div>
        <div id="shifts">
            <h3>Shifts</h3>
            <?php foreach ($shifts as $key => $shift): ?>
                <div class="shift row g-3 mb-3">
                    <input type="hidden" name="shift_id[]" value="<?php echo $shift['id']; ?>">
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
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
    </form>
    <script>
    function addShift() {
        var shifts = document.getElementById('shifts');
        var div = document.createElement('div');
        div.className = 'shift row g-3 mb-3';
        div.innerHTML = `
            <input type="hidden" name="shift_id[]" value="0">
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
            $group_id = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT) ?: null;
            $lat = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT) ?: null;
            $lng = filter_input(INPUT_POST, 'lng', FILTER_VALIDATE_FLOAT) ?: null;
            $radius = filter_input(INPUT_POST, 'radius', FILTER_VALIDATE_INT) ?: null;
            $stmt = $conn->prepare("UPDATE events SET name = ?, description = ?, location = ?, lat = ?, lng = ?, radius = ?, group_id = ? WHERE id = ?");
            $stmt->bind_param("sssddiis", $_POST['name'], $_POST['description'], $_POST['location'], $lat, $lng, $radius, $group_id, $event_id);
            $stmt->execute();
            foreach ($_POST['shift_id'] as $key => $shift_id) {
                $date = $_POST['shift_date'][$key];
                $start = $_POST['shift_start'][$key];
                $end = $_POST['shift_end'][$key];
                if ($shift_id == 0) {
                    $shift_stmt = $conn->prepare("INSERT INTO shifts (event_id, `date`, time_start, time_end) VALUES (?, ?, ?, ?)");
                    $shift_stmt->bind_param("isss", $event_id, $date, $start, $end);
                } else {
                    $shift_stmt = $conn->prepare("UPDATE shifts SET `date` = ?, time_start = ?, time_end = ? WHERE id = ?");
                    $shift_stmt->bind_param("sssi", $date, $start, $end, $shift_id);
                }
                $shift_stmt->execute();
            }
            echo "<div class='alert alert-success mt-3'>Event updated successfully!</div>";
        } catch (Exception $e) {
            error_log("Edit event error: " . $e->getMessage());
            echo "<div class='alert alert-danger mt-3'>Error updating event.</div>";
        }
    }
    ?>
</div>
<?php include 'footer.php'; ?>