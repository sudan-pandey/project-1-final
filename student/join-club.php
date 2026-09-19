<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireRole('student');

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        header("Location: clubs.php?error=" . urlencode("CSRF token validation failed."));
        exit;
    }

    $clubId = intval($_POST['club_id'] ?? 0);

    if ($clubId <= 0) {
        header("Location: clubs.php?error=" . urlencode("Invalid club chosen."));
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Strict Club Head Check - A Club Head CANNOT join any club as a regular member
        if (($_SESSION['user_role'] ?? '') === 'club_head') {
            $pdo->rollBack();
            header("Location: clubs.php?error=" . urlencode("Club Heads are strictly prohibited from joining other clubs as regular members."));
            exit;
        }

        $stmtHead = $pdo->prepare("SELECT id, name FROM clubs WHERE club_head_id = ? LIMIT 1");
        $stmtHead->execute([$userId]);
        $headingClub = $stmtHead->fetch();
        if ($headingClub) {
            $pdo->rollBack();
            header("Location: clubs.php?error=" . urlencode("You are currently heading the '" . $headingClub['name'] . "' club and cannot join other clubs as a member."));
            exit;
        }

        // Verify target club exists
        $clubCheck = $pdo->prepare("SELECT id, name FROM clubs WHERE id = ? LIMIT 1");
        $clubCheck->execute([$clubId]);
        $targetClub = $clubCheck->fetch();
        if (!$targetClub) {
            throw new Exception("The selected club does not exist.");
        }

        // 2. Check if user is ALREADY an active member of this target club
        $stmtActiveThis = $pdo->prepare("SELECT id FROM memberships WHERE user_id = ? AND club_id = ? AND status = 'active' LIMIT 1");
        $stmtActiveThis->execute([$userId, $clubId]);
        if ($stmtActiveThis->fetch()) {
            $pdo->rollBack();
            header("Location: clubs.php?error=" . urlencode("You are already an active member of '" . $targetClub['name'] . "'. You cannot join the same club again."));
            exit;
        }

        // 3. Check if user has a PENDING join request for this target club
        $stmtPendingThis = $pdo->prepare("SELECT id FROM memberships WHERE user_id = ? AND club_id = ? AND status = 'pending' LIMIT 1");
        $stmtPendingThis->execute([$userId, $clubId]);
        if ($stmtPendingThis->fetch()) {
            $pdo->rollBack();
            header("Location: clubs.php?error=" . urlencode("You already have a pending membership request for '" . $targetClub['name'] . "'. Please wait for the Club Head's approval."));
            exit;
        }

        // 4. Check Maximum Club Limit configured by Admin (max 5)
        $maxClubs = intval(getSystemSetting($pdo, 'max_student_clubs', 5));
        $stmtActiveCount = $pdo->prepare("SELECT COUNT(*) FROM memberships WHERE user_id = ? AND status = 'active'");
        $stmtActiveCount->execute([$userId]);
        $activeCount = intval($stmtActiveCount->fetchColumn());

        if ($activeCount >= $maxClubs) {
            $_SESSION['max_clubs_modal'] = [
                'max_clubs' => $maxClubs,
                'current_count' => $activeCount
            ];
            $pdo->rollBack();
            header("Location: clubs.php?error=" . urlencode("You cannot join more clubs because you have reached the maximum allowed limit of " . $maxClubs . " active club membership(s)."));
            exit;
        }

        // 5. Insert new join request with status = 'pending'
        $insertStmt = $pdo->prepare("INSERT INTO memberships (user_id, club_id, responsibility_id, status, requested_at) VALUES (?, ?, NULL, 'pending', NOW())");
        $insertStmt->execute([$userId, $clubId]);

        $pdo->commit();
        header("Location: clubs.php?success=" . urlencode("Your request to join '" . $targetClub['name'] . "' has been submitted successfully! Please wait for approval."));
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: clubs.php?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: clubs.php");
    exit;
}
