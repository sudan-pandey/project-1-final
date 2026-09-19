<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('student');

$userId = $_SESSION['user_id'];

try {
    // Check if student belongs to a club
    $membership = getActiveMembership($pdo, $userId);

    // Get simple counts of upcoming events they can attend
    $upcomingEventsCount = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= NOW() AND status='upcoming'")->fetchColumn();

    // Counts of assigned tasks to current user
    $stmtPending = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'pending'");
    $stmtPending->execute([$userId]);
    $pendingTasksCount = $stmtPending->fetchColumn();

    $stmtProgress = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'in_progress'");
    $stmtProgress->execute([$userId]);
    $progressTasksCount = $stmtProgress->fetchColumn();

    $stmtCompleted = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'completed'");
    $stmtCompleted->execute([$userId]);
    $completedTasksCount = $stmtCompleted->fetchColumn();

} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <?php require_once '../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="flex-header">
            <h2>Student Dashboard</h2>
            <span class="badge">Student Hub</span>
        </div>

        <?php displayAlerts(); ?>

        <!-- Active Club Membership banner -->
        <?php if ($membership): ?>
            <div class="feature-card" style="border-left: 5px solid var(--success); margin-bottom: 35px;">
                <h3>🎉 Welcome Member of <?php echo escape($membership['club_name']); ?>!</h3>
                <p>Your current responsibility: <strong><?php echo $membership['responsibility_name'] ? escape($membership['responsibility_name']) : 'General Member'; ?></strong></p>
                <div style="margin-top: 15px;">
                    <a href="my-club.php" class="btn btn-primary btn-sm">View Club Directory</a>
                </div>
            </div>

            <!-- Student Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-icon-wrapper stat-icon-amber">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <h5>Pending Tasks</h5>
                    <div class="value"><?php echo $pendingTasksCount; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon-wrapper stat-icon-cyan">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/></svg>
                    </div>
                    <h5>In Progress Tasks</h5>
                    <div class="value"><?php echo $progressTasksCount; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon-wrapper stat-icon-green">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <h5>Completed Tasks</h5>
                    <div class="value"><?php echo $completedTasksCount; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon-wrapper stat-icon-blue">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <h5>Upcoming Events</h5>
                    <div class="value"><?php echo $upcomingEventsCount; ?></div>
                </div>
            </div>
        <?php else: ?>
            <div class="feature-card" style="border-left: 5px solid var(--warning); margin-bottom: 35px;">
                <h3>🏛️ You are not in any Club yet!</h3>
                <p>Students must belong to a club to receive tasks, view member discussions, and participate in club management. You can only join one active club at a time.</p>
                <div style="margin-top: 15px;">
                    <a href="clubs.php" class="btn btn-warning btn-sm">Browse & Join Clubs</a>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
