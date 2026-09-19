-- Migration Script for College Club Management System Missing Requirements
-- Apply this file to update existing databases safely without dropping data

-- 1. Clubs table updates
ALTER TABLE `clubs` 
  ADD COLUMN IF NOT EXISTS `logo` VARCHAR(255) DEFAULT NULL AFTER `club_head_id`,
  ADD COLUMN IF NOT EXISTS `email_subject` VARCHAR(255) DEFAULT NULL AFTER `logo`,
  ADD COLUMN IF NOT EXISTS `email_body` TEXT DEFAULT NULL AFTER `email_subject`;

-- 2. Memberships table updates
ALTER TABLE `memberships`
  MODIFY COLUMN `status` ENUM('pending', 'active', 'inactive', 'rejected') NOT NULL DEFAULT 'pending',
  ADD COLUMN IF NOT EXISTS `leave_status` ENUM('none', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'none' AFTER `status`,
  ADD COLUMN IF NOT EXISTS `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `leave_status`,
  MODIFY COLUMN `joined_at` TIMESTAMP NULL DEFAULT NULL;

-- 3. Announcement reads tracking table
CREATE TABLE IF NOT EXISTS `announcement_reads` (
  `announcement_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `read_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`announcement_id`, `user_id`),
  FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Announcements table schema updates for scope and priority
-- Ensure column exists as VARCHAR or ENUM first if added manually as VARCHAR(50)
ALTER TABLE `announcements` ADD COLUMN IF NOT EXISTS `scope` VARCHAR(50) NOT NULL DEFAULT 'GLOBAL' AFTER `club_id`;

-- Standardize legacy or user manually added values ('all' -> 'GLOBAL')
UPDATE `announcements` SET `scope` = 'GLOBAL' WHERE `scope` = 'all' OR `scope` IS NULL OR `scope` = '';
UPDATE `announcements` SET `scope` = 'CLUB' WHERE `club_id` IS NOT NULL AND (`scope` = 'GLOBAL' OR `scope` = 'all');

-- Enforce ENUM types on scope and priority
ALTER TABLE `announcements`
  MODIFY COLUMN `scope` ENUM('GLOBAL', 'CLUB', 'PRIVATE') NOT NULL DEFAULT 'GLOBAL',
  MODIFY COLUMN `priority` ENUM('Announcement', 'Urgent', 'Event', 'General') NOT NULL DEFAULT 'Announcement';

-- Standardize general priority entries
UPDATE `announcements` SET `priority` = 'Announcement' WHERE `priority` = 'General';

-- 5. System settings table
CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_key` VARCHAR(50) PRIMARY KEY,
  `setting_value` VARCHAR(255) NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES ('max_student_clubs', '5')
ON DUPLICATE KEY UPDATE `setting_value` = `setting_value`;
