<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireRole('club_head');

$userId = $_SESSION['user_id'];
$club = getOwnClub($pdo, $userId);

if (!$club) {
    header("Location: dashboard.php?error=" . urlencode("No club assignment yet."));
    exit;
}

$clubId = $club['id'];
$error = '';
$success = '';

// Handle posting / deleting announcements
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = "CSRF verification failed.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $scope = trim($_POST['scope'] ?? 'CLUB');
            if (!in_array($scope, ['GLOBAL', 'CLUB', 'PRIVATE'])) {
                $scope = 'CLUB';
            }

            $priority = trim($_POST['priority'] ?? 'Announcement');
            if (!in_array($priority, ['Announcement', 'Event', 'Urgent', 'General'])) {
                $priority = 'Announcement';
            }

            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');

            if (empty($title) || empty($content)) {
                $error = "Title and content cannot be blank.";
            } else {
                try {
                    $targetClubId = ($scope === 'GLOBAL') ? null : $clubId;
                    $ins = $pdo->prepare("INSERT INTO announcements (club_id, scope, title, priority, content, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                    $ins->execute([$targetClubId, $scope, $title, $priority, $content, $userId]);
                    if ($scope === 'GLOBAL') {
                        $success = "General public announcement broadcasted globally to all students and members!";
                    } elseif ($scope === 'PRIVATE') {
                        $success = "Private club message posted successfully!";
                    } else {
                        $success = "Club announcement published successfully!";
                    }
                } catch (PDOException $e) {
                    $error = "Database Error: " . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $annId = intval($_POST['announcement_id'] ?? 0);
            if ($annId > 0) {
                // Verify ownership: created by this user or belongs to head's club
                $chk = $pdo->prepare("SELECT id FROM announcements WHERE id = ? AND (created_by = ? OR club_id = ?) LIMIT 1");
                $chk->execute([$annId, $userId, $clubId]);
                if ($chk->fetch()) {
                    $del = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
                    $del->execute([$annId]);
                    $success = "Announcement deleted successfully.";
                } else {
                    $error = "Security Error: Unauthorized operation.";
                }
            }
        }
    }
}

