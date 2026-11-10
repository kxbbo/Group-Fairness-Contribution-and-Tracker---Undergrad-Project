-- =====================================================================
-- FairShare: Group Project Contribution and Fairness Tracker
-- Database: MySQL 8.0+ / InnoDB
-- Charset: utf8mb4 / utf8mb4_unicode_ci
-- Compatible with: Laravel 11 Eloquent ORM & Python AI Microservice
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `fairshare_db`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `fairshare_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Table: users (Core Authentication, Roles, Availability & Skills)
-- Supports: System Auth, F4 (Skill-Based Team Suggestion)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `email_verified_at` TIMESTAMP NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'instructor', 'student') NOT NULL DEFAULT 'student',
    `skills` JSON NULL COMMENT 'JSON array of skills and proficiency levels (e.g., ["Laravel", "Python", "Frontend"])',
    `availability` JSON NULL COMMENT 'JSON schedule of weekly availability slots for F4 algorithm',
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: courses (Course Configuration & Fairness Formula)
-- Supports: F1 (Course and Fairness-Formula Setup)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `courses`;
CREATE TABLE `courses` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `instructor_id` BIGINT UNSIGNED NOT NULL,
    `code` VARCHAR(50) NOT NULL COMMENT 'e.g. CSE470',
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `team_size_min` TINYINT UNSIGNED NOT NULL DEFAULT 3,
    `team_size_max` TINYINT UNSIGNED NOT NULL DEFAULT 5,
    `evaluation_rounds_count` TINYINT UNSIGNED NOT NULL DEFAULT 2,
    `formula_weights` JSON NOT NULL COMMENT '{"tasks": 30, "peer_rating": 30, "hours": 15, "evidence": 15, "attendance": 10} - Must sum to 100',
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_courses_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_courses_instructor` (`instructor_id`),
    INDEX `idx_courses_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: milestones (Course Milestones & Deadlines)
-- Supports: F1 (Milestones), F15 (Rush Detector baseline)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `milestones`;
CREATE TABLE `milestones` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `course_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `deadline` DATETIME NOT NULL,
    `weight_percent` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_milestones_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
    INDEX `idx_milestones_course_deadline` (`course_id`, `deadline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: teams (Team Setup, Invite Codes & Leadership)
-- Supports: F2 (Team Formation and Management)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `teams`;
CREATE TABLE `teams` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `course_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `invite_code` VARCHAR(32) NOT NULL UNIQUE,
    `leader_id` BIGINT UNSIGNED NULL,
    `locked` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'When locked, no student can join or transfer',
    `locked_at` DATETIME NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_teams_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_teams_leader` FOREIGN KEY (`leader_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_teams_course` (`course_id`),
    INDEX `idx_teams_invite_code` (`invite_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: team_members (Membership and Transfer Audit Trail)
-- Supports: F2 (Transfer History), F5 (Team Pulse Dashboard)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `team_members`;
CREATE TABLE `team_members` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `role_title` VARCHAR(100) NULL DEFAULT 'Member' COMMENT 'Optional designated role like Frontend, Backend, QA',
    `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `left_at` DATETIME NULL COMMENT 'Non-null indicates member transferred out to preserve historical audit',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_team_members_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_team_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_team_members_team_user` (`team_id`, `user_id`),
    INDEX `idx_team_members_active` (`team_id`, `left_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: charters (Digital Team Charters)
-- Supports: F3 (Digital Team Charter)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `charters`;
CREATE TABLE `charters` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `roles_description` TEXT NULL COMMENT 'Defined team roles & responsibilities',
    `meeting_times` TEXT NULL COMMENT 'Agreed recurring meeting schedules',
    `late_policy` TEXT NULL COMMENT 'Agreed policy regarding overdue submissions and penalties',
    `version` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Increments on edits; edits invalidate previous acceptances',
    `is_fully_accepted` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_charters_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
    INDEX `idx_charters_team` (`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: charter_acceptances (Charter Sign-off Audit)
-- Supports: F3 (Digital Team Charter Acceptance)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `charter_acceptances`;
CREATE TABLE `charter_acceptances` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `charter_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `charter_version` INT UNSIGNED NOT NULL DEFAULT 1,
    `accepted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_charter_acceptances_charter` FOREIGN KEY (`charter_id`) REFERENCES `charters` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_charter_acceptances_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_charter_user_version` (`charter_id`, `user_id`, `charter_version`),
    INDEX `idx_charter_acceptances_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: tasks (Weighted Kanban Tasks)
-- Supports: F6 (Weighted Task Management), F7 (Peer-Verified Completion)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `tasks`;
CREATE TABLE `tasks` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `milestone_id` BIGINT UNSIGNED NULL,
    `assignee_id` BIGINT UNSIGNED NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `difficulty_weight` TINYINT UNSIGNED NOT NULL DEFAULT 1 CHECK (`difficulty_weight` BETWEEN 1 AND 5),
    `status` ENUM('to_do', 'in_progress', 'in_review', 'done') NOT NULL DEFAULT 'to_do',
    `due_date` DATETIME NULL,
    `verified_by` BIGINT UNSIGNED NULL COMMENT 'Cannot be assignee_id; teammate who verified work',
    `verified_at` DATETIME NULL,
    `return_reason` TEXT NULL COMMENT 'Mandatory reason if reviewer returns task',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_tasks_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tasks_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `milestones` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tasks_assignee` FOREIGN KEY (`assignee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tasks_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_tasks_team_status` (`team_id`, `status`),
    INDEX `idx_tasks_assignee` (`assignee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: task_verifications (Verification & Return Audit History)
-- Supports: F7 (Peer-Verified Completion)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `task_verifications`;
CREATE TABLE `task_verifications` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` BIGINT UNSIGNED NOT NULL,
    `verifier_id` BIGINT UNSIGNED NOT NULL,
    `action` ENUM('verified', 'returned') NOT NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_task_verifications_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_task_verifications_verifier` FOREIGN KEY (`verifier_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_task_verifications_task` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: contribution_splits (Shared Task Contribution Split)
-- Supports: F9 (Shared-Task Contribution Split)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `contribution_splits`;
CREATE TABLE `contribution_splits` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `declared_percent` DECIMAL(5, 2) NOT NULL CHECK (`declared_percent` BETWEEN 0.00 AND 100.00),
    `status` ENUM('pending', 'confirmed', 'disputed') NOT NULL DEFAULT 'pending',
    `confirmed_by` BIGINT UNSIGNED NULL,
    `confirmed_at` DATETIME NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_splits_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_splits_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_splits_confirmed_by` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    UNIQUE KEY `uk_task_user_split` (`task_id`, `user_id`),
    INDEX `idx_splits_task` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: task_handovers (Task Handover & Fractional Credit Log)
-- Supports: F10 (Handover Credit Log)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `task_handovers`;
CREATE TABLE `task_handovers` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` BIGINT UNSIGNED NOT NULL,
    `from_user_id` BIGINT UNSIGNED NOT NULL,
    `to_user_id` BIGINT UNSIGNED NOT NULL,
    `percent_done` DECIMAL(5, 2) NOT NULL CHECK (`percent_done` BETWEEN 0.00 AND 100.00),
    `handover_note` TEXT NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_handovers_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_handovers_from_user` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_handovers_to_user` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_handovers_task` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: evidence_files (Evidence Vault with SHA-256 Hashing)
-- Supports: F11 (Evidence Vault)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `evidence_files`;
CREATE TABLE `evidence_files` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` BIGINT UNSIGNED NOT NULL,
    `uploader_id` BIGINT UNSIGNED NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_size_bytes` BIGINT UNSIGNED NOT NULL,
    `mime_type` VARCHAR(100) NULL,
    `version` INT UNSIGNED NOT NULL DEFAULT 1,
    `sha256_hash` CHAR(64) NOT NULL COMMENT 'SHA-256 checksum for tamper-evidence',
    `is_altered` BOOLEAN NOT NULL DEFAULT FALSE,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_evidence_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_evidence_uploader` FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_evidence_task` (`task_id`),
    INDEX `idx_evidence_uploader` (`uploader_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: time_logs (Time Logs with Plausibility Flags)
-- Supports: F12 (Time Logging with Plausibility Checks)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `time_logs`;
CREATE TABLE `time_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `task_id` BIGINT UNSIGNED NOT NULL,
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME NOT NULL,
    `minutes` INT UNSIGNED NOT NULL,
    `work_description` TEXT NULL,
    `approved_by` BIGINT UNSIGNED NULL COMMENT 'Leader or instructor approval ID',
    `approved_at` DATETIME NULL,
    `plausibility_flag` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'True if flagged (e.g., excessive hours without evidence)',
    `plausibility_reason` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_time_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_time_logs_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_time_logs_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_time_logs_user_times` (`user_id`, `start_time`, `end_time`),
    INDEX `idx_time_logs_task` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: meetings (Team Meetings & Rotating Check-in Codes)
-- Supports: F13 (Meeting Attendance and Action Items)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `meetings`;
CREATE TABLE `meetings` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `agenda` TEXT NOT NULL,
    `minutes` TEXT NULL,
    `check_in_code` VARCHAR(32) NOT NULL,
    `code_expires_at` DATETIME NOT NULL,
    `starts_at` DATETIME NOT NULL,
    `ends_at` DATETIME NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_meetings_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
    INDEX `idx_meetings_team` (`team_id`),
    INDEX `idx_meetings_code` (`check_in_code`, `code_expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: meeting_attendances (Meeting Check-in Records)
-- Supports: F13 (Meeting Attendance)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `meeting_attendances`;
CREATE TABLE `meeting_attendances` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `meeting_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `check_in_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('present', 'late', 'absent', 'excused') NOT NULL DEFAULT 'present',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_attendance_meeting` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_attendance_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_meeting_user` (`meeting_id`, `user_id`),
    INDEX `idx_attendance_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: meeting_action_items (Action Items converted into Tasks)
-- Supports: F13 (Meeting Attendance and Action Items)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `meeting_action_items`;
CREATE TABLE `meeting_action_items` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `meeting_id` BIGINT UNSIGNED NOT NULL,
    `converted_task_id` BIGINT UNSIGNED NULL COMMENT 'Linked Task if converted',
    `assignee_id` BIGINT UNSIGNED NULL,
    `item_description` TEXT NOT NULL,
    `due_date` DATE NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_action_items_meeting` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_action_items_task` FOREIGN KEY (`converted_task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_action_items_assignee` FOREIGN KEY (`assignee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_action_items_meeting` (`meeting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: peer_rounds (Anonymous Peer Evaluation Rounds)
-- Supports: F16 (Anonymous Peer Evaluation Rounds)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `peer_rounds`;
CREATE TABLE `peer_rounds` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `course_id` BIGINT UNSIGNED NOT NULL,
    `round_number` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `title` VARCHAR(255) NOT NULL,
    `opens_at` DATETIME NOT NULL,
    `closes_at` DATETIME NOT NULL,
    `status` ENUM('scheduled', 'open', 'closed', 'processed') NOT NULL DEFAULT 'scheduled',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_peer_rounds_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
    INDEX `idx_peer_rounds_course_status` (`course_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: peer_ratings (Anonymous Peer Ratings Matrix)
-- Supports: F16 (Anonymous Peer Evaluation Rounds)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `peer_ratings`;
CREATE TABLE `peer_ratings` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `round_id` BIGINT UNSIGNED NOT NULL,
    `rater_id` BIGINT UNSIGNED NOT NULL,
    `ratee_id` BIGINT UNSIGNED NOT NULL,
    `effort` TINYINT UNSIGNED NOT NULL CHECK (`effort` BETWEEN 1 AND 5),
    `reliability` TINYINT UNSIGNED NOT NULL CHECK (`reliability` BETWEEN 1 AND 5),
    `communication` TINYINT UNSIGNED NOT NULL CHECK (`communication` BETWEEN 1 AND 5),
    `quality` TINYINT UNSIGNED NOT NULL CHECK (`quality` BETWEEN 1 AND 5),
    `comments` TEXT NULL,
    `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_peer_ratings_round` FOREIGN KEY (`round_id`) REFERENCES `peer_rounds` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_peer_ratings_rater` FOREIGN KEY (`rater_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_peer_ratings_ratee` FOREIGN KEY (`ratee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chk_no_self_rating` CHECK (`rater_id` <> `ratee_id`),
    UNIQUE KEY `uk_round_rater_ratee` (`round_id`, `rater_id`, `ratee_id`),
    INDEX `idx_peer_ratings_ratee` (`ratee_id`),
    INDEX `idx_peer_ratings_round` (`round_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: rating_flags (AI Rating Integrity Flags)
-- Supports: F17 (AI Rating Integrity Check via Python Microservice)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `rating_flags`;
CREATE TABLE `rating_flags` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `round_id` BIGINT UNSIGNED NOT NULL,
    `rater_id` BIGINT UNSIGNED NOT NULL,
    `flag_type` ENUM('uniform_scoring', 'reciprocal_inflation', 'extreme_outlier', 'ml_anomaly') NOT NULL,
    `anomaly_score` DECIMAL(6, 4) NULL,
    `severity` ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    `flag_details` JSON NULL COMMENT 'Specific features/scores that triggered flag',
    `is_reviewed` BOOLEAN NOT NULL DEFAULT FALSE,
    `reviewed_by` BIGINT UNSIGNED NULL,
    `reviewed_at` DATETIME NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_rating_flags_round` FOREIGN KEY (`round_id`) REFERENCES `peer_rounds` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rating_flags_rater` FOREIGN KEY (`rater_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rating_flags_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_rating_flags_round` (`round_id`),
    INDEX `idx_rating_flags_rater` (`rater_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: fairness_scores (Calculated Explainable Fairness Scores)
-- Supports: F18 (Explainable Fairness Score Engine & What-If Simulator)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `fairness_scores`;
CREATE TABLE `fairness_scores` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `task_score` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `peer_score` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `hours_score` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `evidence_score` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `attendance_score` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `plausibility_deduction` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `components_breakdown` JSON NOT NULL COMMENT 'Full mathematical breakdown of normalized metrics',
    `total_fairness_score` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `computed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_fairness_scores_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fairness_scores_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
    INDEX `idx_fairness_scores_user_team` (`user_id`, `team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: team_health (Team Health & Early Risk Predictions)
-- Supports: F19 (Team Health and Risk Detection)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `team_health`;
CREATE TABLE `team_health` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('on_track', 'at_risk', 'critical') NOT NULL DEFAULT 'on_track',
    `progress_gap_percent` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `inactivity_days` INT UNSIGNED NOT NULL DEFAULT 0,
    `overdue_tasks_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `workload_imbalance_ratio` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `rush_ratio` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `reasons` JSON NOT NULL COMMENT 'Specific contributing triggers (e.g. ["High inactivity", "Overdue tasks > 3"])',
    `detected_by` ENUM('ml_model', 'rule_fallback') NOT NULL DEFAULT 'rule_fallback',
    `computed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_team_health_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
    INDEX `idx_team_health_team` (`team_id`),
    INDEX `idx_team_health_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: concerns (Private / Confidential Student Concerns)
-- Supports: F20 (Private Concern Reporting and Resolution)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `concerns`;
CREATE TABLE `concerns` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('pending', 'under_review', 'resolved', 'dismissed') NOT NULL DEFAULT 'pending',
    `instructor_response` TEXT NULL,
    `responded_by` BIGINT UNSIGNED NULL,
    `responded_at` DATETIME NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_concerns_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_concerns_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_concerns_instructor` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_concerns_user` (`user_id`),
    INDEX `idx_concerns_team` (`team_id`),
    INDEX `idx_concerns_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: grade_suggestions (Suggested Contribution Factors & Overrides)
-- Supports: F20 (Grade-Adjustment Suggestions)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `grade_suggestions`;
CREATE TABLE `grade_suggestions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `team_id` BIGINT UNSIGNED NOT NULL,
    `course_id` BIGINT UNSIGNED NOT NULL,
    `fairness_score_id` BIGINT UNSIGNED NULL,
    `suggested_factor` DECIMAL(4, 2) NOT NULL DEFAULT 1.00 COMMENT 'e.g. 1.05 or 0.85 multiplier on group grade',
    `final_factor` DECIMAL(4, 2) NOT NULL DEFAULT 1.00,
    `is_overridden` BOOLEAN NOT NULL DEFAULT FALSE,
    `override_reason` TEXT NULL COMMENT 'Mandatory written reason if final_factor <> suggested_factor',
    `instructor_id` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_grade_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_grade_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_grade_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_grade_fairness_score` FOREIGN KEY (`fairness_score_id`) REFERENCES `fairness_scores` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_grade_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_grade_user_team` (`user_id`, `team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: audit_logs (Append-Only Tamper-Evident System Audit Trail)
-- Supports: System Integrity, Security Non-Functional Requirements
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `actor_id` BIGINT UNSIGNED NULL COMMENT 'User who performed the action, null if system',
    `action` VARCHAR(100) NOT NULL COMMENT 'e.g. "task_moved", "weights_updated", "file_uploaded"',
    `entity_type` VARCHAR(100) NOT NULL COMMENT 'e.g. "Task", "Course", "EvidenceFile"',
    `entity_id` BIGINT UNSIGNED NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_audit_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_audit_entity` (`entity_type`, `entity_id`),
    INDEX `idx_audit_actor` (`actor_id`),
    INDEX `idx_audit_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

