-- ================================================================
-- Skill Swap — Sample Data Seed
-- Indian users with complementary teach/learn skills
-- Run AFTER importing schema.sql
--
-- Usage:
--   mysql -u root -p skillswap < config/seed.sql
-- ================================================================

USE skillswap;

-- ── Clear existing sample data ───────────────────────────────────
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE chat;
TRUNCATE TABLE reviews;
TRUNCATE TABLE skill_requests;
TRUNCATE TABLE user_skills;
TRUNCATE TABLE skills;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- ── 1. Skills Catalogue ──────────────────────────────────────────
INSERT INTO skills (skill_id, skill_name) VALUES
  (1,  'Python'),
  (2,  'Graphic Design'),
  (3,  'Java'),
  (4,  'React'),
  (5,  'Video Editing'),
  (6,  'Digital Marketing'),
  (7,  'Machine Learning'),
  (8,  'Figma'),
  (9,  'Node.js'),
  (10, 'Spanish'),
  (11, 'Photography'),
  (12, 'Guitar'),
  (13, 'Excel'),
  (14, 'SEO'),
  (15, 'Blender 3D');

-- ── 2. Sample Users ──────────────────────────────────────────────
-- NOTE: firebase_uid values are placeholders.
-- When a real user registers, register_user.php upserts by firebase_uid.
-- These seed users let you see the app populated immediately.
INSERT INTO users (id, firebase_uid, name, email, created_at) VALUES
  (1,  'seed_rahul',  'Rahul Sharma',   'rahul.sharma@example.com',  '2026-01-10 09:00:00'),
  (2,  'seed_priya',  'Priya Patel',    'priya.patel@example.com',   '2026-01-12 10:30:00'),
  (3,  'seed_amit',   'Amit Verma',     'amit.verma@example.com',    '2026-01-15 11:00:00'),
  (4,  'seed_sneha',  'Sneha Iyer',     'sneha.iyer@example.com',    '2026-01-18 08:45:00'),
  (5,  'seed_arjun',  'Arjun Mehta',    'arjun.mehta@example.com',   '2026-01-20 14:00:00'),
  (6,  'seed_kavya',  'Kavya Reddy',    'kavya.reddy@example.com',   '2026-01-22 16:00:00'),
  (7,  'seed_rohan',  'Rohan Nair',     'rohan.nair@example.com',    '2026-01-25 09:30:00'),
  (8,  'seed_ananya', 'Ananya Singh',   'ananya.singh@example.com',  '2026-02-01 12:00:00');

-- ── 3. User Skills ───────────────────────────────────────────────
-- Rahul: teaches Python + ML, wants Design + Figma
INSERT INTO user_skills (user_id, skill_id, skill_type) VALUES
  (1, 1,  'teach'), -- Python
  (1, 7,  'teach'), -- ML
  (1, 2,  'learn'), -- Graphic Design
  (1, 8,  'learn'); -- Figma

-- Priya: teaches Design + Figma, wants Python + ML  (mutual w/ Rahul)
INSERT INTO user_skills (user_id, skill_id, skill_type) VALUES
  (2, 2,  'teach'), -- Graphic Design
  (2, 8,  'teach'), -- Figma
  (2, 1,  'learn'), -- Python
  (2, 7,  'learn'); -- ML

-- Amit: teaches Java + Node.js, wants React
INSERT INTO user_skills (user_id, skill_id, skill_type) VALUES
  (3, 3,  'teach'), -- Java
  (3, 9,  'teach'), -- Node.js
  (3, 4,  'learn'); -- React

-- Sneha: teaches React, wants Java + Node.js  (mutual w/ Amit)
INSERT INTO user_skills (user_id, skill_id, skill_type) VALUES
  (4, 4,  'teach'), -- React
  (4, 3,  'learn'), -- Java
  (4, 9,  'learn'); -- Node.js

-- Arjun: teaches Video Editing, wants Digital Marketing
INSERT INTO user_skills (user_id, skill_id, skill_type) VALUES
  (5, 5,  'teach'), -- Video Editing
  (5, 6,  'learn'); -- Digital Marketing

-- Kavya: teaches Digital Marketing + SEO, wants Video Editing  (mutual w/ Arjun)
INSERT INTO user_skills (user_id, skill_id, skill_type) VALUES
  (6, 6,  'teach'), -- Digital Marketing
  (6, 14, 'teach'), -- SEO
  (6, 5,  'learn'); -- Video Editing

