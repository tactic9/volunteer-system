<?php
include 'header.php';
if (!has_permission('admin_access')) {
    header('Location: login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'])) {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    try {
        $stmt = $conn->prepare("INSERT INTO roles (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        echo "<div class='alert alert-success'>Role created successfully!</div>";
    } catch (Exception $e) {
        error_log("Role creation error: " . $e->getMessage());
        echo "<div class='alert alert-danger'>Failed to create role.</div>";
    }
}
$stmt = $conn->prepare("SELECT * FROM roles ORDER BY name");
$stmt->execute();
$roles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
    <h1><?php echo $lang['manage_roles'] ?? 'Manage Roles'; ?></h1>
    <form method="post" class="row g-3 mb-4">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <div class="col-md-6">
            <label class="form-label"><?php echo $lang['role_name'] ?? 'Role Name'; ?></label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-md-6 align-self-end">
            <button type="submit" class="btn btn-primary"><?php echo $lang['create_role'] ?? 'Create Role'; ?></button>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo $lang['role_name'] ?? 'Role Name'; ?></th>
                    <th><?php echo $lang['action'] ?? 'Action'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $role): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($role['name']); ?></td>
                        <td>
                            <a href="edit_role.php?id=<?php echo $role['id']; ?>" class="btn btn-warning btn-sm"><?php echo $lang['edit'] ?? 'Edit'; ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php include 'footer.php'; ?>