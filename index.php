<?php
// Main landing redirect / welcome page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is already logged in, redirect them to their respective dashboards
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Club Management System - Campus Portal</title>
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
            <div class="header-actions">
                <button id="themeToggleBtn" class="theme-toggle-btn" title="Toggle Light/Dark Theme" aria-label="Toggle Light/Dark Theme">
                    <svg id="themeToggleIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="5"></circle>
                        <line x1="12" y1="1" x2="12" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="23"></line>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                        <line x1="1" y1="12" x2="3" y2="12"></line>
                        <line x1="21" y1="12" x2="23" y2="12"></line>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                </button>
                <nav class="landing-nav">
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="landing-main">
        <section class="hero-section">
            <div class="container hero-container">
                <div class="hero-content">
                    <span class="badge">🎓 College Club Portal</span>
                    <h2>Your Campus Clubs, Perfectly Organized</h2>
                    <p>Discover student clubs, explore upcoming workshops, RSVP to events, manage member tasks, and coordinate activities across campus—all in one place.</p>
                    <div class="hero-actions">
                        <a href="register.php" class="btn btn-primary btn-lg">Register Student Account</a>
                        <a href="login.php" class="btn btn-secondary btn-lg">Login to Portal</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="features-section">
            <div class="container">
                <h3 class="section-title">Built For Campus Club Excellence</h3>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🛡️</div>
                        <h4>Role & Joining Safeguards</h4>
                        <p>Strict "one active club per student" enforcement and designated responsibility tracking managed securely on the server side.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📅</div>
                        <h4>Event Directory & RSVPs</h4>
                        <p>Explore campus workshops, competitions, and activities with automated calendar listings and check-ins.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">✅</div>
                        <h4>Task Coordination</h4>
                        <p>Club leaders delegate responsibilities directly to student members, tracking pending, in-progress, and completed items.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📢</div>
                        <h4>Announcements & Feedback</h4>
                        <p>Stay informed with targeted announcements and share 1-5 star event feedback to improve future campus activities.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> College Club Management System. Built with Procedural PHP & MySQL.</p>
        </div>
    </footer>
    <script src="assets/js/script.js"></script>
</body>
</html>
