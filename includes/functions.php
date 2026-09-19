<?php
// Common Utility Functions

/**
 * Fetches a system setting value by key, with a default fallback
 *
 * @param PDO $pdo
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function getSystemSetting($pdo, $key, $default = null) {
    if (!$pdo) return $default;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Sets or updates a system setting value by key
 *
 * @param PDO $pdo
 * @param string $key
 * @param mixed $value
 * @return bool
 */
function setSystemSetting($pdo, $key, $value) {
    if (!$pdo) return false;
    try {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)
                               ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        return $stmt->execute([$key, strval($value)]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Escapes HTML to protect against XSS
 * @param string $value
 * @return string
 */
function escape($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Renders HTML for a club logo/image or a clean placeholder.
 *
 * @param string|null $logoFilename
 * @param string $clubName
 * @param int $size Width and height in pixels (default: 60)
 * @param string $extraClass Additional CSS classes
 * @return string HTML string
 */
function renderClubLogo($logoFilename, $clubName, $size = 60, $extraClass = '') {
    $svgW = 30;
    $svgH = 30;

    if (is_array($size)) {
        $w = $size[0];
        $h = $size[1] ?? $size[0];
        $sizeStyle = "width: {$w}px; height: {$h}px;";
        $svgW = round($w * 0.4);
        $svgH = round($h * 0.4);
    } elseif (is_numeric($size)) {
        $sizeStyle = "width: {$size}px; height: {$size}px;";
        $svgW = round($size * 0.5);
        $svgH = round($size * 0.5);
    } else {
        $sizeStyle = $size;
    }

    // Check if relative path or absolute path exists for logo file
    $logoPath = '../uploads/clubs/' . $logoFilename;
    $rootLogoPath = 'uploads/clubs/' . $logoFilename;
    $src = null;

    if (!empty($logoFilename)) {
        if (file_exists($logoPath)) {
            $src = $logoPath;
        } elseif (file_exists($rootLogoPath)) {
            $src = $rootLogoPath;
        } elseif (file_exists(__DIR__ . '/../uploads/clubs/' . $logoFilename)) {
            $src = '../uploads/clubs/' . $logoFilename;
        }
    }

    if ($src) {
        return sprintf(
            '<img src="%s" alt="%s Logo" class="club-logo-img %s" style="%s object-fit: cover; border-radius: 12px; border: 1px solid var(--border-color); flex-shrink: 0;" />',
            escape($src),
            escape($clubName),
            escape($extraClass),
            $sizeStyle
        );
    }

    // Default icon/placeholder SVG or initial letter badge
    $fontSize = '1.2rem';

    return sprintf(
        '<div class="club-logo-placeholder %s" style="%s border-radius: 12px; background: var(--border-color, #334155); color: #818cf8; display: inline-flex; align-items: center; justify-content: center; font-size: %s; font-weight: 700; flex-shrink: 0; border: 1px solid var(--border-color);"><svg width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="opacity: 0.9;"><path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-3"></path><path d="M9 9l0 .01"></path><path d="M9 12l0 .01"></path></svg></div>',
        escape($extraClass),
        $sizeStyle,
        $fontSize,
        $svgW,
        $svgH
    );
}

/**
 * Gets the count of unread announcements for a student user.
 *
 * @param PDO $pdo
 * @param int $userId
 * @return int
 */
function getUnreadAnnouncementsCount($pdo, $userId) {
    if (!$userId || !$pdo) return 0;
    try {
        require_once __DIR__ . '/auth.php';
        $userRole = $_SESSION['user_role'] ?? 'student';

        if ($userRole === 'admin') {
            $stmt = $pdo->prepare("SELECT COUNT(*)
                                   FROM announcements a
                                   LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id AND ar.user_id = ?
                                   WHERE ar.announcement_id IS NULL AND a.scope != 'PRIVATE'");
            $stmt->execute([$userId]);
        } elseif ($userRole === 'club_head') {
            $club = getOwnClub($pdo, $userId);
            $clubId = $club ? intval($club['id']) : 0;
            if ($clubId > 0) {
                $stmt = $pdo->prepare("SELECT COUNT(*)
                                       FROM announcements a
                                       LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id AND ar.user_id = ?
                                       WHERE ar.announcement_id IS NULL
                                         AND (a.scope = 'GLOBAL' OR (a.scope IN ('CLUB', 'PRIVATE') AND a.club_id = ?))");
                $stmt->execute([$userId, $clubId]);
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*)
                                       FROM announcements a
                                       LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id AND ar.user_id = ?
                                       WHERE ar.announcement_id IS NULL
                                         AND a.scope = 'GLOBAL'");
                $stmt->execute([$userId]);
            }
        } else {
            // Student
            $membership = getActiveMembership($pdo, $userId);
            $clubId = $membership ? intval($membership['club_id']) : 0;
            if ($clubId > 0) {
                $stmt = $pdo->prepare("SELECT COUNT(*)
                                       FROM announcements a
                                       LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id AND ar.user_id = ?
                                       WHERE ar.announcement_id IS NULL
                                         AND (a.scope = 'GLOBAL' OR (a.scope IN ('CLUB', 'PRIVATE') AND a.club_id = ?))");
                $stmt->execute([$userId, $clubId]);
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*)
                                       FROM announcements a
                                       LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id AND ar.user_id = ?
                                       WHERE ar.announcement_id IS NULL
                                         AND a.scope = 'GLOBAL'");
                $stmt->execute([$userId]);
            }
        }
        return intval($stmt->fetchColumn());
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Gets the count of pending/in-progress tasks for a club managed by a club head.
 *
 * @param PDO $pdo
 * @param int $userId
 * @return int
 */
function getClubHeadPendingTasksCount($pdo, $userId) {
    if (!$userId || !$pdo) return 0;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(t.id)
                               FROM tasks t
                               JOIN clubs c ON t.club_id = c.id
                               WHERE c.club_head_id = ? AND t.status IN ('pending', 'in_progress')");
        $stmt->execute([$userId]);
        return intval($stmt->fetchColumn());
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Gets the count of all pending/in-progress tasks system-wide for admin.
 *
 * @param PDO $pdo
 * @return int
 */
function getAllPendingTasksCount($pdo) {
    if (!$pdo) return 0;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status IN ('pending', 'in_progress')");
        return intval($stmt->fetchColumn());
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Gets the count of uncompleted assigned tasks for a student user.
 *
 * @param PDO $pdo
 * @param int $userId
 * @return int
 */
function getPendingTasksCount($pdo, $userId) {
    if (!$userId || !$pdo) return 0;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*)
                               FROM tasks
                               WHERE assigned_to = ? AND status IN ('pending', 'in_progress')");
        $stmt->execute([$userId]);
        return intval($stmt->fetchColumn());
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Renders success/error alerts from GET query parameters
 */
function displayAlerts() {
    if (isset($_GET['success'])) {
        echo '<div class="alert alert-success">' . escape($_GET['success']) . '</div>';
    }
    if (isset($_GET['error'])) {
        echo '<div class="alert alert-danger">' . escape($_GET['error']) . '</div>';
    }
}

/**
 * Checks if a task is overdue
 * @param string $deadline
 * @param string $status
 * @return bool
 */
function isTaskOverdue($deadline, $status) {
    if ($status === 'completed' || $status === 'cancelled') {
        return false;
    }
    return strtotime($deadline) < strtotime(date('Y-m-d'));
}

/**
 * Sends a membership approval email to a student.
 * Uses custom template from clubs table if present, otherwise uses default template.
 *
 * @param PDO $pdo
 * @param int $studentUserId
 * @param int $clubId
 * @return array ['success' => bool, 'message' => string]
 */
function sendMembershipApprovalEmail($pdo, $studentUserId, $clubId) {
    try {
        // Fetch student info
        $stmtUser = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ? LIMIT 1");
        $stmtUser->execute([$studentUserId]);
        $student = $stmtUser->fetch();

        if (!$student) {
            return ['success' => false, 'message' => 'Student user not found.'];
        }

        // Fetch club and club head info
        $stmtClub = $pdo->prepare("SELECT c.*, u.full_name AS head_name 
                                   FROM clubs c 
                                   LEFT JOIN users u ON c.club_head_id = u.id 
                                   WHERE c.id = ? LIMIT 1");
        $stmtClub->execute([$clubId]);
        $club = $stmtClub->fetch();

        if (!$club) {
            return ['success' => false, 'message' => 'Club not found.'];
        }

        $studentName = $student['full_name'];
        $studentEmail = $student['email'];
        $clubName = $club['name'];
        $clubHeadName = $club['head_name'] ?: 'Club Head';

        // Custom template check
        $subjectTemplate = !empty($club['email_subject']) ? $club['email_subject'] : "Club Membership Approved - {club_name}";
        $bodyTemplate = !empty($club['email_body']) ? $club['email_body'] : "Dear {student_name},\n\nCongratulations!\n\nYour request to join {club_name} has been approved.\n\nYou are now an active member of {club_name}.\n\nClub Head:\n{club_head_name}\n\nYou can now log in to the College Club Management System.\n\nRegards,\n{club_name}";

        // Replace placeholders
        $placeholders = [
            '{student_name}' => $studentName,
            '{club_name}' => $clubName,
            '{club_head_name}' => $clubHeadName,
            '{student_email}' => $studentEmail
        ];

        $subject = strtr($subjectTemplate, $placeholders);
        $body = strtr($bodyTemplate, $placeholders);

        require_once __DIR__ . '/mailer.php';

        // Attempt SMTP mail sending
        $result = sendSmtpEmail($studentEmail, $subject, $body);

        if ($result['success']) {
            return ['success' => true, 'message' => 'Email sent successfully via SMTP.'];
        } else {
            return ['success' => false, 'message' => $result['message'] . ' Student remains an active member.'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error attempting email dispatch: ' . $e->getMessage()];
    }
}
?>