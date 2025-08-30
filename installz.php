<?php
require_once 'vendor/autoload.php';
use Dotenv\Dotenv;

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error_log');

// Load .env file
$dotenv = Dotenv::createImmutable(__DIR__);
try {
    $dotenv->load();
} catch (Exception $e) {
    error_log("Dotenv error: " . $e->getMessage());
    die("<div class='alert alert-danger'>Error loading .env: " . htmlspecialchars($e->getMessage()) . "</div>");
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error_log');

// Initialize connection without database selection
$conn = new mysqli($db_host, $db_user, $db_pass);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("<div class='alert alert-danger'>Connection failed: " . htmlspecialchars($conn->connect_error) . "</div>");
}

// Set charset
$conn->set_charset('utf8mb4');

// Create database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
if ($conn->error) {
    error_log("Database creation failed: " . $conn->error);
    die("<div class='alert alert-danger'>Database creation failed: " . htmlspecialchars($conn->error) . "</div>");
}

// Select database
$conn->select_db($db_name);

// Table creation queries in dependency order
$tables = [
    // Users table (no dependencies)
    "users" => "
        CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    // Roles table (no dependencies)
    "roles" => "
        CREATE TABLE IF NOT EXISTS roles (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            permissions TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    // Events table (depends on users)
    "events" => "
        CREATE TABLE IF NOT EXISTS events (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            location VARCHAR(255) NOT NULL,
            lat DECIMAL(9,6),
            lng DECIMAL(9,6),
            radius DECIMAL(5,2),
            created_by INT UNSIGNED NOT NULL,
            status ENUM('pending', 'approved', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    // Groups table (depends on users)
    "groups" => "
        CREATE TABLE IF NOT EXISTS `groups` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            created_by INT UNSIGNED NOT NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    // User_roles table (depends on users, roles)
    "user_roles" => "
        CREATE TABLE IF NOT EXISTS user_roles (
            user_id INT UNSIGNED NOT NULL,
            role_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (user_id, role_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    // Shifts table (depends on events)
    "shifts" => "
        CREATE TABLE IF NOT EXISTS shifts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_id INT UNSIGNED NOT NULL,
            date DATE NOT NULL,
            time_start TIME NOT NULL,
            time_end TIME NOT NULL,
            slots INT NOT NULL,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    // Signups table (depends on users, shifts)
    "signups" => "
        CREATE TABLE IF NOT EXISTS signups (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            shift_id INT UNSIGNED NOT NULL,
            signup_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            substitution_requested TIMESTAMP,
            substitution_approved TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    // Checkins table (depends on users, shifts)
    "checkins" => "
        CREATE TABLE IF NOT EXISTS checkins (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            shift_id INT UNSIGNED NOT NULL,
            checkin_time TIMESTAMP NOT NULL,
            checkout_time TIMESTAMP,
            hours DECIMAL(5,2),
            manual_adjustment DECIMAL(5,2),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    // Notifications table (depends on users, events)
    "notifications" => "
        CREATE TABLE IF NOT EXISTS notifications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            event_id INT UNSIGNED NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    // Feedback table (depends on users, events)
    "feedback" => "
        CREATE TABLE IF NOT EXISTS feedback (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            event_id INT UNSIGNED NOT NULL,
            feedback TEXT NOT NULL,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

// Create tables in order
foreach ($tables as $table_name => $query) {
    if (!$conn->query($query)) {
        error_log("Error creating table $table_name: " . $conn->error);
        die("<div class='alert alert-danger'>Error creating table $table_name: " . htmlspecialchars($conn->error) . "</div>");
    }
}

// Add indexes for performance
$indexes = [
    "CREATE INDEX IF NOT EXISTS idx_event_created_by ON events(created_by)",
    "CREATE INDEX IF NOT EXISTS idx_shift_event_id ON shifts(event_id)",
    "CREATE INDEX IF NOT EXISTS idx_checkin_user_id ON checkins(user_id)"
];
foreach ($indexes as $index) {
    if (!$conn->query($index)) {
        error_log("Error creating index: " . $conn->error);
    }
}

// Insert sample data (only if tables are empty)
$conn->query("INSERT INTO users (first_name, last_name, email, password) 
              SELECT 'Admin', 'User', 'admin@example.com', '" . password_hash('admin123', PASSWORD_DEFAULT) . "' 
              WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@example.com')");
$conn->query("INSERT INTO users (first_name, last_name, email, password) 
              SELECT 'John', 'Doe', 'john.doe@example.com', '" . password_hash('user123', PASSWORD_DEFAULT) . "' 
              WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'john.doe@example.com')");

$conn->query("INSERT INTO roles (name, permissions) 
              SELECT 'Admin', 'admin_access,manage_events' 
              WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'Admin')");
$conn->query("INSERT INTO roles (name, permissions) 
              SELECT 'Volunteer', 'signup_events,checkin' 
              WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'Volunteer')");

$conn->query("INSERT INTO user_roles (user_id, role_id) 
              SELECT 1, 1 
              WHERE NOT EXISTS (SELECT 1 FROM user_roles WHERE user_id = 1 AND role_id = 1)");
$conn->query("INSERT INTO user_roles (user_id, role_id) 
              SELECT 2, 2 
              WHERE NOT EXISTS (SELECT 1 FROM user_roles WHERE user_id = 2 AND role_id = 2)");

$conn->query("INSERT INTO `groups` (name, description, created_by) 
              SELECT 'Community Volunteers', 'Local community volunteer group', 1 
              WHERE NOT EXISTS (SELECT 1 FROM `groups` WHERE name = 'Community Volunteers')");

$conn->query("INSERT INTO events (name, location, lat, lng, radius, created_by, status) 
              SELECT 'Community Cleanup', 'Central Park, NYC', 40.7128, -74.0060, 0.5, 1, 'approved' 
              WHERE NOT EXISTS (SELECT 1 FROM events WHERE name = 'Community Cleanup')");

$conn->query("INSERT INTO shifts (event_id, date, time_start, time_end, slots) 
              SELECT 1, '2025-09-01', '09:00:00', '12:00:00', 10 
              WHERE NOT EXISTS (SELECT 1 FROM shifts WHERE event_id = 1 AND date = '2025-09-01')");

$conn->query("INSERT INTO signups (user_id, shift_id, signup_date) 
              SELECT 2, 1, NOW() 
              WHERE NOT EXISTS (SELECT 1 FROM signups WHERE user_id = 2 AND shift_id = 1)");

$conn->query("INSERT INTO checkins (user_id, shift_id, checkin_time, hours) 
              SELECT 2, 1, '2025-09-01 09:00:00', 3.0 
              WHERE NOT EXISTS (SELECT 1 FROM checkins WHERE user_id = 2 AND shift_id = 1)");

$conn->query("INSERT INTO notifications (user_id, event_id, message) 
              SELECT 2, 1, 'You signed up for Community Cleanup!' 
              WHERE NOT EXISTS (SELECT 1 FROM notifications WHERE user_id = 2 AND event_id = 1)");

$conn->query("INSERT INTO feedback (user_id, event_id, feedback, rating) 
              SELECT 2, 1, 'Great event!', 5 
              WHERE NOT EXISTS (SELECT 1 FROM feedback WHERE user_id = 2 AND event_id = 1)");

// Output success message
echo "<div class='alert alert-success'>Installation completed successfully! Database and sample data created.</div>";
echo "<p><a href='login.php' class='btn btn-primary'>Go to Login</a></p>";

// Close connection
$conn->close();
?>