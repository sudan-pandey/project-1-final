<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('club_head');

$userId = $_SESSION['user_id'];
$club = getOwnClub($pdo, $userId);

if (!$club) {
    header("Location: dashboard.php?error=" . urlencode("No club assignment yet."));
    exit;
}

$clubId = $club['id'];

try {
    // Fetch feedback rating logs left by student attendees ONLY for events organized by this Club!
    $stmt = $pdo->prepare("SELECT f.*, e.title AS event_title, u.full_name AS student_name, u.email AS student_email
                           FROM feedback f
                           JOIN events e ON f.event_id = e.id
                           JOIN users u ON f.user_id = u.id
                           WHERE e.club_id = ?
                           ORDER BY f.submitted_at DESC");
    $stmt->execute([$clubId]);
    $feedbacks = $stmt->fetchAll();

    // Calculate Average rating of all completed events under this club
    $stmtAvg = $pdo->prepare("SELECT AVG(f.rating) AS avg_rating, COUNT(f.id) AS total_count
                             FROM feedback f
                             JOIN events e ON f.event_id = e.id
                             WHERE e.club_id = ?");
    $stmtAvg->execute([$clubId]);
    $ratingStats = $stmtAvg->fetch();

    $avgRating = $ratingStats['avg_rating'] ? round($ratingStats['avg_rating'], 2) : 0;
    $totalFeedbackCount = $ratingStats['total_count'];
} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <?php require_once '../includes/sidebar.php'; ?>

    <main class="main-content">
        <h2>Feedback & Star Ratings</h2>
        <p class="text-muted">Review constructive remarks and rating aggregates left by student participants on finished events.</p>

        <!-- Stats Overview Card -->
        <div class="stats-grid" style="margin-top: 25px;">
            <div class="stat-card">
                <h5>Average Club Event Rating</h5>
                <div class="value">
                    ⭐ <?php echo $avgRating; ?> <span style="font-size: 1rem; color: var(--text-muted);">/ 5</span>
                </div>
            </div>
            <div class="stat-card">
                <h5>Total Feedbacks Submitted</h5>
                <div class="value"><?php echo $totalFeedbackCount; ?></div>
            </div>
        </div>

        <div class="table-responsive" style="margin-top: 25px;">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Student Email</th>
                        <th>Event Title</th>
                        <th>Rating Stars</th>
                        <th>Detailed Comments</th>
                        <th>Submitted At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($feedbacks)): ?>
                        <tr>
                            <td colspan="6" class="text-muted" style="text-align: center; font-style: italic;">No feedback has been submitted by attendees yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($feedbacks as $fb): ?>
                            <tr>
                                <td><strong><?php echo escape($fb['student_name']); ?></strong></td>
                                <td><?php echo escape($fb['student_email']); ?></td>
                                <td><?php echo escape($fb['event_title']); ?></td>
                                <td>
                                    <span class="stars-display">
                                        <?php echo str_repeat('★', $fb['rating']) . str_repeat('☆', 5 - $fb['rating']); ?>
                                    </span>
                                    (<?php echo escape($fb['rating']); ?>/5)
                                </td>
                                <td><?php echo escape($fb['comments']); ?></td>
                                <td><?php echo escape($fb['submitted_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
