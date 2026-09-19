<?php
// Authentication and Role Authorization Helpers

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Ensures user is authenticated. Otherwise redirects to login.
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit;
    }
}

/**
 * Ensures user has a matching role or admin.
 * @param string|array $allowed_roles
 */
function requireRole($allowed_roles) {
    requireLogin();

    $user_role = $_SESSION['user_role'] ?? '';

    // Admins have access everywhere except possibly specific member views
    if ($user_role === 'admin') {
        return;
    }

    if (is_array($allowed_roles)) {
        if (!in_array($user_role, $allowed_roles)) {
            // Forbidden access
            header("Location: ../login.php?error=unauthorized");
            exit;
        }
    } else {
        if ($user_role !== $allowed_roles) {
            header("Location: ../login.php?error=unauthorized");
            exit;
        }
    }
}

/**
 * Get active student club membership (first active)
 * @param PDO $pdo
 * @param int $user_id
 * @return array|false
 */
function getActiveMembership($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT m.*, c.name AS club_name, r.name AS responsibility_name
                           FROM memberships m
                           JOIN clubs c ON m.club_id = c.id
                           LEFT JOIN responsibilities r ON m.responsibility_id = r.id
                           WHERE m.user_id = ? AND m.status = 'active' LIMIT 1");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Get all active student club memberships
 * @param PDO $pdo
 * @param int $user_id
 * @return array
 */
function getActiveMemberships($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT m.*, c.name AS club_name, r.name AS responsibility_name
                           FROM memberships m
                           JOIN clubs c ON m.club_id = c.id
                           LEFT JOIN responsibilities r ON m.responsibility_id = r.id
                           WHERE m.user_id = ? AND m.status = 'active'
                           ORDER BY c.name ASC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * Get own club for Club Head
 * @param PDO $pdo
 * @param int $user_id
 * @return array|false
 */
function getOwnClub($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM clubs WHERE club_head_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}
?>