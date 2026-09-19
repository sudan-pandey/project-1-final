<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireRole('club_head');

$userId = $_SESSION['user_id'];
$club = getOwnClub($pdo, $userId);

if (!$club) {
    header("Location: dashboard.php?error=" . urlencode("No club assignment yet."));
    exit;
}

$clubId = $club['id'];
$error = '';
$success = $_GET['success'] ?? '';

// Handle cancel/delete/mark completed task actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = "CSRF verification failed.";
    } else {
        $taskId = intval($_POST['task_id'] ?? 0);

        // 1. Strict task ownership verification: Validate task is assigned within THIS club!
        $stmtCheck = $pdo->prepare("SELECT id FROM tasks WHERE id = ? AND club_id = ? LIMIT 1");
        $stmtCheck->execute([$taskId, $clubId]);

        if (!$stmtCheck->fetch()) {
            $error = "Security Error: Unauthorized task access.";
        } else {
            $action = $_POST['action'];
            if ($action === 'cancel') {
                $stmtCancel = $pdo->prepare("UPDATE tasks SET status = 'cancelled' WHERE id = ?");
                $stmtCancel->execute([$taskId]);
                $success = "Task cancelled successfully.";
            } elseif ($action === 'complete') {
                $stmtComp = $pdo->prepare("UPDATE tasks SET status = 'completed', completed_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmtComp->execute([$taskId]);
                $success = "Task marked completed successfully.";
            } elseif ($action === 'delete') {
                $stmtDel = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
                $stmtDel->execute([$taskId]);
                $success = "Task deleted successfully from records.";
            }
        }
    }
}

// Fetch all tasks delegated in this club
try {
    $stmt = $pdo->prepare("SELECT t.*, u.full_name AS assigned_to_name, e.title AS event_title, r.name AS responsibility_name
                           FROM tasks t
                           JOIN users u ON t.assigned_to = u.id
                           LEFT JOIN events e ON t.event_id = e.id
                           LEFT JOIN responsibilities r ON t.responsibility_id = r.id
                           WHERE t.club_id = ?
                           ORDER BY t.created_at DESC");
    $stmt->execute([$clubId]);
    $tasks = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <?php require_once '../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="flex-header">
            <h2>Task Assignments Coordination</h2>
            <a href="create-task.php" class="btn btn-primary">+ Create & Assign Task</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo escape($success); ?></div>
        <?php endif; ?>

        <div class="table-responsive" style="margin-top: 25px;">
            <table>
                <thead>
                    <tr>
                        <th>Task Title</th>
                        <th>Assigned To</th>
                        <th>Designated Position</th>
                        <th>Linked Event</th>
                        <th>Priority</th>
                        <th>Deadline Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr>
                            <td colspan="8" class="text-muted" style="text-align: center; font-style: italic;">No tasks have been delegated in your club yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><strong><?php echo escape($task['title']); ?></strong></td>
                                <td><?php echo escape($task['assigned_to_name']); ?></td>
                                <td><?php echo $task['responsibility_name'] ? escape($task['responsibility_name']) : '<span class="text-muted">General member</span>'; ?></td>
                                <td><?php echo $task['event_title'] ? escape($task['event_title']) : '<span class="text-muted">None</span>'; ?></td>
                                <td>
                                    <span class="status-badge" style="background-color: rgba(255, 255, 255, 0.1); color: var(--text-main);">
                                        <?php echo escape($task['priority']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo escape($task['deadline']); ?>
                                    <?php if (isTaskOverdue($task['deadline'], $task['status'])): ?>
                                        <span class="overdue-badge">OVERDUE</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo 'status-' . $task['status']; ?>">
                                        <?php echo strtoupper(escape($task['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="task-details.php?id=<?php echo $task['id']; ?>" class="btn btn-outline btn-sm" style="padding: 3px 6px; font-size: 0.8rem;">Details</a>
                                        <a href="edit-task.php?id=<?php echo $task['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 3px 6px; font-size: 0.8rem;">Edit</a>

                                        <?php if ($task['status'] !== 'completed' && $task['status'] !== 'cancelled'): ?>
                                            <form action="tasks.php" method="POST" style="display: inline;" onsubmit="return confirm('Mark this task as completed?');">
                                                <?php csrfInput(); ?>
                                                <input type="hidden" name="action" value="complete">
                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm" style="padding: 3px 6px; font-size: 0.8rem; background-color: var(--success);">✓ Done</button>
                                            </form>

                                            <form action="tasks.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this task?');">
                                                <?php csrfInput(); ?>
                                                <input type="hidden" name="action" value="cancel">
                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                <button type="submit" class="btn btn-warning btn-sm" style="padding: 3px 6px; font-size: 0.8rem;">Cancel</button>
                                            </form>
                                        <?php endif; ?>

                                        <form action="tasks.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this task record permanently?');">
                                            <?php csrfInput(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" style="padding: 3px 6px; font-size: 0.8rem;">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
