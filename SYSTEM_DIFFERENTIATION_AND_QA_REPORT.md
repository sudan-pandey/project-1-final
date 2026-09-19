# College Club Management System: System Differentiation & Quality Assurance Audit Report

---

## 1. Executive Summary

This document presents a comprehensive **Quality Assurance (QA) Audit** and **System Differentiation Analysis** for the **College Club Management System with Event & Task Management**. Developed specifically to meet the academic and technical standards of Tribhuvan University (TU) BCA 4th-Semester web development criteria, the system provides a lightweight, secure, and robust web platform designed using vanilla web technologies (**HTML5, CSS3, JavaScript, Procedural PHP with PDO, and MySQL**).

Unlike generic educational systems or commercial event platforms, this application incorporates domain-specific constraints (such as the strict **One Active Club Per Student** rule), a multi-tiered **Role-Based Access Control (RBAC)** model, transactional data integrity, custom SMTP email template dispatching, and comprehensive task delegation with comment audit logs—all without external frameworks or third-party library dependencies.

---

## 2. Literature Review & System Differentiation

### 2.1 Contextual Background & Literature Comparison

Traditional educational club and event management systems documented in academic literature generally fall into two categories:

1. **Monolithic Commercial Enterprise Systems** (e.g., CampusGroups, Presence, CampusLabs):
   - *Strengths*: Feature-rich, multi-tenant, cloud-hosted.
   - *Weaknesses*: High operational cost, steep learning curve, heavy resource consumption, complex vendor lock-in, and lack of fine-grained customization for localized university regulations.
2. **Generic Academic Student Projects**:
   - *Strengths*: Simple architecture, easy to deploy.
   - *Weaknesses*: Often plagued by critical OWASP vulnerabilities (SQL Injection, Stored/Reflected XSS, Session Fixation, Unrestricted File Uploads), unnormalized database schemas, lack of transactional safety, and absence of business constraint enforcement (e.g., students joining dozens of clubs simultaneously without active participation).

### 2.2 Key Differentiating Features of This System

The table below highlights how this system differs from standard solutions found in existing academic literature and software repositories:

| Feature / Dimension | Standard Literature / Conventional Systems | This College Club Management System |
| :--- | :--- | :--- |
| **Membership Model** | Unrestricted multi-club membership leading to student burnout and inactive accounts. | **Strict One Active Club Rule**: Enforced at the server level via atomic database transactions to foster meaningful commitment. |
| **Task Delegation** | Simple to-do lists or static text descriptions without progress tracking. | **4-State Task Lifecycle with Comment Audit Logs**: Tasks transition through `Pending` &rarr; `In Progress` &rarr; `Completed` or `Cancelled` with real-time user comment tracking. |
| **Email Dispatch** | Global, unconfigurable notification system or hardcoded email text. | **Club-Specific Custom Email Templates**: Each Club Head can customize approval email subjects and body text using dynamic placeholders (`{student_name}`, `{club_name}`, `{club_head_name}`). |
| **Leadership Model** | Flat membership vs. admin permissions. | **Three-Tiered Privilege Architecture**: Admin, Club Head, and Student with explicit organizational lead designations (e.g., Graphics Lead, Technical Lead). |
| **Security Architecture** | Framework-dependent or vulnerable procedural PHP scripts. | **Zero-Framework Defense-in-Depth**: Integrated CSRF tokens, PDO prepared statements, context-aware HTML escaping, and session rotation on authentication. |
| **Technology Footprint** | Heavy framework dependencies (Node modules, vendor directories). | **Pure Vanilla Tech Stack**: Runs natively on standard XAMPP/LAMP stacks with zero package manager overhead or external dependency vulnerabilities. |

---

## 3. Comprehensive Quality Assurance (QA) Audit Report

### 3.1 QA Audit Scope & Methodology

The QA testing process evaluated the complete system across three core testing paradigms:
1. **Functional Testing**: Verification of all user roles (Admin, Club Head, Student) and CRUD operations.
2. **UI/UX & Ergonomics Testing**: Navigation clarity, responsiveness across viewports, dark/light theme switching, alert messaging, and modal usability.
3. **Security Audit**: OWASP Top 10 vulnerability assessment including input sanitization, authentication durability, access control, and file upload safety.

