<?php
include 'header.php';
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}
?>
<div class="container py-4">
    <h1>Create Event</h1>
    <form method="post" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <div class="col-md-6">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"></textarea>
        </div>
        <div class="col-md-4">
            <label class="form-label">Latitude</label>
            <input type="number" step="0.000001" name="lat" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Longitude</label>
            <input type="number" step="0.000001" name="lng" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Radius (meters)</label>
            <input type="number" name="radius" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label">Type</label>
            <select name="type" class="form-control" id="eventType">
                <option value="regular">Regular</option>
                <option value="personal">Personal</option>
            </select>
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
                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                }
                ?>
            </select>
        </div>
        <div id="shifts">
            <h3>Shifts</h3>
            <div class="shift row g-3 mb-3">
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
                <div class="col-12">
                    <label class="form-label">Assign Users</label>
                    <select name="assigned_users[0][]" multiple class="form-control">
                        <?php
                        $stmt = $conn->prepare("SELECT id, first_name, last_name FROM users WHERE id != ?");
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($user = $result->fetch_assoc()) {
                            echo "<option value='{$user['id']}'>{$user['first_name']} {$user['last_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-12">
            <button type="button" onclick="addShift()" class="btn btn-secondary">Add Shift</button>
        </div>
        <div class="col-md-6" id="manual_hours" style="display:none;">
            <label class="form-label">Manual Hours (for Personal)</label>
            <input type="number" step="0.1" name="manual_hours" class="form-control">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Create</button>
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
            <div class="col-12">
                <label class="form-label">Assign Users</label>
                <select name="assigned_users[${count}][]" multiple class="form-control">
                    ${document.querySelector('select[name="assigned_users[0][]"]').innerHTML}
                </select>
            </div>
        `;
        shifts.appendChild(div);
    }
    document.getElementById('eventType').addEventListener('change', function() {
        document.getElementById('manual_hours').style.display = this.value === 'personal' ? 'block' : 'none';
    });
    </script>
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'])) {
        try {
            $user_id = $_SESSION['user_id'];
            $role = get_user_role();
            $status = in_array($role, ['lead', 'admin', 'super_admin']) ? 'approved' : 'pending';
            $group_id = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT) ?: null;
            $coordinator_id = ($_POST['type'] === 'personal') ? $user_id : null;
            $lat = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT) ?: null;
            $lng = filter_input(INPUT_POST, 'lng', FILTER_VALIDATE_FLOAT) ?: null;
            $radius = filter_input(INPUT_POST, 'radius', FILTER_VALIDATE_INT) ?: null;
            $stmt = $conn->prepare("INSERT INTO events (name, description, location, lat, lng, radius, group_id, status, coordinator_id, type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssddiisisi", $_POST['name'], $_POST['description'], $_POST['location'], $lat, $lng, $radius, $group_id, $status, $coordinator_id, $_POST['type'], $user_id);
            $stmt->execute();
            $event_id = $conn->insert_id;
            foreach ($_POST['shift_date'] as $key => $date) {
                $start = $_POST['shift_start'][$key];
                $end = $_POST['shift_end'][$key];
                $shift_stmt = $conn->prepare("INSERT INTO shifts (event_id, `date`, time_start, time_end) VALUES (?, ?, ?, ?)");
                $shift_stmt->bind_param("isss", $event_id, $date, $start, $end);
                $shift_stmt->execute();
                $shift_id = $conn->insert_id;
                if (isset($_POST['assigned_users'][$key])) {
                    foreach ($_POST['assigned_users'][$key] as $assigned_id) {
                        $signup_stmt = $conn->prepare("INSERT INTO signups (user_id, shift_id) VALUES (?, ?)");
                        $signup_stmt->bind_param("ii", $assigned_id, $shift_id);
                        $signup_stmt->execute();
                    }
                }
                if ($_POST['type'] === 'personal') {
                    $signup_stmt = $conn->prepare("INSERT INTO signups (user_id, shift_id) VALUES (?, ?)");
                    $signup_stmt->bind_param("ii", $user_id, $shift_id);
                    $signup_stmt->execute();
                    $signup_id = $conn->insert_id;
                    $manual_hours = filter_input(INPUT_POST, 'manual_hours', FILTER_VALIDATE_FLOAT) ?: 0;
                    $checkin_stmt = $conn->prepare("INSERT INTO checkins (signup_id, hours, manual_adjust) VALUES (?, 0, ?)");
                    $checkin_stmt->bind_param("id", $signup_id, $manual_hours);
                    $checkin_stmt->execute();
                }
            }
            echo "<div class='alert alert-success mt-3'>Event created! Status: $status</div>";
        } catch (Exception $e) {
            error_log("Create event error: " . $e->getMessage());
            echo "<div class='alert alert-danger mt-3'>Error creating event.</div>";
        }
    }
    ?>
</div>
<?php include 'footer.php'; ?>