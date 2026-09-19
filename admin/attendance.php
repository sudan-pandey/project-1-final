<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('admin');

try {
    $stmt = $pdo->query("SELECT a.*, e.title AS event_title, u.full_name AS student_name, c.name AS club_name
                           FROM attendance a
                           JOIN events e ON a.event_id = e.id
                           JOIN users u ON a.user_id = u.id
                           JOIN clubs c ON e.club_id = c.id
                           ORDER BY a.marked_at DESC");
    $attendance = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <?php require_once '../includes/sidebar.php'; ?>

    <main class="main-content">
        <h2>Global Attendance Logs</h2>
        <p class="text-muted">Consolidated database records of checked-in students for major events.</p>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Event</th>
                        <th>Host Club</th>
                        <th>Status</th>
                        <th>Marked At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendance)): ?>
                        <tr>
                            <td colspan="6" class="text-muted" style="text-align: center; font-style: italic;">No attendance logs generated yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attendance as $att): ?>
                            <tr>
                                <td><?php echo escape($att['id']); ?></td>
                                <td><strong><?php echo escape($att['student_name']); ?></strong></td>
                                <td><?php echo escape($att['event_title']); ?></td>
                                <td><?php echo escape($att['club_name']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $att['status'] === 'present' ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo strtoupper(escape($att['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo escape($att['marked_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
