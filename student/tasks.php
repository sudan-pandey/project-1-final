<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('student');

$userId = $_SESSION['user_id'];

try {
    $membership = getActiveMembership($pdo, $userId);

    if (!$membership) {
        header("Location: dashboard.php?error=" . urlencode("Please join a club to access task management."));
        exit;
    }

    // Fetch tasks assigned specifically to this logged-in student user
    $stmt = $pdo->prepare("SELECT t.*, e.title AS event_title, r.name AS responsibility_name
                           FROM tasks t
                           LEFT JOIN events e ON t.event_id = e.id
                           LEFT JOIN responsibilities r ON t.responsibility_id = r.id
                           WHERE t.assigned_to = ?
                           ORDER BY t.created_at DESC");
    $stmt->execute([$userId]);
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
        <h2>My Task Board</h2>
        <p class="text-muted">Stay updated on your responsibilities, change active status, and communicate progress.</p>

        <?php displayAlerts(); ?>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Task Name</th>
                        <th>Linked Event</th>
                        <th>Assignment Lead</th>
                        <th>Priority</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr>
                            <td colspan="7" class="text-muted" style="text-align: center; font-style: italic;">No tasks have been assigned to you.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><strong><?php echo escape($task['title']); ?></strong></td>
                                <td><?php echo $task['event_title'] ? escape($task['event_title']) : '<span class="text-muted">None</span>'; ?></td>
                                <td><?php echo $task['responsibility_name'] ? escape($task['responsibility_name']) : '<span class="text-muted">General Contribution</span>'; ?></td>
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
                                    <div style="display: flex; gap: 10px;">
                                        <a href="task.php?id=<?php echo $task['id']; ?>" class="btn btn-outline btn-sm" aria-label="View details and comments for task: <?php echo escape($task['title']); ?>">Details & Comments</a>
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
