# College Club Management System with Event & Task Management

A secure, robust, and lightweight Web Application designed for **BCA 4th-Semester Tribhuvan University (TU), Nepal projects**.

The system leverages vanilla web technologies (**HTML5, CSS3, Vanilla JavaScript, Procedural PHP, and MySQL**) to demonstrate proper database design, user privilege authorization, CRUD operations, event management, and web security principles without utilizing external frameworks, libraries, or APIs.

---

## 🏛️ Project Concept & Domain Model

In modern college environments, student clubs organize events, assign responsibilities, and coordinate tasks. This system serves as a centralized hub supporting:
- **Administration Management**: Full system auditing, club creation, and role delegation.
- **Club Leadership (Club Head)**: Event and announcement broadcasts, manual attendance checks, member role updates, and direct task assignments.
- **Club Membership (Student)**: Event registration, visual calendar views, direct task progress updates (Pending &rarr; In Progress &rarr; Completed), comments/update logs, and star-rating feedback.

### Key Architectural Constraint: **One Active Club Per Student**
To enforce focused student commitment, a student can belong to **at most one active club at any given time**. This constraint is enforced strictly on the server-side inside `join-club.php` to prevent manipulation.

---

## 🏗️ Technology Stack

- **Frontend**: Semantic HTML5, Custom CSS3 Dark/Light Palette, and Vanilla JS.
- **Backend**: Procedural PHP (using safe PDO Database interaction wrappers).
- **Database**: MySQL.
- **Development Environment**: XAMPP (Apache, MySQL, and phpMyAdmin).

---

## 🗃️ Relational Database Schema Design

The relational model consists of **11 interrelated tables** meticulously structured to support cascades and null-assignment constraints:

```
  ┌──────────┐         ┌─────────────┐
  │  USERS   │─────────│ MEMBERSHIPS │─────────┐
  └──────────┘         └─────────────┘         │
       │                      │                │
       │                      ▼                ▼
       │               ┌──────────────┐   ┌─────────┐
       │               │RESPONSIBILITY│   │  CLUBS  │
       │               └──────────────┘   └─────────┘
       │                                       │
       ├──────────────┬────────────────┬───────┤
       │              │                │       │
       ▼              ▼                ▼       ▼
┌──────────────┐┌────────────┐┌──────────────┐┌─────────┐
│REGISTRATIONS ││ ATTENDANCE ││ANNOUNCEMENTS ││  TASKS  │
└──────────────┘└────────────┘└──────────────┘└─────────┘
       │              │                │       │
       └──────────────┼────────────────┼───────┘
                      ▼                ▼
                ┌────────────┐┌──────────────┐
                │  FEEDBACK  ││TASK_COMMENTS │
                └────────────┘└──────────────┘
```

### Table Breakdown:
1. **`users`**: Master student, club head, and administrator accounts. Passwords are securely hashed.
2. **`clubs`**: Registered student clubs linked to a specific head user (`club_head_id`).
3. **`responsibilities`**: Designated organizational leads (e.g. Graphics Lead, Technical Lead).
4. **`memberships`**: Active student club subscriptions (enforces at most one active club per user).
5. **`events`**: Workshops, competitions, hackathons, and activities organized by a club.
6. **`registrations`**: Event subscription ledger for students.
7. **`attendance`**: Manual check-in ledger (Present/Absent) updated by Club Heads.
8. **`announcements`**: Structured club bulletin board posts.
9. **`feedback`**: Constructive feedback and 1-5 Star ratings submitted by event registrants.
10. **`tasks`**: Work items created by Club Heads, connecting roles to deadlines and priorities.
11. **`task_comments`**: Dialogue/progress update logs on assigned tasks.

---

## 🔒 Security Architectures & Project Defense

To score top marks in a viva defense, this project integrates standard web safety measures out-of-the-box:

1. **Password Cryptography**: No plain-text passwords. Utilizes standard PHP `password_hash()` and `password_verify()` with `PASSWORD_DEFAULT`.
2. **Session Hijacking & Fixation Defense**: Session cookies are initialized with `session_start()`. Crucially, `session_regenerate_id(true)` is called immediately upon user verification during login to rotate session identifiers.
3. **Cross-Site Request Forgery (CSRF) Mitigation**: Forms submitting state alterations via POST contain hidden tokens (`csrfInput()`), which are generated cryptographically and verified by the server prior to database updates.
4. **Cross-Site Scripting (XSS) Prevention**: All displayed user-generated contents are passed through an HTML entity encoder helper `escape()` utilizing `htmlspecialchars()`.
5. **SQL Injection Blockade**: Avoids raw SQL concatenation. 100% of the database operations use PDO parameterized prepared statements.
6. **IDOR & Privilege Elevation Prevention**:
   - **No Self-Elevation**: The public register page strictly locks user registration to `role = student` on the server level, preventing malicious requests containing `role=admin` or `role=club_head` from gaining administrative access.
   - **Task/Event Ownership Enforcement**: Enforces that a student can only modify task statuses that match their own `user_id` on the server. Club Heads are restricted from viewing or altering events, attendance logs, announcements, or members of clubs other than their own.

---

## 🚀 Installation & Local Environment Setup

1. **Install XAMPP**: Install XAMPP on your Windows PC.
2. **Clone Project**: Clone or move the project folder to the Apache htdocs directory:
   ```text
   C:\xampp\htdocs\college-club-management\
   ```
3. **Setup Database**:
   - Start the Apache and MySQL modules on the XAMPP Control Panel.
   - Go to `http://localhost/phpmyadmin/` in your web browser.
   - Create a new database named `college_club_management`.
   - Select the database, click the **Import** tab, choose the `database/database.sql` file, and click **Import**.
4. **Run Application**:
   - Access the system through:
     ```text
     http://localhost/college-club-management/
     ```

---
If any file related problem while running locally.
1.  **sudo chmod -R 775 /opt/lampp/htdocs/project/uploads/clubs**
2.  **sudo chown -R daemon:daemon /opt/lampp/htdocs/project/uploads/clubs**

## 🔑 Pre-Seeded Default Test Credentials

| Role | Email | Password | Access Details |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@admin.com` | `admin123` | Can create clubs, manage user accounts/roles, define responsibilities, and monitor overall system activity. |
