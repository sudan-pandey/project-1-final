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

        // 1. Double check that the assigned member actually belongs to this Club Head's own club!
        // This is a vital security business rule check.
        $chkStmt = $pdo->prepare("SELECT id, responsibility_id FROM memberships WHERE user_id = ? AND club_id = ? AND status = 'active' LIMIT 1");
        $chkStmt->execute([$assignedTo, $clubId]);
        $membershipRecord = $chkStmt->fetch();

        if (empty($title) || $assignedTo <= 0 || empty($deadline)) {
            $error = "Task Title, Assigned Member, and Deadline are mandatory fields.";
        } elseif (!$membershipRecord) {
            $error = "Security Error: The selected student must be an active member of your club.";
        } else {
            try {
                // Determine target responsibility linked to this student
                $respId = $membershipRecord['responsibility_id'];

                $ins = $pdo->prepare("INSERT INTO tasks (club_id, event_id, assigned_to, assigned_by, responsibility_id, title, description, priority, status, deadline)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
                $ins->execute([$clubId, $eventId, $assignedTo, $userId, $respId, $title, $description, $priority, $deadline]);

                header("Location: tasks.php?success=" . urlencode("Task successfully created and delegated!"));
                exit;
            } catch (PDOException $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch active members of own club to populate assign drop-down
try {
    $stmtMembers = $pdo->prepare("SELECT m.user_id, u.full_name, r.name AS responsibility_name
                                  FROM memberships m
                                  JOIN users u ON m.user_id = u.id
                                  LEFT JOIN responsibilities r ON m.responsibility_id = r.id
                                  WHERE m.club_id = ? AND m.status = 'active'
                                  ORDER BY u.full_name ASC");
    $stmtMembers->execute([$clubId]);
    $members = $stmtMembers->fetchAll();

    // Fetch own club events to link task
    $stmtEvents = $pdo->prepare("SELECT id, title FROM events WHERE club_id = ? ORDER BY event_date DESC");
    $stmtEvents->execute([$clubId]);
    $events = $stmtEvents->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <?php require_once '../includes/sidebar.php'; ?>

    <main class="main-content" style="max-width: 650px;">
        <h2>Create & Assign Task</h2>
        <p class="text-muted">Delegate a task to an active club member. Only members of your own club are available.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>

        <form action="create-task.php" method="POST" style="margin-top: 20px;">
            <?php csrfInput(); ?>

            <div class="form-group">
                <label for="title">Task Title</label>
                <input type="text" id="title" name="title" class="form-control" placeholder="e.g. Design Event Promotional Banner" required value="<?php echo isset($_POST['title']) ? escape($_POST['title']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="description">Detailed Description</label>
                <textarea id="description" name="description" class="form-control" rows="4" placeholder="Mention size dimensions, specific details, or requirements..."><?php echo isset($_POST['description']) ? escape($_POST['description']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="assigned_to">Assign To Club Member</label>
                <select id="assigned_to" name="assigned_to" class="form-control" required>
                    <option value="">-- Choose Member --</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?php echo $m['user_id']; ?>" <?php echo (isset($_POST['assigned_to']) && intval($_POST['assigned_to']) === intval($m['user_id'])) ? 'selected' : ''; ?>>
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
                        <option value="<?php echo $ev['id']; ?>" <?php echo (isset($_POST['event_id']) && intval($_POST['event_id']) === intval($ev['id'])) ? 'selected' : ''; ?>>
                            <?php echo escape($ev['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="priority">Task Priority</label>
                <select id="priority" name="priority" class="form-control">
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                    <option value="Urgent">Urgent</option>
                </select>
            </div>

            <div class="form-group">
                <label for="deadline">Deadline Date</label>
                <input type="date" id="deadline" name="deadline" class="form-control" required value="<?php echo isset($_POST['deadline']) ? escape($_POST['deadline']) : ''; ?>">
            </div>

            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn btn-primary">Delegate Task</button>
                <a href="tasks.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
