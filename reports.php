<?php
include 'header.php';
if (!has_permission('admin_access')) {
    header('Location: login.php');
    exit;
}
$user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT) ?: null;
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT) ?: null;
$group_id = filter_input(INPUT_GET, 'group_id', FILTER_VALIDATE_INT) ?: null;
$start_date = filter_input(INPUT_GET, 'start_date', FILTER_SANITIZE_STRING) ?: null;
$end_date = filter_input(INPUT_GET, 'end_date', FILTER_SANITIZE_STRING) ?: null;

$query = "SELECT c.id, c.user_id, u.first_name, u.last_name, e.name AS event_name, g.name AS group_name, c.checkin_time, c.checkout_time, c.hours, c.manual_adjust
          FROM checkins c
          JOIN users u ON c.user_id = u.id
          JOIN events e ON c.event_id = e.id
          LEFT JOIN `groups` g ON e.group_id = g.id
          WHERE 1=1";
$params = [];
$types = '';
if ($user_id) {
    $query .= " AND c.user_id = ?";
    $params[] = $user_id;
    $types .= 'i';
}
if ($event_id) {
    $query .= " AND c.event_id = ?";
    $params[] = $event_id;
    $types .= 'i';
}
if ($group_id) {
    $query .= " AND e.group_id = ?";
    $params[] = $group_id;
    $types .= 'i';
}
if ($start_date) {
    $query .= " AND c.checkin_time >= ?";
    $params[] = $start_date . ' 00:00:00';
    $types .= 's';
}
if ($end_date) {
    $query .= " AND c.checkin_time <= ?";
    $params[] = $end_date . ' 23:59:59';
    $types .= 's';
}
$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$checkins = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Aggregate data for charts
$hours_by_user = [];
$hours_by_event = [];
foreach ($checkins as $checkin) {
    $user_key = $checkin['first_name'] . ' ' . $checkin['last_name'];
    $hours_by_user[$user_key] = ($hours_by_user[$user_key] ?? 0) + ($checkin['hours'] + ($checkin['manual_adjust'] ?? 0));
    $event_key = $checkin['event_name'];
    $hours_by_event[$event_key] = ($hours_by_event[$event_key] ?? 0) + ($checkin['hours'] + ($checkin['manual_adjust'] ?? 0));
}
?>
    <h1><?php echo $lang['reports_title'] ?? 'Volunteer Reports'; ?></h1>
    <form method="get" class="row g-3 mb-4">
        <div class="col-md-3">
            <label class="form-label"><?php echo $lang['user'] ?? 'User'; ?></label>
            <select name="user_id" class="form-select">
                <option value=""><?php echo $lang['all_users'] ?? 'All Users'; ?></option>
                <?php
                $users = $conn->query("SELECT id, first_name, last_name FROM users ORDER BY first_name");
                while ($user = $users->fetch_assoc()) {
                    $selected = $user_id == $user['id'] ? 'selected' : '';
                    echo "<option value='{$user['id']}' $selected>{$user['first_name']} {$user['last_name']}</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?php echo $lang['event'] ?? 'Event'; ?></label>
            <select name="event_id" class="form-select">
                <option value=""><?php echo $lang['all_events'] ?? 'All Events'; ?></option>
                <?php
                $events = $conn->query("SELECT id, name FROM events WHERE status = 'approved' ORDER BY name");
                while ($event = $events->fetch_assoc()) {
                    $selected = $event_id == $event['id'] ? 'selected' : '';
                    echo "<option value='{$event['id']}' $selected>{$event['name']}</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?php echo $lang['group'] ?? 'Group'; ?></label>
            <select name="group_id" class="form-select">
                <option value=""><?php echo $lang['all_groups'] ?? 'All Groups'; ?></option>
                <?php
                $groups = $conn->query("SELECT id, name FROM `groups` ORDER BY name");
                while ($group = $groups->fetch_assoc()) {
                    $selected = $group_id == $group['id'] ? 'selected' : '';
                    echo "<option value='{$group['id']}' $selected>{$group['name']}</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?php echo $lang['start_date'] ?? 'Start Date'; ?></label>
            <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label"><?php echo $lang['end_date'] ?? 'End Date'; ?></label>
            <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>">
        </div>
        <div class="col-md-3 align-self-end">
            <button type="submit" class="btn btn-primary"><?php echo $lang['filter'] ?? 'Filter'; ?></button>
            <a href="generate_pdf.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success"><?php echo $lang['download_pdf'] ?? 'Download PDF'; ?></a>
        </div>
    </form>
    <div class="row">
        <div class="col-md-6">
            <h3><?php echo $lang['hours_by_user'] ?? 'Hours by User'; ?></h3>
            <canvas id="userChart"></canvas>
        </div>
        <div class="col-md-6">
            <h3><?php echo $lang['hours_by_event'] ?? 'Hours by Event'; ?></h3>
            <canvas id="eventChart"></canvas>
        </div>
    </div>
    <div class="table-responsive mt-4">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo $lang['user'] ?? 'User'; ?></th>
                    <th><?php echo $lang['event'] ?? 'Event'; ?></th>
                    <th><?php echo $lang['group'] ?? 'Group'; ?></th>
                    <th><?php echo $lang['checkin_time'] ?? 'Check-In Time'; ?></th>
                    <th><?php echo $lang['checkout_time'] ?? 'Check-Out Time'; ?></th>
                    <th><?php echo $lang['hours'] ?? 'Hours'; ?></th>
                    <th><?php echo $lang['manual_adjust'] ?? 'Manual Adjustment'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checkins as $checkin): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($checkin['first_name'] . ' ' . $checkin['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($checkin['event_name']); ?></td>
                        <td><?php echo htmlspecialchars($checkin['group_name'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($checkin['checkin_time']); ?></td>
                        <td><?php echo htmlspecialchars($checkin['checkout_time'] ?: 'N/A'); ?></td>
                        <td><?php echo number_format($checkin['hours'], 2); ?></td>
                        <td><?php echo number_format($checkin['manual_adjust'] ?? 0, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
        const userChart = new Chart(document.getElementById('userChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($hours_by_user)); ?>,
                datasets: [{
                    label: '<?php echo $lang['hours'] ?? 'Hours'; ?>',
                    data: <?php echo json_encode(array_values($hours_by_user)); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: { scales: { y: { beginAtZero: true } } }
        });
        const eventChart = new Chart(document.getElementById('eventChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_keys($hours_by_event)); ?>,
                datasets: [{
                    label: '<?php echo $lang['hours'] ?? 'Hours'; ?>',
                    data: <?php echo json_encode(array_values($hours_by_event)); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 206, 86, 0.5)',
                        'rgba(75, 192, 192, 0.5)',
                        'rgba(153, 102, 255, 0.5)'
                    ]
                }]
            }
        });
    </script>
<?php include 'footer.php'; ?>