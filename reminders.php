<?php
require_once 'config.php';
use PHPMailer\PHPMailer\PHPMailer;

// Run via cron (e.g., daily)
$stmt = $conn->prepare("SELECT s.id, s.date, s.time_start, e.name AS event_name, u.email, u.first_name
                        FROM signups su
                        JOIN shifts s ON su.shift_id = s.id
                        JOIN events e ON s.event_id = e.id
                        JOIN users u ON su.user_id = u.id
                        WHERE s.date = DATE(DATE_ADD(NOW(), INTERVAL 1 DAY))");
$stmt->execute();
$signups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($signups as $signup) {
    send_email(
        $signup['email'],
        "Upcoming Volunteer Shift Reminder",
        "<p>Hi {$signup['first_name']},<br>You have a volunteer shift tomorrow for {$signup['event_name']} on {$signup['date']} at {$signup['time_start']}.</p>"
    );
}
echo "Reminders sent successfully.";
?>