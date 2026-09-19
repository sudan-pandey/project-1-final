<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/functions.php';

requireRole('admin');

$error = '';
$success = '';

// Handle POST operations safely with CSRF validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = 'CSRF verification failed.';
    } else {
        $action = $_POST['action'] ?? '';
        $targetUserId = intval($_POST['user_id'] ?? 0);

        if ($targetUserId > 0) {
            // Prevent changing own administrative role or status to lock self out
            if ($targetUserId === intval($_SESSION['user_id'])) {
                $error = "You cannot modify your own administrator role or status.";
            } else {
                if ($action === 'change_role') {
                    $newRole = $_POST['role'] ?? '';
                    if (in_array($newRole, ['student', 'club_head', 'admin'])) {
                        // If elevating or demoting, check constraints. E.g. demoting club_head means removing club assignments.
                        $pdo->beginTransaction();
                        try {
                            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                            $stmt->execute([$newRole, $targetUserId]);

                            if ($newRole !== 'club_head') {
                                // Clear them from club heads if any
                                $clearStmt = $pdo->prepare("UPDATE clubs SET club_head_id = NULL WHERE club_head_id = ?");
                                $clearStmt->execute([$targetUserId]);
                            }
                            $pdo->commit();
                            $success = "User role successfully changed to " . strtoupper($newRole) . ".";
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            $error = "Failed to update role: " . $e->getMessage();
                        }
                    } else {
                        $error = "Invalid role value selected.";
                    }
                } elseif ($action === 'toggle_status') {
                    $newStatus = $_POST['status'] ?? '';
                    if (in_array($newStatus, ['active', 'inactive'])) {
                        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                        $stmt->execute([$newStatus, $targetUserId]);
                        $success = "User status successfully changed to " . strtoupper($newStatus) . ".";
                    } else {
                        $error = "Invalid status value selected.";
                    }
                }
            }
        } else {
            $error = "Invalid user specified.";
        }
    }
}

// Fetch all registered users
try {
    $users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    die("Query Error: " . htmlspecialchars($e->getMessage()));
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <?php require_once '../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="flex-header">
            <h2>User Account & Roles Management</h2>
            <span class="badge">Security Controls</span>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo escape($success); ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Registered Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo escape($u['id']); ?></td>
                            <td><strong><?php echo escape($u['full_name']); ?></strong></td>
                            <td><?php echo escape($u['email']); ?></td>
                            <td>
                                <span class="status-badge" style="background-color: #334155; color: white;">
                                    <?php echo strtoupper(escape($u['role'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $u['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo strtoupper(escape($u['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo escape($u['created_at']); ?></td>
                            <td>
                                <!-- Prevent Admin modifying their own roles directly on the UI -->
                                <?php if (intval($u['id']) !== intval($_SESSION['user_id'])): ?>
                                    <div style="display: flex; gap: 10px;">
                                        <!-- Role modification form -->
                                        <form action="users.php" method="POST" style="display: inline-flex; align-items: center; gap: 5px;">
                                            <?php csrfInput(); ?>
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <select name="role" class="form-control" style="width: auto; padding: 4px 8px; font-size: 0.85rem;" onchange="this.form.submit()">
                                                <option value="student" <?php echo $u['role'] === 'student' ? 'selected' : ''; ?>>student</option>
                                                <option value="club_head" <?php echo $u['role'] === 'club_head' ? 'selected' : ''; ?>>club_head</option>
                                                <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>admin</option>
                                            </select>
                                        </form>

                                        <!-- Status modification toggle -->
                                        <form action="users.php" method="POST" style="display: inline;">
                                            <?php csrfInput(); ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $u['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                            <button type="submit" class="btn btn-outline btn-sm">
                                                Toggle Status
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted" style="font-style: italic;">Self (Locked)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