-- Rohan: teaches Guitar + Photography, wants Python
INSERT INTO user_skills (user_id, skill_id, skill_type) VALUES
  (7, 12, 'teach'), -- Guitar
  (7, 11, 'teach'), -- Photography
  (7, 1,  'learn'); -- Python

-- Ananya: teaches Figma + Blender, wants React + SEO
INSERT INTO user_skills (user_id, skill_id, skill_type) VALUES
  (8, 8,  'teach'), -- Figma
  (8, 15, 'teach'), -- Blender 3D
  (8, 4,  'learn'), -- React
  (8, 14, 'learn'); -- SEO

-- ── 4. Skill Requests ────────────────────────────────────────────
INSERT INTO skill_requests (request_id, sender_id, receiver_id, message, status, created_at) VALUES
  (1, 1, 2, 'Hi Priya! I can teach you Python in exchange for Figma lessons 🙌', 'accepted',  '2026-02-05 10:00:00'),
  (2, 3, 4, 'Hey Sneha, want to swap Java for React?', 'pending',   '2026-02-08 11:30:00'),
  (3, 5, 6, 'Arjun here — would love a Digital Marketing session!', 'pending',   '2026-02-10 14:00:00'),
  (4, 2, 7, 'Priya would like to learn Photography from Rohan!', 'accepted',  '2026-02-12 09:00:00'),
  (5, 7, 1, 'Rohan here — can we do Guitar for Python?', 'rejected',  '2026-02-14 16:30:00');

-- ── 5. Reviews ────────────────────────────────────────────────────
INSERT INTO reviews (review_id, reviewer_id, reviewed_user_id, rating, comment, tags, created_at) VALUES
  (1, 2, 1, 5, 'Rahul is an amazing Python teacher! Super patient and clear explanations.', 'Knowledgeable,Patient,Clear Explanations', '2026-02-20 10:00:00'),
  (2, 1, 2, 5, 'Priya taught me Figma brilliantly. Highly recommended!', 'Knowledgeable,Creative,Enthusiastic', '2026-02-21 11:00:00'),
  (3, 4, 3, 4, 'Amit knows Java very well. Sessions were structured and helpful.', 'Knowledgeable,Patient', '2026-02-22 09:30:00'),
  (4, 3, 4, 5, 'Sneha is an excellent React teacher. Very hands-on.', 'Creative,Clear Explanations', '2026-02-23 12:00:00'),
  (5, 6, 5, 4, 'Arjun helped me with Video Editing basics. Good session!', 'Enthusiastic,Patient', '2026-02-24 14:00:00'),
  (6, 2, 7, 5, 'Rohan is a fantastic Photography teacher. Learnt so much!', 'Creative,Knowledgeable', '2026-02-25 15:00:00');

-- ── 6. Chat Messages ──────────────────────────────────────────────
-- Rahul (1) ↔ Priya (2) — accepted swap
INSERT INTO chat (sender_id, receiver_id, message, timestamp) VALUES
  (1, 2, 'Hi Priya! Ready to start our skill swap? 😊', '2026-02-06 09:00:00'),
  (2, 1, 'Yes! So excited. Shall we start with Python basics?', '2026-02-06 09:02:00'),
  (1, 2, 'Perfect! Let me share a beginner roadmap first 🚀', '2026-02-06 09:05:00'),
  (2, 1, 'Awesome! And I will prep a Figma onboarding session for you!', '2026-02-06 09:07:00'),
  (1, 2, 'Great, see you at 6 PM today then 👍', '2026-02-06 09:10:00'),

  (2, 1, 'That Python session was super helpful, Rahul!', '2026-02-13 20:00:00'),
  (1, 2, 'Glad to hear it! Auto-layout in Figma blew my mind 🎨', '2026-02-13 20:05:00'),
  (2, 1, 'Haha, you will master it soon. Same time next week?', '2026-02-13 20:08:00'),
  (1, 2, 'For sure! 🙌', '2026-02-13 20:10:00'),

-- Priya (2) ↔ Rohan (7) — accepted swap
  (2, 7, 'Hi Rohan! Looking forward to the Photography session 📸', '2026-02-13 10:00:00'),
  (7, 2, 'Same here Priya! Let me know your current level first', '2026-02-13 10:03:00'),
  (2, 7, 'Complete beginner honestly 😅', '2026-02-13 10:05:00'),
  (7, 2, 'No worries, we start with composition basics. Easy and fun!', '2026-02-13 10:07:00');
