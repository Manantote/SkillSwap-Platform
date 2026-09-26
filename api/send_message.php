<?php
/**
 * send_message.php
 * POST: Send a chat message.
 *
 * Required:
 *   X-Firebase-UID header (sender)
 *   receiver_uid — firebase_uid of the recipient
 *   message      — text content
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST method required.', 405);
}

$senderUID = requireUID();
$body      = json_decode(file_get_contents('php://input'), true) ?? [];

$receiverUID = trim($body['receiver_uid'] ?? $_POST['receiver_uid'] ?? '');
$message     = trim($body['message']      ?? $_POST['message']      ?? '');

if (!$receiverUID) jsonResponse(false, 'receiver_uid is required.', 400);
if (!$message)     jsonResponse(false, 'message is required.',      400);
if (mb_strlen($message) > 5000) jsonResponse(false, 'Message too long.', 400);

$pdo = getDB();

$senderId   = getUserIdByUID($pdo, $senderUID);
$receiverId = getUserIdByUID($pdo, $receiverUID);

if (!$senderId)   jsonResponse(false, 'Sender not found.',   404);
if (!$receiverId) jsonResponse(false, 'Receiver not found.', 404);

// ── Check if a block exists between these two users ──
$blockCheck = $pdo->prepare(
    'SELECT 1 FROM blocked_users 
     WHERE (blocker_id = ? AND blocked_id = ?) 
        OR (blocker_id = ? AND blocked_id = ?)'
);
$blockCheck->execute([$senderId, $receiverId, $receiverId, $senderId]);
if ($blockCheck->fetch()) {
    jsonResponse(false, 'Cannot send message. A block is active between these users.', 403);
}

// ── Prevent duplicate message spam within 2 seconds ──
$dupCheck = $pdo->prepare(
    'SELECT chat_id FROM chat 
     WHERE sender_id = ? AND receiver_id = ? AND message = ? 
     AND timestamp >= DATE_SUB(NOW(), INTERVAL 2 SECOND)
     LIMIT 1'
);
$dupCheck->execute([$senderId, $receiverId, $message]);
if ($dupCheck->fetchColumn()) {
    jsonResponse(false, 'Duplicate message detected. Please wait.', 409);
}

$stmt = $pdo->prepare(
    'INSERT INTO chat (sender_id, receiver_id, message) VALUES (?, ?, ?)'
);
$stmt->execute([$senderId, $receiverId, $message]);

jsonResponse(true, [
    'chat_id'   => (int)$pdo->lastInsertId(),
    'timestamp' => date('Y-m-d H:i:s'),
]);
