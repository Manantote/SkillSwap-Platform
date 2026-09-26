-- ================================================
-- Skill Swap Platform — MySQL Schema
-- Database Name: skillswap_fixed
-- ================================================

DROP DATABASE IF EXISTS skillswap_fixed;
CREATE DATABASE skillswap_fixed
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE skillswap_fixed;

-- ── Users ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id           INT          NOT NULL AUTO_INCREMENT,
  firebase_uid VARCHAR(128) NOT NULL UNIQUE,
  name         VARCHAR(100) NOT NULL,
  email        VARCHAR(200) NOT NULL,
  profile_pic  VARCHAR(255) DEFAULT NULL,
  created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  
  -- Online status tracking
  is_online    TINYINT(1)   DEFAULT 0,
  last_seen    DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Skills (global catalogue) ────────────────────
CREATE TABLE IF NOT EXISTS skills (
  skill_id   INT          NOT NULL AUTO_INCREMENT,
  skill_name VARCHAR(100) NOT NULL UNIQUE,
  PRIMARY KEY (skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── User Skills ──────────────────────────────────
-- skill_type: 'teach' | 'learn'
CREATE TABLE IF NOT EXISTS user_skills (
  user_skill_id INT         NOT NULL AUTO_INCREMENT,
  user_id       INT         NOT NULL,
  skill_id      INT         NOT NULL,
  skill_type    ENUM('teach','learn') NOT NULL,
  PRIMARY KEY (user_skill_id),
  UNIQUE KEY uq_user_skill_type (user_id, skill_id, skill_type),
  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
  FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Skill Requests ───────────────────────────────
-- status: 'pending' | 'accepted' | 'rejected'
CREATE TABLE IF NOT EXISTS skill_requests (
  request_id  INT  NOT NULL AUTO_INCREMENT,
  sender_id   INT  NOT NULL,
  receiver_id INT  NOT NULL,
  skill_id    INT  NULL,
  message     TEXT NULL,
  status      ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (request_id),
  FOREIGN KEY (sender_id)   REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Meetings ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS meetings (
  id           INT          NOT NULL AUTO_INCREMENT,
  user1_id     INT          NOT NULL,
  user2_id     INT          NOT NULL,
  meet_link    VARCHAR(255) NOT NULL,
  created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Reviews ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS reviews (
  review_id        INT  NOT NULL AUTO_INCREMENT,
  reviewer_id      INT  NOT NULL,
  reviewed_user_id INT  NOT NULL,
  rating           TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment          TEXT NULL,
  tags             VARCHAR(500) NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (review_id),
  FOREIGN KEY (reviewer_id)      REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Chat ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS chat (
  chat_id     INT       NOT NULL AUTO_INCREMENT,
  sender_id   INT       NOT NULL,
  receiver_id INT       NOT NULL,
  message     TEXT      NOT NULL,
  timestamp   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (chat_id),
  FOREIGN KEY (sender_id)   REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: blocked_users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS blocked_users (
  blocker_id INT NOT NULL,
  blocked_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (blocker_id, blocked_id),
  FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: reports
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS reports (
  report_id   INT AUTO_INCREMENT PRIMARY KEY,
  reporter_id INT NOT NULL,
  reported_id INT NOT NULL,
  reason      TEXT NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reported_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: notifications
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  type       VARCHAR(50) NOT NULL,
  message    VARCHAR(255) NOT NULL,
  is_read    TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Indexes for performance ───────────────────────
CREATE INDEX IF NOT EXISTS idx_chat_convo ON chat (sender_id, receiver_id, timestamp);
CREATE INDEX IF NOT EXISTS idx_requests_receiver ON skill_requests (receiver_id, status);
CREATE INDEX IF NOT EXISTS idx_user_skills_user ON user_skills (user_id, skill_type);
CREATE INDEX IF NOT EXISTS idx_meetings_users ON meetings (user1_id, user2_id);
