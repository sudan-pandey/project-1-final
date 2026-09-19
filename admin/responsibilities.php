<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireRole('admin');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = "CSRF verification failed.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($name)) {
                $error = "Responsibility name is required.";
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT id FROM responsibilities WHERE name = ? LIMIT 1");
                    $stmt->execute([$name]);
                    if ($stmt->fetch()) {
                        $error = "A responsibility with this name already exists.";
                    } else {
                        $insert = $pdo->prepare("INSERT INTO responsibilities (name, description) VALUES (?, ?)");
                        $insert->execute([$name, $description]);
                        $success = "Responsibility designation created successfully.";
                    }
                } catch (PDOException $e) {
                    $error = "DB Error: " . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $respId = intval($_POST['id'] ?? 0);
            if ($respId > 0) {
                try {
                    $del = $pdo->prepare("DELETE FROM responsibilities WHERE id = ?");
                    $del->execute([$respId]);
                    $success = "Responsibility designation removed successfully.";
                } catch (PDOException $e) {
                    $error = "Cannot delete responsibility. It is currently linked to tasks or memberships.";
                }
            }
        }
    }
}

// Fetch all responsibilities
try {
    $resps = $pdo->query("SELECT * FROM responsibilities ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    die("Query Error: " . htmlspecialchars($e->getMessage()));
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <?php require_once '../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="flex-header">
            <h2>Organizational Responsibilities / Leads</h2>
            <span class="badge">Designations</span>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo escape($success); ?></div>
        <?php endif; ?>

        <!-- Form to Add Designation -->
        <div class="feature-card" style="margin-bottom: 30px;">
            <h3>Create New Designation</h3>
            <form action="responsibilities.php" method="POST" style="margin-top: 15px; display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end;">
                <?php csrfInput(); ?>
                <input type="hidden" name="action" value="create">

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="name">Designation Title</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Media Lead" required>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="description">Short Description</label>
                    <input type="text" id="description" name="description" class="form-control" placeholder="e.g. Coordinates press releases">
                </div>

                <button type="submit" class="btn btn-primary">Add Designation</button>
            </form>
        </div>

        <!-- Listing of Responsibilities -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resps as $r): ?>
                        <tr>
                            <td><?php echo escape($r['id']); ?></td>
                            <td><strong><?php echo escape($r['name']); ?></strong></td>
                            <td><?php echo escape($r['description']); ?></td>
                            <td>
                                <!-- Prevent deleting core items if required, or just allow admin cascade -->
                                <form action="responsibilities.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this responsibility designation?');">
                                    <?php csrfInput(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
