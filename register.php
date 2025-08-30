<?php
include 'header.php';
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'])) {
    $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    try {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo "<div class='alert alert-danger'>Email already registered.</div>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $first_name, $last_name, $email, $hashed_password);
            $stmt->execute();
            $_SESSION['user_id'] = $conn->insert_id;
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            header('Location: index.php');
            exit;
        }
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        echo "<div class='alert alert-danger'>Registration failed. Please try again.</div>";
    }
}
?>
    <h1><?php echo $lang['register'] ?? 'Register'; ?></h1>
    <form method="post" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <div class="col-md-6">
            <label class="form-label"><?php echo $lang['first_name'] ?? 'First Name'; ?></label>
            <input type="text" name="first_name" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label"><?php echo $lang['last_name'] ?? 'Last Name'; ?></label>
            <input type="text" name="last_name" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label"><?php echo $lang['email'] ?? 'Email'; ?></label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label"><?php echo $lang['password'] ?? 'Password'; ?></label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary"><?php echo $lang['register'] ?? 'Register'; ?></button>
            <a href="login.php" class="btn btn-link"><?php echo $lang['login'] ?? 'Login'; ?></a>
        </div>
    </form>
<?php include 'footer.php'; ?>