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

// Dropdown filter for choosing which event to mark attendance for
$selectedEventId = intval($_GET['event_id'] ?? 0);

// Fetch completed/upcoming events organized by own club
try {
    $stmtEvents = $pdo->prepare("SELECT id, title, status FROM events WHERE club_id = ? ORDER BY event_date DESC");
    $stmtEvents->execute([$clubId]);
    $events = $stmtEvents->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}

// Handle Attendance Submit Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedEventId > 0) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = "CSRF verification failed.";
    } else {
        // Verify event belongs to own club
        $stmtCheck = $pdo->prepare("SELECT id FROM events WHERE id = ? AND club_id = ? LIMIT 1");
        $stmtCheck->execute([$selectedEventId, $clubId]);
        if (!$stmtCheck->fetch()) {
            $error = "Security Error: Unauthorized event access.";
        } else {
            // Read attendee status arrays from POST
            $attendanceStatuses = $_POST['attendance'] ?? []; // key is user_id, value is 'present' or 'absent'

            $pdo->beginTransaction();
            try {
                // To keep database clean, we insert or update values for all registrants
                $stmtUpsert = $pdo->prepare("INSERT INTO attendance (event_id, user_id, status) VALUES (?, ?, ?)
                                             ON DUPLICATE KEY UPDATE status = VALUES(status), marked_at = CURRENT_TIMESTAMP");

                foreach ($attendanceStatuses as $targetUserId => $statusValue) {
                    $targetUserId = intval($targetUserId);
                    if ($targetUserId > 0 && in_array($statusValue, ['present', 'absent'])) {
                        $stmtUpsert->execute([$selectedEventId, $targetUserId, $statusValue]);
                    }
                }

                $pdo->commit();
                $success = "Event attendance log updated successfully!";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Failed to mark attendance: " . $e->getMessage();
            }
        }
    }
}

// Fetch registrants of selected event, joined with attendance if already marked
$registrants = [];
if ($selectedEventId > 0) {
    try {
        $stmtRegs = $pdo->prepare("SELECT r.user_id, u.full_name, u.email, a.status AS attendance_status
                                   FROM registrations r
                                   JOIN users u ON r.user_id = u.id
                                   LEFT JOIN attendance a ON r.event_id = a.event_id AND r.user_id = a.user_id
                                   WHERE r.event_id = ?
                                   ORDER BY u.full_name ASC");
        $stmtRegs->execute([$selectedEventId]);
        $registrants = $stmtRegs->fetchAll();
    } catch (PDOException $e) {
        die("Database Error: " . htmlspecialchars($e->getMessage()));
    }
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <?php require_once '../includes/sidebar.php'; ?>

    <main class="main-content">
        <h2>Manual Attendance Tracker</h2>
        <p class="text-muted">Select an event from the dropdown to check-in registered student attendees.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo escape($success); ?></div>
        <?php endif; ?>

        <!-- Event Selection Filter -->
        <div class="feature-card" style="margin-top: 20px; margin-bottom: 30px;">
            <form action="attendance.php" method="GET" style="display: flex; align-items: center; gap: 15px;">
                <label for="event_id" style="font-weight: bold;">Select Event:</label>
                <select id="event_id" name="event_id" class="form-control" style="width: auto;" onchange="this.form.submit()">
                    <option value="">-- Choose Club Event --</option>
                    <?php foreach ($events as $ev): ?>
                        <option value="<?php echo $ev['id']; ?>" <?php echo $selectedEventId === intval($ev['id']) ? 'selected' : ''; ?>>
                            <?php echo escape($ev['title']); ?> (<?php echo escape($ev['status']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <noscript><button type="submit" class="btn btn-secondary btn-sm">Filter</button></noscript>
            </form>
        </div>

        <?php if ($selectedEventId > 0): ?>
            <h3>Attendee Attendance Registry</h3>
            <p class="text-muted" style="margin-bottom: 15px;">Review the student sign-ups and set present status.</p>

            <form action="attendance.php?event_id=<?php echo $selectedEventId; ?>" method="POST">
                <?php csrfInput(); ?>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40%;">Student Name</th>
                                <th style="width: 30%;">Email Address</th>
                                <th style="width: 30%;">Attendance Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($registrants)): ?>
                                <tr>
                                    <td colspan="3" class="text-muted" style="text-align: center; font-style: italic;">No student registrations found for this event. Students must register for the event first.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($registrants as $reg): ?>
                                    <tr>
                                        <td><strong><?php echo escape($reg['full_name']); ?></strong></td>
                                        <td><?php echo escape($reg['email']); ?></td>
                                        <td>
                                            <!-- Simple checkbox toggle present / absent or radio button -->
                                            <div style="display: inline-flex; gap: 15px;">
                                                <label style="font-weight: normal; cursor: pointer;">
                                                    <input type="radio" name="attendance[<?php echo $reg['user_id']; ?>]" value="present" <?php echo ($reg['attendance_status'] === 'present' || $reg['attendance_status'] === null) ? 'checked' : ''; ?>>
                                                    <span style="color: var(--success); margin-left: 5px; font-weight: 600;">Present</span>
                                                </label>
                                                <label style="font-weight: normal; cursor: pointer;">
                                                    <input type="radio" name="attendance[<?php echo $reg['user_id']; ?>]" value="absent" <?php echo $reg['attendance_status'] === 'absent' ? 'checked' : ''; ?>>
                                                    <span style="color: var(--danger); margin-left: 5px; font-weight: 600;">Absent</span>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($registrants)): ?>
                    <button type="submit" class="btn btn-primary btn-lg" style="margin-top: 20px;">Save Attendance Log</button>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <p class="text-muted" style="font-style: italic;">Please select an event above to start tracking attendance.</p>
        <?php endif; ?>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
