<?php
include 'header.php';
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'])) {
    $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    try {
        if ($password) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $first_name, $last_name, $email, $hashed_password, $_SESSION['user_id']);
        } else {
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
            $stmt->bind_param("sssi", $first_name, $last_name, $email, $_SESSION['user_id']);
        }
        $stmt->execute();
        $_SESSION['user_name'] = $first_name . ' ' . $last_name;
        $_SESSION['user_email'] = $email;
        echo "<div class='alert alert-success'>Profile updated successfully!</div>";
    } catch (Exception $e) {
        error_log("Profile update error: " . $e->getMessage());
        echo "<div class='alert alert-danger'>Failed to update profile.</div>";
    }
}
?>
    <h1><?php echo $lang['user_profile'] ?? 'User Profile'; ?></h1>
    <form method="post" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <div class="col-md-6">
            <label class="form-label"><?php echo $lang['first_name'] ?? 'First Name'; ?></label>
            <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label"><?php echo $lang['last_name'] ?? 'Last Name'; ?></label>
            <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label"><?php echo $lang['email'] ?? 'Email'; ?></label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label"><?php echo $lang['password'] ?? 'Password (leave blank to keep current)'; ?></label>
            <input type="password" name="password" class="form-control">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary"><?php echo $lang['save'] ?? 'Save'; ?></button>
            <a href="index.php" class="btn btn-secondary"><?php echo $lang['back'] ?? 'Back'; ?></a>
        </div>
    </form>
<?php include 'footer.php'; ?>