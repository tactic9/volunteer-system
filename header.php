<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start(); // Start output buffering
session_start();
require_once 'config.php';
require_once 'functions.php';

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://devild9.stu.cofc.edu/volunteer-system-php/');
}
ob_end_clean(); // Clear buffer if no output is intended
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="<?php echo BASE_URL; ?>index.php"><?php echo $lang['home']; ?></a></li>
                <?php if (is_logged_in()): ?>
                    <li><a href="<?php echo BASE_URL; ?>dashboard.php"><?php echo $lang['dashboard']; ?></a></li>
                    <li><a href="<?php echo BASE_URL; ?>logout.php"><?php echo $lang['logout']; ?></a></li>
                <?php else: ?>
                    <li><a href="<?php echo BASE_URL; ?>login.php"><?php echo $lang['login']; ?></a></li>
                    <li><a href="<?php echo BASE_URL; ?>register.php"><?php echo $lang['register']; ?></a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>