---

### 3.2 Functional Testing Audit Matrix

| Module | Test Case / Scenario | Inputs / Action | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Auth** | Student Account Registration | Valid name, unique email, matching password | User created with `role='student'`, redirect to login with alert | As expected. Password properly hashed with `password_hash()`. | **PASS** |
| **Auth** | Privilege Escalation Prevention | Form tampering injecting `role=admin` on register | System ignores injected parameters and enforces `role='student'` | As expected. Backend hardcodes role assignment. | **PASS** |
| **Auth** | User Login & Session Rotation | Valid credentials | Session initialized, `session_regenerate_id(true)` called, user redirected to role dashboard | As expected. Old session ID invalidated. | **PASS** |
| **Club** | Club Creation (Admin) | Name: "Robotics Club", description | Club record created in `clubs` table with default NULL head | As expected. Duplicate name prevented by DB constraint. | **PASS** |
| **Club** | Assign Club Head (Admin) | Select user and target club | User role updated to `club_head`, `clubs.club_head_id` updated | As expected. Managed within atomic transaction. | **PASS** |
| **Student**| Join Club Request | Click "Join Club" | Pending record added to `memberships`. Modal shown if already in a club. | As expected. Enforces **One Active Club** rule. | **PASS** |
| **Club Head**| Member Approval & Email Trigger | Click "Approve" on pending join request | Membership updated to `active`, SMTP mail attempt triggered using club template | As expected. Safe fallback if SMTP server is unconfigured. | **PASS** |
| **Club Head**| Member Leave Request Approval | Click "Approve Leave" | Membership updated to `inactive`, active tasks assigned to student auto-cancelled | As expected. Transactional integrity preserved. | **PASS** |
| **Event** | Create Event & Register | Club Head creates event; Student registers | Event listed on calendar; unique registration entry created | As expected. Composite key `(event_id, user_id)` prevents double registration. | **PASS** |
| **Task** | Task Delegation & Commenting | Club Head assigns task; Student posts update comment | Task created; status updated by student; comment saved in `task_comments` | As expected. Cross-user modification forbidden by IDOR check. | **PASS** |
| **Feedback**| Event Star Rating | Student selects 1–5 stars and comment post-event | Feedback entry recorded; summary visible to Club Head & Admin | As expected. Rating bounded between 1 and 5. | **PASS** |

---

### 3.3 UI/UX & Ergonomics Evaluation

#### 1. Responsive & Modular Interface Design
- **Sidebar & Topbar Navigation**: A fixed sidebar paired with a top navigation header provides consistent navigation across all dashboards.
- **Theme Switcher (Dark/Light Mode)**: CSS custom properties (`var(--bg-color)`, `var(--card-bg)`, `var(--text-color)`) dynamically adjust the presentation without page reloads, persisting preference via JavaScript `localStorage`.
- **Visual Status Badges**: Colored status indicators (`Pending` [Orange], `Active`/`Completed` [Green], `Inactive`/`Cancelled` [Red], `Urgent` [Crimson]) allow users to quickly scan tables and lists.

#### 2. User Feedback & Alert System
- **Unified Alert Banner**: Success and error messages are conveyed via standard `.alert .alert-success` and `.alert .alert-danger` banners rendered safely through the `escape()` function.
- **Modal Notifications**: Special modal dialogs inform students when they attempt to join a second club while already holding an active membership.

---

### 3.4 In-Depth OWASP Security Audit

```
┌─────────────────────────────────────────────────────────────────────────┐
│                     SECURITY LAYER ARCHITECTURE                         │
├─────────────────────────────────────────────────────────────────────────┤
│ 1. ROUTE GUARD        : requireLogin() & requireRole('admin'|'head'|..) │
│ 2. CSRF PROTECTION    : hash_equals($_SESSION['csrf_token'], $_POST)    │
│ 3. INPUT PARAMETER    : Filtered / Sanitized inputs                     │
│ 4. DATABASE LAYER     : PDO Parameterized Prepared Statements          │
│ 5. OUTPUT RENDER      : escape() -> htmlspecialchars(..., ENT_QUOTES)   │
│ 6. FILE UPLOAD        : MIME finfo + Extension whitelist + Hex rename   │
└─────────────────────────────────────────────────────────────────────────┘
```

