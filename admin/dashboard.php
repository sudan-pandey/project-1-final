<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Enforce strict administrator authorization
requireRole('admin');

try {
    // Collect broad summary metrics
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalClubs = $pdo->query("SELECT COUNT(*) FROM clubs")->fetchColumn();
    $totalMembers = $pdo->query("SELECT COUNT(*) FROM memberships WHERE status='active'")->fetchColumn();
    $totalEvents = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $totalTasks = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
    $totalFeedback = $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();

    // Query recent activities / announcements
    $recentAnnouncements = $pdo->query("SELECT a.*, COALESCE(c.name, 'Global System') AS club_name
                                        FROM announcements a
                                        LEFT JOIN clubs c ON a.club_id = c.id
                                        WHERE a.scope != 'PRIVATE'
                                        ORDER BY a.created_at DESC LIMIT 5")->fetchAll();
} catch (PDOException $e) {
    die("Query Error: " . htmlspecialchars($e->getMessage()));
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <!-- Admin Sidebar Navigation -->
    <?php require_once '../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="flex-header">
            <h2>Administration Dashboard</h2>
            <span class="badge">System-Level Control</span>
        </div>

        <?php displayAlerts(); ?>

        <!-- Overall Summary Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-icon-wrapper stat-icon-purple">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h5>Registered Users</h5>
                <div class="value"><?php echo $totalUsers; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon-wrapper stat-icon-cyan">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                </div>
                <h5>Total Clubs</h5>
                <div class="value"><?php echo $totalClubs; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon-wrapper stat-icon-green">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>
                </div>
                <h5>Active Members</h5>
                <div class="value"><?php echo $totalMembers; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon-wrapper stat-icon-blue">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <h5>Total Events</h5>
                <div class="value"><?php echo $totalEvents; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon-wrapper stat-icon-amber">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                </div>
                <h5>Assigned Tasks</h5>
                <div class="value"><?php echo $totalTasks; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon-wrapper stat-icon-red">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
                <h5>Feedbacks</h5>
                <div class="value"><?php echo $totalFeedback; ?></div>
            </div>
        </div>

        <!-- Recent System Activity / Announcements -->
        <div class="card-grid" style="grid-template-columns: 1fr; margin-top: 20px;">
            <div class="feature-card">
                <h3>Latest Club Announcements</h3>
                <p>Global view of recent updates dispatched by club coordination teams.</p>

                <?php if (empty($recentAnnouncements)): ?>
                    <p class="text-muted" style="font-style: italic;">No announcements published yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Club</th>
                                    <th>Title</th>
                                    <th>Content</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentAnnouncements as $ann): ?>
                                    <tr>
                                        <td><strong><?php echo escape($ann['club_name']); ?></strong></td>
                                        <td><?php echo escape($ann['title']); ?></td>
                                        <td><?php echo escape(substr($ann['content'], 0, 75)) . (strlen($ann['content']) > 75 ? '...' : ''); ?></td>
                                        <td><?php echo escape($ann['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
