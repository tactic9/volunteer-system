<?php
function is_logged_in() {
  return isset($_SESSION['user_id']);
}

function has_permission($permission) {
  if (!is_logged_in()) return false;
  // Implement permission check based on user_roles and role_permissions tables
  global $conn;
  $user_id = $_SESSION['user_id'];
  $stmt = $conn->prepare("SELECT p.permission FROM role_permissions p JOIN user_roles u ON p.role_id = u.role_id WHERE u.user_id = ? AND p.permission = ?");
  $stmt->bind_param('is', $user_id, $permission);
  $stmt->execute();
  $result = $stmt->get_result();
  return $result->num_rows > 0;
}

function get_user_role() {
  if (!is_logged_in()) return null;
  global $conn;
  $user_id = $_SESSION['user_id'];
  $stmt = $conn->prepare("SELECT r.role_name FROM roles r JOIN user_roles u ON r.id = u.role_id WHERE u.user_id = ? LIMIT 1");
  $stmt->bind_param('i', $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  return $row ? $row['role_name'] : null;
}

function generate_csrf_token() {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
  return is_string($token) && is_string($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function send_email($to, $subject, $message) {
  // Implement with PHPMailer
  global $conn; // If needed for logging
  require 'vendor/autoload.php';
  $mail = new PHPMailer\PHPMailer\PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->Host = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['SMTP_USER'];
    $mail->Password = $_ENV['SMTP_PASS'];
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    $mail->setFrom($_ENV['SMTP_USER'], 'Volunteer System');
    $mail->addAddress($to);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $message;
    $mail->send();
    return true;
  } catch (Exception $e) {
    error_log("Email error: {$mail->ErrorInfo}");
    return false;
  }
}

function haversine_distance($lat1, $lon1, $lat2, $lon2) {
  $earth_radius = 6371; // km
  $dLat = deg2rad($lat2 - $lat1);
  $dLon = deg2rad($lon2 - $lon1);
  $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
  $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
  return $earth_radius * $c;
}
?>