<?php
/**
 * get_user_skills.php
 * GET: Return the teach and learn skills for a given user.
 *
 * Required:
 *   uid — firebase_uid  (query param)
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

$stmt = $pdo->prepare(
    'SELECT s.skill_id, s.skill_name, us.skill_type
     FROM user_skills us
     JOIN skills s ON s.skill_id = us.skill_id
     WHERE us.user_id = ?
     ORDER BY us.skill_type, s.skill_name'
);
$stmt->execute([$userId]);
$rows = $stmt->fetchAll();

$teach = [];
$learn = [];
foreach ($rows as $row) {
    if ($row['skill_type'] === 'teach') {
        $teach[] = ['id' => $row['skill_id'], 'name' => $row['skill_name']];
    } else {
        $learn[] = ['id' => $row['skill_id'], 'name' => $row['skill_name']];
    }
}

jsonResponse(true, ['teach' => $teach, 'learn' => $learn]);
