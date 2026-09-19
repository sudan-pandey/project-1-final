# Beginner-Friendly Developer Guide & System Flow Architecture

---

## 1. Introduction & Developer Onboarding

Welcome to the **College Club Management System** codebase! This guide is written specifically for future developers, junior software engineers, and BCA students who want to understand, maintain, or extend this application.

### 1.1 Technology Stack & Architectural Pattern

- **Language & Runtime**: Procedural PHP (Version 8.0+)
- **Database Layer**: MySQL / MariaDB via **PHP Data Objects (PDO)** with prepared statements.
- **Frontend Presentation**: Semantic HTML5, Custom CSS3 with Dark/Light Theme variables, and Vanilla JavaScript (ES6). No jQuery or framework dependencies.
- **Architecture**: Modular Procedural Architecture with centralized helper includes for authentication (`includes/auth.php`), security (`includes/csrf.php`), common utilities (`includes/functions.php`), and layout wrappers (`header.php`, `navbar.php`, `sidebar.php`, `footer.php`).

---

## 2. Comprehensive Directory & File Mapping

Every folder and file in this repository has a single, clearly defined responsibility. Below is a complete file-by-file reference.

```
college-club-management/
├── config/                  # Database and server configurations
├── database/                # SQL schema creation and migration scripts
├── includes/                # Shared helpers, layouts, and security functions
├── admin/                   # Administrator control panel scripts
├── club-head/               # Club Head control panel scripts
├── student/                 # Student portal scripts
├── uploads/                 # Uploaded media assets (e.g., club logos)
├── assets/                  # CSS stylesheets and JavaScript files
└── (Root Level Files)       # Entry points, login/register, style, and scripts
```

---

### 2.1 Root Directory Files

| File Name | Purpose & Functionality Description |
| :--- | :--- |
| `index.php` | **Public Landing Page**: Displays welcome banner, quick overview of clubs, system features, and login/register action buttons. |
| `login.php` | **Authentication Entry Point**: Renders the login form, verifies user credentials via `password_verify()`, regenerates session IDs, and redirects users to their role-specific dashboard (`admin/`, `club-head/`, or `student/`). |
| `register.php` | **Student Account Registration**: Allows public users to create a student account. Enforces unique email check, password confirmation, and strictly hardcodes `role='student'` to prevent self-privilege elevation. |
| `logout.php` | **Session Termination**: Clears `$_SESSION` array, destroys the session, expires session cookies, and redirects the user back to `login.php`. |
| `test_approval_email.php` | **Developer SMTP Diagnostic Tool**: Utility script to test SMTP mail configuration defined in `config/mail.php` or `includes/mailer.php`. |
| `style.css` / `assets/css/style.css` | **Global Stylesheet**: Defines CSS custom properties (variables), responsive layout grids, dark/light theme color tokens, forms, tables, buttons, and alert styling. |
| `script.js` / `assets/js/script.js` | **Global Frontend Script**: Handles dark/light mode toggle persistence in `localStorage`, sidebar collapse behavior, modal displays, and form interactions. |
| `README.md` | Project overview, key constraints, table schemas, installation steps, and default test credentials. |
| `changelog.md` | Revision history documenting new features, enhancements, and security improvements. |

---

### 2.2 Configuration Directory (`config/`)

| File Name | Purpose & Functionality Description |
| :--- | :--- |
| `config/database.php` | **Database Connection Setup**: Establishes PDO database connection to MySQL with strict error handling (`PDO::ERRMODE_EXCEPTION`) and utf8mb4 encoding. |
| `config/mail.php.example` | **SMTP Configuration Template**: Example configuration file defining SMTP host, port, username, password, and encryption settings for sending email notifications. |

---

### 2.3 Shared Includes & Core Helpers (`includes/`)

