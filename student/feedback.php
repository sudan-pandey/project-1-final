<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireRole('student');

$userId = $_SESSION['user_id'];
$eventId = intval($_GET['event_id'] ?? 0);

if ($eventId <= 0) {
    header("Location: events.php");
    exit;
}

try {
    $membership = getActiveMembership($pdo, $userId);

    // Verify student is registered for this event, and that event status is completed.
    $stmtCheck = $pdo->prepare("SELECT e.*,
                                 (SELECT id FROM registrations r WHERE r.event_id = e.id AND r.user_id = ? LIMIT 1) AS registered_id,
                                 (SELECT id FROM feedback f WHERE f.event_id = e.id AND f.user_id = ? LIMIT 1) AS existing_feedback_id
                               FROM events e
                               WHERE e.id = ? LIMIT 1");
    $stmtCheck->execute([$userId, $userId, $eventId]);
    $event = $stmtCheck->fetch();

    if (!$event) {
        header("Location: events.php?error=" . urlencode("Event not found."));
        exit;
    }

    if ($event['status'] !== 'completed') {
        header("Location: events.php?error=" . urlencode("You can only submit feedback for completed events."));
        exit;
    }

    if (!$event['registered_id']) {
        header("Location: events.php?error=" . urlencode("Only registered attendees can submit feedback."));
        exit;
    }

    if ($event['existing_feedback_id']) {
        header("Location: events.php?error=" . urlencode("You have already submitted feedback for this event."));
        exit;
    }

    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($csrfToken)) {
            $error = "CSRF verification failed.";
        } else {
            $rating = intval($_POST['rating'] ?? 0);
            $comments = trim($_POST['comments'] ?? '');

            if ($rating < 1 || $rating > 5) {
                $error = "Please provide an event rating between 1 and 5 stars.";
            } else {
                // Insert Feedback
                $fbStmt = $pdo->prepare("INSERT INTO feedback (event_id, user_id, rating, comments) VALUES (?, ?, ?, ?)");
                $fbStmt->execute([$eventId, $userId, $rating, $comments]);

                header("Location: events.php?success=" . urlencode("Thank you! Your feedback has been recorded."));
                exit;
            }
        }
    }
} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <?php require_once '../includes/sidebar.php'; ?>

    <main class="main-content" style="max-width: 600px;">
        <h2>Event Feedback & Reviews</h2>
        <p class="text-muted">Tell us about your experience in <strong><?php echo escape($event['title']); ?></strong>.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>

        <form action="feedback.php?event_id=<?php echo $eventId; ?>" method="POST" style="margin-top: 25px;">
            <?php csrfInput(); ?>

            <div class="form-group">
                <label style="font-weight: bold; display: block; margin-bottom: 10px;">Select Event Rating (1-5 Stars)</label>
                <!-- Modern CSS rating widget using flex row reverse -->
                <div class="star-rating">
                    <input type="radio" id="star5" name="rating" value="5" <?php echo (isset($_POST['rating']) && intval($_POST['rating']) === 5) ? 'checked' : ''; ?>><label for="star5" title="Excellent">★</label>
                    <input type="radio" id="star4" name="rating" value="4" <?php echo (isset($_POST['rating']) && intval($_POST['rating']) === 4) ? 'checked' : ''; ?>><label for="star4" title="Good">★</label>
                    <input type="radio" id="star3" name="rating" value="3" <?php echo (isset($_POST['rating']) && intval($_POST['rating']) === 3) ? 'checked' : ''; ?>><label for="star3" title="Average">★</label>
                    <input type="radio" id="star2" name="rating" value="2" <?php echo (isset($_POST['rating']) && intval($_POST['rating']) === 2) ? 'checked' : ''; ?>><label for="star2" title="Poor">★</label>
                    <input type="radio" id="star1" name="rating" value="1" <?php echo (isset($_POST['rating']) && intval($_POST['rating']) === 1) ? 'checked' : ''; ?>><label for="star1" title="Very Poor">★</label>
                </div>
            </div>

            <div class="form-group">
                <label for="comments" style="font-weight: bold;">Review Comments</label>
                <textarea id="comments" name="comments" class="form-control" rows="5" placeholder="Share your suggestions, key take-aways, or thoughts on the coordination..." required><?php echo isset($_POST['comments']) ? escape($_POST['comments']) : ''; ?></textarea>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Submit Feedback</button>
                <a href="events.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
