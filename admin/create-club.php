<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireRole('admin');

$error = '';

try {
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

                // Check uniqueness on name
                $stmt = $pdo->prepare("SELECT id FROM clubs WHERE name = ? LIMIT 1");
                $stmt->execute([$name]);
                if ($stmt->fetch()) {
                    throw new Exception("A club with this name already exists.");
                }

                // If Club Head selected, strictly verify they do not head another club
                if ($newHeadId !== null) {
                    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'club_head' AND status = 'active' LIMIT 1");
                    $checkStmt->execute([$newHeadId]);
                    if (!$checkStmt->fetch()) {
                        throw new Exception("Selected candidate must be an active user with the role 'club_head'.");
                    }

                    $existsStmt = $pdo->prepare("SELECT name FROM clubs WHERE club_head_id = ? LIMIT 1");
                    $existsStmt->execute([$newHeadId]);
                    $clashingClub = $existsStmt->fetch();
                    if ($clashingClub) {
                        throw new Exception("This user is already heading the '" . $clashingClub['name'] . "' club. A user cannot be the Club Head of 2 clubs at a time.");
                    }
                }

                $insertStmt = $pdo->prepare("INSERT INTO clubs (name, description, club_head_id) VALUES (?, ?, ?)");
                $insertStmt->execute([$name, $description, $newHeadId]);

                $pdo->commit();
                header("Location: clubs.php?success=" . urlencode("Club created successfully!"));
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
        <h2>Create New Club</h2>
        <p class="text-muted">Register a new student organization inside the college hub.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>

        <form action="create-club.php" method="POST" style="margin-top: 20px;">
            <?php csrfInput(); ?>

            <div class="form-group">
                <label for="name">Club Name</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Robotics & Automation Club" required value="<?php echo isset($_POST['name']) ? escape($_POST['name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4" placeholder="Provide a short synopsis of the club goals and activities..."><?php echo isset($_POST['description']) ? escape($_POST['description']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="club_head_id">Assign Club Head (Optional)</label>
                <select id="club_head_id" name="club_head_id" class="form-control">
                    <option value="">-- No Head Assigned --</option>
                    <?php foreach ($candidates as $cand): ?>
                        <option value="<?php echo $cand['id']; ?>" <?php echo (isset($_POST['club_head_id']) && intval($_POST['club_head_id']) === intval($cand['id'])) ? 'selected' : ''; ?>>
                            <?php echo escape($cand['full_name']); ?> (<?php echo escape($cand['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted" style="display: block; margin-top: 5px;">
                    A user cannot head more than 1 club at a time.
                </small>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Create Club</button>
                <a href="clubs.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
