<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('admin');

try {
    // Fetch upcoming events from all clubs to list on calendar
    $stmt = $pdo->query("SELECT e.id, e.title, e.event_date, e.location, e.status, c.name AS club_name
                           FROM events e
                           JOIN clubs c ON e.club_id = c.id
                           WHERE e.status != 'cancelled'
                           ORDER BY e.event_date ASC");
    $events = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
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
        <h2>Global Events Calendar</h2>
        <p class="text-muted">Master visual schedule displaying monthly workshops and activities across all clubs.</p>

        <div class="feature-card" style="margin-top: 20px;">
            <h3 style="text-align: center; margin-bottom: 20px;">📅 <?php echo $monthName; ?></h3>

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
                                        <a href="events.php" class="event-tag" title="<?php echo escape($ev['club_name'] . ': ' . $ev['title']); ?>">
                                            [<?php echo escape($ev['club_name']); ?>] <?php echo escape($ev['title']); ?>
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
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
