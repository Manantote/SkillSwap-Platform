-- ================================================================
-- TEST SEED — Quiz & Certificate System
-- Seeds fake completed sessions + learning_progress so user 1
-- (Manan) is immediately eligible to take the quiz for Python (skill_id=5)
-- Run: mysql -u root --socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock skillswap_new < config/seed_test_sessions.sql
-- ================================================================

USE skillswap;

-- ── 1. Seed 3 completed sessions (learner=1, teacher=2, skill=Python) ─────────
INSERT IGNORE INTO sessions (learner_id, teacher_id, skill_id, start_time, end_time, duration_minutes, status)
VALUES
  (1, 2, 5, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY + INTERVAL 25 MINUTE, 25, 'completed'),
  (1, 2, 5, NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY + INTERVAL 30 MINUTE, 30, 'completed'),
  (1, 2, 5, NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY + INTERVAL 20 MINUTE, 20, 'completed');

-- ── 2. Seed learning_progress — 3 sessions, 75 minutes total → eligible ──────
INSERT INTO learning_progress (user_id, skill_id, sessions_completed, total_time, status)
VALUES (1, 5, 3, 75, 'learning')
ON DUPLICATE KEY UPDATE
  sessions_completed = 3,
  total_time         = 75,
  status             = 'learning';

-- ── Verify ────────────────────────────────────────────────────────────────────
SELECT 'Sessions seeded:' AS info, COUNT(*) AS count
  FROM sessions WHERE learner_id = 1 AND skill_id = 5;

SELECT 'Progress record:' AS info, sessions_completed, total_time, status
  FROM learning_progress WHERE user_id = 1 AND skill_id = 5;
