-- College Club Management System Database Schema
-- For Tribhuvan University BCA 4th Semester Project

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `announcement_reads`;
DROP TABLE IF EXISTS `task_comments`;
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `feedback`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `registrations`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `memberships`;
DROP TABLE IF EXISTS `responsibilities`;
DROP TABLE IF EXISTS `clubs`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'club_head', 'admin') NOT NULL DEFAULT 'student',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Clubs Table
CREATE TABLE `clubs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `club_head_id` INT DEFAULT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `email_subject` VARCHAR(255) DEFAULT NULL,
  `email_body` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`club_head_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Responsibilities Table
CREATE TABLE `responsibilities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Memberships Table (One active club per student)
CREATE TABLE `memberships` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `club_id` INT NOT NULL,
  `responsibility_id` INT DEFAULT NULL,
  `status` ENUM('pending', 'active', 'inactive', 'rejected') NOT NULL DEFAULT 'pending',
  `leave_status` ENUM('none', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'none',
  `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `joined_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`responsibility_id`) REFERENCES `responsibilities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Events Table
CREATE TABLE `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `club_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `event_date` DATETIME NOT NULL,
  `location` VARCHAR(150) NOT NULL,
  `status` ENUM('upcoming', 'completed', 'cancelled') NOT NULL DEFAULT 'upcoming',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Registrations Table
CREATE TABLE `registrations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_event_user_reg` (`event_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Attendance Table
CREATE TABLE `attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `status` ENUM('present', 'absent') NOT NULL DEFAULT 'present',
  `marked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_event_user_attendance` (`event_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Announcements Table
CREATE TABLE `announcements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `club_id` INT DEFAULT NULL,
  `scope` ENUM('GLOBAL', 'CLUB', 'PRIVATE') NOT NULL DEFAULT 'GLOBAL',
  `title` VARCHAR(150) NOT NULL,
  `priority` ENUM('Announcement', 'Urgent', 'Event', 'General') NOT NULL DEFAULT 'Announcement',
  `content` TEXT NOT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Feedback Table
CREATE TABLE `feedback` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `rating` INT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comments` TEXT,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_event_user_feedback` (`event_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Tasks Table
CREATE TABLE `tasks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `club_id` INT NOT NULL,
  `event_id` INT DEFAULT NULL,
  `assigned_to` INT NOT NULL,
  `assigned_by` INT NOT NULL,
  `responsibility_id` INT DEFAULT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `priority` ENUM('Low', 'Medium', 'High', 'Urgent') NOT NULL DEFAULT 'Medium',
  `status` ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `deadline` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`responsibility_id`) REFERENCES `responsibilities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Task Comments Table
CREATE TABLE `task_comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `task_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `comment` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Announcement Reads Table
CREATE TABLE `announcement_reads` (
  `announcement_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `read_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`announcement_id`, `user_id`),
  FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Data

-- Insert default Responsibilities
INSERT INTO `responsibilities` (`id`, `name`, `description`) VALUES
(1, 'Logistics Lead', 'Manages resource allocation, venue setup, and material supply.'),
(2, 'Graphics Lead', 'Handles banner designs, visual elements, and graphics assets.'),
(3, 'Technical Lead', 'Responsible for hardware setups, coding events, and technical assistance.'),
(4, 'Social Media Lead', 'Manages online handles, outreach campaigns, and event promotion.'),
(5, 'Finance Lead', 'Tracks budgets, sponsorships, and internal club expenditures.'),
(6, 'Event Coordinator', 'Coordinates event flows, schedules, and handles guest management.'),
(7, 'Photography Lead', 'Captures moments and takes photos/videos during activities.'),
(8, 'Content Lead', 'Drafts invitations, emails, and press releases for club affairs.'),
(9, 'Public Relations Lead', 'Represents club in inter-college dialogues and external tie-ups.'),
(10, 'Registration Lead', 'Coordinates sign-ups, checks registrations, and takes attendance.');

-- Insert default Admin (Password: admin123)
-- Hash generated via password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `status`) VALUES
(1, 'College Admin', 'admin@admin.com', '$2y$10$8.6FqTXLq.d0eaR9tT6QReKMUE0Bljxa0g7/rJz5XEi.rqSFGvmEu', 'admin', 'active');

-- Insert default Clubs
INSERT INTO `clubs` (`id`, `name`, `description`, `club_head_id`) VALUES
(1, 'Computer Club', 'Club for tech enthusiasts, organizing coding challenges, seminars, and web development workshops.', NULL),
(2, 'Sports Club', 'Organizing intra-college athletic matches, football tournaments, and indoor games.', NULL),
(3, 'Cultural Club', 'Promoting art, drama, music, and literary contributions through exhibitions and talent shows.', NULL);

-- 13. System Settings Table
CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_key` VARCHAR(50) PRIMARY KEY,
  `setting_value` VARCHAR(255) NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES ('max_student_clubs', '5')
ON DUPLICATE KEY UPDATE `setting_value` = `setting_value`;
