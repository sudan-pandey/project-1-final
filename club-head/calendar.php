<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('club_head');

$userId = $_SESSION['user_id'];
$club = getOwnClub($pdo, $userId);

$events = [];
if ($club) {
    $clubId = $club['id'];
    try {
        // Fetch non-cancelled events for this club
        $stmt = $pdo->prepare("SELECT id, title, event_date, location, status
                               FROM events
                               WHERE club_id = ? AND status != 'cancelled'
                               ORDER BY event_date ASC");
        $stmt->execute([$clubId]);
        $events = $stmt->fetchAll();
    } catch (PDOException $e) {
        die("Database Error: " . htmlspecialchars($e->getMessage()));
    }
}

// Prepare simple month calendar logic
$month = intval(date('m'));
$year = intval(date('Y'));
$monthName = date('F Y');

$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = intval(date('t', $firstDayOfMonth));
$dayOfWeek = intval(date('w', $firstDayOfMonth)); // 0 (Sunday) to 6 (Saturday)
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <?php require_once '../includes/sidebar.php'; ?>

    <main class="main-content">
        <h2>Club Events Calendar</h2>
        <p class="text-muted">Interactive visual agenda displaying monthly workshops and activities for your club.</p>

        <?php if (!$club): ?>
            <div class="feature-card" style="border-left: 5px solid var(--danger); margin-top: 20px;">
                <h3>🏛️ No Club Assignment</h3>
                <p>You have not been assigned to head a specific club yet. Please reach out to the Administrator to configure your leadership duties.</p>
            </div>
        <?php else: ?>
            <div class="feature-card" style="margin-top: 20px;">
                <h3 style="text-align: center; margin-bottom: 20px;">📅 <?php echo $monthName; ?> - <?php echo escape($club['name']); ?></h3>

                <div class="calendar-wrapper">
                <div class="calendar-grid">
                    <!-- Day Headers -->
                    <div class="calendar-day-header">Sun</div>
                    <div class="calendar-day-header">Mon</div>
                    <div class="calendar-day-header">Tue</div>
                    <div class="calendar-day-header">Wed</div>
                    <div class="calendar-day-header">Thu</div>
                    <div class="calendar-day-header">Fri</div>
                    <div class="calendar-day-header">Sat</div>

                    <!-- Empty days before first day of month -->
                    <?php for ($i = 0; $i < $dayOfWeek; $i++): ?>
                        <div class="calendar-day" style="opacity: 0.3;"></div>
                    <?php endfor; ?>

                    <!-- Days of current month -->
                    <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                        <?php
                        $currentDateString = sprintf("%04d-%02d-%02d", $year, $month, $day);

                        // Filter events happening on this specific day
                        $dayEvents = array_filter($events, function($e) use ($currentDateString) {
                            return date('Y-m-d', strtotime($e['event_date'])) === $currentDateString;
                        });
                        ?>
                        <div class="calendar-day" <?php echo ($currentDateString === date('Y-m-d')) ? 'style="border-color: var(--primary-color); background-color: rgba(79, 70, 229, 0.05);"' : ''; ?>>
                            <div class="day-num"><?php echo $day; ?></div>
                            <?php if (!empty($dayEvents)): ?>
                                <div class="expandable-text calendar-events-container">
                                    <div class="events-list-content">
                                        <?php foreach ($dayEvents as $ev): ?>
                                            <a href="events.php" class="event-tag" title="<?php echo escape($ev['title']); ?>">
                                                <?php echo escape($ev['title']); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
