<?php
/**
 * match_users.php
 * GET: Find mutually matched users.
 *
 * Matching logic:
 *   Current user teaches skill X and wants to learn skill Y
 *   Matched user teaches skill Y and wants to learn skill X
 *
 * Required:
 *   uid — firebase_uid of the current user
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

$uid = trim($_GET['uid'] ?? '');
if (!$uid) {
    jsonResponse(false, 'uid parameter is required.', 400);
}

$pdo    = getDB();
$userId = getUserIdByUID($pdo, $uid);
if (!$userId) {
    jsonResponse(false, 'User not found.', 404);
}

/*
 * Strategy:
 * 1. Get all skills the current user teaches  → $teaches  (skill_ids)
 * 2. Get all skills the current user wants     → $wants    (skill_ids)
 * 3. Find OTHER users who:
 *    - teach at least one skill from $wants
 *    - want  at least one skill from $teaches
 */

// Current user's teach skill IDs
$teachStmt = $pdo->prepare(
    'SELECT skill_id FROM user_skills WHERE user_id = ? AND skill_type = "teach"'
);
$teachStmt->execute([$userId]);
$teaches = $teachStmt->fetchAll(PDO::FETCH_COLUMN);

// Current user's learn skill IDs
$learnStmt = $pdo->prepare(
    'SELECT skill_id FROM user_skills WHERE user_id = ? AND skill_type = "learn"'
);
$learnStmt->execute([$userId]);
$wants = $learnStmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($teaches) || empty($wants)) {
    // Not enough data to match — return empty
    jsonResponse(true, []);
}

// Build match query
$teachPlaceholders = implode(',', array_fill(0, count($teaches), '?'));
$wantsPlaceholders = implode(',', array_fill(0, count($wants),   '?'));

$sql = "
    SELECT DISTINCT
        u.id,
        u.firebase_uid,
        u.name,
        u.email,
        ROUND(COALESCE(AVG(r.rating), 0), 1) AS avg_rating,
        COUNT(DISTINCT r.review_id)           AS review_count
    FROM users u
    -- Candidate teaches something I want
    JOIN user_skills us_teach
        ON us_teach.user_id    = u.id
       AND us_teach.skill_type  = 'teach'
       AND us_teach.skill_id   IN ($wantsPlaceholders)
    -- Candidate wants something I teach
    JOIN user_skills us_learn
        ON us_learn.user_id    = u.id
       AND us_learn.skill_type  = 'learn'
       AND us_learn.skill_id   IN ($teachPlaceholders)
    LEFT JOIN reviews r
        ON r.reviewed_user_id = u.id
    WHERE u.id != ?
    GROUP BY u.id, u.firebase_uid, u.name, u.email
    ORDER BY avg_rating DESC
    LIMIT 20
";

$params = array_merge($wants, $teaches, [$userId]);
$stmt   = $pdo->prepare($sql);
$stmt->execute($params);
$matches = $stmt->fetchAll();

// For each matched user, include their skill details
foreach ($matches as &$match) {
    $mid = $match['id'];

    // Skills matched user teaches (that we want)
    $ts = $pdo->prepare(
        "SELECT s.skill_name FROM user_skills us
         JOIN skills s ON s.skill_id = us.skill_id
         WHERE us.user_id = ? AND us.skill_type = 'teach'
           AND us.skill_id IN ($wantsPlaceholders)"
    );
    $ts->execute(array_merge([$mid], $wants));
    $match['teaches'] = $ts->fetchAll(PDO::FETCH_COLUMN);

    // Skills matched user wants (that we teach)
    $ls = $pdo->prepare(
        "SELECT s.skill_name FROM user_skills us
         JOIN skills s ON s.skill_id = us.skill_id
         WHERE us.user_id = ? AND us.skill_type = 'learn'
           AND us.skill_id IN ($teachPlaceholders)"
    );
    $ls->execute(array_merge([$mid], $teaches));
    $match['wants'] = $ls->fetchAll(PDO::FETCH_COLUMN);

    $match['id'] = (int)$match['id'];
    $match['avg_rating']   = (float)$match['avg_rating'];
    $match['review_count'] = (int)$match['review_count'];
}

jsonResponse(true, $matches);