| File Name | Purpose & Functionality Description |
| :--- | :--- |
| `includes/auth.php` | **Authorization & RBAC Middleware**: Defines `requireLogin()`, `requireRole()`, `getActiveMembership()`, and `getOwnClub()`. Enforces route protection across all panels. |
| `includes/csrf.php` | **CSRF Security Engine**: Generates cryptographic CSRF tokens (`generateCSRFToken()`), validates tokens using `hash_equals()` (`verifyCSRFToken()`), and outputs hidden token inputs (`csrfInput()`). |
| `includes/functions.php` | **Global Utilities & Helper Library**: Contains XSS escaping (`escape()`), club logo renderer (`renderClubLogo()`), unread announcement counter (`getUnreadAnnouncementsCount()`), task counters, alert rendering (`displayAlerts()`), task overdue check, and SMTP email dispatch trigger (`sendMembershipApprovalEmail()`). |
| `includes/header.php` | **HTML Head Component**: Standard HTML `<head>` include containing meta tags, title tags, stylesheet links, and script inclusions. |
| `includes/navbar.php` | **Top Navigation Header**: Renders brand logo, top navigation links, current user profile badge, unread announcement indicators, and dark mode toggle button. |
| `includes/sidebar.php` | **Role-Aware Sidebar**: Dynamic vertical menu rendering navigation links based on user role (`admin`, `club_head`, or `student`). |
| `includes/footer.php` | **HTML Footer Component**: Closing HTML tags, copyright notices, and scripts. |
| `includes/mailer.php` | **Standalone SMTP Mailer**: Low-level socket-based or PHPMailer SMTP client implementation that dispatches outgoing email messages. |

---

### 2.4 Administrator Panel (`admin/`)

| File Name | Purpose & Functionality Description |
| :--- | :--- |
| `admin/dashboard.php` | **Admin Executive Overview**: Displays overall system metrics (total clubs, users, events, active memberships, pending tasks). |
| `admin/users.php` | **User Account Directory**: Lists all registered users. Allows Admin to toggle status (`active`/`inactive`), update roles (`student`, `club_head`, `admin`), or delete accounts. |
| `admin/clubs.php` | **Club Management Directory**: View all registered college clubs, inspect assigned Club Heads, and access edit/create tools. |
| `admin/create-club.php` | **Create New Club**: Form for Admin to register a new student club. |
| `admin/edit-club.php` | **Edit Club Details**: Modify name and description of an existing club. |
| `admin/assign-head.php` | **Delegate Club Leadership**: Form to assign or change the Club Head for any college club. |
| `admin/memberships.php` | **Global Membership Auditor**: Admin view of all student club memberships system-wide across all statuses (`pending`, `active`, `inactive`, `rejected`). |
| `admin/responsibilities.php` | **Designation Master Directory**: CRUD management for student leadership roles (e.g. Graphics Lead, Technical Lead). |
| `admin/events.php` | **Global Events Directory**: View all upcoming, completed, or cancelled events organized across all clubs. |
| `admin/registrations.php` | **Event Registration Auditor**: System-wide view of student event registrations. |
| `admin/attendance.php` | **Global Attendance Auditor**: View event attendance records (Present / Absent) across all events. |
| `admin/tasks.php` | **Global Task Monitor**: View all assigned work items, status progress, and deadlines across all clubs. |
| `admin/announcements.php` | **System Announcement Board**: Create and view campus-wide or club-specific announcements. |
| `admin/calendar.php` | **Interactive Visual Calendar**: Visual monthly view of scheduled college events. |
| `admin/feedback.php` | **Global Event Feedback Directory**: Audit student star-ratings and feedback comments for completed events. |

---

### 2.5 Club Head Panel (`club-head/`)

| File Name | Purpose & Functionality Description |
| :--- | :--- |
| `club-head/dashboard.php` | **Club Head Operational Dashboard**: Metrics for own club (active members, pending join/leave requests, upcoming events, open tasks). |
| `club-head/club.php` | **Manage Club Profile & Custom Email**: Update club description, upload club logo, and customize membership approval email template subjects and message body text. |
| `club-head/members.php` | **Club Membership & Lead Delegation**: Review pending student join/leave requests, approve/reject members, assign responsibilities (leads), or remove members. |
| `club-head/responsibilities.php` | **View Club Responsibilities**: Reference directory of available organizational leads. |
| `club-head/events.php` | **Club Events Manager**: List and manage events hosted by own club. |
| `club-head/create-event.php` | **Create Club Event**: Form to create new upcoming workshops, hackathons, or meetings. |
| `club-head/edit-event.php` | **Edit Club Event**: Modify event details, location, date/time, or status (`upcoming`, `completed`, `cancelled`). |
| `club-head/registrations.php` | **Event Attendees List**: View students who registered for own club's events. |
| `club-head/attendance.php` | **Attendance Check-In Ledger**: Mark student event attendees as `Present` or `Absent`. |
| `club-head/tasks.php` | **Task Management Panel**: View, filter, and track status of tasks assigned to club members. |
| `club-head/create-task.php` | **Assign New Task**: Delegate work item to a club member with priority, deadline, and optional event link. |
| `club-head/edit-task.php` | **Edit Task**: Modify title, description, priority, or deadline of an existing task. |
| `club-head/task-details.php` | **Task Detail & Comment Log**: View detailed task progress, change status, and post dialogue comments. |
| `club-head/announcements.php` | **Broadcast Announcements**: Post bulletin announcements to club members. |
| `club-head/calendar.php` | **Club Event Calendar**: Visual event schedule for own club. |
| `club-head/feedback.php` | **Event Ratings & Reviews**: Review student star ratings and feedback submitted for own club events. |

