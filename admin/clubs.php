<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireRole('admin');

$error = '';
$success = $_GET['success'] ?? '';

// Handle Club Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = "CSRF verification failed.";
    } else {
        $clubId = intval($_POST['club_id'] ?? 0);
        if ($clubId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM clubs WHERE id = ?");
                $stmt->execute([$clubId]);
                $success = "Club deleted successfully.";
            } catch (PDOException $e) {
                $error = "Failed to delete club: " . $e->getMessage();
            }
        } else {
            $error = "Invalid club specified.";
        }
    }
}

// Fetch all clubs and their respective heads
try {
    $stmt = $pdo->query("SELECT c.*, u.full_name AS head_name
                           FROM clubs c
                           LEFT JOIN users u ON c.club_head_id = u.id
                           ORDER BY c.name ASC");
    $clubs = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Query Failed: " . htmlspecialchars($e->getMessage()));
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <?php require_once '../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="flex-header">
            <h2>Clubs Management</h2>
            <a href="create-club.php" class="btn btn-primary">+ Create New Club</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo escape($success); ?></div>
        <?php endif; ?>

        <div class="card-grid" style="grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));">
            <?php if (empty($clubs)): ?>
                <p class="text-muted" style="font-style: italic;">No clubs registered inside the system.</p>
            <?php else: ?>
                <?php foreach ($clubs as $club): ?>
                    <div class="card" style="display: flex; flex-direction: row; gap: 20px; align-items: stretch;">
                        <div style="flex-shrink: 0; display: flex; align-items: stretch;">
                            <?php echo renderClubLogo($club['logo'] ?? null, $club['name'], [140, 210]); ?>
                        </div>
                        <div style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <h3 style="margin-top: 0; margin-bottom: 8px; font-size: 1.35rem; font-weight: 700;"><?php echo escape($club['name']); ?></h3>
                                <p style="margin-bottom: 12px; color: var(--text-muted); font-size: 0.95rem; line-height: 1.5;"><?php echo escape($club['description']); ?></p>
                                <div style="margin-bottom: 15px; font-size: 0.9rem;">
                                    <span class="text-muted">Club Head:</span>
                                    <strong>
                                        <?php echo $club['head_name'] ? escape($club['head_name']) : '<span style="color:var(--warning);">Unassigned</span>'; ?>
                                    </strong>
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px; margin-top: auto; flex-wrap: wrap;">
                                <a href="assign-head.php?club_id=<?php echo $club['id']; ?>" class="btn btn-outline btn-sm">Assign Head</a>
                                <a href="edit-club.php?club_id=<?php echo $club['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                <form action="clubs.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this club? All events, memberships, and tasks under this club will be permanently removed.');">
                                    <?php csrfInput(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="club_id" value="<?php echo $club['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
