<?php
/**
 * start_session.php
 * POST — Create a new learning session and return a Google Meet link.
 *
 * Required (header or body):
 *   X-Firebase-UID  — current user (learner)
 *
 * Body (JSON or POST):
 *   teacher_uid   string  Firebase UID of the teacher
 *   skill_id      int     Skill being exchanged (optional, 0 = generic)
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST method required.', 405);
}

$learnerUID = requireUID();

$body       = json_decode(file_get_contents('php://input'), true) ?? [];
$teacherUID = trim($body['teacher_uid'] ?? $_POST['teacher_uid'] ?? '');
$skillId    = (int)($body['skill_id']   ?? $_POST['skill_id']   ?? 0);

if (!$teacherUID) {
    jsonResponse(false, 'teacher_uid is required.', 400);
}
if ($learnerUID === $teacherUID) {
    jsonResponse(false, 'Learner and teacher cannot be the same user.', 400);
}

$pdo = getDB();

$learnerId = getUserIdByUID($pdo, $learnerUID);
$teacherId = getUserIdByUID($pdo, $teacherUID);

if (!$learnerId || !$teacherId) {
    jsonResponse(false, 'User not found.', 404);
}

// Validate skill if provided
if ($skillId) {
    $sk = $pdo->prepare('SELECT skill_id FROM skills WHERE skill_id = ?');
    $sk->execute([$skillId]);
    if (!$sk->fetch()) {
        jsonResponse(false, 'Skill not found.', 404);
    }
}

// Prevent double-starting: block if learner already has an active session
$active = $pdo->prepare(
    'SELECT id FROM sessions WHERE learner_id = ? AND status = "active" LIMIT 1'
);
$active->execute([$learnerId]);
if ($active->fetch()) {
    jsonResponse(false, 'You already have an active session. End it before starting a new one.', 409);
}

// Get or generate a Meet link for this pair
$u1 = min($learnerId, $teacherId);
$u2 = max($learnerId, $teacherId);

$meetStmt = $pdo->prepare(
    'SELECT meet_link FROM meetings WHERE user1_id = ? AND user2_id = ? LIMIT 1'
);
$meetStmt->execute([$u1, $u2]);
$meetLink = $meetStmt->fetchColumn();

if (!$meetLink) {
    $meetLink = 'https://meet.google.com/' . randStr(3) . '-' . randStr(4) . '-' . randStr(3);
    $ins = $pdo->prepare('INSERT IGNORE INTO meetings (user1_id, user2_id, meet_link) VALUES (?, ?, ?)');
    $ins->execute([$u1, $u2, $meetLink]);
}

// Insert session record
$now = date('Y-m-d H:i:s');
$ins = $pdo->prepare(
    'INSERT INTO sessions (learner_id, teacher_id, skill_id, start_time, status, meet_link)
     VALUES (?, ?, ?, ?, "active", ?)'
);
$ins->execute([
    $learnerId,
    $teacherId,
    $skillId ?: null,
    $now,
    $meetLink
]);
$sessionId = (int)$pdo->lastInsertId();

jsonResponse(true, [
    'session_id' => $sessionId,
    'meet_link'  => $meetLink,
    'start_time' => $now,
]);

// ────────────────────────────────────────────────────────────────────────────
function randStr(int $len): string {
    $s = '';
    for ($i = 0; $i < $len; $i++) $s .= chr(mt_rand(97, 122));
    return $s;
}
