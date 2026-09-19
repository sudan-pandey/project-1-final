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
    // Fetch registrations for events organized by THIS club head's club!
    $stmt = $pdo->prepare("SELECT r.*, e.title AS event_title, u.full_name AS student_name, u.email AS student_email, e.event_date
                           FROM registrations r
                           JOIN events e ON r.event_id = e.id
                           JOIN users u ON r.user_id = u.id
                           WHERE e.club_id = ?
                           ORDER BY e.event_date DESC, r.registered_at DESC");
    $stmt->execute([$clubId]);
    $registrations = $stmt->fetchAll();
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
                <h2>Event Registrations</h2>
                <p class="subtitle">A list of all students signed up for your club's workshops and programs.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Student Email</th>
                        <th>Event Title</th>
                        <th>Event Date</th>
                        <th>Registered Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registrations)): ?>
                        <tr>
                            <td colspan="5" class="text-muted" style="text-align: center; font-style: italic;">No student signups recorded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($registrations as $reg): ?>
                            <tr>
                                <td><strong><?php echo escape($reg['student_name']); ?></strong></td>
                                <td><?php echo escape($reg['student_email']); ?></td>
                                <td><?php echo escape($reg['event_title']); ?></td>
                                <td><?php echo escape($reg['event_date']); ?></td>
                                <td><?php echo escape($reg['registered_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
