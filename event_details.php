<?php
include 'header.php';
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}
$event_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$event_id) {
    echo "<div class='alert alert-danger'>Invalid event ID.</div>";
    include 'footer.php';
    exit;
}
$stmt = $conn->prepare("SELECT e.*, g.name AS group_name FROM events e LEFT JOIN `groups` g ON e.group_id = g.id WHERE e.id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
if (!$event) {
    echo "<div class='alert alert-danger'>Event not found.</div>";
    include 'footer.php';
    exit;
}
$shifts_stmt = $conn->prepare("SELECT s.*, COUNT(si.id) as signup_count FROM shifts s LEFT JOIN signups si ON s.id = si.shift_id WHERE s.event_id = ? GROUP BY s.id");
$shifts_stmt->bind_param("i", $event_id);
$shifts_stmt->execute();
$shifts = $shifts_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<div class="container py-4">
    <h1><?php echo htmlspecialchars($event['name']); ?></h1>
    <div class="card mb-4">
        <div class="card-body">
            <p><strong>Description:</strong> <?php echo htmlspecialchars($event['description'] ?: 'N/A'); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location'] ?: 'N/A'); ?></p>
            <?php if ($event['lat'] && $event['lng']): ?>
                <p><strong>Coordinates:</strong> <?php echo htmlspecialchars($event['lat'] . ', ' . $event['lng']); ?></p>
                <button onclick="checkDistance()" class="btn btn-info">Check Distance</button>
            <?php endif; ?>
            <p><strong>Group:</strong> <?php echo htmlspecialchars($event['group_name'] ?: 'N/A'); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($event['status']); ?></p>
        </div>
    </div>
    <h3>Shifts</h3>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Signups</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shifts as $shift): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($shift['date']); ?></td>
                        <td><?php echo htmlspecialchars($shift['time_start']); ?></td>
                        <td><?php echo htmlspecialchars($shift['time_end']); ?></td>
                        <td><?php echo $shift['signup_count']; ?></td>
                        <td><a href="checkin.php?shift_id=<?php echo $shift['id']; ?>" class="btn btn-primary btn-sm">Check In</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
    function checkDistance() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(position => {
                const lat = <?php echo $event['lat']; ?>;
                const lng = <?php echo $event['lng']; ?>;
                const radius = <?php echo $event['radius'] ?? 500; ?>;
                const userLat = position.coords.latitude;
                const userLng = position.coords.longitude;
                const R = 6371000; // Earth radius in meters
                const dLat = (lat - userLat) * Math.PI / 180;
                const dLng = (lng - userLng) * Math.PI / 180;
                const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                          Math.cos(userLat * Math.PI / 180) * Math.cos(lat * Math.PI / 180) *
                          Math.sin(dLng/2) * Math.sin(dLng/2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                const distance = R * c;
                alert(distance <= radius ? 'You are within the event radius!' : 'You are outside the event radius.');
            }, () => alert('Geolocation required.'));
        } else {
            alert('Geolocation not supported.');
        }
    }
    </script>
</div>
<?php include 'footer.php'; ?>