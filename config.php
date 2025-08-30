<?php
session_start();
require_once 'vendor/autoload.php'; // For dotenv, PHPMailer
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db_host = $_ENV['DB_HOST'] ?? 'localhost';
$db_user = $_ENV['DB_USER'] ?? 'devild9_vva_admin';
$db_pass = $_ENV['DB_PASS'] ?? 'uf4M6y6^as&t*HMu';
$db_name = $_ENV['DB_NAME'] ?? 'devild9_volunteer_db';

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    error_log("DB connection error: " . $e->getMessage());
    die("Database error. Please try again later.");
}

$lang = $_SESSION['lang'] ?? 'en';
$lang_file = __DIR__ . "/lang/$lang.php";
if (file_exists($lang_file)) {
    $lang = include $lang_file;
} else {
    error_log("Language file missing: $lang_file");
    $lang = include __DIR__ . '/lang/en.php';
}
?>