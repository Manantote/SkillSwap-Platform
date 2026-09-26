<?php
/**
 * get_chat_partners.php
 * GET: Return all users the current user has accepted swap requests with.
 * These form the chat sidebar.
 *
 * Required:
 *   uid — firebase_uid of current user
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

// Find all users who have an accepted request with this user (either side)
$stmt = $pdo->prepare("
    SELECT DISTINCT
        u.id,
        u.firebase_uid,
        u.name,
        u.email,
        u.last_seen,
        (u.is_online = 1 AND u.last_seen >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)) AS is_currently_online,
        -- Latest message
        (SELECT c.message FROM chat c
         WHERE (c.sender_id = u.id AND c.receiver_id = :me1)
            OR (c.sender_id = :me2 AND c.receiver_id = u.id)
         ORDER BY c.timestamp DESC LIMIT 1) AS last_message,
        (SELECT c.timestamp FROM chat c
         WHERE (c.sender_id = u.id AND c.receiver_id = :me3)
            OR (c.sender_id = :me4 AND c.receiver_id = u.id)
         ORDER BY c.timestamp DESC LIMIT 1) AS last_timestamp,
        -- Teach skills of partner
        GROUP_CONCAT(DISTINCT CASE WHEN us.skill_type='teach' THEN s.skill_name END ORDER BY s.skill_name SEPARATOR ', ') AS teaches,
        GROUP_CONCAT(DISTINCT CASE WHEN us.skill_type='learn' THEN s.skill_name END ORDER BY s.skill_name SEPARATOR ', ') AS wants
    FROM skill_requests sr
    JOIN users u ON u.id = CASE
        WHEN sr.sender_id = :me5 THEN sr.receiver_id
        ELSE sr.sender_id
    END
    LEFT JOIN user_skills us ON us.user_id = u.id
    LEFT JOIN skills s ON s.skill_id = us.skill_id
    WHERE (sr.sender_id = :me6 OR sr.receiver_id = :me7)
      AND sr.status = 'accepted'
    GROUP BY u.id, u.firebase_uid, u.name, u.email
    ORDER BY last_timestamp DESC
");
$stmt->execute([
    ':me1' => $userId, ':me2' => $userId,
    ':me3' => $userId, ':me4' => $userId,
    ':me5' => $userId, ':me6' => $userId,
    ':me7' => $userId,
]);

$partners = array_map(function($row) {
    $row['id'] = (int)$row['id'];
    return $row;
}, $stmt->fetchAll());

jsonResponse(true, $partners);
