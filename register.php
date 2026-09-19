<?php
require_once 'config/database.php';
require_once 'includes/csrf.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validate CSRF
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = 'Invalid CSRF verification. Please try again.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // 2. Client and Server Validation
        if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
            $error = 'All fields are required.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // 3. Prevent self-role-escalation
            // Even if a malicious attacker posts a "role" or "status" field, we explicitly hardcode student and active on the backend.
            $role = 'student';
            $status = 'active';

            // Hash the password securely
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            try {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Email address is already registered.';
                } else {
                    // Create account
                    $insertStmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
                    $insertStmt->execute([$fullName, $email, $hashedPassword, $role, $status]);

                    // Redirect with success
                    header("Location: login.php?success=" . urlencode("Account registered successfully! You can now log in."));
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'System Error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - ClubManager</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="landing-body">
    <header class="landing-header">
        <div class="container header-container">
            <a href="index.php" class="logo">
                <span class="logo-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                </span>
                CampusClubs
            </a>
            <nav class="landing-nav">
                <a href="login.php" class="btn btn-outline">Login</a>
            </nav>
        </div>
    </header>

    <main class="auth-wrapper">
        <div class="auth-card">
            <h3>Student Registration</h3>
            <p>Create your active student account to explore and join college clubs.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo escape($error); ?></div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <?php csrfInput(); ?>

                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g. Rahul Sharma" required value="<?php echo isset($_POST['full_name']) ? escape($_POST['full_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="student@example.com" required value="<?php echo isset($_POST['email']) ? escape($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Repeat Password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 10px;">Register Account</button>
            </form>

            <div style="text-align: center; margin-top: 20px;">
                <span style="color: var(--text-muted);">Already have an account?</span>
                <a href="login.php" style="color: var(--primary-color); text-decoration: none; font-weight: bold; margin-left: 5px;">Login here</a>
            </div>
        </div>
    </main>

    <footer class="landing-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> College Club Management System. Built with PHP & MySQL.</p>
        </div>
    </footer>
    <script src="assets/js/script.js"></script>
</body>
</html>
