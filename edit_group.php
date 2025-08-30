<?php
include 'header.php';
if (!has_permission('admin_access')) {
    header('Location: login.php');
    exit;
}
$group_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$group_id) {
    echo "<div class='alert alert-danger'>Invalid group ID.</div>";
    include 'footer.php';
    exit;
}
$stmt = $conn->prepare("SELECT * FROM `groups` WHERE id = ?");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$group = $stmt->get_result()->fetch_assoc();
if (!$group) {
    echo "<div class='alert alert-danger'>Group not found.</div>";
    include 'footer.php';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'])) {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    try {
        $stmt = $conn->prepare("UPDATE `groups` SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $group_id);
        $stmt->execute();
        echo "<div class='alert alert-success'>Group updated successfully!</div>";
    } catch (Exception $e) {
        error_log("Group update error: " . $e->getMessage());
        echo "<div class='alert alert-danger'>Failed to update group.</div>";
    }
}
?>
    <h1><?php echo $lang['edit_group'] ?? 'Edit Group'; ?></h1>
    <form method="post" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <div class="col-md-6">
            <label class="form-label"><?php echo $lang['group_name'] ?? 'Group Name'; ?></label>
            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($group['name']); ?>" required>
        </div>
        <div class="col-md-6 align-self-end">
            <button type="submit" class="btn btn-primary"><?php echo $lang['save'] ?? 'Save'; ?></button>
            <a href="manage_groups.php" class="btn btn-secondary"><?php echo $lang['back'] ?? 'Back'; ?></a>
        </div>
    </form>
<?php include 'footer.php'; ?>