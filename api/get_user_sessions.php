<?php
/**
 * get_user_sessions.php
 * GET — Return all sessions for the authenticated user (as learner or teacher).
 *
 * Optional query params:
 *   role      'learner'|'teacher'|'both'  (default: both)
 *   skill_id  int                          (filter by skill)
 *   status    'active'|'completed'|'invalid'
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

$uid    = requireUID();
$pdo    = getDB();
$userId = getUserIdByUID($pdo, $uid);

if (!$userId) {
    jsonResponse(false, 'User not found.', 404);
}

$role    = $_GET['role']     ?? 'both';
$skillId = (int)($_GET['skill_id'] ?? 0);
$status  = $_GET['status']   ?? '';

// Build WHERE clauses
$conditions = [];
$params     = [];

if ($role === 'learner') {
    $conditions[] = 's.learner_id = ?'; $params[] = $userId;
} elseif ($role === 'teacher') {
    $conditions[] = 's.teacher_id = ?'; $params[] = $userId;
} else {
    $conditions[] = '(s.learner_id = ? OR s.teacher_id = ?)';
    $params[] = $userId; $params[] = $userId;
}

if ($skillId) {
    $conditions[] = 's.skill_id = ?'; $params[] = $skillId;
}

$allowed = ['active', 'completed', 'invalid'];
if ($status && in_array($status, $allowed)) {
    $conditions[] = 's.status = ?'; $params[] = $status;
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$sql = "
    SELECT
        s.id,
        s.start_time,
        s.end_time,
        s.duration_minutes,
        s.status,
        s.meet_link,
        sk.skill_name,
        s.skill_id,
        l.name  AS learner_name,
        l.id    AS learner_id,
        t.name  AS teacher_name,
        t.id    AS teacher_id
    FROM sessions s
    LEFT JOIN skills sk ON sk.skill_id = s.skill_id
    JOIN users l ON l.id = s.learner_id
    JOIN users t ON t.id = s.teacher_id
    {$where}
    ORDER BY s.created_at DESC
    LIMIT 100
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sessions = $stmt->fetchAll();

// Also fetch learning_progress summary for each unique skill
$progressMap = [];
$progStmt = $pdo->prepare(
    'SELECT lp.*, sk.skill_name
     FROM learning_progress lp
     JOIN skills sk ON sk.skill_id = lp.skill_id
     WHERE lp.user_id = ?'
);
$progStmt->execute([$userId]);
foreach ($progStmt->fetchAll() as $row) {
    $progressMap[$row['skill_id']] = $row;
}

jsonResponse(true, [
    'sessions'   => $sessions,
    'progress'   => array_values($progressMap),
    'user_id'    => $userId,
]);
