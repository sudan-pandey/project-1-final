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

// Handle cancel/delete event actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = "CSRF verification failed.";
    } else {
        $eventId = intval($_POST['event_id'] ?? 0);

        // 1. Strict ownership check: Verify event belongs to THIS club head's club!
        $stmtCheck = $pdo->prepare("SELECT id FROM events WHERE id = ? AND club_id = ? LIMIT 1");
        $stmtCheck->execute([$eventId, $clubId]);

        if (!$stmtCheck->fetch()) {
            $error = "Security Error: You are not authorized to modify this event.";
        } else {
            $action = $_POST['action'];
            if ($action === 'cancel') {
                $stmtCancel = $pdo->prepare("UPDATE events SET status = 'cancelled' WHERE id = ?");
                $stmtCancel->execute([$eventId]);
                $success = "Event cancelled successfully.";
            } elseif ($action === 'delete') {
                $stmtDel = $pdo->prepare("DELETE FROM events WHERE id = ?");
                $stmtDel->execute([$eventId]);
                $success = "Event deleted successfully.";
            }
        }
    }
}

// Fetch all events organized by this club
try {
    $stmt = $pdo->prepare("SELECT e.*,
                             (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS reg_count
                           FROM events e
                           WHERE e.club_id = ?
                           ORDER BY e.event_date DESC");
    $stmt->execute([$clubId]);
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
        <div class="flex-header">
            <div>
                <h2>Manage Club Events</h2>
                <p class="subtitle">View, create, and manage upcoming and past club events.</p>
            </div>
            <a href="create-event.php" class="btn btn-primary">+ Create New Event</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo escape($success); ?></div>
        <?php endif; ?>

        <?php if (empty($events)): ?>
            <div class="empty-state-card">
                <div class="empty-state-icon-wrapper">
                    <svg class="empty-state-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                        <line x1="10" y1="14" x2="14" y2="14"></line>
                        <line x1="12" y1="12" x2="12" y2="16"></line>
                    </svg>
                </div>
                <h3 class="empty-state-headline">No events scheduled</h3>
                <p class="empty-state-description">
                    No events have been posted for your club yet. Get started by organizing your first club workshop, meeting, or activity.
                </p>
                <a href="create-event.php" class="btn btn-primary">+ Create New Event</a>
            </div>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($events as $event): ?>
                    <div class="card">
                        <div>
                            <span class="badge" style="margin-bottom: 10px;"><?php echo strtoupper(escape($event['status'])); ?></span>
                            <h3 style="word-break: break-word; overflow-wrap: anywhere;"><?php echo escape($event['title']); ?></h3>
                            <div class="expandable-text">
                                <p class="text-content"><?php echo escape($event['description']); ?></p>
                            </div>

                            <div style="font-size: 0.9rem; margin-bottom: 20px; color: var(--text-muted);">
                                <div>📍 <strong>Location:</strong> <?php echo escape($event['location']); ?></div>
                                <div>📅 <strong>Date:</strong> <?php echo escape($event['event_date']); ?></div>
                                <div>👥 <strong>Total Registrations:</strong> <?php echo escape($event['reg_count']); ?></div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: auto;">
                            <a href="edit-event.php?id=<?php echo $event['id']; ?>" class="btn btn-outline btn-sm">Edit</a>

                            <?php if ($event['status'] === 'upcoming'): ?>
                                <form action="events.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this event?');">
                                    <?php csrfInput(); ?>
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                    <button type="submit" class="btn btn-warning btn-sm">Cancel Event</button>
                                </form>
                            <?php endif; ?>

                            <form action="events.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this event? This will also remove any related registrations and attendance logs.');">
                                <?php csrfInput(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
