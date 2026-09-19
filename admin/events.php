<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('admin');

try {
    $stmt = $pdo->query("SELECT e.*, c.name AS club_name
                           FROM events e
                           JOIN clubs c ON e.club_id = c.id
                           ORDER BY e.event_date DESC");
    $events = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <?php require_once '../includes/sidebar.php'; ?>

    <main class="main-content">
        <h2>Global Events Directory</h2>
        <p class="text-muted">Master listing of all club workshops, competitions, hackathons, and cultural activities.</p>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Club</th>
                        <th>Title</th>
                        <th>Date & Time</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($events)): ?>
                        <tr>
                            <td colspan="7" class="text-muted" style="text-align: center; font-style: italic;">No events registered.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?php echo escape($event['id']); ?></td>
                                <td><strong><?php echo escape($event['club_name']); ?></strong></td>
                                <td><?php echo escape($event['title']); ?></td>
                                <td><?php echo escape($event['event_date']); ?></td>
                                <td><?php echo escape($event['location']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo 'status-' . $event['status']; ?>">
                                        <?php echo strtoupper(escape($event['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo escape($event['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
