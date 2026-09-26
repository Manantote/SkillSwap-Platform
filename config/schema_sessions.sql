-- ================================================================
-- Skill Swap — Session + Quiz + Certificate Schema
-- Run AFTER existing schema:
--   mysql -u root skillswap_new < config/schema_sessions.sql
-- ================================================================

USE skillswap;

-- ── 1. Sessions ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sessions (
  id               INT           NOT NULL AUTO_INCREMENT,
  learner_id       INT           NOT NULL,
  teacher_id       INT           NOT NULL,
  skill_id         INT           NULL,
  start_time       DATETIME      NOT NULL,
  end_time         DATETIME      NULL,
  duration_minutes INT           NULL,
  status           ENUM('active','completed','invalid') NOT NULL DEFAULT 'active',
  meet_link        VARCHAR(255)  NULL,
  created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (learner_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (skill_id)   REFERENCES skills(skill_id) ON DELETE SET NULL,
  INDEX idx_sessions_learner  (learner_id, status),
  INDEX idx_sessions_teacher  (teacher_id),
  INDEX idx_sessions_skill    (skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 2. Learning Progress ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS learning_progress (
  id                  INT  NOT NULL AUTO_INCREMENT,
  user_id             INT  NOT NULL,
  skill_id            INT  NOT NULL,
  sessions_completed  INT  NOT NULL DEFAULT 0,
  total_time          INT  NOT NULL DEFAULT 0,   -- minutes
  status              ENUM('learning','completed') NOT NULL DEFAULT 'learning',
  last_updated        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_skill (user_id, skill_id),
  FOREIGN KEY (user_id)  REFERENCES users(id)         ON DELETE CASCADE,
  FOREIGN KEY (skill_id) REFERENCES skills(skill_id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 3. Quiz Attempts ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS quiz_attempts (
  id           INT       NOT NULL AUTO_INCREMENT,
  user_id      INT       NOT NULL,
  skill_id     INT       NOT NULL,
  score        TINYINT   NOT NULL DEFAULT 0,   -- 0-100 (percentage)
  total_q      TINYINT   NOT NULL DEFAULT 10,
  correct_q    TINYINT   NOT NULL DEFAULT 0,
  passed       TINYINT(1) NOT NULL DEFAULT 0,
  answers_json TEXT      NULL,                 -- JSON snapshot of answers submitted
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id)  REFERENCES users(id)        ON DELETE CASCADE,
  FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE,
  INDEX idx_quiz_user_skill (user_id, skill_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 4. Certificates ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS certificates (
  id             INT          NOT NULL AUTO_INCREMENT,
  user_id        INT          NOT NULL,
  skill_id       INT          NOT NULL,
  quiz_attempt_id INT         NOT NULL,
  cert_token     VARCHAR(64)  NOT NULL UNIQUE,  -- secure random token for URL
  issued_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_skill_cert (user_id, skill_id),
  FOREIGN KEY (user_id)          REFERENCES users(id)        ON DELETE CASCADE,
  FOREIGN KEY (skill_id)         REFERENCES skills(skill_id) ON DELETE CASCADE,
  FOREIGN KEY (quiz_attempt_id)  REFERENCES quiz_attempts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 5. Meetings table (if not already present from get_meet_link.php) ──
CREATE TABLE IF NOT EXISTS meetings (
  id        INT          NOT NULL AUTO_INCREMENT,
  user1_id  INT          NOT NULL,
  user2_id  INT          NOT NULL,
  meet_link VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_meeting_pair (user1_id, user2_id),
  FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
