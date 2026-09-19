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
        header("Location: events.php?error=" . urlencode("CSRF token verification failed."));
        exit;
    }

    $eventId = intval($_POST['event_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($eventId <= 0) {
        header("Location: events.php?error=" . urlencode("Invalid event."));
        exit;
    }

    try {
        // Check if event exists and is not cancelled/completed
        $stmtCheck = $pdo->prepare("SELECT * FROM events WHERE id = ? LIMIT 1");
        $stmtCheck->execute([$eventId]);
        $event = $stmtCheck->fetch();

        if (!$event) {
            header("Location: events.php?error=" . urlencode("Event not found."));
            exit;
        }

        if ($event['status'] !== 'upcoming') {
            header("Location: events.php?error=" . urlencode("You can only register/unregister for upcoming events."));
            exit;
        }

        if ($action === 'register') {
            // Register Student
            $stmtReg = $pdo->prepare("INSERT IGNORE INTO registrations (event_id, user_id) VALUES (?, ?)");
            $stmtReg->execute([$eventId, $userId]);

            header("Location: events.php?success=" . urlencode("Successfully registered for the event '" . $event['title'] . "'!"));
            exit;
        } elseif ($action === 'unregister') {
            // Unregister Student
            $stmtUnreg = $pdo->prepare("DELETE FROM registrations WHERE event_id = ? AND user_id = ?");
            $stmtUnreg->execute([$eventId, $userId]);

            header("Location: events.php?success=" . urlencode("Successfully unregistered from the event."));
            exit;
        } else {
            header("Location: events.php");
            exit;
        }
    } catch (PDOException $e) {
        header("Location: events.php?error=" . urlencode("System Error: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: events.php");
    exit;
}
?>