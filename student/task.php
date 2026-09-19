<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireRole('student');

$userId = $_SESSION['user_id'];
$taskId = intval($_GET['id'] ?? 0);

if ($taskId <= 0) {
    header("Location: tasks.php");
    exit;
}

try {
    $membership = getActiveMembership($pdo, $userId);

    // Fetch the task
    $stmt = $pdo->prepare("SELECT t.*, e.title AS event_title, r.name AS responsibility_name, creator.full_name AS assigner_name
                           FROM tasks t
                           LEFT JOIN events e ON t.event_id = e.id
                           LEFT JOIN responsibilities r ON t.responsibility_id = r.id
                           JOIN users creator ON t.assigned_by = creator.id
                           WHERE t.id = ? LIMIT 1");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();

    if (!$task) {
        header("Location: tasks.php?error=" . urlencode("Task not found."));
        exit;
    }

    // 1. Strict Ownership Security Verification:
    // A student can ONLY view or update tasks assigned directly to them!
    if (intval($task['assigned_to']) !== intval($userId)) {
        header("Location: tasks.php?error=" . urlencode("Security Error: Access denied to task details. IDOR attack blocked."));
        exit;
    }

    $error = '';
    $success = '';

    // Handle POST operations (Updating status, or posting a task update comment)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($csrfToken)) {
            $error = "CSRF verification failed.";
        } else {
            $action = $_POST['action'] ?? '';

            if ($action === 'update_status') {
                $newStatus = $_POST['status'] ?? '';
                if (in_array($newStatus, ['pending', 'in_progress', 'completed'])) {

                    $completedAt = ($newStatus === 'completed') ? date('Y-m-d H:i:s') : null;

                    $updateStmt = $pdo->prepare("UPDATE tasks SET status = ?, completed_at = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $updateStmt->execute([$newStatus, $completedAt, $taskId]);

                    $success = "Task status changed successfully to " . strtoupper(str_replace('_', ' ', $newStatus));
                    // Refresh task details
                    $task['status'] = $newStatus;
                    $task['completed_at'] = $completedAt;
                } else {
                    $error = "Invalid status chosen.";
                }
            } elseif ($action === 'add_comment') {
                $commentText = trim($_POST['comment'] ?? '');
                if (empty($commentText)) {
                    $error = "Comment cannot be empty.";
                } else {
                    $commentStmt = $pdo->prepare("INSERT INTO task_comments (task_id, user_id, comment) VALUES (?, ?, ?)");
                    $commentStmt->execute([$taskId, $userId, $commentText]);
                    $success = "Comment posted successfully.";
                }
            }
        }
    }

    // Fetch comments for this task
    $commentQuery = $pdo->prepare("SELECT tc.*, u.full_name AS commentator, u.role AS commentator_role
                                   FROM task_comments tc
                                   JOIN users u ON tc.user_id = u.id
                                   WHERE tc.task_id = ?
                                   ORDER BY tc.created_at ASC");
    $commentQuery->execute([$taskId]);
    $comments = $commentQuery->fetchAll();

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
            <h2>Task Details</h2>
            <a href="tasks.php" class="btn btn-outline btn-sm">&larr; Back to Task Board</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo escape($success); ?></div>
        <?php endif; ?>

        <!-- Task Info Display -->
        <div class="feature-card" style="margin-bottom: 30px;">
            <div style="float: right;">
                <span class="status-badge <?php echo 'status-' . $task['status']; ?>">
                    <?php echo strtoupper(str_replace('_', ' ', $task['status'])); ?>
                </span>
                <?php if (isTaskOverdue($task['deadline'], $task['status'])): ?>
                    <span class="overdue-badge">OVERDUE</span>
                <?php endif; ?>
            </div>
            <h3><?php echo escape($task['title']); ?></h3>
            <p style="margin-top: 15px; font-size: 1.1rem; color: var(--text-main);"><?php echo escape($task['description']); ?></p>

            <hr style="border-color: var(--border-color); margin: 20px 0;">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 0.95rem;">
                <div><strong>📅 Deadline:</strong> <?php echo escape($task['deadline']); ?></div>
                <div><strong>🎖️ Responsibility Lead:</strong> <?php echo $task['responsibility_name'] ? escape($task['responsibility_name']) : 'General'; ?></div>
                <div><strong>🔗 Linked Event:</strong> <?php echo $task['event_title'] ? escape($task['event_title']) : '<span class="text-muted">None</span>'; ?></div>
                <div><strong>👤 Assigned By:</strong> <?php echo escape($task['assigner_name']); ?></div>
                <div><strong>🚨 Priority Level:</strong> <?php echo escape($task['priority']); ?></div>
                <div>
                    <strong>🕒 Completed At:</strong>
                    <?php echo $task['completed_at'] ? escape($task['completed_at']) : '<span class="text-muted">Not Completed Yet</span>'; ?>
                </div>
            </div>

            <!-- Task Status Form Control -->
            <?php if ($task['status'] !== 'cancelled'): ?>
                <div style="margin-top: 25px; border-top: 1px dashed var(--border-color); padding-top: 20px;">
                    <form action="task.php?id=<?php echo $taskId; ?>" method="POST" style="display: flex; align-items: center; gap: 15px;">
                        <?php csrfInput(); ?>
                        <input type="hidden" name="action" value="update_status">

                        <label for="status" style="font-weight: bold;">Change My Task Progress:</label>
                        <select id="status" name="status" class="form-control" style="width: auto;">
                            <option value="pending" <?php echo $task['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo $task['status'] === 'completed' ? 'selected' : ''; ?>>Completed / Mark as Done</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Update Progress</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- Task Discussion & Comments -->
        <div class="comments-section">
            <h3>💬 Task Updates & Discussion</h3>
            <p class="text-muted">Communicate with your club head and document task milestones here.</p>

            <div style="margin-top: 20px;">
                <?php if (empty($comments)): ?>
                    <p class="text-muted" style="font-style: italic; margin-bottom: 20px;">No updates have been posted for this task.</p>
                <?php else: ?>
                    <?php foreach ($comments as $com): ?>
                        <div class="comment-box">
                            <span class="time"><?php echo escape($com['created_at']); ?></span>
                            <div class="author">
                                <?php echo escape($com['commentator']); ?>
                                <span class="badge" style="margin-bottom: 0; padding: 1px 6px; font-size: 0.75rem;">
                                    <?php echo strtoupper(escape($com['commentator_role'])); ?>
                                </span>
                            </div>
                            <p style="margin-top: 8px; color: var(--text-main);"><?php echo escape($com['comment']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Comment Form -->
                <div class="feature-card" style="background-color: var(--bg-card-hover);">
                    <form action="task.php?id=<?php echo $taskId; ?>" method="POST">
                        <?php csrfInput(); ?>
                        <input type="hidden" name="action" value="add_comment">

                        <div class="form-group">
                            <label for="comment" style="font-weight: bold;">Leave a Progress Update:</label>
                            <textarea id="comment" name="comment" class="form-control" placeholder="Write down task drafts, submit assets links, or document road-blocks..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-secondary btn-sm">Post Comment</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
