<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireRole('admin');

$error = '';
$success = '';

// Handle Membership Cancellation / Removal by admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'revoke') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = "CSRF verification failed.";
    } else {
        $membershipId = intval($_POST['membership_id'] ?? 0);
        if ($membershipId > 0) {
            try {
                // Remove membership entirely or set status to inactive
                $stmt = $pdo->prepare("DELETE FROM memberships WHERE id = ?");
                $stmt->execute([$membershipId]);
                $success = "Membership registration successfully removed.";
            } catch (PDOException $e) {
                $error = "Failed to revoke membership: " . $e->getMessage();
            }
        } else {
            $error = "Invalid membership spec.";
        }
    }
}

// Fetch all active memberships
try {
    $stmt = $pdo->query("SELECT m.*, u.full_name, u.email, c.name AS club_name, r.name AS responsibility_name
                           FROM memberships m
                           JOIN users u ON m.user_id = u.id
                           JOIN clubs c ON m.club_id = c.id
                           LEFT JOIN responsibilities r ON m.responsibility_id = r.id
                           ORDER BY m.joined_at DESC");
    $memberships = $stmt->fetchAll();
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
            <h2>Club Membership Logs</h2>
            <span class="badge">Active & Historic</span>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo escape($success); ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Club Name</th>
                        <th>Responsibility</th>
                        <th>Status</th>
                        <th>Joined At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($memberships)): ?>
                        <tr>
                            <td colspan="8" class="text-muted" style="text-align: center; font-style: italic;">No memberships logged yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($memberships as $m): ?>
                            <tr>
                                <td><?php echo escape($m['id']); ?></td>
                                <td><strong><?php echo escape($m['full_name']); ?></strong></td>
                                <td><?php echo escape($m['email']); ?></td>
                                <td><?php echo escape($m['club_name']); ?></td>
                                <td>
                                    <?php echo $m['responsibility_name'] ? escape($m['responsibility_name']) : '<span class="text-muted">General Member</span>'; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $m['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo escape($m['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo escape($m['joined_at']); ?></td>
                                <td>
                                    <form action="memberships.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to revoke this student membership?');">
                                        <?php csrfInput(); ?>
                                        <input type="hidden" name="action" value="revoke">
                                        <input type="hidden" name="membership_id" value="<?php echo $m['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Revoke</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
