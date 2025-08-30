<?php
include 'header.php';
if (!has_permission('admin_access')) {
    header('Location: login.php');
    exit;
}
$role_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$role_id) {
    echo "<div class='alert alert-danger'>Invalid role ID.</div>";
    include 'footer.php';
    exit;
}
$stmt = $conn->prepare("SELECT * FROM roles WHERE id = ?");
$stmt->bind_param("i", $role_id);
$stmt->execute();
$role = $stmt->get_result()->fetch_assoc();
if (!$role) {
    echo "<div class='alert alert-danger'>Role not found.</div>";
    include 'footer.php';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'])) {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    try {
        $stmt = $conn->prepare("UPDATE roles SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $role_id);
        $stmt->execute();
        echo "<div class='alert alert-success'>Role updated successfully!</div>";
    } catch (Exception $e) {
        error_log("Role update error: " . $e->getMessage());
        echo "<div class='alert alert-danger'>Failed to update role.</div>";
    }
}
?>
    <h1><?php echo $lang['edit_role'] ?? 'Edit Role'; ?></h1>
    <form method="post" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <div class="col-md-6">
            <label class="form-label"><?php echo $lang['role_name'] ?? 'Role Name'; ?></label>
            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($role['name']); ?>" required>
        </div>
        <div class="col-md-6 align-self-end">
            <button type="submit" class="btn btn-primary"><?php echo $lang['save'] ?? 'Save'; ?></button>
            <a href="manage_roles.php" class="btn btn-secondary"><?php echo $lang['back'] ?? 'Back'; ?></a>
        </div>
    </form>
<?php include 'footer.php'; ?>