// Fetch announcements for this Club Head (Global + Own Club notices & Private Messages)
try {
    $stmt = $pdo->prepare("SELECT a.*,
                                  COALESCE(c.name, 'System Admin') AS sender_name,
                                  u.full_name AS publisher
                           FROM announcements a
                           LEFT JOIN clubs c ON a.club_id = c.id
                           LEFT JOIN users u ON a.created_by = u.id
                           WHERE a.scope = 'GLOBAL'
                              OR (a.club_id = ? AND a.scope IN ('CLUB', 'PRIVATE'))
                           ORDER BY a.created_at DESC");
    $stmt->execute([$clubId]);
    $announcements = $stmt->fetchAll();

    // Automatically mark all current announcements as read when club head views page
    if (!empty($announcements) && !empty($userId)) {
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
        <h2>Club Announcements</h2>
        <p class="text-muted">Broadcast updates, agendas, or alerts directly to your club members' dashboards.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo escape($success); ?></div>
        <?php endif; ?>

        <!-- Create Announcement Form -->
        <div class="feature-card" style="margin-top: 20px; margin-bottom: 30px;">
            <h3>Post Club Notice / Private Message</h3>
            <form action="announcements.php" method="POST" style="margin-top: 15px;">
                <?php csrfInput(); ?>
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label for="scope">Channel / Audience</label>
                    <select id="scope" name="scope" class="form-control" onchange="updateNoticeNotice()">
                        <option value="GLOBAL">🌐 General Public Broadcast (All Admins, Students & All Club Members)</option>
                        <option value="CLUB">🏛️ Standard Club Broadcast (All <?php echo escape($club['name']); ?> Members)</option>
                        <option value="PRIVATE">🔒 Private Communication Channel (Members of <?php echo escape($club['name']); ?> Only)</option>
                    </select>
                    <small id="private_notice_help" class="text-muted" style="display: none; margin-top: 5px; color: #a78bfa;">🔒 Private Channel Messages are restricted exclusively to authorized members of this club.</small>
                    <small id="global_notice_help" class="text-muted" style="display: block; margin-top: 5px; color: #60a5fa;">🌐 General Public Messages are visible to everyone (Admins, Students, and Members of all clubs).</small>
                </div>

                <div class="form-group">
                    <label for="priority">Notice Type</label>
                    <select id="priority" name="priority" class="form-control">
                        <option value="Announcement">📢 Announcement (General updates & info)</option>
                        <option value="Event">📅 Event (Workshops, hackathons, meetings)</option>
                        <option value="Urgent">🚨 Urgent (Immediate action / deadline today)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="title">Message Title</label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="e.g. Mandatory Stand-up Meeting tomorrow" required>
                </div>

                <div class="form-group">
                    <label for="content">Detailed Content</label>
                    <textarea id="content" name="content" class="form-control" rows="4" placeholder="Enter full message details..." required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Publish Message</button>
            </form>
        </div>

        <script>
        function updateNoticeNotice() {
            var scope = document.getElementById('scope').value;
            var pHelp = document.getElementById('private_notice_help');
            var gHelp = document.getElementById('global_notice_help');
            if (scope === 'PRIVATE') {
                pHelp.style.display = 'block';
                gHelp.style.display = 'none';
            } else if (scope === 'GLOBAL') {
                pHelp.style.display = 'none';
                gHelp.style.display = 'block';
            } else {
                pHelp.style.display = 'none';
                gHelp.style.display = 'none';
            }
        }
        </script>

        <!-- Club Communication Navigation Tabs -->
        <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
            <button type="button" class="btn btn-secondary filter-btn active" onclick="filterNotices('all', this)">All Channel Messages</button>
            <button type="button" class="btn btn-secondary filter-btn" onclick="filterNotices('Announcement', this)">📢 Announcements</button>
            <button type="button" class="btn btn-secondary filter-btn" onclick="filterNotices('Event', this)">📅 Events</button>
            <button type="button" class="btn btn-secondary filter-btn" onclick="filterNotices('Urgent', this)">🚨 Urgent</button>
            <button type="button" class="btn btn-secondary filter-btn" onclick="filterNotices('PRIVATE', this)">🔒 Private Messages</button>
        </div>

        <h3>Club Communication Feed</h3>
        <div style="margin-top: 20px;">
            <?php if (empty($announcements)): ?>
                <p class="text-muted" style="font-style: italic;">No communication records found for your club.</p>
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
                                $scopeLabel = '🔒 Private Channel Message';
                                $scopeBg = '#7c3aed';
                            } else {
                                $scopeLabel = '🏛️ Club Notice (' . escape($sender) . ')';
                                $scopeBg = '#0369a1';
                            }
                        ?>
                        <div class="feature-card notice-item" data-type="<?php echo escape($priority); ?>" data-scope="<?php echo escape($scope); ?>" style="border-left: 5px solid <?php echo $badgeColor; ?>; <?php echo ($priority === 'Urgent') ? 'background-color: rgba(239, 68, 68, 0.05);' : ''; ?>">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <span class="status-badge" style="background-color: <?php echo $scopeBg; ?>; color: #fff; padding: 4px 10px; font-weight: 600; border-radius: 6px;">
                                        <?php echo $scopeLabel; ?>
                                    </span>
                                    <span class="status-badge" style="background-color: <?php echo $badgeColor; ?>; color: #fff; padding: 4px 10px; font-weight: 600; border-radius: 6px;">
                                        <?php echo $typeIcon; ?> <?php echo escape($priority); ?>
                                    </span>
                                </div>
                                <span class="text-muted" style="font-size: 0.85rem;">🕒 <?php echo escape($ann['created_at']); ?></span>
                            </div>

                            <h4 style="margin-bottom: 10px; color: var(--text-main); font-size: 1.25rem; word-break: break-word; overflow-wrap: anywhere;"><?php echo escape($ann['title']); ?></h4>
                            <div class="expandable-text">
                                <p class="text-content" style="color: var(--text-main); margin-bottom: 0; line-height: 1.6; white-space: pre-wrap;"><?php echo escape($ann['content']); ?></p>
                            </div>

                            <div style="border-top: 1px dashed var(--border-color); padding-top: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                                <span class="text-muted" style="font-size: 0.85rem;">Posted by: <strong><?php echo escape($ann['publisher'] ?: 'System Admin'); ?></strong></span>

                                <?php if (intval($ann['created_by']) === intval($userId) || (!empty($ann['club_id']) && intval($ann['club_id']) === intval($clubId))): ?>
                                    <form action="announcements.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                        <?php csrfInput(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="announcement_id" value="<?php echo $ann['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <script>
        function filterNotices(filter, btn) {
            var buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(function(b) { b.classList.remove('active'); b.style.backgroundColor = '#334155'; });
            btn.classList.add('active');
            btn.style.backgroundColor = 'var(--primary-color)';

            var items = document.querySelectorAll('.notice-item');
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
