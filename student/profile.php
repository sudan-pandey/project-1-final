<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireRole('student');

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

try {
    $membership = getActiveMembership($pdo, $userId);

    // Fetch user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = "CSRF verification failed.";
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        if (empty($fullName) || empty($email)) {
            $error = "Full Name and Email are mandatory fields.";
        } else {
            try {
                // Verify email uniqueness excluding current
                $uStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
                $uStmt->execute([$email, $userId]);
                if ($uStmt->fetch()) {
                    $error = "This email is already linked to another account.";
                } else {
                    $pdo->beginTransaction();

                    // Update simple fields first
                    $upStmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                    $upStmt->execute([$fullName, $email, $userId]);
                    $_SESSION['user_name'] = $fullName;
                    $_SESSION['user_email'] = $email;

                    // Update password if requested
                    if (!empty($currentPassword) && !empty($newPassword)) {
                        if (password_verify($currentPassword, $user['password'])) {
                            $newHashed = password_hash($newPassword, PASSWORD_DEFAULT);
                            $pwStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $pwStmt->execute([$newHashed, $userId]);
                            $success = "Profile details and password updated successfully!";
                        } else {
                            throw new Exception("The current password you provided is incorrect.");
                        }
                    } else {
                        $success = "Profile details updated successfully!";
                    }

                    $pdo->commit();
                    // Reload user details
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                }
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
        <h2>Profile Settings</h2>
        <p class="text-muted">Manage your student credentials and credentials security settings.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo escape($success); ?></div>
        <?php endif; ?>

        <form action="profile.php" method="POST" style="margin-top: 25px;">
            <?php csrfInput(); ?>

            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo escape($user['full_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo escape($user['email']); ?>" required>
            </div>

            <fieldset style="border: 1px dashed var(--border-color); padding: 15px; border-radius: var(--radius); margin-bottom: 25px;">
                <legend style="padding: 0 10px; font-weight: bold; color: var(--text-muted);">Change Password (Optional)</legend>

                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Required to set new password">
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Min. 6 characters">
                </div>
            </fieldset>

            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
