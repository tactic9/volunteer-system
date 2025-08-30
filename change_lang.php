<?php
include 'config.php';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'es'])) {
    $_SESSION['lang'] = $_GET['lang'];
} else {
    error_log("Invalid language attempt: " . ($_GET['lang'] ?? 'none'));
}
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit;
?>