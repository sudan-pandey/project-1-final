#!/usr/bin/env php
<?php
/**
 * CLI Test Command for Club Membership Approval Email Verification
 * Usage: php test_approval_email.php <student_email>
 */

if (php_sapi_name() !== 'cli' && empty($_SERVER['argv'])) {
    echo "This script must be executed from the command line (CLI).\n";
    exit(1);
}

// Check command line arguments
$targetEmail = $argv[1] ?? null;

if (empty($targetEmail)) {
    echo "=======================================================\n";
    echo "  Club Membership Approval Email Verification Tool\n";
    echo "=======================================================\n";
    echo "Usage: php test_approval_email.php <student_email>\n";
    echo "Example: php test_approval_email.php student@example.com\n";
    echo "=======================================================\n";
    exit(1);
}

echo "=======================================================\n";
echo "  [EMAIL VERIFICATION TEST COMMAND]\n";
echo "=======================================================\n";
echo "Target Email: {$targetEmail}\n";
echo "Simulating membership approval notification trigger...\n\n";

try {
    // Load database connection and functions
    $dbConfigFile = __DIR__ . '/config/database.php';
    $functionsFile = __DIR__ . '/includes/functions.php';

    if (!file_exists($dbConfigFile) || !file_exists($functionsFile)) {
        throw new Exception("Missing required application configuration or functions file.");
    }

    require_once $dbConfigFile;
    require_once $functionsFile;

    // 1. Look up student record
    $stmtUser = $pdo->prepare("SELECT id, full_name, email, role FROM users WHERE email = ? LIMIT 1");
    $stmtUser->execute([$targetEmail]);
    $user = $stmtUser->fetch();

    if (!$user) {
        echo "[ERROR] User record not found for email: {$targetEmail}\n";
        echo "Please ensure the student user exists in the 'users' table.\n";
        exit(1);
    }

    $studentUserId = $user['id'];
    $studentName = $user['full_name'];

    echo "Found Student: {$studentName} (ID: {$studentUserId}, Role: {$user['role']})\n";

    // 2. Determine target club for membership approval simulation
    $stmtMemb = $pdo->prepare("SELECT club_id FROM memberships WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmtMemb->execute([$studentUserId]);
    $membership = $stmtMemb->fetch();

    $clubId = null;
    if ($membership && !empty($membership['club_id'])) {
        $clubId = $membership['club_id'];
    } else {
        // Fallback to first available club in database
        $stmtFirstClub = $pdo->query("SELECT id FROM clubs ORDER BY id ASC LIMIT 1");
        $clubId = $stmtFirstClub->fetchColumn();
    }

    if (!$clubId) {
        echo "[ERROR] No active clubs found in the database to simulate approval for.\n";
        exit(1);
    }

    // Fetch club details for logging
    $stmtClubInfo = $pdo->prepare("SELECT name FROM clubs WHERE id = ? LIMIT 1");
    $stmtClubInfo->execute([$clubId]);
    $clubName = $stmtClubInfo->fetchColumn();

    echo "Target Club: {$clubName} (ID: {$clubId})\n";
    echo "Attempting mail dispatch via sendMembershipApprovalEmail()...\n\n";

    // 3. Trigger approval notification mail dispatch inside try/catch block
    $result = sendMembershipApprovalEmail($pdo, $studentUserId, $clubId);

    if ($result['success']) {
        echo "-------------------------------------------------------\n";
        echo "[SUCCESS] Email dispatched successfully!\n";
        echo "Message: " . $result['message'] . "\n";
        echo "-------------------------------------------------------\n";
        exit(0);
    } else {
        echo "-------------------------------------------------------\n";
        echo "[FAILURE] Email dispatch failed.\n";
        echo "Details: " . $result['message'] . "\n";
        echo "Diagnostic Note: Check sendmail/SMTP configuration in php.ini.\n";
        echo "-------------------------------------------------------\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "-------------------------------------------------------\n";
    echo "[EXCEPTION ENCOUNTERED]\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "-------------------------------------------------------\n";
    exit(1);
}
