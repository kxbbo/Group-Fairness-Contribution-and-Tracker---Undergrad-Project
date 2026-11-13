-- =====================================================================
-- FairShare: Realistic Demo Seed Data
-- =====================================================================

USE `fairshare_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Seed Users (1 Admin, 1 Instructor, 4 Students for Team FairShare)
-- Default bcrypt hashes for password: "password123"
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `skills`, `availability`) VALUES
(1, 'System Administrator', 'admin@fairshare.edu', '$2y$12$e80yqRkOsqaB6j9M.s.j5OCzF86m845N3Jp26N7lQoQ.F4uJ7bKeq', 'admin', NULL, NULL),
(2, 'Dr. Sarah Mitchell', 'instructor@fairshare.edu', '$2y$12$e80yqRkOsqaB6j9M.s.j5OCzF86m845N3Jp26N7lQoQ.F4uJ7bKeq', 'instructor', NULL, NULL),
(3, 'Alex Johnson', 'alex@student.edu', '$2y$12$e80yqRkOsqaB6j9M.s.j5OCzF86m845N3Jp26N7lQoQ.F4uJ7bKeq', 'student', '["Project Management", "Backend", "Laravel"]', '{"monday": ["10:00-14:00"], "wednesday": ["12:00-16:00"]}'),
(4, 'Brian Chen', 'brian@student.edu', '$2y$12$e80yqRkOsqaB6j9M.s.j5OCzF86m845N3Jp26N7lQoQ.F4uJ7bKeq', 'student', '["Backend", "Database", "MySQL"]', '{"tuesday": ["14:00-18:00"], "thursday": ["10:00-14:00"]}'),
(5, 'Clara Oswald', 'clara@student.edu', '$2y$12$e80yqRkOsqaB6j9M.s.j5OCzF86m845N3Jp26N7lQoQ.F4uJ7bKeq', 'student', '["Frontend", "Bootstrap", "Chart.js"]', '{"monday": ["12:00-16:00"], "friday": ["09:00-13:00"]}'),
(6, 'David Kim', 'david@student.edu', '$2y$12$e80yqRkOsqaB6j9M.s.j5OCzF86m845N3Jp26N7lQoQ.F4uJ7bKeq', 'student', '["Machine Learning", "Python", "FastAPI"]', '{"wednesday": ["14:00-18:00"], "friday": ["14:00-18:00"]}');

-- 2. Seed Course
INSERT INTO `courses` (`id`, `instructor_id`, `code`, `title`, `description`, `team_size_min`, `team_size_max`, `evaluation_rounds_count`, `formula_weights`) VALUES
(1, 2, 'CSE470', 'Software Engineering Capstone', 'Capstone group project course covering full-stack web and ML development.', 3, 5, 2, '{"tasks": 30, "peer_rating": 30, "hours": 15, "evidence": 15, "attendance": 10}');

-- 3. Seed Milestones
INSERT INTO `milestones` (`id`, `course_id`, `title`, `description`, `deadline`, `weight_percent`) VALUES
(1, 1, 'Sprint 1: Architecture & Foundation', 'SRS, ERD, and core project setup.', DATE_ADD(NOW(), INTERVAL 7 DAY), 25.00),
(2, 1, 'Sprint 2: Core Workflows', 'Task boards, evidence vaults, and peer rating forms.', DATE_ADD(NOW(), INTERVAL 14 DAY), 25.00),
(3, 1, 'Sprint 3: AI Service & Analytics', 'Integration with Python service and contribution charts.', DATE_ADD(NOW(), INTERVAL 21 DAY), 25.00),
(4, 1, 'Sprint 4: Final Submission & Defense', 'Final testing, grade adjustments, and demo.', DATE_ADD(NOW(), INTERVAL 28 DAY), 25.00);

-- 4. Seed Team
INSERT INTO `teams` (`id`, `course_id`, `name`, `invite_code`, `leader_id`, `locked`) VALUES
(1, 1, 'Team Alpha', 'ALPHA470', 3, 0);

-- 5. Seed Team Members
INSERT INTO `team_members` (`team_id`, `user_id`, `role_title`, `joined_at`) VALUES
(1, 3, 'Team Leader & PM', NOW()),
(1, 4, 'Backend Lead', NOW()),
(1, 5, 'Frontend Lead', NOW()),
(1, 6, 'AI/ML Lead', NOW());

-- 6. Seed Team Charter
INSERT INTO `charters` (`id`, `team_id`, `roles_description`, `meeting_times`, `late_policy`, `version`, `is_fully_accepted`) VALUES
(1, 1, 'Alex: Setup & Coordination; Brian: Tasks & Workload; Clara: Evidence & Frontend; David: AI & Fairness engine.', 'Wednesdays 4 PM & Fridays 2 PM on Discord/Campus', 'Tasks delayed over 24h without notice deduct 10% from the internal contribution split.', 1, 1);

-- 7. Seed Charter Acceptances
INSERT INTO `charter_acceptances` (`charter_id`, `user_id`, `charter_version`, `accepted_at`) VALUES
(1, 3, 1, NOW()),
(1, 4, 1, NOW()),
(1, 5, 1, NOW()),
(1, 6, 1, NOW());

-- 8. Seed Tasks
INSERT INTO `tasks` (`id`, `team_id`, `milestone_id`, `assignee_id`, `title`, `description`, `difficulty_weight`, `status`, `due_date`, `verified_by`, `verified_at`) VALUES
(1, 1, 1, 3, 'Course and Formula Setup (F1)', 'Implement course creation and weight validation in Laravel.', 3, 'done', NOW(), 4, NOW()),
(2, 1, 1, 4, 'Weighted Task Management (F6)', 'Build Kanban board with difficulty ratings and status transitions.', 3, 'done', NOW(), 3, NOW()),
(3, 1, 1, 5, 'Evidence Vault (F11)', 'File upload system with SHA-256 hash generation.', 2, 'in_review', DATE_ADD(NOW(), INTERVAL 2 DAY), NULL, NULL),
(4, 1, 1, 6, 'Anonymous Peer Evaluation (F16)', 'Create anonymous rating form and storage.', 3, 'in_progress', DATE_ADD(NOW(), INTERVAL 3 DAY), NULL, NULL);

-- 9. Seed Task Verifications
INSERT INTO `task_verifications` (`task_id`, `verifier_id`, `action`, `notes`) VALUES
(1, 4, 'verified', 'Controller, validation, and audit log fully implemented and verified.'),
(2, 3, 'verified', 'Kanban columns and status change policies tested thoroughly.');

-- 10. Seed Evidence Files
INSERT INTO `evidence_files` (`id`, `task_id`, `uploader_id`, `file_name`, `file_path`, `file_size_bytes`, `mime_type`, `version`, `sha256_hash`, `notes`) VALUES
(1, 1, 3, 'CourseController.php', 'uploads/evidence/1/CourseController.php', 4096, 'text/x-php', 1, 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855', 'Initial controller implementation.'),
(2, 2, 4, 'KanbanBoard.blade.php', 'uploads/evidence/2/KanbanBoard.blade.php', 8192, 'text/plain', 1, 'ca978112ca1bbdcafac231b39a23dc4da786eff8147c4e72b9807785afee48bb', 'Blade template for weighted task board.');

-- 11. Seed Time Logs
INSERT INTO `time_logs` (`id`, `user_id`, `task_id`, `start_time`, `end_time`, `minutes`, `work_description`, `approved_by`, `approved_at`, `plausibility_flag`, `plausibility_reason`) VALUES
(1, 3, 1, '2026-11-09 10:00:00', '2026-11-09 13:00:00', 180, 'Built CourseController and form request validation.', 3, NOW(), 0, NULL),
(2, 4, 2, '2026-11-09 14:00:00', '2026-11-09 17:30:00', 210, 'Designed Task migration and Kanban UI.', 3, NOW(), 0, NULL);

-- 12. Seed Meetings and Attendance
INSERT INTO `meetings` (`id`, `team_id`, `title`, `agenda`, `check_in_code`, `code_expires_at`, `starts_at`) VALUES
(1, 1, 'Sprint 1 Kickoff Meeting', 'Assign roles, review SRS and set up Git repository.', 'SEC894', DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW());

INSERT INTO `meeting_attendances` (`meeting_id`, `user_id`, `status`) VALUES
(1, 3, 'present'),
(1, 4, 'present'),
(1, 5, 'present'),
(1, 6, 'present');

-- 13. Seed Peer Evaluation Round
INSERT INTO `peer_rounds` (`id`, `course_id`, `round_number`, `title`, `opens_at`, `closes_at`, `status`) VALUES
(1, 1, 1, 'Mid-term Peer Evaluation Round 1', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 3 DAY), 'open');

SET FOREIGN_KEY_CHECKS = 1;

