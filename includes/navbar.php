<?php
// Nav bar that dynamically renders links based on user role
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = $_SESSION['user_role'] ?? '';
$fullName = $_SESSION['user_name'] ?? 'User';

$roleLabel = 'User';
if ($role === 'club_head') {
    $roleLabel = 'Club Head';
} elseif ($role === 'admin') {
    $roleLabel = 'Admin';
} elseif ($role === 'student') {
    $roleLabel = 'Student';
}

// Determine photo URL if uploaded
$photoUrl = $_SESSION['profile_picture'] ?? $_SESSION['user_photo'] ?? '';

// Fallback to club head logo if assigned and photoUrl empty
if (empty($photoUrl) && $role === 'club_head' && isset($pdo)) {
    try {
        $stmtClubLogo = $pdo->prepare("SELECT logo FROM clubs WHERE club_head_id = ? LIMIT 1");
        $stmtClubLogo->execute([$_SESSION['user_id'] ?? 0]);
        $cLogo = $stmtClubLogo->fetchColumn();
        if ($cLogo && file_exists(__DIR__ . '/../uploads/clubs/' . $cLogo)) {
            $photoUrl = '../uploads/clubs/' . $cLogo;
        }
    } catch (Exception $e) {
        // Fallback silently if DB unavailable
    }
}
?>
<header class="app-header">
    <div class="container header-container">
        <a href="../index.php" class="logo">
            <span class="logo-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            </span>
            CampusClubs
        </a>

        <div class="header-actions">
            <!-- Theme Toggle Button -->
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

        <?php if (!empty($_SESSION['user_id'])): ?>
            <div class="header-profile-block">
                <div class="user-text-info">
                    <span class="profile-name"><?php echo htmlspecialchars($fullName); ?></span>
                    <span class="profile-role"><?php echo htmlspecialchars($roleLabel); ?></span>
                </div>
                <div class="profile-avatar-wrapper">
                    <?php if (!empty($photoUrl)): ?>
                        <img id="headerProfileImg" src="<?php echo htmlspecialchars($photoUrl); ?>" alt="<?php echo htmlspecialchars($fullName); ?> Profile" class="header-profile-img">
                    <?php else: ?>
                        <img id="headerProfileImg" src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='40' height='40' viewBox='0 0 24 24' fill='%2394a3b8'><path d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z'/></svg>" alt="<?php echo htmlspecialchars($fullName); ?> Profile" class="header-profile-img">
                    <?php endif; ?>
                </div>
                <a href="../logout.php" class="header-logout-btn" title="Logout" aria-label="Logout">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                </a>
            </div>
            <script>
                // Client-side clubHeadPhotoURL variable initialization
                window.clubHeadPhotoURL = "<?php echo !empty($photoUrl) ? addslashes($photoUrl) : ''; ?>";
                if (window.clubHeadPhotoURL) {
                    var headerImg = document.getElementById('headerProfileImg');
                    if (headerImg && !headerImg.src.endsWith(window.clubHeadPhotoURL)) {
                        headerImg.src = window.clubHeadPhotoURL;
                    }
                }
            </script>
        <?php else: ?>
            <nav class="app-nav">
                <a href="../login.php" class="btn btn-outline btn-sm">Login</a>
                <a href="../register.php" class="btn btn-primary btn-sm">Register</a>
            </nav>
        <?php endif; ?>
        </div>
    </div>
</header>
