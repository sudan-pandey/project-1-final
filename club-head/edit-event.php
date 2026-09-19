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
$eventId = intval($_GET['id'] ?? 0);

if ($eventId <= 0) {
    header("Location: events.php");
    exit;
}

try {
    // 1. Strict ownership check: Verify event belongs to THIS club head's club!
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND club_id = ? LIMIT 1");
    $stmt->execute([$eventId, $clubId]);
    $event = $stmt->fetch();

    if (!$event) {
        header("Location: events.php?error=" . urlencode("Event not found or unauthorized."));
        exit;
    }
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
        $eventDate = $_POST['event_date'] ?? '';
        $location = trim($_POST['location'] ?? '');
        $status = $_POST['status'] ?? 'upcoming';

        if (empty($title) || empty($eventDate) || empty($location)) {
            $error = "Title, Event Date, and Location are required fields.";
        } else {
            try {
                $updateStmt = $pdo->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, location = ?, status = ? WHERE id = ? AND club_id = ?");
                $updateStmt->execute([$title, $description, $eventDate, $location, $status, $eventId, $clubId]);

                header("Location: events.php?success=" . urlencode("Event updated successfully!"));
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

    <main class="main-content" style="max-width: 600px;">
        <h2>Edit Club Event</h2>
        <p class="text-muted">Modify the date, location, title, or completion status of your event.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>

        <form action="edit-event.php?id=<?php echo $eventId; ?>" method="POST" style="margin-top: 20px;">
            <?php csrfInput(); ?>

            <div class="form-group">
                <label for="title">Event Title</label>
                <input type="text" id="title" name="title" class="form-control" required value="<?php echo escape($event['title']); ?>">
            </div>

            <div class="form-group">
                <label for="description">Event Description</label>
                <textarea id="description" name="description" class="form-control" rows="5"><?php echo escape($event['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="event_date">Date & Time</label>
                <!-- Reformat DATETIME to match datetime-local input requirements -->
                <?php $dateTimeLocal = date('Y-m-d\TH:i', strtotime($event['event_date'])); ?>
                <input type="datetime-local" id="event_date" name="event_date" class="form-control" required value="<?php echo $dateTimeLocal; ?>">
            </div>

            <div class="form-group">
                <label for="location">Venue / Location</label>
                <input type="text" id="location" name="location" class="form-control" required value="<?php echo escape($event['location']); ?>">
            </div>

            <div class="form-group">
                <label for="status">Event Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="upcoming" <?php echo $event['status'] === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                    <option value="completed" <?php echo $event['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $event['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="events.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
