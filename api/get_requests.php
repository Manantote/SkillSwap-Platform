<?php
/**
 * get_requests.php
 * GET: Fetch sent and received skill swap requests for a user.
 *
 * Required:
 *   uid — firebase_uid
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
if (!$userId) jsonResponse(false, 'User not found.', 404);

// Helper to enrich request rows with user info
function enrichRequests(PDO $pdo, array $rows, string $role): array {
    // $role: 'other_as_sender' or 'other_as_receiver'
    return array_map(function ($row) use ($pdo, $role) {
        $otherId = ($role === 'receiver')
            ? $row['receiver_id']
            : $row['sender_id'];

        $uStmt = $pdo->prepare('SELECT name, email, firebase_uid FROM users WHERE id = ?');
        $uStmt->execute([$otherId]);
        $other = $uStmt->fetch();

        // Teach/learn skills of the OTHER user
        $tsStmt = $pdo->prepare(
            'SELECT s.skill_name FROM user_skills us
             JOIN skills s ON s.skill_id = us.skill_id
             WHERE us.user_id = ? AND us.skill_type = "teach"'
        );
        $tsStmt->execute([$otherId]);
        $lsStmt = $pdo->prepare(
            'SELECT s.skill_name FROM user_skills us
             JOIN skills s ON s.skill_id = us.skill_id
             WHERE us.user_id = ? AND us.skill_type = "learn"'
        );
        $lsStmt->execute([$otherId]);

        return [
            'request_id'      => (int)$row['request_id'],
            'status'          => $row['status'],
            'message'         => $row['message'],
            'created_at'      => $row['created_at'],
            'other_user'      => $other,
            'other_teaches'   => $tsStmt->fetchAll(PDO::FETCH_COLUMN),
            'other_wants'     => $lsStmt->fetchAll(PDO::FETCH_COLUMN),
        ];
    }, $rows);
}

// Received requests
$rStmt = $pdo->prepare(
    'SELECT * FROM skill_requests WHERE receiver_id = ? ORDER BY created_at DESC'
);
$rStmt->execute([$userId]);
$received = enrichRequests($pdo, $rStmt->fetchAll(), 'sender');

// Sent requests
$sStmt = $pdo->prepare(
    'SELECT * FROM skill_requests WHERE sender_id = ? ORDER BY created_at DESC'
);
$sStmt->execute([$userId]);
$sent = enrichRequests($pdo, $sStmt->fetchAll(), 'receiver');

// Accepted (from both sides)
$accepted = array_filter(
    array_merge($received, $sent),
    fn($r) => $r['status'] === 'accepted'
);

jsonResponse(true, [
    'received' => $received,
    'sent'     => $sent,
    'accepted' => array_values($accepted),
]);
