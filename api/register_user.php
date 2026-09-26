<?php
/**
 * register_user.php
 * POST: Store or update a Firebase user in the MySQL database.
 *
 * Required POST fields:
 *   firebase_uid, name, email
 * Optional:
 *   teach_skills (comma-separated), learn_skills (comma-separated)
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST method required.', 405);
}

// Parse JSON body or fallback to POST fields
$body  = json_decode(file_get_contents('php://input'), true) ?? [];
$uid   = trim($body['firebase_uid']  ?? $_POST['firebase_uid']  ?? '');
$name  = trim($body['name']          ?? $_POST['name']          ?? '');
$email = trim($body['email']         ?? $_POST['email']         ?? '');

if (!$uid || !$name || !$email) {
    jsonResponse(false, 'firebase_uid, name and email are required.', 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email address.', 400);
}

$pdo = getDB();

// Upsert user
$stmt = $pdo->prepare(
    'INSERT INTO users (firebase_uid, name, email)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE name = VALUES(name), email = VALUES(email)'
);
$stmt->execute([$uid, $name, $email]);
$userId = (int)($pdo->lastInsertId() ?: getUserIdByUID($pdo, $uid));

// Handle initial teach skills
$teachRaw = $body['teach_skills'] ?? $_POST['teach_skills'] ?? '';
$learnRaw = $body['learn_skills'] ?? $_POST['learn_skills'] ?? '';

if ($teachRaw) storeUserSkills($pdo, $userId, $teachRaw, 'teach');
if ($learnRaw) storeUserSkills($pdo, $userId, $learnRaw, 'learn');

jsonResponse(true, ['user_id' => $userId, 'firebase_uid' => $uid]);

// ─────────────────────────────────────────────────────────────────────────────
function storeUserSkills(PDO $pdo, int $userId, string $rawSkills, string $type): void {
    $skills = array_filter(array_map('trim', explode(',', $rawSkills)));
    foreach ($skills as $skillName) {
        if (!$skillName) continue;
        // Insert skill into catalogue if not exists
        $s = $pdo->prepare('INSERT IGNORE INTO skills (skill_name) VALUES (?)');
        $s->execute([$skillName]);
        $skillId = (int)($pdo->lastInsertId() ?: getSkillId($pdo, $skillName));

        // Insert user–skill mapping
        $u = $pdo->prepare(
            'INSERT IGNORE INTO user_skills (user_id, skill_id, skill_type) VALUES (?, ?, ?)'
        );
        $u->execute([$userId, $skillId, $type]);
    }
}

function getSkillId(PDO $pdo, string $skillName): int {
    $s = $pdo->prepare('SELECT skill_id FROM skills WHERE skill_name = ?');
    $s->execute([$skillName]);
    return (int)$s->fetchColumn();
}
