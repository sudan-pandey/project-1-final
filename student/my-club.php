<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireRole('student');

$userId = $_SESSION['user_id'];

try {
    // Verify all active memberships for this student
    $activeMemberships = getActiveMemberships($pdo, $userId);

    if (empty($activeMemberships)) {
        header("Location: clubs.php?error=" . urlencode("Please join a club first to view its details."));
        exit;
    }

    // Determine target active club ID from GET query parameter or default to first
    $targetClubId = intval($_GET['club_id'] ?? 0);
    $membership = null;

    if ($targetClubId > 0) {
        foreach ($activeMemberships as $m) {
            if (intval($m['club_id']) === $targetClubId) {
                $membership = $m;
                break;
            }
        }
    }

    if (!$membership) {
        $membership = $activeMemberships[0];
    }

    $clubId = intval($membership['club_id']);

    // Fetch club info & head
    $stmtClub = $pdo->prepare("SELECT c.*, u.full_name AS head_name, u.email AS head_email
                               FROM clubs c
                               LEFT JOIN users u ON c.club_head_id = u.id
                               WHERE c.id = ? LIMIT 1");
    $stmtClub->execute([$clubId]);
    $clubInfo = $stmtClub->fetch();

    // Fetch fellow members with responsibilities
    $stmtMembers = $pdo->prepare("SELECT m.*, u.full_name, u.email, r.name AS responsibility_name
                                  FROM memberships m
                                  JOIN users u ON m.user_id = u.id
                                  LEFT JOIN responsibilities r ON m.responsibility_id = r.id
                                  WHERE m.club_id = ? AND m.status = 'active'
                                  ORDER BY u.full_name ASC");
    $stmtMembers->execute([$clubId]);
    $fellowMembers = $stmtMembers->fetchAll();

    // Student Leave Club Action (Requires Club Head Approval)
    $error = '';
    $success = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'leave') {
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($csrfToken)) {
            $error = "CSRF verification failed.";
        } else {
            if (intval($clubInfo['club_head_id']) === intval($userId)) {
                $error = "As the Club Head, you cannot leave the club. Please contact the Admin to delegate leadership first.";
            } elseif ($membership['leave_status'] === 'pending') {
                $error = "You already have a pending leave request for this club. Please wait for the Club Head's approval.";
            } else {
                try {
                    $upStmt = $pdo->prepare("UPDATE memberships SET leave_status = 'pending' WHERE id = ? AND status = 'active'");
                    $upStmt->execute([$membership['id']]);
                    $success = "Your request to leave the club has been submitted. You remain an active member until approved by the Club Head.";
                    // Refresh membership data
                    $membership['leave_status'] = 'pending';
                } catch (Exception $e) {
                    $error = "Failed to request leave: " . $e->getMessage();
                }
            }
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
        <?php if (count($activeMemberships) > 1): ?>
            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 20px; flex-wrap: wrap; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <span style="font-weight: 600; color: var(--text-muted);">Your Joined Clubs:</span>
                <?php foreach ($activeMemberships as $m): ?>
                    <a href="my-club.php?club_id=<?php echo $m['club_id']; ?>"
                       class="btn <?php echo intval($m['club_id']) === $clubId ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                        <?php echo escape($m['club_name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="flex-header">
            <h2><?php echo escape($clubInfo['name']); ?></h2>
            <?php if ($membership['leave_status'] === 'pending'): ?>
                <button class="btn btn-secondary btn-sm" disabled style="cursor: not-allowed;">⏳ Leave Request Pending</button>
            <?php else: ?>
                <form action="my-club.php?club_id=<?php echo $clubId; ?>" method="POST" onsubmit="return confirm('Are you sure you want to request to leave this club? Your request will be reviewed by the Club Head.');">
                    <?php csrfInput(); ?>
                    <input type="hidden" name="action" value="leave">
                    <button type="submit" class="btn btn-danger btn-sm">Request to Leave Club</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo escape($success); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>

        <div class="feature-card" style="margin-bottom: 30px;">
            <div style="display: flex; align-items: stretch; gap: 24px; flex-wrap: wrap;">
                <?php echo renderClubLogo($clubInfo['logo'] ?? null, $clubInfo['name'], [140, 180]); ?>
                <div style="flex: 1; min-width: 240px; display: flex; flex-direction: column; justify-content: center;">
                    <h3 style="margin-top: 0; margin-bottom: 8px; font-size: 1.35rem;"><?php echo escape($clubInfo['name']); ?></h3>
                    <p style="margin-bottom: 12px; color: var(--text-muted); line-height: 1.6;"><?php echo escape($clubInfo['description']); ?></p>
                    <div style="font-size: 0.95rem;">
                        <span class="text-muted">Club Head:</span>
                        <strong>
                            <?php echo $clubInfo['head_name'] ? escape($clubInfo['head_name']) . " (" . escape($clubInfo['head_email']) . ")" : '<span style="color:var(--warning);">None assigned</span>'; ?>
                        </strong>
                    </div>
                </div>
            </div>
        </div>

        <h3>Active Members Directory</h3>
        <p class="text-muted" style="margin-bottom: 15px;">A view of your fellow club peers and their designated lead positions.</p>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email Address</th>
                        <th>Assigned Responsibility</th>
                        <th>Joined Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fellowMembers as $member): ?>
                        <tr <?php echo intval($member['user_id']) === intval($userId) ? 'style="background-color: rgba(79, 70, 229, 0.08);"' : ''; ?>>
                            <td>
                                <strong><?php echo escape($member['full_name']); ?></strong>
                                <?php if (intval($member['user_id']) === intval($userId)): ?>
                                    <span class="badge" style="margin-left: 10px; margin-bottom: 0; padding: 2px 8px;">You</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo escape($member['email']); ?></td>
                            <td>
                                <?php echo $member['responsibility_name'] ? '<span class="status-badge" style="background-color:#4f46e5; color:white;">' . escape($member['responsibility_name']) . '</span>' : '<span class="text-muted">General Member</span>'; ?>
                            </td>
                            <td><?php echo escape($member['joined_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
