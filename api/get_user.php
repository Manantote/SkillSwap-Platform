<?php
/**
 * get_user.php
 * GET: Fetch a user's profile by firebase_uid.
 *
 * Query params:
 *   uid (firebase_uid)
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'GET method required.', 405);
}

$uid = trim($_GET['uid'] ?? $_GET['user_id'] ?? '');
if (!$uid) {
    jsonResponse(false, 'user_id or uid parameter is required.', 400);
}

$pdo = getDB();

$stmt = $pdo->prepare('
    SELECT id, firebase_uid, name, email, created_at, last_seen,
           (is_online = 1 AND last_seen >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)) AS is_currently_online
    FROM users 
    WHERE firebase_uid = ?
');
$stmt->execute([$uid]);
$user = $stmt->fetch();
if ($user && isset($user['is_currently_online'])) {
    $user['is_currently_online'] = (bool)$user['is_currently_online'];
}

if (!$user) {
    jsonResponse(false, 'User not found.', 404);
}

// Fetch avg rating
$rStmt = $pdo->prepare(
    'SELECT ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS review_count
     FROM reviews WHERE reviewed_user_id = ?'
);
$rStmt->execute([$user['id']]);
$ratingData = $rStmt->fetch();

$user['avg_rating']   = $ratingData['avg_rating']   ?? null;
$user['review_count'] = (int)($ratingData['review_count'] ?? 0);

// Fetch teach skills
$tStmt = $pdo->prepare(
    'SELECT s.skill_name FROM user_skills us
     JOIN skills s ON s.skill_id = us.skill_id
     WHERE us.user_id = ? AND us.skill_type = "teach"'
);
$tStmt->execute([$user['id']]);
$user['teach_skills'] = $tStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch learn skills
$lStmt = $pdo->prepare(
    'SELECT s.skill_name FROM user_skills us
     JOIN skills s ON s.skill_id = us.skill_id
     WHERE us.user_id = ? AND us.skill_type = "learn"'
);
$lStmt->execute([$user['id']]);
$user['learn_skills'] = $lStmt->fetchAll(PDO::FETCH_COLUMN);

jsonResponse(true, $user);
