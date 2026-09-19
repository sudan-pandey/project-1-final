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
$taskId = intval($_GET['id'] ?? 0);

if ($taskId <= 0) {
    header("Location: tasks.php");
    exit;
}

try {
    // 1. Strict task ownership verification: Validate task belongs to THIS club head's club!
    $stmt = $pdo->prepare("SELECT t.*, u.full_name AS assigned_to_name, e.title AS event_title, r.name AS responsibility_name, creator.full_name AS assigner_name
                           FROM tasks t
                           JOIN users u ON t.assigned_to = u.id
                           JOIN users creator ON t.assigned_by = creator.id
                           LEFT JOIN events e ON t.event_id = e.id
                           LEFT JOIN responsibilities r ON t.responsibility_id = r.id
                           WHERE t.id = ? AND t.club_id = ? LIMIT 1");
    $stmt->execute([$taskId, $clubId]);
    $task = $stmt->fetch();

    if (!$task) {
        header("Location: tasks.php?error=" . urlencode("Task not found or unauthorized."));
        exit;
    }

    $error = '';
    $success = '';

    // Handle Comment posting by Club Head
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($csrfToken)) {
            $error = "CSRF verification failed.";
        } else {
            $commentText = trim($_POST['comment'] ?? '');
            if (empty($commentText)) {
                $error = "Comment message cannot be blank.";
            } else {
                $ins = $pdo->prepare("INSERT INTO task_comments (task_id, user_id, comment) VALUES (?, ?, ?)");
                $ins->execute([$taskId, $userId, $commentText]);
                $success = "Progress comment posted successfully.";
            }
        }
    }

    // Fetch comments
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
            <h2>Task Allocation Log</h2>
            <a href="tasks.php" class="btn btn-outline btn-sm">&larr; Back to Tasks</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo escape($success); ?></div>
        <?php endif; ?>

        <!-- Task Core Info Card -->
        <div class="feature-card" style="margin-bottom: 30px;">
            <div style="float: right;">
                <span class="status-badge <?php echo 'status-' . $task['status']; ?>">
                    <?php echo strtoupper(escape($task['status'])); ?>
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
                <div><strong>👤 Assigned To:</strong> <?php echo escape($task['assigned_to_name']); ?></div>
                <div><strong>🎖️ Responsibility:</strong> <?php echo $task['responsibility_name'] ? escape($task['responsibility_name']) : 'General member'; ?></div>
                <div><strong>🔗 Related Event:</strong> <?php echo $task['event_title'] ? escape($task['event_title']) : '<span class="text-muted">None</span>'; ?></div>
                <div><strong>🚨 Priority Level:</strong> <?php echo escape($task['priority']); ?></div>
                <div><strong>🕒 Completed Date:</strong> <?php echo $task['completed_at'] ? escape($task['completed_at']) : '<span class="text-muted">Not Completed</span>'; ?></div>
            </div>
        </div>

        <!-- Comments list -->
        <div class="comments-section">
            <h3>💬 Member Progress Updates</h3>
            <p class="text-muted">View progress remarks, submitted assets, or leave advice for the member.</p>

            <div style="margin-top: 20px;">
                <?php if (empty($comments)): ?>
                    <p class="text-muted" style="font-style: italic; margin-bottom: 20px;">No comments have been logged yet.</p>
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

                <div class="feature-card" style="background-color: var(--bg-card-hover);">
                    <form action="task-details.php?id=<?php echo $taskId; ?>" method="POST">
                        <?php csrfInput(); ?>
                        <div class="form-group">
                            <label for="comment" style="font-weight: bold;">Post Coordination Reply:</label>
                            <textarea id="comment" name="comment" class="form-control" placeholder="Write feedback, instructions, or approve progress here..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-secondary btn-sm">Submit Reply</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
