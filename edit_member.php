<?php
include 'header.php';
if (!has_permission('admin_access')) {
    header('Location: login.php');
    exit;
}
$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user_id) {
    echo "<div class='alert alert-danger'>Invalid user ID.</div>";
    include 'footer.php';
    exit;
}
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
    echo "<div class='alert alert-danger'>User not found.</div>";
    include 'footer.php';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'])) {
    $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $role_ids = $_POST['roles'] ?? [];
    try {
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("sssi", $first_name, $last_name, $email, $user_id);
        $stmt->execute();
        $stmt = $conn->prepare("DELETE FROM user_roles WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        foreach ($role_ids as $role_id) {
            $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $role_id);
            $stmt->execute();
        }
        echo "<div class='alert alert-success'>User updated successfully!</div>";
    } catch (Exception $e) {
        error_log("User update error: " . $e->getMessage());
        echo "<div class='alert alert-danger'>Failed to update user.</div>";
    }
}
$stmt = $conn->prepare("SELECT role_id FROM user_roles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_roles = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'role_id');
?>
    <h1><?php echo $lang['edit_member'] ?? 'Edit Member'; ?></h1>
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
            <label class="form-label"><?php echo $lang['roles'] ?? 'Roles'; ?></label>
            <select name="roles[]" class="form-select" multiple>
                <?php
                $stmt = $conn->prepare("SELECT id, name FROM roles ORDER BY name");
                $stmt->execute();
                $roles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                foreach ($roles as $role) {
                    $selected = in_array($role['id'], $current_roles) ? 'selected' : '';
                    echo "<option value='{$role['id']}' $selected>" . htmlspecialchars($role['name']) . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary"><?php echo $lang['save'] ?? 'Save'; ?></button>
            <a href="manage_members.php" class="btn btn-secondary"><?php echo $lang['back'] ?? 'Back'; ?></a>
        </div>
    </form>
<?php include 'footer.php'; ?>