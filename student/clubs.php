<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('student');

$userId = $_SESSION['user_id'];

try {
    // Get Admin configured Max Clubs setting (1-5, default 5)
    $maxClubs = intval(getSystemSetting($pdo, 'max_student_clubs', 5));

    // Fetch all active memberships for this student
    $activeMemberships = getActiveMemberships($pdo, $userId);
    $activeClubIds = array_map('intval', array_column($activeMemberships, 'club_id'));

    // Fetch all pending membership requests for this student
    $stmtPending = $pdo->prepare("SELECT club_id FROM memberships WHERE user_id = ? AND status = 'pending'");
    $stmtPending->execute([$userId]);
    $pendingClubIds = array_map('intval', $stmtPending->fetchAll(PDO::FETCH_COLUMN));

    // Check if user is a Club Head
    $isClubHead = (($_SESSION['user_role'] ?? '') === 'club_head') || (bool)getOwnClub($pdo, $userId);

    // Fetch all clubs
    $stmt = $pdo->query("SELECT c.*, u.full_name AS head_name,
                           (SELECT COUNT(*) FROM memberships m WHERE m.club_id = c.id AND m.status = 'active') AS member_count
                           FROM clubs c
                           LEFT JOIN users u ON c.club_head_id = u.id
                           ORDER BY c.name ASC");
    $clubs = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}

$maxClubsModal = $_SESSION['max_clubs_modal'] ?? null;
unset($_SESSION['max_clubs_modal']);
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <?php require_once '../includes/sidebar.php'; ?>

    <main class="main-content">
        <h2>Browse Clubs Directory</h2>
        <p class="text-muted">A student can belong to up to <strong><?php echo $maxClubs; ?> active club(s)</strong> at a time. Enforced on the server.</p>

        <?php displayAlerts(); ?>

        <div class="card-grid" style="grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));">
            <?php foreach ($clubs as $club): ?>
                <?php
                $clubId = intval($club['id']);
                $isMember = in_array($clubId, $activeClubIds);
                $isPending = in_array($clubId, $pendingClubIds);
                ?>
                <div class="card" style="display: flex; flex-direction: row; gap: 20px; align-items: stretch;">
                    <div style="flex-shrink: 0; display: flex; align-items: stretch;">
                        <?php echo renderClubLogo($club['logo'] ?? null, $club['name'], [140, 210]); ?>
                    </div>
                    <div style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <h3 style="margin-top: 0; margin-bottom: 8px; font-size: 1.35rem; font-weight: 700;"><?php echo escape($club['name']); ?></h3>
                            <p style="margin-bottom: 12px; color: var(--text-muted); font-size: 0.95rem; line-height: 1.5;"><?php echo escape($club['description']); ?></p>
                            <div style="font-size: 0.9rem; margin-bottom: 15px;">
                                <div><span class="text-muted">Club Head:</span> <strong><?php echo $club['head_name'] ? escape($club['head_name']) : 'Unassigned'; ?></strong></div>
                                <div><span class="text-muted">Total Active Members:</span> <strong><?php echo $club['member_count']; ?></strong></div>
                            </div>
                        </div>

                        <div style="margin-top: auto;">
                            <?php if ($isMember): ?>
                                <span class="status-badge status-active" style="display: block; text-align: center; padding: 8px;">✓ Your Active Club</span>
                            <?php elseif ($isPending): ?>
                                <span class="status-badge status-pending" style="display: block; text-align: center; padding: 8px; background: #f39c12; color: #fff;">⏳ Request Pending</span>
                            <?php elseif ($isClubHead): ?>
                                <button class="btn btn-secondary" style="width: 100%; cursor: not-allowed;" disabled title="Club Heads cannot join other clubs as members">Club Head Restricted</button>
                            <?php else: ?>
                                <form action="join-club.php" method="POST">
                                    <?php csrfInput(); ?>
                                    <input type="hidden" name="club_id" value="<?php echo $clubId; ?>">
                                    <button type="submit" class="btn btn-primary" style="width: 100%;">Join Club</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<?php if ($maxClubsModal): ?>
<div id="maxClubsModal" class="modal-overlay">
    <div class="modal-content" style="text-align: center;">
        <h3 style="margin-top: 0; color: var(--text-main);">Club Limit Reached</h3>
        <p style="margin: 20px 0; font-size: 1rem; line-height: 1.6; color: var(--text-muted);">
            You can belong to a maximum of <strong style="color: var(--text-main);"><?php echo escape($maxClubsModal['max_clubs']); ?> active club(s)</strong> at a time.<br><br>
            You are currently an active member of <strong style="color: var(--text-main);"><?php echo escape($maxClubsModal['current_count']); ?></strong> club(s).<br><br>
            To join a new club, please request to leave one of your existing active clubs first.
        </p>
        <button onclick="document.getElementById('maxClubsModal').style.display='none'" class="btn btn-primary" style="min-width: 120px;">OK</button>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
