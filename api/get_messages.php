<?php
/**
 * get_messages.php
 * GET: Fetch chat messages between two users.
 *
 * Required:
 *   sender_uid   — firebase_uid of one side
 *   receiver_uid — firebase_uid of the other side
 * Optional:
 *   since        — ISO timestamp; only return messages after this time
 *   limit        — number of messages to return (default 50, max 200)
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

$senderUID   = trim($_GET['sender_uid']   ?? '');
$receiverUID = trim($_GET['receiver_uid'] ?? '');

if (!$senderUID || !$receiverUID) {
    jsonResponse(false, 'sender_uid and receiver_uid are required.', 400);
}

$pdo = getDB();

$senderId   = getUserIdByUID($pdo, $senderUID);
$receiverId = getUserIdByUID($pdo, $receiverUID);

if (!$senderId)   jsonResponse(false, 'Sender not found.',   404);
if (!$receiverId) jsonResponse(false, 'Receiver not found.', 404);

$limit = min((int)($_GET['limit'] ?? 50), 200);
$since = $_GET['since'] ?? null;

if ($since) {
    $stmt = $pdo->prepare(
        'SELECT c.chat_id, c.sender_id, c.receiver_id, c.message, c.timestamp,
                u.name AS sender_name, u.firebase_uid AS sender_uid
         FROM chat c
         JOIN users u ON u.id = c.sender_id
         WHERE (
             (c.sender_id = ? AND c.receiver_id = ?)
          OR (c.sender_id = ? AND c.receiver_id = ?)
         )
         AND c.timestamp > ?
         ORDER BY c.timestamp ASC
         LIMIT ?'
    );
    $stmt->execute([$senderId, $receiverId, $receiverId, $senderId, $since, $limit]);
} else {
    $stmt = $pdo->prepare(
        'SELECT c.chat_id, c.sender_id, c.receiver_id, c.message, c.timestamp,
                u.name AS sender_name, u.firebase_uid AS sender_uid
         FROM chat c
         JOIN users u ON u.id = c.sender_id
         WHERE (
             (c.sender_id = ? AND c.receiver_id = ?)
          OR (c.sender_id = ? AND c.receiver_id = ?)
         )
         ORDER BY c.timestamp ASC
         LIMIT ?'
    );
    $stmt->execute([$senderId, $receiverId, $receiverId, $senderId, $limit]);
}

$messages = array_map(function ($row) {
    $row['chat_id']   = (int)$row['chat_id'];
    $row['sender_id'] = (int)$row['sender_id'];
    return $row;
}, $stmt->fetchAll());

jsonResponse(true, $messages);
