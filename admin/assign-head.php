<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireRole('admin');

$error = '';
$clubId = intval($_GET['club_id'] ?? 0);

if ($clubId <= 0) {
    header("Location: clubs.php");
    exit;
}

try {
    // Fetch club info
    $stmt = $pdo->prepare("SELECT * FROM clubs WHERE id = ? LIMIT 1");
    $stmt->execute([$clubId]);
    $club = $stmt->fetch();

    if (!$club) {
        header("Location: clubs.php?error=" . urlencode("Club not found."));
        exit;
    }

    // Fetch active candidates for Club Head (all active users with role = club_head)
    $candidateStmt = $pdo->query("SELECT id, full_name, email FROM users WHERE role = 'club_head' AND status = 'active' ORDER BY full_name ASC");
    $candidates = $candidateStmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = "CSRF verification failed.";
    } else {
        // Selection of target Club Head ID
        $headIdInput = $_POST['club_head_id'] ?? '';
        $newHeadId = $headIdInput === '' ? null : intval($headIdInput);

        try {
            $pdo->beginTransaction();

            // If a head is being assigned, verify the user possesses role = club_head
            if ($newHeadId !== null) {
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'club_head' AND status = 'active' LIMIT 1");
                $checkStmt->execute([$newHeadId]);
                if (!$checkStmt->fetch()) {
                    throw new Exception("Selected candidate must be an active user with the role 'club_head'.");
                }

                // A Club Head can head at most one club to maintain simple structured alignment
                $existsStmt = $pdo->prepare("SELECT id, name FROM clubs WHERE club_head_id = ? AND id != ? LIMIT 1");
                $existsStmt->execute([$newHeadId, $clubId]);
                $clashingClub = $existsStmt->fetch();
                if ($clashingClub) {
                    throw new Exception("This user is already heading the '" . $clashingClub['name'] . "' club.");
                }
            }

            // Assign Club Head
            $updateStmt = $pdo->prepare("UPDATE clubs SET club_head_id = ? WHERE id = ?");
            $updateStmt->execute([$newHeadId, $clubId]);

            $pdo->commit();
            header("Location: clubs.php?success=" . urlencode("Club Head assigned successfully!"));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <?php require_once '../includes/sidebar.php'; ?>

    <main class="main-content" style="max-width: 600px;">
        <h2>Assign Club Head</h2>
        <p class="text-muted">Set or modify the designated leader of the <strong><?php echo escape($club['name']); ?></strong>.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>

        <form action="assign-head.php?club_id=<?php echo $clubId; ?>" method="POST" style="margin-top: 20px;">
            <?php csrfInput(); ?>

            <div class="form-group">
                <label for="club_head_id">Select Club Head Candidate</label>
                <select id="club_head_id" name="club_head_id" class="form-control">
                    <option value="">-- No Head Assigned --</option>
                    <?php foreach ($candidates as $cand): ?>
                        <option value="<?php echo $cand['id']; ?>" <?php echo intval($club['club_head_id']) === intval($cand['id']) ? 'selected' : ''; ?>>
                            <?php echo escape($cand['full_name']); ?> (<?php echo escape($cand['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted" style="display: block; margin-top: 5px;">
                    Only active users with the role <strong>'club_head'</strong> are shown.
                </small>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Assign Head</button>
                <a href="clubs.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
