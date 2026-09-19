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

// Handle Member Removal and Responsibility assignments/changes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = "CSRF verification failed.";
    } else {
        $action = $_POST['action'] ?? '';
        $membershipId = intval($_POST['membership_id'] ?? 0);

        if ($membershipId > 0) {
            // 1. Strict Ownership Check: Verify membership belongs to THIS club head's club!
            $stmtOwner = $pdo->prepare("SELECT * FROM memberships WHERE id = ? AND club_id = ? LIMIT 1");
            $stmtOwner->execute([$membershipId, $clubId]);
            $memberRecord = $stmtOwner->fetch();

            if (!$memberRecord) {
                $error = "Security Error: Selected membership request does not belong to your club.";
            } else {
                $targetStudentId = $memberRecord['user_id'];

                if ($action === 'approve_join') {
                    $upStmt = $pdo->prepare("UPDATE memberships SET status = 'active', joined_at = NOW() WHERE id = ? AND club_id = ?");
                    $upStmt->execute([$membershipId, $clubId]);

                    // Trigger email sending
                    $emailResult = sendMembershipApprovalEmail($pdo, $targetStudentId, $clubId);
                    if ($emailResult['success']) {
                        $success = "Membership request approved! Approval email sent to student.";
                    } else {
                        $success = "Membership request approved successfully! Note: " . $emailResult['message'];
                    }
                } elseif ($action === 'reject_join') {
                    $upStmt = $pdo->prepare("UPDATE memberships SET status = 'rejected' WHERE id = ? AND club_id = ?");
                    $upStmt->execute([$membershipId, $clubId]);
                    $success = "Join request rejected.";
                } elseif ($action === 'approve_leave') {
                    $pdo->beginTransaction();
                    try {
                        $upStmt = $pdo->prepare("UPDATE memberships SET status = 'inactive', leave_status = 'approved' WHERE id = ? AND club_id = ?");
                        $upStmt->execute([$membershipId, $clubId]);

                        $cancelTasks = $pdo->prepare("UPDATE tasks SET status = 'cancelled' WHERE assigned_to = ? AND club_id = ? AND status IN ('pending', 'in_progress')");
                        $cancelTasks->execute([$targetStudentId, $clubId]);

                        $pdo->commit();
                        $success = "Leave request approved. Student is no longer an active member.";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error = "Failed to approve leave request: " . $e->getMessage();
                    }
                } elseif ($action === 'reject_leave') {
                    $upStmt = $pdo->prepare("UPDATE memberships SET leave_status = 'rejected' WHERE id = ? AND club_id = ?");
                    $upStmt->execute([$membershipId, $clubId]);
                    $success = "Leave request rejected. Student remains an active member.";
                } elseif ($action === 'change_responsibility') {
                    $respIdInput = $_POST['responsibility_id'] ?? '';
                    $newRespId = $respIdInput === '' ? null : intval($respIdInput);

                    $upStmt = $pdo->prepare("UPDATE memberships SET responsibility_id = ? WHERE id = ? AND club_id = ?");
                    $upStmt->execute([$newRespId, $membershipId, $clubId]);
                    $success = "Member responsibility updated successfully.";
                } elseif ($action === 'remove_member') {
                    $pdo->beginTransaction();
                    try {
                        $delStmt = $pdo->prepare("UPDATE memberships SET status = 'inactive' WHERE id = ? AND club_id = ?");
                        $delStmt->execute([$membershipId, $clubId]);

                        $cancelTasks = $pdo->prepare("UPDATE tasks SET status = 'cancelled' WHERE assigned_to = ? AND club_id = ? AND status IN ('pending', 'in_progress')");
                        $cancelTasks->execute([$targetStudentId, $clubId]);

                        $pdo->commit();
                        $success = "Member successfully removed from the club.";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error = "Failed to remove member: " . $e->getMessage();
                    }
                }
            }
        } else {
            $error = "Invalid member specified.";
        }
    }
}

