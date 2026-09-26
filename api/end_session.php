<?php
/**
 * end_session.php
 * POST — Mark a session as ended, calculate duration, update learning_progress.
 *
 * Required:
 *   X-Firebase-UID  — current user (learner)
 *
 * Body:
 *   session_id  int
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST method required.', 405);
}

$learnerUID = requireUID();

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$sessionId = (int)($body['session_id'] ?? $_POST['session_id'] ?? 0);

if (!$sessionId) {
    jsonResponse(false, 'session_id is required.', 400);
}

$pdo = getDB();

$learnerId = getUserIdByUID($pdo, $learnerUID);
if (!$learnerId) {
    jsonResponse(false, 'User not found.', 404);
}

// Fetch the session — must belong to this learner and still be active
$stmt = $pdo->prepare(
    'SELECT * FROM sessions WHERE id = ? AND learner_id = ? AND status = "active" LIMIT 1'
);
$stmt->execute([$sessionId, $learnerId]);
$session = $stmt->fetch();

if (!$session) {
    jsonResponse(false, 'Active session not found or does not belong to you.', 404);
}

$now      = new DateTime();
$start    = new DateTime($session['start_time']);
$duration = (int)round(($now->getTimestamp() - $start->getTimestamp()) / 60);
$endTime  = $now->format('Y-m-d H:i:s');

// Determine validity
$MIN_DURATION = 15;
$status = $duration >= $MIN_DURATION ? 'completed' : 'invalid';

// Update session record
$upd = $pdo->prepare(
    'UPDATE sessions
     SET end_time = ?, duration_minutes = ?, status = ?
     WHERE id = ?'
);
$upd->execute([$endTime, $duration, $status, $sessionId]);

// If session is valid, update learning_progress
$progressUpdate = null;
if ($status === 'completed') {
    $skillId   = (int)$session['skill_id'];
    $teacherId = (int)$session['teacher_id'];

    // Update progress for learner on this skill
    if ($skillId) {
        $progressUpdate = upsertProgress($pdo, $learnerId, $skillId, $duration);
    }
}

jsonResponse(true, [
    'session_id'       => $sessionId,
    'duration_minutes' => $duration,
    'status'           => $status,
    'valid'            => $status === 'completed',
    'message'          => $status === 'completed'
        ? "Session recorded! Duration: {$duration} minutes."
        : "Session too short ({$duration} min). Minimum 15 minutes required.",
    'progress'         => $progressUpdate,
]);

// ─────────────────────────────────────────────────────────────────────────────
/**
 * Upsert learning_progress and return updated row.
 */
function upsertProgress(PDO $pdo, int $userId, int $skillId, int $addMinutes): array {
    // Insert or increment
    $stmt = $pdo->prepare(
        'INSERT INTO learning_progress (user_id, skill_id, sessions_completed, total_time, status)
         VALUES (?, ?, 1, ?, "learning")
         ON DUPLICATE KEY UPDATE
           sessions_completed = sessions_completed + 1,
           total_time         = total_time + VALUES(total_time)'
    );
    $stmt->execute([$userId, $skillId, $addMinutes]);

    // Fetch current state
    $get = $pdo->prepare(
        'SELECT * FROM learning_progress WHERE user_id = ? AND skill_id = ? LIMIT 1'
    );
    $get->execute([$userId, $skillId]);
    $row = $get->fetch();

    // Auto-complete check: ≥3 sessions AND ≥60 minutes
    if ($row['sessions_completed'] >= 3 && $row['total_time'] >= 60 && $row['status'] === 'learning') {
        $pdo->prepare('UPDATE learning_progress SET status = "completed" WHERE id = ?')
            ->execute([$row['id']]);
        $row['status'] = 'completed';
    }

    return $row;
}
