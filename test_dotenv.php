<?php
require 'vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
try {
    $dotenv->load();
    echo "Environment variables loaded successfully!<br>";
    echo "DB_HOST: " . htmlspecialchars($_ENV['DB_HOST']) . "<br>";
    echo "DB_USER: " . htmlspecialchars($_ENV['DB_USER']) . "<br>";
    echo "DB_NAME: " . htmlspecialchars($_ENV['DB_NAME']) . "<br>";
    echo "SMTP_HOST: " . htmlspecialchars($_ENV['SMTP_HOST']) . "<br>";
} catch (Exception $e) {
    die("Error loading .env: " . htmlspecialchars($e->getMessage()));
}
?>