---

### 2.6 Student Portal (`student/`)

| File Name | Purpose & Functionality Description |
| :--- | :--- |
| `student/dashboard.php` | **Student Home Portal**: Personalized dashboard showing active club membership, assigned tasks, upcoming event registrations, and recent announcements. |
| `student/clubs.php` | **Explore College Clubs**: Browse all active college clubs, view logos, descriptions, and request membership. |
| `student/join-club.php` | **Join Club Action Handler**: Backend processor that enforces the **One Active Club Per Student** constraint before creating a pending join request. |
| `student/my-club.php` | **Student's Active Club Hub**: View own club details, leadership, fellow members, and request to leave club. |
| `student/profile.php` | **Student Account Profile**: Update personal details or change account password. |
| `student/events.php` | **Browse All Events**: View upcoming college events with search/filter options. |
| `student/event.php` | **Event Details & Registration**: View full event information, register for events, or cancel registration. |
| `student/register-event.php` | **Event Registration Processor**: Action script processing event sign-ups. |
| `student/tasks.php` | **My Assigned Tasks**: List of tasks assigned to the student by Club Head. |
| `student/task.php` | **Task Detail & Update**: Update task status (`Pending` &rarr; `In Progress` &rarr; `Completed`) and post progress comments. |
| `student/announcements.php` | **Student Bulletin Board**: View announcements and automatically mark them as read. |
| `student/calendar.php` | **Student Personal Calendar**: Visual calendar displaying registered events and task deadlines. |
| `student/feedback.php` | **Submit Event Feedback**: Submit 1–5 star ratings and feedback for events attended. |

---

## 3. Relational Database Schema Architecture

The relational model consists of **12 interrelated tables** structured to ensure data integrity, cascade rules, and normalized relationships.

```
                  ┌──────────────┐
                  │    USERS     │
                  └──────────────┘
                         │
          ┌──────────────┼──────────────┐
          │              │              │
          ▼              ▼              ▼
   ┌────────────┐  ┌──────────┐  ┌──────────────┐
   │ MEMBERSHIPS│  │  CLUBS   │  │ RESPONSIBILITY│
   └────────────┘  └──────────┘  └──────────────┘
          │              │              │
          ├──────────────┼──────────────┤
          │              │              │
          ▼              ▼              ▼
   ┌────────────┐  ┌──────────┐  ┌──────────────┐
   │   EVENTS   │  │  TASKS   │  │ANNOUNCEMENTS │
   └────────────┘  └──────────┘  └──────────────┘
          │              │              │
          ├──────────────┼──────────────┤
          ▼              ▼              ▼
   ┌────────────┐  ┌──────────┐  ┌──────────────┐
   │ATTENDANCE /│  │  TASK    │  │ANNOUNCEMENT  │
   │FEEDBACK /  │  │ COMMENTS │  │    READS     │
   │REGISTRATION│  └──────────┘  └──────────────┘
   └────────────┘
```

### Table Breakdown:
1. `users`: Master user accounts (`id`, `full_name`, `email`, `password`, `role`, `status`).
2. `clubs`: College club entities (`id`, `name`, `description`, `club_head_id`, `logo`, `email_subject`, `email_body`).
3. `responsibilities`: Designation titles for member leadership leads (`id`, `name`, `description`).
4. `memberships`: Student club memberships (`id`, `user_id`, `club_id`, `responsibility_id`, `status`, `leave_status`).
5. `events`: Organized activities (`id`, `club_id`, `title`, `description`, `event_date`, `location`, `status`).
6. `registrations`: Event sign-ups (`id`, `event_id`, `user_id`, `registered_at`). Composite unique key `(event_id, user_id)`.
7. `attendance`: Check-in ledger (`id`, `event_id`, `user_id`, `status`). Composite unique key `(event_id, user_id)`.
8. `announcements`: Bulletin board posts (`id`, `club_id`, `title`, `priority`, `content`, `created_by`).
9. `feedback`: Post-event ratings (`id`, `event_id`, `user_id`, `rating`, `comments`). Composite unique key `(event_id, user_id)`.
10. `tasks`: Assigned work items (`id`, `club_id`, `event_id`, `assigned_to`, `assigned_by`, `responsibility_id`, `title`, `priority`, `status`, `deadline`).
11. `task_comments`: Progress dialogue logs (`id`, `task_id`, `user_id`, `comment`, `created_at`).
12. `announcement_reads`: Read status tracker (`announcement_id`, `user_id`, `read_at`). Primary key `(announcement_id, user_id)`.

