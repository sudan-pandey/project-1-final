<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireRole('student');

$userId = $_SESSION['user_id'];

try {
    $membership = getActiveMembership($pdo, $userId);
    $studentClubId = $membership ? intval($membership['club_id']) : 0;

    // Fetch announcements filtered strictly by student audience scope
    if ($studentClubId > 0) {
        $stmt = $pdo->prepare("SELECT a.*,
                                      COALESCE(c.name, 'System Admin') AS sender_name,
                                      c.logo AS club_logo,
                                      u.full_name AS publisher,
                                      (SELECT read_at FROM announcement_reads ar WHERE ar.announcement_id = a.id AND ar.user_id = ? LIMIT 1) AS read_at
                               FROM announcements a
                               LEFT JOIN clubs c ON a.club_id = c.id
                               LEFT JOIN users u ON a.created_by = u.id
                               WHERE a.scope = 'GLOBAL'
                                  OR (a.club_id = ? AND a.scope IN ('CLUB', 'PRIVATE'))
                               ORDER BY a.created_at DESC");
        $stmt->execute([$userId, $studentClubId]);
    } else {
        $stmt = $pdo->prepare("SELECT a.*,
                                      COALESCE(c.name, 'System Admin') AS sender_name,
                                      c.logo AS club_logo,
                                      u.full_name AS publisher,
                                      (SELECT read_at FROM announcement_reads ar WHERE ar.announcement_id = a.id AND ar.user_id = ? LIMIT 1) AS read_at
                               FROM announcements a
                               LEFT JOIN clubs c ON a.club_id = c.id
                               LEFT JOIN users u ON a.created_by = u.id
                               WHERE a.scope = 'GLOBAL'
                               ORDER BY a.created_at DESC");
        $stmt->execute([$userId]);
    }
    $announcements = $stmt->fetchAll();

    // Automatically mark all current announcements as read when student views page
    if (!empty($announcements)) {
        $markStmt = $pdo->prepare("INSERT IGNORE INTO announcement_reads (announcement_id, user_id) VALUES (?, ?)");
        foreach ($announcements as $ann) {
            $markStmt->execute([$ann['id'], $userId]);
        }
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
        <h2>Announcements Board</h2>
        <p class="text-muted">Stay informed with updates, notices, and broadcasts from all student clubs and system administration.</p>

        <!-- Communication Filter Tabs -->
        <div style="display: flex; gap: 10px; margin-top: 20px; margin-bottom: 20px; flex-wrap: wrap;">
            <button type="button" class="btn btn-secondary filter-btn active" onclick="filterStudentNotices('all', this)">All Notices</button>
            <button type="button" class="btn btn-secondary filter-btn" onclick="filterStudentNotices('Announcement', this)">📢 Announcements</button>
            <button type="button" class="btn btn-secondary filter-btn" onclick="filterStudentNotices('Event', this)">📅 Events</button>
            <button type="button" class="btn btn-secondary filter-btn" onclick="filterStudentNotices('Urgent', this)">🚨 Urgent</button>
            <button type="button" class="btn btn-secondary filter-btn" onclick="filterStudentNotices('PRIVATE', this)">🔒 Private Messages</button>
        </div>

        <div style="margin-top: 15px;">
            <?php if (empty($announcements)): ?>
                <p class="text-muted" style="font-style: italic;">No announcements have been published for your scope yet.</p>
            <?php else: ?>
                <div class="card-grid" style="grid-template-columns: 1fr; gap: 20px;">
                    <?php foreach ($announcements as $ann): ?>
                        <?php
                            $priority = !empty($ann['priority']) ? $ann['priority'] : 'Announcement';
                            if ($priority === 'General') { $priority = 'Announcement'; }
                            $badgeColor = 'var(--primary-color)';
                            $typeIcon = '📢';
                            if ($priority === 'Urgent') {
                                $badgeColor = 'var(--danger)';
                                $typeIcon = '🚨';
                            } elseif ($priority === 'Event') {
                                $badgeColor = '#0284c7';
                                $typeIcon = '📅';
                            }

                            $scope = $ann['scope'] ?? 'GLOBAL';
                            $sender = !empty($ann['sender_name']) ? $ann['sender_name'] : 'System Admin';

                            if ($scope === 'GLOBAL') {
                                $scopeLabel = '🌐 Global Broadcast';
                                $scopeBg = '#334155';
                            } elseif ($scope === 'PRIVATE') {
                                $scopeLabel = '🔒 Private Club Channel';
                                $scopeBg = '#7c3aed';
                            } else {
                                $scopeLabel = '🏛️ Club Notice (' . escape($sender) . ')';
                                $scopeBg = '#0369a1';
                            }
                        ?>
                        <?php $isUnread = empty($ann['read_at']); ?>
                        <div class="feature-card student-notice-item" data-type="<?php echo escape($priority); ?>" data-scope="<?php echo escape($scope); ?>" style="border-left: 5px solid <?php echo $badgeColor; ?>; position: relative; <?php echo ($priority === 'Urgent') ? 'background-color: rgba(239, 68, 68, 0.05);' : ''; ?>">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <?php if (!empty($ann['club_id'])): ?>
                                        <?php echo renderClubLogo($ann['club_logo'] ?? null, $sender, 32); ?>
                                    <?php endif; ?>
                                    <span class="status-badge" style="background-color: <?php echo $scopeBg; ?>; color: #fff; padding: 4px 10px; font-weight: 600; border-radius: 6px;">
                                        <?php echo $scopeLabel; ?>
                                    </span>
                                    <span class="status-badge" style="background-color: <?php echo $badgeColor; ?>; color: #fff; padding: 4px 10px; font-weight: 600; border-radius: 6px;">
                                        <?php echo $typeIcon; ?> <?php echo escape($priority); ?>
                                    </span>
                                    <?php if ($isUnread): ?>
                                        <span class="status-badge" style="background-color: #ef4444; color: #fff; padding: 2px 8px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; border-radius: 4px;">
                                            NEW
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-muted" style="font-size: 0.85rem;">🕒 <?php echo escape($ann['created_at']); ?></span>
                            </div>

                            <h4 style="margin-bottom: 10px; color: var(--text-main); font-size: 1.25rem; word-break: break-word; overflow-wrap: anywhere;"><?php echo escape($ann['title']); ?></h4>
                            <div class="expandable-text">
                                <p class="text-content" style="color: var(--text-main); margin-bottom: 0; line-height: 1.6; white-space: pre-wrap;"><?php echo escape($ann['content']); ?></p>
                            </div>

                            <div style="border-top: 1px dashed var(--border-color); padding-top: 10px; color: var(--text-muted); font-size: 0.85rem;">
                                Posted by: <strong><?php echo escape($ann['publisher'] ?: 'System Admin'); ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <script>
        function filterStudentNotices(filter, btn) {
            var buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(function(b) { b.classList.remove('active'); b.style.backgroundColor = '#334155'; });
            btn.classList.add('active');
            btn.style.backgroundColor = 'var(--primary-color)';

            var items = document.querySelectorAll('.student-notice-item');
            items.forEach(function(item) {
                var itemType = item.getAttribute('data-type');
                var itemScope = item.getAttribute('data-scope');

                if (filter === 'all') {
                    item.style.display = 'block';
                } else if (filter === 'PRIVATE') {
                    item.style.display = (itemScope === 'PRIVATE') ? 'block' : 'none';
                } else {
                    item.style.display = (itemType === filter) ? 'block' : 'none';
                }
            });
        }
        </script>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