#### 1. Injection Attacks (SQLi & Command Injection)
- **Status**: **FULLY MITIGATED**
- **Evidence**: All database queries utilize PDO prepared statements with bound parameter arrays (e.g., `$stmt->execute([$param1, $param2])`). Zero raw string concatenation was identified in SQL execution paths.

#### 2. Cross-Site Scripting (XSS)
- **Status**: **FULLY MITIGATED**
- **Evidence**: Dynamic user inputs displayed in HTML bodies, attributes, and textareas are filtered using the custom `escape()` function:
  ```php
  function escape($value) {
      return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
  }
  ```

#### 3. Cross-Site Request Forgery (CSRF)
- **Status**: **FULLY MITIGATED**
- **Evidence**: Cryptographically secure tokens are generated per session using `random_bytes(32)`. State-changing POST requests require token submission via `csrfInput()` and validation via `hash_equals()`.

#### 4. Broken Access Control & Indirect Object Reference (IDOR)
- **Status**: **FULLY MITIGATED**
- **Evidence**: Route guards (`requireRole('admin')`, `requireRole('club_head')`, `requireRole('student')`) strictly enforce authorization. Club Heads cannot view or alter events, tasks, or memberships outside their assigned club (`WHERE club_id = ?`). Students cannot update task statuses assigned to other users (`WHERE id = ? AND assigned_to = ?`).

#### 5. Session Fixation & Cryptographic Controls
- **Status**: **FULLY MITIGATED**
- **Evidence**: Password storage uses `password_hash($pass, PASSWORD_DEFAULT)` producing strong BCrypt/Argon2 hashes. Session identifiers are invalidated and regenerated upon successful user authentication using `session_regenerate_id(true)`.

#### 6. Insecure File Upload Handling
- **Status**: **FULLY MITIGATED**
- **Evidence**: Club logo uploads in `club-head/club.php` enforce a strict 5 MB file size ceiling, check both file extension (`.jpg`, `.jpeg`, `.png`, `.webp`) and MIME type using `finfo_file()`, and rename files to randomized hexadecimal strings (`club_1_a3f89e21...png`) to prevent directory traversal and direct code execution.

---

### 3.5 Edge Case & Stress Handling Analysis

1. **Concurrent Club Join Requests**:
   - *Risk*: A student opening multiple browser tabs to submit join requests to different clubs simultaneously.
   - *Mitigation*: Managed using database transactions (`$pdo->beginTransaction()`) and active status verification queries prior to record insertion.
2. **Orphaned Dependent Records on Deletion**:
   - *Risk*: Deleting a user or club causing dangling foreign keys in registration or task logs.
   - *Mitigation*: All foreign key relationships in `database/database.sql` specify appropriate cascading rules (`ON DELETE CASCADE` or `ON DELETE SET NULL`).
3. **Mail Dispatch Failures**:
   - *Risk*: Unconfigured SMTP credentials causing script failure during member approval.
   - *Mitigation*: The mailer module traps PHPMailer/Socket exceptions, records the error, completes the database membership status update, and returns a graceful alert message informing the user that membership was approved despite email delivery failure.

---

### 3.6 QA Defect & Optimization Matrix

The following non-breaking recommendations are identified for future maintenance:

| Severity | Category | Description | Recommendation |
| :--- | :--- | :--- | :--- |
| **Low** | UX / Usability | Pagination is not implemented on high-volume tables (e.g., All Registrations or User Lists). | Implement SQL `LIMIT` and `OFFSET` pagination for datasets exceeding 50 records. |
| **Low** | Operational | Log file `dev_server.log` accumulates output during local development testing. | Add `dev_server.log` to `.gitignore` and implement log rotation. |
| **Low** | Convenience | Password reset / forgotten password flow relies on admin manual update. | Introduce self-service password reset token generation via SMTP mailer. |

---

## 4. Conclusion

The **College Club Management System** satisfies all functional, technical, and security standards expected of a Tribhuvan University BCA 4th-Semester final project. The zero-framework procedural PHP + PDO architecture ensures maximum execution speed, easy hosting across standard Apache/MySQL servers (such as XAMPP or LAMP), and full compliance with standard web security guidelines.
