<?php
/**
 * submit_quiz.php
 * POST — Grade a quiz attempt and issue a certificate if passed.
 *
 * Required:
 *   X-Firebase-UID
 *
 * Body:
 *   attempt_id   int
 *   quiz_token   string
 *   answers      array  [0-indexed answer choices submitted by user]
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST method required.', 405);
}

$uid  = requireUID();
$body = json_decode(file_get_contents('php://input'), true) ?? [];

$attemptId = (int)($body['attempt_id'] ?? 0);
$quizToken = trim($body['quiz_token']  ?? '');
$answers   = $body['answers']          ?? [];   // user's choices, 0-indexed per question

if (!$attemptId || !$quizToken || !is_array($answers)) {
    jsonResponse(false, 'attempt_id, quiz_token and answers array are required.', 400);
}

$pdo    = getDB();
$userId = getUserIdByUID($pdo, $uid);
if (!$userId) jsonResponse(false, 'User not found.', 404);

// Fetch the pending attempt
$stmt = $pdo->prepare(
    'SELECT * FROM quiz_attempts WHERE id = ? AND user_id = ? AND score = -1 LIMIT 1'
);
$stmt->execute([$attemptId, $userId]);
$attempt = $stmt->fetch();

if (!$attempt) {
    jsonResponse(false, 'Quiz attempt not found, already submitted, or does not belong to you.', 404);
}

// Decode stored correct answers + token
$stored = json_decode($attempt['answers_json'], true);
if (!$stored || ($stored['token'] ?? '') !== $quizToken) {
    jsonResponse(false, 'Invalid quiz token. This attempt may have expired.', 403);
}

$correctAnswers = $stored['answers'];   // array of correct indices
$total          = count($correctAnswers);
$correct        = 0;

foreach ($correctAnswers as $i => $correctIdx) {
    if (isset($answers[$i]) && (int)$answers[$i] === (int)$correctIdx) {
        $correct++;
    }
}

$score  = $total > 0 ? (int)round(($correct / $total) * 100) : 0;
$passed = $score >= 70 ? 1 : 0;

// Persist the graded result
$upd = $pdo->prepare(
    'UPDATE quiz_attempts
     SET score = ?, correct_q = ?, passed = ?, answers_json = NULL
     WHERE id = ?'
);
$upd->execute([$score, $correct, $passed, $attemptId]);

// If passed → generate certificate
$certData = null;
if ($passed) {
    $certData = issueCertificate($pdo, $userId, (int)$attempt['skill_id'], $attemptId);
}

jsonResponse(true, [
    'attempt_id'  => $attemptId,
    'score'       => $score,
    'correct'     => $correct,
    'total'       => $total,
    'passed'      => (bool)$passed,
    'message'     => $passed
        ? "🎉 You passed with {$score}%! Your certificate has been issued."
        : "You scored {$score}%. You need ≥70% to pass. Try again in 24 hours.",
    'certificate' => $certData,
]);

// ─────────────────────────────────────────────────────────────────────────────
function issueCertificate(PDO $pdo, int $userId, int $skillId, int $attemptId): array {
    // Check if certificate already exists
    $check = $pdo->prepare(
        'SELECT * FROM certificates WHERE user_id = ? AND skill_id = ? LIMIT 1'
    );
    $check->execute([$userId, $skillId]);
    $existing = $check->fetch();

    if ($existing) {
        return [
            'cert_token' => $existing['cert_token'],
            'issued_at'  => $existing['issued_at'],
            'already_issued' => true,
        ];
    }

    $token = bin2hex(random_bytes(24));
    $ins   = $pdo->prepare(
        'INSERT INTO certificates (user_id, skill_id, quiz_attempt_id, cert_token)
         VALUES (?, ?, ?, ?)'
    );
    $ins->execute([$userId, $skillId, $attemptId, $token]);

    return [
        'cert_token'     => $token,
        'issued_at'      => date('Y-m-d H:i:s'),
        'already_issued' => false,
    ];
}
