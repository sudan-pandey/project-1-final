<?php
// Shared Reusable Sidebar Component
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($pdo)) {
    @include_once __DIR__ . '/../config/database.php';
}
require_once __DIR__ . '/functions.php';

$role = $_SESSION['user_role'] ?? '';
$currentUserId = $_SESSION['user_id'] ?? 0;
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF']);

// Map subpages/child routes to main parent sidebar navigation item
$activeMap = [
    'create-club.php' => 'clubs.php',
    'edit-club.php' => 'clubs.php',
    'assign-head.php' => 'clubs.php',
    'create-event.php' => 'events.php',
    'edit-event.php' => 'events.php',
    'event.php' => 'events.php',
    'register-event.php' => 'events.php',
    'create-task.php' => 'tasks.php',
    'edit-task.php' => 'tasks.php',
    'task-details.php' => 'tasks.php',
    'task.php' => 'tasks.php',
    'join-club.php' => 'clubs.php',
];

$activeTarget = $activeMap[$currentPage] ?? $currentPage;

// Standard monochrome SVG outline icons
$icons = [
    'dashboard' => '<svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>',
    'users' => '<svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
    'clubs' => '<svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-3"></path><path d="M9 9l0 .01"></path><path d="M9 12l0 .01"></path><path d="M9 15l0 .01"></path><path d="M9 18l0 .01"></path></svg>',
    'responsibilities' => '<svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="6"></circle><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"></path></svg>',
    'memberships' => '<svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>',
    'events' => '<svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
    'calendar' => '<svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><path d="M8 14h.01"></path><path d="M12 14h.01"></path><path d="M16 14h.01"></path><path d="M8 18h.01"></path><path d="M12 18h.01"></path></svg>',
    'registrations' => '<svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
    'attendance' => '<svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
    'announcements' => '<svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>',
    'feedback' => '<svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>',
    'tasks' => '<svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>',
    'profile' => '<svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
    'my-club' => '<svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>',
];

// Determine menu items based on user role
$menuItems = [];

if ($role === 'admin') {
    $unreadAnnouncements = isset($pdo) ? getUnreadAnnouncementsCount($pdo, $currentUserId) : 0;
    $pendingTasks = isset($pdo) ? getAllPendingTasksCount($pdo) : 0;

    $menuItems = [
        ['route' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'dashboard'],
        ['route' => 'users.php', 'label' => 'Users / Roles', 'icon' => 'users'],
        ['route' => 'clubs.php', 'label' => 'Clubs', 'icon' => 'clubs'],
        ['route' => 'responsibilities.php', 'label' => 'Responsibilities', 'icon' => 'responsibilities'],
        ['route' => 'memberships.php', 'label' => 'Memberships', 'icon' => 'memberships'],
        ['route' => 'events.php', 'label' => 'Events Directory', 'icon' => 'events'],
        ['route' => 'calendar.php', 'label' => 'Calendar View', 'icon' => 'calendar'],
        ['route' => 'registrations.php', 'label' => 'Event Registrants', 'icon' => 'registrations'],
        ['route' => 'attendance.php', 'label' => 'Attendance Logs', 'icon' => 'attendance'],
        ['route' => 'announcements.php', 'label' => 'Announcements', 'icon' => 'announcements', 'badge' => $unreadAnnouncements],
        ['route' => 'feedback.php', 'label' => 'Feedback & Ratings', 'icon' => 'feedback'],
        ['route' => 'tasks.php', 'label' => 'Task Assignments', 'icon' => 'tasks', 'badge' => $pendingTasks],
        ['route' => 'profile.php', 'label' => 'Profile Settings', 'icon' => 'profile'],
    ];
} elseif ($role === 'club_head') {
    $unreadAnnouncements = isset($pdo) ? getUnreadAnnouncementsCount($pdo, $currentUserId) : 0;
    $pendingTasks = isset($pdo) ? getClubHeadPendingTasksCount($pdo, $currentUserId) : 0;

    $menuItems = [
        ['route' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'dashboard'],
        ['route' => 'club.php', 'label' => 'Club Details', 'icon' => 'clubs'],
        ['route' => 'members.php', 'label' => 'Members List', 'icon' => 'users'],
        ['route' => 'events.php', 'label' => 'Club Events', 'icon' => 'events'],
        ['route' => 'calendar.php', 'label' => 'Calendar View', 'icon' => 'calendar'],
        ['route' => 'registrations.php', 'label' => 'Event Registrations', 'icon' => 'registrations'],
        ['route' => 'attendance.php', 'label' => 'Mark Attendance', 'icon' => 'attendance'],
        ['route' => 'announcements.php', 'label' => 'Announcements', 'icon' => 'announcements', 'badge' => $unreadAnnouncements],
        ['route' => 'feedback.php', 'label' => 'Feedback Reviews', 'icon' => 'feedback'],
        ['route' => 'tasks.php', 'label' => 'Task Coordination', 'icon' => 'tasks', 'badge' => $pendingTasks],
        ['route' => 'profile.php', 'label' => 'Profile Settings', 'icon' => 'profile'],
    ];
} elseif ($role === 'student') {
    if (!isset($membership) && isset($pdo) && !empty($currentUserId)) {
        $membership = getActiveMembership($pdo, $currentUserId);
    }

    $unreadAnnouncements = isset($pdo) ? getUnreadAnnouncementsCount($pdo, $currentUserId) : 0;
    $pendingTasks = isset($pdo) ? getPendingTasksCount($pdo, $currentUserId) : 0;

    $menuItems = [
        ['route' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'dashboard'],
        ['route' => 'clubs.php', 'label' => 'Join Club', 'icon' => 'clubs'],
    ];

    if (!empty($membership)) {
        $menuItems[] = ['route' => 'my-club.php', 'label' => 'My Club', 'icon' => 'my-club'];
        $menuItems[] = ['route' => 'tasks.php', 'label' => 'My Tasks', 'icon' => 'tasks', 'badge' => $pendingTasks];
    }

    $menuItems[] = ['route' => 'announcements.php', 'label' => 'Announcements', 'icon' => 'announcements', 'badge' => $unreadAnnouncements];
    $menuItems[] = ['route' => 'events.php', 'label' => 'Events', 'icon' => 'events'];
    $menuItems[] = ['route' => 'calendar.php', 'label' => 'Calendar View', 'icon' => 'calendar'];
    $menuItems[] = ['route' => 'profile.php', 'label' => 'Profile Settings', 'icon' => 'profile'];
}
?>

<aside class="sidebar" aria-label="Sidebar Navigation">
    <nav>
        <ul class="sidebar-menu">
            <?php foreach ($menuItems as $item): ?>
                <?php $isActive = ($activeTarget === $item['route']); ?>
                <li>
                    <a href="<?php echo $item['route']; ?>"<?php echo $isActive ? ' class="active" aria-current="page"' : ''; ?>>
                        <?php echo $icons[$item['icon']] ?? ''; ?>
                        <span style="flex-grow: 1;"><?php echo htmlspecialchars($item['label']); ?></span>
                        <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
                            <span class="sidebar-badge"><?php echo $item['badge']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>
