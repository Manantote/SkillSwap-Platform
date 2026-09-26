<?php
/**
 * add_skill.php
 * POST: Add a skill (teach or learn) for the authenticated user.
 *
 * Required:
 *   X-Firebase-UID header (or POST firebase_uid)
 *   skill_name, skill_type ('teach'|'learn')
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST method required.', 405);
}

$uid  = requireUID();
$body = json_decode(file_get_contents('php://input'), true) ?? [];

$skillName = trim($body['skill_name'] ?? $_POST['skill_name'] ?? '');
$skillType = trim($body['skill_type'] ?? $_POST['skill_type'] ?? '');

if (!$skillName) {
    jsonResponse(false, 'skill_name is required.', 400);
}
if (!in_array($skillType, ['teach', 'learn'], true)) {
    jsonResponse(false, 'skill_type must be "teach" or "learn".', 400);
}

$pdo    = getDB();
$userId = getUserIdByUID($pdo, $uid);
if (!$userId) {
    jsonResponse(false, 'User not found. Please register first.', 404);
}

// Insert skill into global catalogue
$s = $pdo->prepare('INSERT IGNORE INTO skills (skill_name) VALUES (?)');
$s->execute([$skillName]);
$skillId = (int)($pdo->lastInsertId() ?: getSkillId($pdo, $skillName));

// Link user → skill
$u = $pdo->prepare(
    'INSERT IGNORE INTO user_skills (user_id, skill_id, skill_type) VALUES (?, ?, ?)'
);
$u->execute([$userId, $skillId, $skillType]);

jsonResponse(true, ['skill_id' => $skillId, 'skill_name' => $skillName, 'skill_type' => $skillType]);

function getSkillId(PDO $pdo, string $skillName): int {
    $s = $pdo->prepare('SELECT skill_id FROM skills WHERE skill_name = ?');
    $s->execute([$skillName]);
    return (int)$s->fetchColumn();
}
