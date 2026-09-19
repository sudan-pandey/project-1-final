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
        $eventDate = $_POST['event_date'] ?? '';
        $location = trim($_POST['location'] ?? '');
        $status = $_POST['status'] ?? 'upcoming';

        if (empty($title) || empty($eventDate) || empty($location)) {
            $error = "Title, Event Date, and Location are required fields.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO events (club_id, title, description, event_date, location, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$clubId, $title, $description, $eventDate, $location, $status]);

                header("Location: events.php?success=" . urlencode("Event created successfully!"));
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
        <h2>Create Club Event</h2>
        <p class="text-muted">Broadcast a new event, competition, training, or workshop for your club.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>

        <form action="create-event.php" method="POST" style="margin-top: 20px;">
            <?php csrfInput(); ?>

            <div class="form-group">
                <label for="title">Event Title</label>
                <input type="text" id="title" name="title" class="form-control" placeholder="e.g. Annual CodeSprint 2026" required value="<?php echo isset($_POST['title']) ? escape($_POST['title']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="description">Event Description</label>
                <textarea id="description" name="description" class="form-control" rows="5" placeholder="Highlight key modules, eligibility rules, agenda..."><?php echo isset($_POST['description']) ? escape($_POST['description']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="event_date">Date & Time</label>
                <input type="datetime-local" id="event_date" name="event_date" class="form-control" required value="<?php echo isset($_POST['event_date']) ? escape($_POST['event_date']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="location">Venue / Location</label>
                <input type="text" id="location" name="location" class="form-control" placeholder="e.g. IT Block Lab 3" required value="<?php echo isset($_POST['location']) ? escape($_POST['location']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="status">Initial Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="upcoming" selected>Upcoming</option>
                    <option value="completed">Completed</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn btn-primary">Publish Event</button>
                <a href="events.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
