<?php
require_once 'config.php';
use PHPMailer\PHPMailer\PHPMailer;

// Cleanup old notifications (older than 30 days)
$stmt = $conn->prepare("DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stmt->execute();

// Send reminders (already in reminders.php, included for cron)
include 'reminders.php';
echo "Cron jobs executed successfully.";
?>