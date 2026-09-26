-- ================================================================
-- SkillSwap Platform — Complete MySQL Schema
-- Database: skillswap_new
-- Auto-generated from analysis of all 28 PHP API files
-- ================================================================





-- ════════════════════════════════════════════════════════════════
-- TABLE: users
-- Used by: ALL API files (via getUserIdByUID in db.php)
-- Columns verified from: get_user.php, register_user.php,
--   set_online.php, set_offline.php, logout_all.php,
--   update_profile.php, get_chat_partners.php, send_call_email.php
-- ════════════════════════════════════════════════════════════════
CREATE TABLE users (
  id           INT          NOT NULL AUTO_INCREMENT,
  firebase_uid VARCHAR(128) NOT NULL UNIQUE,
  name         VARCHAR(100) NOT NULL,
  email        VARCHAR(200) NOT NULL,
  profile_pic  VARCHAR(255) DEFAULT NULL,
  created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  is_online    TINYINT(1)   DEFAULT 0,
  last_seen    DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ════════════════════════════════════════════════════════════════
-- TABLE: skills
-- Used by: get_skills.php, add_skill.php, register_user.php
-- Columns verified from: SELECT skill_id, skill_name ...
--   INSERT IGNORE INTO skills (skill_name)
-- ════════════════════════════════════════════════════════════════
CREATE TABLE skills (
  skill_id   INT          NOT NULL AUTO_INCREMENT,
  skill_name VARCHAR(100) NOT NULL UNIQUE,
  PRIMARY KEY (skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ════════════════════════════════════════════════════════════════
-- TABLE: user_skills
-- Used by: get_user_skills.php, get_user.php, match_users.php,
--   get_requests.php, get_chat_partners.php, add_skill.php,
--   register_user.php
-- Columns verified from: us.user_id, us.skill_id, us.skill_type
--   INSERT IGNORE INTO user_skills (user_id, skill_id, skill_type)
-- ════════════════════════════════════════════════════════════════
CREATE TABLE user_skills (
  user_skill_id INT          NOT NULL AUTO_INCREMENT,
  user_id       INT          NOT NULL,
  skill_id      INT          NOT NULL,
  skill_type    ENUM('teach','learn') NOT NULL,
  PRIMARY KEY (user_skill_id),
  UNIQUE KEY uq_user_skill_type (user_id, skill_id, skill_type),
  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
  FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ════════════════════════════════════════════════════════════════
-- TABLE: skill_requests
-- Used by: send_request.php, get_requests.php,
--   accept_request.php, reject_request.php, get_chat_partners.php
-- Columns verified from: request_id, sender_id, receiver_id,
--   skill_id, message, status, created_at
--   SELECT * FROM skill_requests WHERE receiver_id = ?
-- ════════════════════════════════════════════════════════════════
CREATE TABLE skill_requests (
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

-- ════════════════════════════════════════════════════════════════
-- TABLE: chat
-- Used by: send_message.php, get_messages.php,
--   get_chat_partners.php, clear_chat.php
-- Columns verified from: chat_id, sender_id, receiver_id,
--   message, timestamp
--   INSERT INTO chat (sender_id, receiver_id, message)
--   SELECT c.chat_id, c.sender_id, c.receiver_id, c.message, c.timestamp
-- ════════════════════════════════════════════════════════════════
CREATE TABLE chat (
  chat_id     INT       NOT NULL AUTO_INCREMENT,
  sender_id   INT       NOT NULL,
  receiver_id INT       NOT NULL,
  message     TEXT      NOT NULL,
  timestamp   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (chat_id),
  FOREIGN KEY (sender_id)   REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ════════════════════════════════════════════════════════════════
-- TABLE: meetings
-- Used by: get_meet_link.php
-- Columns verified from EXACT queries:
--   SELECT meet_link FROM meetings WHERE user1_id = ? AND user2_id = ?
--   INSERT INTO meetings (user1_id, user2_id, meet_link) VALUES (?, ?, ?)
-- ════════════════════════════════════════════════════════════════
CREATE TABLE meetings (
  id           INT          NOT NULL AUTO_INCREMENT,
  user1_id     INT          NOT NULL,
  user2_id     INT          NOT NULL,
  meet_link    VARCHAR(255) NOT NULL,
  created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ════════════════════════════════════════════════════════════════
-- TABLE: reviews
-- Used by: add_review.php, get_reviews.php, get_user.php,
--   match_users.php
-- Columns verified from:
--   review_id, reviewer_id, reviewed_user_id, rating, comment,
--   tags, created_at
--   INSERT INTO reviews (reviewer_id, reviewed_user_id, rating, comment, tags)
--   SELECT ROUND(AVG(rating),1), COUNT(*) FROM reviews WHERE reviewed_user_id=?
--   SELECT r.review_id, r.rating, r.comment, r.tags, r.created_at
-- ════════════════════════════════════════════════════════════════
CREATE TABLE reviews (
  review_id        INT     NOT NULL AUTO_INCREMENT,
  reviewer_id      INT     NOT NULL,
  reviewed_user_id INT     NOT NULL,
  rating           TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment          TEXT    NULL,
  tags             VARCHAR(500) NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (review_id),
  FOREIGN KEY (reviewer_id)      REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ════════════════════════════════════════════════════════════════
-- TABLE: notifications
-- Used by: get_notifications.php, add_notification.php, mark_read.php
-- Columns verified from:
--   SELECT id, type, message, is_read, created_at FROM notifications
--   INSERT INTO notifications (user_id, type, message)
--   UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?
--   SELECT COUNT(*) as unread_count FROM notifications WHERE user_id=? AND is_read=0
-- ════════════════════════════════════════════════════════════════
CREATE TABLE notifications (
  id         INT          NOT NULL AUTO_INCREMENT,
  user_id    INT          NOT NULL,
  type       VARCHAR(50)  NOT NULL,
  message    VARCHAR(255) NOT NULL,
  is_read    TINYINT(1)   DEFAULT 0,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ════════════════════════════════════════════════════════════════
-- TABLE: blocked_users
-- Used by: block_user.php, send_message.php, add_notification.php
-- Columns verified from:
--   INSERT IGNORE INTO blocked_users (blocker_id, blocked_id)
--   SELECT 1 FROM blocked_users WHERE blocker_id=? AND blocked_id=?
--   SELECT 1 FROM blocked_users WHERE (blocker_id=? AND blocked_id=?) OR ...
-- ════════════════════════════════════════════════════════════════
CREATE TABLE blocked_users (
  blocker_id INT NOT NULL,
  blocked_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (blocker_id, blocked_id),
  FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ════════════════════════════════════════════════════════════════
-- TABLE: reports
-- Used by: report_user.php
-- Columns verified from:
--   INSERT INTO reports (reporter_id, reported_id, reason)
-- ════════════════════════════════════════════════════════════════
CREATE TABLE reports (
  report_id   INT  NOT NULL AUTO_INCREMENT,
  reporter_id INT  NOT NULL,
  reported_id INT  NOT NULL,
  reason      TEXT NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (report_id),
  FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reported_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ════════════════════════════════════════════════════════════════
-- Performance Indexes
-- ════════════════════════════════════════════════════════════════
CREATE INDEX idx_chat_convo       ON chat (sender_id, receiver_id, timestamp);
CREATE INDEX idx_chat_timestamp   ON chat (timestamp);
CREATE INDEX idx_requests_recv    ON skill_requests (receiver_id, status);
CREATE INDEX idx_requests_send    ON skill_requests (sender_id, status);
CREATE INDEX idx_user_skills_user ON user_skills (user_id, skill_type);
CREATE INDEX idx_user_skills_skill ON user_skills (skill_id, skill_type);
CREATE INDEX idx_meetings_users   ON meetings (user1_id, user2_id);
CREATE INDEX idx_reviews_target   ON reviews (reviewed_user_id);
CREATE INDEX idx_notif_user       ON notifications (user_id, is_read);
CREATE INDEX idx_users_firebase   ON users (firebase_uid);
