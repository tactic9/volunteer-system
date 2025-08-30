<?php
include 'header.php';
if (!has_permission('admin_access')) {
    header('Location: login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'])) {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    try {
        $stmt = $conn->prepare("INSERT INTO `groups` (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        echo "<div class='alert alert-success'>Group created successfully!</div>";
    } catch (Exception $e) {
        error_log("Group creation error: " . $e->getMessage());
        echo "<div class='alert alert-danger'>Failed to create group.</div>";
    }
}
$stmt = $conn->prepare("SELECT * FROM `groups` ORDER BY name");
$stmt->execute();
$groups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
    <h1><?php echo $lang['manage_groups'] ?? 'Manage Groups'; ?></h1>
    <form method="post" class="row g-3 mb-4">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <div class="col-md-6">
            <label class="form-label"><?php echo $lang['group_name'] ?? 'Group Name'; ?></label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-md-6 align-self-end">
            <button type="submit" class="btn btn-primary"><?php echo $lang['create_group'] ?? 'Create Group'; ?></button>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo $lang['group_name'] ?? 'Group Name'; ?></th>
                    <th><?php echo $lang['action'] ?? 'Action'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groups as $group): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($group['name']); ?></td>
                        <td>
                            <a href="edit_group.php?id=<?php echo $group['id']; ?>" class="btn btn-warning btn-sm"><?php echo $lang['edit'] ?? 'Edit'; ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php include 'footer.php'; ?>