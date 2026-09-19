<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('admin');

try {
    $stmt = $pdo->query("SELECT t.*, c.name AS club_name, e.title AS event_title, u.full_name AS assigned_to_name, creator.full_name AS assigned_by_name
                           FROM tasks t
                           JOIN clubs c ON t.club_id = c.id
                           LEFT JOIN events e ON t.event_id = e.id
                           JOIN users u ON t.assigned_to = u.id
                           JOIN users creator ON t.assigned_by = creator.id
                           ORDER BY t.created_at DESC");
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
        <h2>Global Task Assignments Directory</h2>
        <p class="text-muted">High-level view of all active/historical responsibilities and member-assigned tasks.</p>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Club</th>
                        <th>Task Title</th>
                        <th>Assigned To</th>
                        <th>Assigned By</th>
                        <th>Linked Event</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Deadline</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr>
                            <td colspan="9" class="text-muted" style="text-align: center; font-style: italic;">No tasks created.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><?php echo escape($task['id']); ?></td>
                                <td><strong><?php echo escape($task['club_name']); ?></strong></td>
                                <td><?php echo escape($task['title']); ?></td>
                                <td><?php echo escape($task['assigned_to_name']); ?></td>
                                <td><?php echo escape($task['assigned_by_name']); ?></td>
                                <td><?php echo $task['event_title'] ? escape($task['event_title']) : '<span class="text-muted">None</span>'; ?></td>
                                <td>
                                    <span class="status-badge" style="background-color: rgba(255, 255, 255, 0.1); color: var(--text-main);">
                                        <?php echo escape($task['priority']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo 'status-' . $task['status']; ?>">
                                        <?php echo strtoupper(escape($task['status'])); ?>
                                    </span>
                                    <?php if (isTaskOverdue($task['deadline'], $task['status'])): ?>
                                        <span class="overdue-badge">OVERDUE</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo escape($task['deadline']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
