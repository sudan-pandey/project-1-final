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
    $stmt = $pdo->prepare("SELECT * FROM clubs WHERE id = ? LIMIT 1");
    $stmt->execute([$clubId]);
    $club = $stmt->fetch();

    if (!$club) {
        header("Location: clubs.php?error=" . urlencode("Club not found."));
        exit;
    }

    // Fetch active candidates for Club Head
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
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $headIdInput = $_POST['club_head_id'] ?? '';
        $newHeadId = $headIdInput === '' ? null : intval($headIdInput);

        if (empty($name)) {
            $error = "Club name cannot be blank.";
        } else {
            try {
                $pdo->beginTransaction();

                // Check name uniqueness excluding current
                $uStmt = $pdo->prepare("SELECT id FROM clubs WHERE name = ? AND id != ? LIMIT 1");
                $uStmt->execute([$name, $clubId]);
                if ($uStmt->fetch()) {
                    throw new Exception("A club with this name already exists.");
                }

                // If Club Head selected, strictly verify they do not head another club
                if ($newHeadId !== null) {
                    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'club_head' AND status = 'active' LIMIT 1");
                    $checkStmt->execute([$newHeadId]);
                    if (!$checkStmt->fetch()) {
                        throw new Exception("Selected candidate must be an active user with the role 'club_head'.");
                    }

                    $existsStmt = $pdo->prepare("SELECT name FROM clubs WHERE club_head_id = ? AND id != ? LIMIT 1");
                    $existsStmt->execute([$newHeadId, $clubId]);
                    $clashingClub = $existsStmt->fetch();
                    if ($clashingClub) {
                        throw new Exception("This user is already heading the '" . $clashingClub['name'] . "' club. A user cannot be the Club Head of 2 clubs at a time.");
                    }
                }

                $updateStmt = $pdo->prepare("UPDATE clubs SET name = ?, description = ?, club_head_id = ? WHERE id = ?");
                $updateStmt->execute([$name, $description, $newHeadId, $clubId]);

                $pdo->commit();
                header("Location: clubs.php?success=" . urlencode("Club updated successfully!"));
                exit;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = $e->getMessage();
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
        <h2>Edit Club Information</h2>
        <p class="text-muted">Modify name, description, or assigned Club Head of the selected club.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>

        <form action="edit-club.php?club_id=<?php echo $clubId; ?>" method="POST" style="margin-top: 20px;">
            <?php csrfInput(); ?>

            <div class="form-group">
                <label for="name">Club Name</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Club Name" required value="<?php echo escape($club['name']); ?>">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4" placeholder="Club Description..."><?php echo escape($club['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="club_head_id">Assigned Club Head</label>
                <select id="club_head_id" name="club_head_id" class="form-control">
                    <option value="">-- No Head Assigned --</option>
                    <?php foreach ($candidates as $cand): ?>
                        <option value="<?php echo $cand['id']; ?>" <?php echo intval($club['club_head_id']) === intval($cand['id']) ? 'selected' : ''; ?>>
                            <?php echo escape($cand['full_name']); ?> (<?php echo escape($cand['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted" style="display: block; margin-top: 5px;">
                    A user cannot head more than 1 club at a time.
                </small>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="clubs.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
