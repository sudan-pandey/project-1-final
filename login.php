<?php
require_once 'config/database.php';
require_once 'includes/csrf.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect logged in user immediately
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? '';
    if ($role === 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    } elseif ($role === 'club_head') {
        header("Location: club-head/dashboard.php");
        exit;
    } else {
        header("Location: student/dashboard.php");
        exit;
    }
}

$error = '';
$success = $_GET['success'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = 'Invalid CSRF verification. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter both your email and password.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Check if the user is suspended or inactive
                    if ($user['status'] !== 'active') {
                        $error = 'Your account has been deactivated. Please contact the Admin.';
                    } else {
                        // Prevent Session Fixation attacks: regenerate session ID on login
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['full_name'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_email'] = $user['email'];

                        // Redirect to respective panels
                        if ($user['role'] === 'admin') {
                            header("Location: admin/dashboard.php");
                        } elseif ($user['role'] === 'club_head') {
                            header("Location: club-head/dashboard.php");
                        } else {
                            header("Location: student/dashboard.php");
                        }
                        exit;
                    }
                } else {
                    $error = 'Invalid email or password combination.';
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
    <title>Login - ClubManager</title>
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
                <a href="register.php" class="btn btn-outline">Register</a>
            </nav>
        </div>
    </header>

    <main class="auth-wrapper">
        <div class="auth-card">
            <h3>Account Login</h3>
            <p>Access your student, club head, or admin profile dashboard.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo escape($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo escape($success); ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
                <div class="alert alert-danger">Access Denied: You do not have permissions to access that page.</div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <?php csrfInput(); ?>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="student@example.com" required value="<?php echo isset($_POST['email']) ? escape($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Your account password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 10px;">Login</button>
            </form>

            <div style="text-align: center; margin-top: 20px;">
                <span style="color: var(--text-muted);">New here?</span>
                <a href="register.php" style="color: var(--primary-color); text-decoration: none; font-weight: bold; margin-left: 5px;">Register here</a>
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