---

## 4. End-to-End Core System Workflows

### 4.1 Authentication & Session Handling Flow

```
[ User Submits Login Form ]
         │
         ▼
[ Verify CSRF Token ] ──(Invalid)──> [ Redirect with Error ]
         │ (Valid)
         ▼
[ Fetch User by Email from DB ]
         │
         ▼
[ Verify Password using password_verify() ]
         │
         ├──(Failure)──> [ Display "Invalid Email or Password" ]
         │
         └──(Success)──> [ Check status === 'active' ]
                                  │
                                  ├──(Inactive)──> [ Alert Deactivated ]
                                  │
                                  └──(Active)──> [ session_regenerate_id(true) ]
                                                        │
                                                        ▼
                                                 [ Set $_SESSION & Redirect to Dashboard ]
```

---

### 4.2 Club Membership Join Request & "One Active Club" Constraint Flow

```
[ Student Clicks "Join Club" ]
         │
         ▼
[ student/join-club.php ] ──> [ Begin PDO Transaction ]
                                       │
                                       ▼
                       [ Query Active Membership for User ]
                                       │
                    ┌──────────────────┴──────────────────┐
                    │                                     │
           (Active Club Exists)                       (No Active Club)
                    │                                     │
                    ▼                                     ▼
        [ Set Modal Session Flag ]               [ Check Pending Request ]
        [ Rollback Transaction ]                          │
        [ Redirect to clubs.php ]              ┌──────────┴──────────┐
        [ Show "One Club" Modal ]              │                     │
                                        (Pending Exists)        (No Pending)
                                               │                     │
                                               ▼                     ▼
                                      [ Show Error Alert ]    [ Insert Membership ]
                                                              [ status = 'pending' ]
                                                              [ Commit Transaction ]
                                                              [ Show Success Alert ]
```

---

### 4.3 Member Approval & Custom SMTP Email Dispatch Flow

```
[ Club Head Clicks "Approve Join Request" ]
         │
         ▼
[ Verify CSRF & Ownership Check (club_id === Head's Club) ]
         │
         ▼
[ UPDATE memberships SET status = 'active', joined_at = NOW() ]
         │
         ▼
[ Execute sendMembershipApprovalEmail() ]
         │
         ▼
[ Query Club Custom Email Subject & Body Templates ]
         │
         ▼
[ Replace Placeholders: {student_name}, {club_name}, {club_head_name} ]
         │
         ▼
[ Call sendSmtpEmail() via mailer.php ]
         │
         ├──(SMTP Success)──> [ Display "Approved & Email Sent!" ]
         └──(SMTP Fallback)──> [ Display "Approved! (Email delivery failed)" ]
```

---

### 4.4 Task Lifecycle & Progress Commenting Flow

```
[ Club Head Creates Task ] ──> [ Status = 'pending' ]
                                       │
                                       ▼
                      [ Student Views Task in Portal ]
                                       │
                                       ▼
                   [ Student Updates Status to 'in_progress' ]
                                       │
                                       ▼
                      [ Student / Head Adds Comment ]
                                       │
                                       ▼
                  [ INSERT INTO task_comments RECORD ]
                                       │
                                       ▼
                   [ Student Marks Status as 'completed' ]
                                       │
                                       ▼
                   [ System Sets completed_at = NOW() ]
```

---

## 5. Security Best Practices for Future Developers

When writing new features or modifying existing pages, always follow these rules:

1. **Route Protection**: Start every protected page with:
   ```php
   require_once '../config/database.php';
   require_once '../includes/auth.php';
   require_once '../includes/functions.php';
   require_once '../includes/csrf.php';

   requireRole('student'); // or 'club_head', 'admin'
   ```
2. **CSRF Enforcement**: Include `<?php csrfInput(); ?>` in every POST form, and verify at the top of script:
   ```php
   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
       if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
           die("CSRF token validation failed.");
       }
   }
   ```
3. **Database Security**: Never concatenate user inputs into SQL strings. Always use prepared statements:
   ```php
   $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
   $stmt->execute([$userEmail]);
   ```
4. **XSS Prevention**: Always escape output rendered into HTML:
   ```php
   echo escape($userInput);
   ```
5. **Ownership & Access Control Checks**: Always verify that the requested entity belongs to the currently logged-in user or their assigned club before performing updates or deletions.
