<?php
session_start();
ob_start();
require_once 'config.php';
require_once 'functions.php';

$error = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    $stmt = $conn->prepare("SELECT id, password, role_id FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role_id'] = $row['role_id'];
            if ($remember) {
                setcookie('user_id', $row['id'], time() + 30*24*60*60, '/');
            }
            header('Location: dashboard.php');
            exit; // Exit immediately after redirect
        } else {
            $error = $lang['login_failed'];
        }
    } else {
        $error = $lang['login_failed'];
    }
}

// Only include header and output HTML after login logic
include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['login']; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
</head>
<body>
    <main>
        <h1><?php echo $lang['login']; ?></h1>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="email"><?php echo $lang['email']; ?>:</label>
            <input type="email" name="email" id="email" required>
            <label for="password"><?php echo $lang['password']; ?>:</label>
            <input type="password" name="password" id="password" required>
            <label><input type="checkbox" name="remember"> <?php echo $lang['remember_me']; ?></label>
            <button type="submit"><?php echo $lang['login']; ?></button>
            <p><a href="forgot_password.php"><?php echo $lang['forgot_password']; ?></a></p>
        </form>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>