<?php
include 'header.php';
if (!has_permission('admin_access')) {
    header('Location: login.php');
    exit;
}
$stmt = $conn->prepare("SELECT u.*, GROUP_CONCAT(r.name) AS roles
                        FROM users u
                        LEFT JOIN user_roles ur ON u.id = ur.user_id
                        LEFT JOIN roles r ON ur.role_id = r.id
                        GROUP BY u.id
                        ORDER BY u.first_name");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
    <h1><?php echo $lang['manage_members'] ?? 'Manage Members'; ?></h1>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo $lang['name'] ?? 'Name'; ?></th>
                    <th><?php echo $lang['email'] ?? 'Email'; ?></th>
                    <th><?php echo $lang['roles'] ?? 'Roles'; ?></th>
                    <th><?php echo $lang['action'] ?? 'Action'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['roles'] ?: 'None'); ?></td>
                        <td>
                            <a href="edit_member.php?id=<?php echo $user['id']; ?>" class="btn btn-warning btn-sm"><?php echo $lang['edit'] ?? 'Edit'; ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php include 'footer.php'; ?>