# Changelog

All notable changes to the College Club Management System project will be documented in this file.

## [1.0.0] - Initial Setup

### Added
- Created repository directories: `database/`, `config/`, `includes/`, `assets/css/`, `assets/js/`, `student/`, `club-head/`, `admin/`.
- Created robust XAMPP-compatible `database/database.sql` with schema and seed data.
- Created `config/database.php` database connection wrapper utilizing PDO with proper error handling.
- Added default Admin account (`admin@admin.com` / `admin123`).
- Added default clubs and 10 core responsibility categories inside the database.

## [1.1.0] - Authentication and Core Layout Assets

### Added
- Created security helper scripts `includes/auth.php` and `includes/csrf.php`.
- Created helper utility file `includes/functions.php` containing security escape wrappers.
- Created CSS styling variables, dark/light palette elements, responsive tables, and custom controls in `assets/css/style.css`.
- Created `assets/js/script.js` with client-side interaction scripts.
- Implemented core dashboard layout structure using dynamic reusable layouts `includes/header.php`, `includes/navbar.php`, and `includes/footer.php`.

## [1.2.0] - Public Authentication and Roles Control

### Added
- Implemented public student registration `register.php` with server-side role-locking to block elevation.
- Implemented dynamic user logging `login.php` with session rotation to mitigate fixation.
- Added secure logout routines `logout.php`.
- Completed Admin Dashboard (`admin/dashboard.php`) displaying total counts and active announcements.
- Completed Admin User Management panel (`admin/users.php`) supporting account activation/suspension and Club Head selection.

## [1.3.0] - Admin Modules and Global Monitoring

### Added
- Developed complete Club CRUD operations: `admin/clubs.php`, `admin/create-club.php`, and `admin/edit-club.php`.
- Developed Club Head leadership delegation `admin/assign-head.php`.
- Created Designation listings `admin/responsibilities.php` and Student Active memberships log `admin/memberships.php`.
- Built 6 general auditing monitoring grids for administrator tracking: `events.php`, `registrations.php`, `attendance.php`, `announcements.php`, `feedback.php`, and `tasks.php`.

## [1.4.0] - Student Hub & Club Joining

### Added
- Built Student Hub Dashboard (`student/dashboard.php`) displaying stats and active club membership details.
- Built interactive Clubs Browser (`student/clubs.php`) and server-side joiner `student/join-club.php` strictly enforcing "at most one active club per student".
- Built active club peer directory listing (`student/my-club.php`) with leave club action.
- Developed dynamic calendar view (`student/calendar.php`) of upcoming events.

## [1.5.0] - Student Task Progress Board & Event Workflows

### Added
- Built Student event listing (`student/events.php`) and registration script `student/register-event.php`.
- Built My Tasks listing (`student/tasks.php`) and detailed task progress script `student/task.php`.
- Implemented comments/milestones updates inside task-details with strict ownership IDOR validation.
- Built feedback rating script `student/feedback.php` for rating completed events on a 1-5 Star scale.
- Built profile credential modifications (`student/profile.php`) and club announcements browser (`student/announcements.php`).

## [1.6.0] - Club Head Workspace and Coordination

### Added
- Created Club Head Dashboard (`club-head/dashboard.php`) displaying club stats, overdue tasks, and attendee comments.
- Created Club profile viewer `club-head/club.php` and Club Head member delegation control `club-head/members.php` (for assigning positions and removing students).
- Built Club Head events CRUD: `events.php`, `create-event.php`, and `edit-event.php`.
- Built event registration directory `registrations.php` and manual attendance logs tracker `attendance.php`.
- Developed announcement broadcasting dashboard `announcements.php`.
- Developed club event reviews feed (`club-head/feedback.php`) with average rating calculations.
- Developed comprehensive Task Delegation and assignments: `tasks.php`, `create-task.php`, `edit-task.php`, and `task-details.php` with related event linkages and cross-club delegation blocks.
- Added extensive documentation and setup instructions in `README.md`.

## [1.7.0] - Keyboard Accessibility & Focus-Visible Enhancements

### Added
- Improved global accessibility focus-visible outlines for links, buttons, and form inputs.
- Enhanced star-rating feedback widget accessibility by allowing keyboard users to focus and select star-rating radio inputs via visual focus cues on associated labels.

## [1.8.0] - Accessible Action Link Labels in Task Dashboard

### Added
- Added descriptive `aria-label` attributes to "Details & Comments" links in student task board (`student/tasks.php`) to specify the task title for screen readers.

## [1.9.0] - HTTP Security Headers Enhancement

### Added
- Added HTTP response headers (`X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`) in `includes/header.php` before HTML output to protect against clickjacking, MIME-type sniffing, and referrer information disclosure.

## [2.0.0] - Requirements Completion Audit & Feature Enhancements

### Added
- **Database Migration (`database/migration.sql`)**: Updated `memberships` status ENUM (`pending`, `active`, `inactive`, `rejected`), added `leave_status` (`none`, `pending`, `approved`, `rejected`), `requested_at` timestamp, and added `logo`, `email_subject`, and `email_body` columns to `clubs`.
- **Pending Join Request Workflow**: Updated `student/join-club.php` and `student/clubs.php` to submit join requests as `pending`. Strictly enforced server checks for active or pending club status.
- **Responsive One-Club Modal Popup**: Added responsive modal popup with dynamic active club name when a student attempts to join a second club while active, with clear `[OK]` dismissal button.
- **Pending Leave Request Workflow**: Updated `student/my-club.php` allowing students to submit leave requests (`leave_status = 'pending'`). Students remain active members until approved by the Club Head.
- **Club Head Membership Approvals**: Added pending join and leave request management sections in `club-head/members.php` with Approve/Reject actions and strict server-side club ownership checks.
- **Membership Approval Email & Custom Template**: Implemented `sendMembershipApprovalEmail()` in `includes/functions.php` with placeholder support (`{student_name}`, `{club_name}`, `{club_head_name}`, `{student_email}`) and custom template editing in `club-head/club.php`.
- **Club Profile & Image Upload**: Updated `club-head/club.php` to allow Club Heads to edit club description and upload profile image/logo (with 5MB file limit, MIME type & extension checks, and safe unique filename generation).
- **Centralized Announcements Board**: Updated `student/announcements.php` and `club-head/announcements.php` to display announcements from ALL clubs centrally with club name badges.
- **Responsive UI Media Queries**: Updated `assets/css/style.css` with responsive layout rules for mobile and tablet viewports (320px, 375px, 425px, 768px, 1024px, 1366px+).
