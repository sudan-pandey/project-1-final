<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('club_head');

$userId = $_SESSION['user_id'];

try {
    // Identify own club
    $club = getOwnClub($pdo, $userId);

    $totalMembers = 0;
    $upcomingEvents = 0;
    $pendingTasks = 0;
    $inProgressTasks = 0;
    $completedTasks = 0;
    $overdueTasks = 0;
    $recentFeedback = [];

    if ($club) {
        $clubId = $club['id'];

        // 1. Total active members in their own club
        $stmtMemb = $pdo->prepare("SELECT COUNT(*) FROM memberships WHERE club_id = ? AND status = 'active'");
        $stmtMemb->execute([$clubId]);
        $totalMembers = $stmtMemb->fetchColumn();

        // 2. Upcoming events count in their own club
        $stmtEv = $pdo->prepare("SELECT COUNT(*) FROM events WHERE club_id = ? AND status = 'upcoming'");
        $stmtEv->execute([$clubId]);
        $upcomingEvents = $stmtEv->fetchColumn();

        // 3. Task counts inside own club
        $stmtTasksCount = $pdo->prepare("SELECT status, deadline FROM tasks WHERE club_id = ?");
        $stmtTasksCount->execute([$clubId]);
        $clubTasksList = $stmtTasksCount->fetchAll();

        foreach ($clubTasksList as $t) {
            if ($t['status'] === 'pending') {
                $pendingTasks++;
            } elseif ($t['status'] === 'in_progress') {
                $inProgressTasks++;
            } elseif ($t['status'] === 'completed') {
                $completedTasks++;
            }
            if (isTaskOverdue($t['deadline'], $t['status'])) {
                $overdueTasks++;
            }
        }

        // 4. Recent feedback on own club's events
        $stmtFb = $pdo->prepare("SELECT f.*, e.title AS event_title, u.full_name AS student_name
                                 FROM feedback f
                                 JOIN events e ON f.event_id = e.id
                                 JOIN users u ON f.user_id = u.id
                                 WHERE e.club_id = ?
                                 ORDER BY f.submitted_at DESC LIMIT 5");
        $stmtFb->execute([$clubId]);
        $recentFeedback = $stmtFb->fetchAll();
    }
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
            <h2>Club Leader Workspace</h2>
            <span class="badge">Club Head Role</span>
        </div>

        <?php displayAlerts(); ?>

        <?php if (!$club): ?>
            <div class="feature-card" style="border-left: 5px solid var(--danger);">
                <h3>🏛️ No Club Assignment</h3>
                <p>You have successfully registered or been promoted to a Club Head! However, the College Admin has not assigned you to head a specific club yet. Please reach out to the Administrator to configure your leadership duties.</p>
            </div>
        <?php else: ?>
            <div class="feature-card" style="border-left: 5px solid var(--primary-color); margin-bottom: 30px;">
                <h3>🏛️ Heading: <?php echo escape($club['name']); ?></h3>
                <p><?php echo escape($club['description']); ?></p>
            </div>

            <!-- Dashboard Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-icon-wrapper stat-icon-purple">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <h5>Total Members</h5>
                    <div class="value"><?php echo $totalMembers; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon-wrapper stat-icon-blue">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <h5>Upcoming Events</h5>
                    <div class="value"><?php echo $upcomingEvents; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon-wrapper stat-icon-amber">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <h5>Pending Tasks</h5>
                    <div class="value"><?php echo $pendingTasks; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon-wrapper stat-icon-cyan">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/></svg>
                    </div>
                    <h5>In Progress</h5>
                    <div class="value"><?php echo $inProgressTasks; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon-wrapper stat-icon-green">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <h5>Completed</h5>
                    <div class="value"><?php echo $completedTasks; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon-wrapper stat-icon-red">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                    <h5>Overdue Tasks</h5>
                    <div class="value" style="color: var(--danger);"><?php echo $overdueTasks; ?></div>
                </div>
            </div>

            <!-- Recent Feedback Updates -->
            <div class="card-grid" style="grid-template-columns: 1fr; margin-top: 25px;">
                <div class="feature-card">
                    <h3>Recent Event Feedbacks</h3>
                    <p class="text-muted" style="margin-bottom: 15px;">Live reviews sent by students after heading workshops or training sessions.</p>

                    <?php if (empty($recentFeedback)): ?>
                        <p class="text-muted" style="font-style: italic;">No feedback reviews logged yet for your events.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Event Title</th>
                                        <th>Rating Stars</th>
                                        <th>Comments</th>
                                        <th>Submitted At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentFeedback as $fb): ?>
                                        <tr>
                                            <td><strong><?php echo escape($fb['student_name']); ?></strong></td>
                                            <td><?php echo escape($fb['event_title']); ?></td>
                                            <td>
                                                <span class="stars-display">
                                                    <?php echo str_repeat('★', $fb['rating']) . str_repeat('☆', 5 - $fb['rating']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo escape($fb['comments']); ?></td>
                                            <td><?php echo escape($fb['submitted_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
