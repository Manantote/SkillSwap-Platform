<?php
/**
 * send_request.php
 * POST: Send a skill swap request to another user.
 *
 * Required:
 *   X-Firebase-UID header (sender)
 *   receiver_uid — firebase_uid of the receiver
 * Optional:
 *   skill_id — specific skill being requested
 *   message  — personal message
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST method required.', 405);
}

$senderUID = requireUID();
$body       = json_decode(file_get_contents('php://input'), true) ?? [];

$receiverUID = trim($body['receiver_uid'] ?? $_POST['receiver_uid'] ?? '');
$skillId     = (int)($body['skill_id']    ?? $_POST['skill_id']    ?? 0);
$message     = trim($body['message']      ?? $_POST['message']     ?? '');

if (!$receiverUID) {
    jsonResponse(false, 'receiver_uid is required.', 400);
}
if ($receiverUID === $senderUID) {
    jsonResponse(false, 'You cannot send a request to yourself.', 400);
}

$pdo = getDB();

$senderId   = getUserIdByUID($pdo, $senderUID);
$receiverId = getUserIdByUID($pdo, $receiverUID);

if (!$senderId)   jsonResponse(false, 'Sender not found.',   404);
if (!$receiverId) jsonResponse(false, 'Receiver not found.', 404);

// Check for duplicate pending request
$dup = $pdo->prepare(
    'SELECT request_id FROM skill_requests
     WHERE sender_id = ? AND receiver_id = ? AND status = "pending"'
);
$dup->execute([$senderId, $receiverId]);
if ($dup->fetch()) {
    jsonResponse(false, 'A pending request already exists.', 409);
}

$stmt = $pdo->prepare(
    'INSERT INTO skill_requests (sender_id, receiver_id, skill_id, message)
     VALUES (?, ?, ?, ?)'
);
$stmt->execute([
    $senderId,
    $receiverId,
    $skillId ?: null,
    $message ?: null,
]);

jsonResponse(true, ['request_id' => (int)$pdo->lastInsertId()]);
