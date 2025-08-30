<?php
require_once 'config.php';
if (!has_permission('admin_access')) {
    header('Location: login.php');
    exit;
}
$user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT) ?: null;
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT) ?: null;
$group_id = filter_input(INPUT_GET, 'group_id', FILTER_VALIDATE_INT) ?: null;
$start_date = filter_input(INPUT_GET, 'start_date', FILTER_SANITIZE_STRING) ?: null;
$end_date = filter_input(INPUT_GET, 'end_date', FILTER_SANITIZE_STRING) ?: null;

$query = "SELECT u.first_name, u.last_name, e.name AS event_name, g.name AS group_name, c.checkin_time, c.checkout_time, c.hours, c.manual_adjust
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

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="volunteer_report.csv"');
$output = fopen('php://output', 'w');
fputcsv($output, ['Volunteer', 'Event', 'Group', 'Check-In', 'Check-Out', 'Hours', 'Adjustment']);
foreach ($checkins as $checkin) {
    fputcsv($output, [
        $checkin['first_name'] . ' ' . $checkin['last_name'],
        $checkin['event_name'],
        $checkin['group_name'] ?: 'N/A',
        $checkin['checkin_time'],
        $checkin['checkout_time'] ?: 'N/A',
        number_format($checkin['hours'], 2),
        number_format($checkin['manual_adjust'] ?? 0, 2)
    ]);
}
fclose($output);
exit;
?>