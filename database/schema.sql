-- Database Schema for Gym Membership & Class Management System
-- Database: gym_system
-- Storage Engine: InnoDB
-- Character Set: utf8mb4

CREATE DATABASE IF NOT EXISTS `gym_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `gym_system`;

-- 1. Member statuses lookup table
CREATE TABLE IF NOT EXISTS `member_statuses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `status_name` VARCHAR(50) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Instructors lookup table
CREATE TABLE IF NOT EXISTS `instructors` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Membership plans lookup table
CREATE TABLE IF NOT EXISTS `membership_plans` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `plan_name` VARCHAR(100) NOT NULL,
    `duration_days` INT NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Members table
CREATE TABLE IF NOT EXISTS `members` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(20) NOT NULL,
    `date_of_birth` DATE NOT NULL,
    `status_id` INT NOT NULL,
    `joined_on` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_members_status` FOREIGN KEY (`status_id`) REFERENCES `member_statuses` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Memberships table
CREATE TABLE IF NOT EXISTS `memberships` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `member_id` INT NOT NULL,
    `plan_id` INT NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_memberships_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_memberships_plan` FOREIGN KEY (`plan_id`) REFERENCES `membership_plans` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Classes table
CREATE TABLE IF NOT EXISTS `classes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `class_name` VARCHAR(100) NOT NULL,
    `instructor_id` INT NOT NULL,
    `class_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `capacity` INT NOT NULL,
    `room` VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_classes_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Class registrations table
CREATE TABLE IF NOT EXISTS `registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `member_id` INT NOT NULL,
    `class_id` INT NOT NULL,
    `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `attendance_status` VARCHAR(20) NOT NULL DEFAULT 'Registered',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_member_class` (`member_id`, `class_id`),
    CONSTRAINT `fk_registrations_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_registrations_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. System users / admin accounts table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` VARCHAR(20) NOT NULL DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed data for member_statuses
INSERT INTO `member_statuses` (`id`, `status_name`) VALUES
(1, 'Active'),
(2, 'Expired'),
(3, 'Suspended')
ON DUPLICATE KEY UPDATE `status_name` = VALUES(`status_name`);

-- Seed data for instructors
INSERT INTO `instructors` (`id`, `full_name`, `email`) VALUES
(1, 'Marcus Vance', 'marcus.vance@example.com'),
(2, 'Elena Rostova', 'elena.rostova@example.com'),
(3, 'David Kim', 'david.kim@example.com')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- Seed data for membership_plans
INSERT INTO `membership_plans` (`id`, `plan_name`, `duration_days`, `price`) VALUES
(1, 'Monthly Basic', 30, 45.00),
(2, 'Quarterly Standard', 90, 120.00),
(3, 'Annual Premium', 365, 420.00)
ON DUPLICATE KEY UPDATE `plan_name` = VALUES(`plan_name`);

-- Seed sample members
INSERT INTO `members` (`id`, `first_name`, `last_name`, `email`, `phone`, `date_of_birth`, `status_id`, `joined_on`) VALUES
(1, 'Alex', 'Turner', 'alex.turner@example.com', '555-0101', '1990-05-15', 1, '2026-01-10'),
(2, 'Jessica', 'Alba', 'jessica.alba@example.com', '555-0102', '1988-08-22', 1, '2026-02-01'),
(3, 'Robert', 'Chen', 'robert.chen@example.com', '555-0103', '1995-11-30', 2, '2025-11-01')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- Seed sample memberships
INSERT INTO `memberships` (`id`, `member_id`, `plan_id`, `start_date`, `end_date`, `status`) VALUES
(1, 1, 1, CURDATE() - INTERVAL 10 DAY, CURDATE() + INTERVAL 20 DAY, 'Active'),
(2, 2, 2, CURDATE() - INTERVAL 85 DAY, CURDATE() + INTERVAL 5 DAY, 'Active'),
(3, 3, 1, CURDATE() - INTERVAL 60 DAY, CURDATE() - INTERVAL 30 DAY, 'Expired')
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

-- Seed sample classes
INSERT INTO `classes` (`id`, `class_name`, `instructor_id`, `class_date`, `start_time`, `capacity`, `room`) VALUES
(1, 'Morning HIIT Blast', 1, CURDATE(), '08:00:00', 12, 'Studio A'),
(2, 'Vinyasa Flow Yoga', 2, CURDATE(), '10:30:00', 15, 'Studio B'),
(3, 'Strength & Conditioning', 3, CURDATE() + INTERVAL 1 DAY, '17:00:00', 10, 'Weight Room')
ON DUPLICATE KEY UPDATE `class_name` = VALUES(`class_name`);

-- Seed sample registrations
INSERT INTO `registrations` (`id`, `member_id`, `class_id`, `attendance_status`) VALUES
(1, 1, 1, 'Registered'),
(2, 2, 1, 'Registered')
ON DUPLICATE KEY UPDATE `attendance_status` = VALUES(`attendance_status`);

-- Seed default admin user (credentials: admin / admin123)
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`) VALUES
(1, 'admin', 'admin@apexfitness.local', '$2y$10$tX4QrijBGcgUzPxVtHEFfuTwawcjTwunDc3iFGcNoCwPmsg1bs9IO', 'System Administrator', 'admin')
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`);
