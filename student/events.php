<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('student');

$userId = $_SESSION['user_id'];

try {
    $membership = getActiveMembership($pdo, $userId);

    // Fetch all events with status 'upcoming' or 'completed'
    // Also grab registration status for current user
    $stmt = $pdo->prepare("SELECT e.*, c.name AS club_name, c.logo AS club_logo,
                             (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS reg_count,
                             (SELECT id FROM registrations r WHERE r.event_id = e.id AND r.user_id = ? LIMIT 1) AS user_reg_id,
                             (SELECT status FROM attendance a WHERE a.event_id = e.id AND a.user_id = ? LIMIT 1) AS user_att_status,
                             (SELECT id FROM feedback f WHERE f.event_id = e.id AND f.user_id = ? LIMIT 1) AS user_feedback_id
                           FROM events e
                           JOIN clubs c ON e.club_id = c.id
                           ORDER BY e.event_date DESC");
    $stmt->execute([$userId, $userId, $userId]);
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
        <h2>Events Directory</h2>
        <p class="text-muted">Explore college-wide workshops, training sessions, hackathons, and sports activities.</p>

        <?php displayAlerts(); ?>

        <div class="card-grid">
            <?php if (empty($events)): ?>
                <p class="text-muted" style="font-style: italic;">No events have been posted yet.</p>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <div class="card">
                        <div>
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                <?php echo renderClubLogo($event['club_logo'] ?? null, $event['club_name'], 36); ?>
                                <span class="badge" style="margin-bottom: 0;"><?php echo escape($event['club_name']); ?></span>
                            </div>
                            <h3 style="word-break: break-word; overflow-wrap: anywhere;"><?php echo escape($event['title']); ?></h3>
                            <div class="expandable-text">
                                <p class="text-content"><?php echo escape($event['description']); ?></p>
                            </div>

                            <div style="font-size: 0.9rem; margin-bottom: 15px; color: var(--text-muted);">
                                <div>📍 <strong>Location:</strong> <?php echo escape($event['location']); ?></div>
                                <div>📅 <strong>Date:</strong> <?php echo escape($event['event_date']); ?></div>
                                <div>👥 <strong>Registrants:</strong> <?php echo escape($event['reg_count']); ?></div>
                            </div>
                        </div>

                        <div style="margin-top: auto; display: flex; flex-direction: column; gap: 8px;">
                            <?php if ($event['status'] === 'cancelled'): ?>
                                <span class="status-badge status-cancelled" style="text-align: center; padding: 6px;">Event Cancelled</span>
                            <?php elseif ($event['status'] === 'completed'): ?>
                                <span class="status-badge status-completed" style="text-align: center; padding: 6px; margin-bottom: 5px;">Event Completed</span>

                                <!-- Feedback rule: Student can leave feedback if they registered (or registered & attended) and haven't submitted already -->
                                <?php if ($event['user_reg_id'] && !$event['user_feedback_id']): ?>
                                    <a href="feedback.php?event_id=<?php echo $event['id']; ?>" class="btn btn-warning btn-sm" style="text-align: center;">Leave Feedback</a>
                                <?php elseif ($event['user_feedback_id']): ?>
                                    <span class="text-muted" style="text-align: center; font-size: 0.85rem; font-style: italic;">Feedback Submitted ✓</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Upcoming active event actions -->
                                <?php if ($event['user_reg_id']): ?>
                                    <span class="status-badge status-active" style="text-align: center; padding: 6px; margin-bottom: 5px;">✓ Registered</span>
                                    <form action="register-event.php" method="POST">
                                        <?php csrfInput(); ?>
                                        <input type="hidden" name="action" value="unregister">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" style="width: 100%;">Unregister</button>
                                    </form>
                                <?php else: ?>
                                    <form action="register-event.php" method="POST">
                                        <?php csrfInput(); ?>
                                        <input type="hidden" name="action" value="register">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                        <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">Register For Event</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