// Fetch pending join requests, pending leave requests, and active members
try {
    $stmtPendingJoin = $pdo->prepare("SELECT m.*, u.full_name, u.email
                                      FROM memberships m
                                      JOIN users u ON m.user_id = u.id
                                      WHERE m.club_id = ? AND m.status = 'pending'
                                      ORDER BY m.requested_at DESC");
    $stmtPendingJoin->execute([$clubId]);
    $pendingJoinRequests = $stmtPendingJoin->fetchAll();

    $stmtPendingLeave = $pdo->prepare("SELECT m.*, u.full_name, u.email
                                       FROM memberships m
                                       JOIN users u ON m.user_id = u.id
                                       WHERE m.club_id = ? AND m.status = 'active' AND m.leave_status = 'pending'
                                       ORDER BY m.updated_at DESC");
    $stmtPendingLeave->execute([$clubId]);
    $pendingLeaveRequests = $stmtPendingLeave->fetchAll();

    $stmtMembers = $pdo->prepare("SELECT m.*, u.full_name, u.email, r.name AS responsibility_name
                                  FROM memberships m
                                  JOIN users u ON m.user_id = u.id
                                  LEFT JOIN responsibilities r ON m.responsibility_id = r.id
                                  WHERE m.club_id = ? AND m.status = 'active'
                                  ORDER BY u.full_name ASC");
    $stmtMembers->execute([$clubId]);
    $members = $stmtMembers->fetchAll();

    // Fetch responsibilities list for dropdown
    $resps = $pdo->query("SELECT * FROM responsibilities ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <?php require_once '../includes/sidebar.php'; ?>

    <main class="main-content">
        <h2>Club Members & Responsibilities</h2>
        <p class="text-muted">Manage active student members, delegate leads, or remove students from the group.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo escape($success); ?></div>
        <?php endif; ?>

        <!-- Pending Membership Join Requests -->
        <h3 style="margin-top: 30px;">📥 Pending Join Requests</h3>
        <p class="text-muted">Review students requesting to join your club.</p>

        <div class="table-responsive" style="margin-top: 15px; margin-bottom: 35px;">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Requested Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pendingJoinRequests)): ?>
                        <tr>
                            <td colspan="5" class="text-muted" style="text-align: center; font-style: italic;">No pending join requests at this time.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pendingJoinRequests as $req): ?>
                            <tr>
                                <td><strong><?php echo escape($req['full_name']); ?></strong></td>
                                <td><?php echo escape($req['email']); ?></td>
                                <td><?php echo escape($req['requested_at']); ?></td>
                                <td><span class="status-badge" style="background:#f39c12; color:#fff;">Pending</span></td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <form action="members.php" method="POST" style="display:inline;">
                                            <?php csrfInput(); ?>
                                            <input type="hidden" name="action" value="approve_join">
                                            <input type="hidden" name="membership_id" value="<?php echo $req['id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                        </form>
                                        <form action="members.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to reject this request?');">
                                            <?php csrfInput(); ?>
                                            <input type="hidden" name="action" value="reject_join">
                                            <input type="hidden" name="membership_id" value="<?php echo $req['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pending Leave Requests -->
        <?php if (!empty($pendingLeaveRequests)): ?>
            <h3>📤 Pending Leave Requests</h3>
            <p class="text-muted">Members who have requested to leave your club.</p>
            <div class="table-responsive" style="margin-top: 15px; margin-bottom: 35px;">
                <table>
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingLeaveRequests as $lreq): ?>
                            <tr>
                                <td><strong><?php echo escape($lreq['full_name']); ?></strong></td>
                                <td><?php echo escape($lreq['email']); ?></td>
                                <td><span class="status-badge" style="background:#e74c3c; color:#fff;">Leave Pending</span></td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <form action="members.php" method="POST" style="display:inline;" onsubmit="return confirm('Approve leave request? Student will be removed from active club members.');">
                                            <?php csrfInput(); ?>
                                            <input type="hidden" name="action" value="approve_leave">
                                            <input type="hidden" name="membership_id" value="<?php echo $lreq['id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm">Approve Leave</button>
                                        </form>
                                        <form action="members.php" method="POST" style="display:inline;">
                                            <?php csrfInput(); ?>
                                            <input type="hidden" name="action" value="reject_leave">
                                            <input type="hidden" name="membership_id" value="<?php echo $lreq['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Reject Leave</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h3>👥 Active Members Directory</h3>
        <div class="table-responsive" style="margin-top: 15px;">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Assigned Responsibility</th>
                        <th>Joined At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($members)): ?>
                        <tr>
                            <td colspan="5" class="text-muted" style="text-align: center; font-style: italic;">No active students in your club yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($members as $m): ?>
                            <tr>
                                <td><strong><?php echo escape($m['full_name']); ?></strong></td>
                                <td><?php echo escape($m['email']); ?></td>
                                <td>
                                    <!-- Responsibility Dropdown Form -->
                                    <form action="members.php" method="POST" style="display: flex; align-items: center; gap: 8px;">
                                        <?php csrfInput(); ?>
                                        <input type="hidden" name="action" value="change_responsibility">
                                        <input type="hidden" name="membership_id" value="<?php echo $m['id']; ?>">
                                        <select name="responsibility_id" class="form-control" style="padding: 4px 8px; width: auto; font-size: 0.85rem;" onchange="this.form.submit()">
                                            <option value="">-- General Member --</option>
                                            <?php foreach ($resps as $r): ?>
                                                <option value="<?php echo $r['id']; ?>" <?php echo intval($m['responsibility_id']) === intval($r['id']) ? 'selected' : ''; ?>>
                                                    <?php echo escape($r['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td><?php echo escape($m['joined_at']); ?></td>
                                <td>
                                    <!-- Remove Member Form -->
                                    <form action="members.php" method="POST" onsubmit="return confirm('Are you sure you want to remove this member from your club? Their pending tasks will be cancelled.');" style="display: inline;">
                                        <?php csrfInput(); ?>
                                        <input type="hidden" name="action" value="remove_member">
                                        <input type="hidden" name="membership_id" value="<?php echo $m['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
