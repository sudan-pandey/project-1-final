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
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND club_id = ? LIMIT 1");
    $stmt->execute([$taskId, $clubId]);
    $task = $stmt->fetch();

    if (!$task) {
        header("Location: tasks.php?error=" . urlencode("Task not found or unauthorized."));
        exit;
    }

    // Fetch active members of own club to reassign
    $stmtMembers = $pdo->prepare("SELECT m.user_id, u.full_name, r.name AS responsibility_name
                                  FROM memberships m
                                  JOIN users u ON m.user_id = u.id
                                  LEFT JOIN responsibilities r ON m.responsibility_id = r.id
                                  WHERE m.club_id = ? AND m.status = 'active'
                                  ORDER BY u.full_name ASC");
    $stmtMembers->execute([$clubId]);
    $members = $stmtMembers->fetchAll();

    // Fetch own club events
    $stmtEvents = $pdo->prepare("SELECT id, title FROM events WHERE club_id = ? ORDER BY event_date DESC");
    $stmtEvents->execute([$clubId]);
    $events = $stmtEvents->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = "CSRF verification failed.";
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $assignedTo = intval($_POST['assigned_to'] ?? 0);
        $eventIdInput = $_POST['event_id'] ?? '';
        $eventId = $eventIdInput === '' ? null : intval($eventIdInput);
        $priority = $_POST['priority'] ?? 'Medium';
        $deadline = $_POST['deadline'] ?? '';
        $status = $_POST['status'] ?? 'pending';

        // 2. Strict reassignment verification: Validate target user is an active member of own club
        $chkStmt = $pdo->prepare("SELECT id, responsibility_id FROM memberships WHERE user_id = ? AND club_id = ? AND status = 'active' LIMIT 1");
        $chkStmt->execute([$assignedTo, $clubId]);
        $membershipRecord = $chkStmt->fetch();

        if (empty($title) || $assignedTo <= 0 || empty($deadline)) {
            $error = "Task Title, Assigned Member, and Deadline are mandatory fields.";
        } elseif (!$membershipRecord) {
            $error = "Security Error: Selected member does not belong to your club.";
        } else {
            try {
                $respId = $membershipRecord['responsibility_id'];

                $completedAt = ($status === 'completed') ? date('Y-m-d H:i:s') : null;

                $update = $pdo->prepare("UPDATE tasks
                                         SET event_id = ?, assigned_to = ?, responsibility_id = ?, title = ?, description = ?, priority = ?, status = ?, deadline = ?, completed_at = ?
                                         WHERE id = ? AND club_id = ?");
                $update->execute([$eventId, $assignedTo, $respId, $title, $description, $priority, $status, $deadline, $completedAt, $taskId, $clubId]);

                header("Location: tasks.php?success=" . urlencode("Task details updated successfully!"));
                exit;
            } catch (PDOException $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <?php require_once '../includes/sidebar.php'; ?>

    <main class="main-content" style="max-width: 650px;">
        <h2>Edit Task Details</h2>
        <p class="text-muted">Modify status, reassign ownership, or configure deadlines for this task.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>

        <form action="edit-task.php?id=<?php echo $taskId; ?>" method="POST" style="margin-top: 20px;">
            <?php csrfInput(); ?>

            <div class="form-group">
                <label for="title">Task Title</label>
                <input type="text" id="title" name="title" class="form-control" required value="<?php echo escape($task['title']); ?>">
            </div>

            <div class="form-group">
                <label for="description">Detailed Description</label>
                <textarea id="description" name="description" class="form-control" rows="4"><?php echo escape($task['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="assigned_to">Assign To Club Member</label>
                <select id="assigned_to" name="assigned_to" class="form-control" required>
                    <?php foreach ($members as $m): ?>
                        <option value="<?php echo $m['user_id']; ?>" <?php echo intval($task['assigned_to']) === intval($m['user_id']) ? 'selected' : ''; ?>>
                            <?php echo escape($m['full_name']); ?> (<?php echo $m['responsibility_name'] ? escape($m['responsibility_name']) : 'General Member'; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="event_id">Related Event (Optional)</label>
                <select id="event_id" name="event_id" class="form-control">
                    <option value="">-- No Related Event --</option>
                    <?php foreach ($events as $ev): ?>
                        <option value="<?php echo $ev['id']; ?>" <?php echo intval($task['event_id']) === intval($ev['id']) ? 'selected' : ''; ?>>
                            <?php echo escape($ev['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="priority">Task Priority</label>
                <select id="priority" name="priority" class="form-control">
                    <option value="Low" <?php echo $task['priority'] === 'Low' ? 'selected' : ''; ?>>Low</option>
                    <option value="Medium" <?php echo $task['priority'] === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="High" <?php echo $task['priority'] === 'High' ? 'selected' : ''; ?>>High</option>
                    <option value="Urgent" <?php echo $task['priority'] === 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                </select>
            </div>

            <div class="form-group">
                <label for="status">Task Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="pending" <?php echo $task['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $task['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $task['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>

            <div class="form-group">
                <label for="deadline">Deadline Date</label>
                <input type="date" id="deadline" name="deadline" class="form-control" required value="<?php echo escape($task['deadline']); ?>">
            </div>

            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="tasks.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
