<?php
/**
 * clear_chat.php
 * POST: Deletes all messages between the logged-in user and a partner.
 *
 * Required:
 *   X-Firebase-UID header
 *   partner_uid 
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST method required.', 405);
}

$currentUID = requireUID();
$body       = json_decode(file_get_contents('php://input'), true) ?? [];
$partnerUID = trim($body['partner_uid'] ?? $_POST['partner_uid'] ?? '');

if (!$partnerUID) {
    jsonResponse(false, 'partner_uid is required.', 400);
}

$pdo = getDB();
$myId = getUserIdByUID($pdo, $currentUID);
$partnerId = getUserIdByUID($pdo, $partnerUID);

if (!$myId || !$partnerId) {
    jsonResponse(false, 'User not found.', 404);
}

$stmt = $pdo->prepare(
    'DELETE FROM chat 
     WHERE (sender_id = ? AND receiver_id = ?) 
        OR (sender_id = ? AND receiver_id = ?)'
);
$stmt->execute([$myId, $partnerId, $partnerId, $myId]);

jsonResponse(true, ['message' => 'Chat cleared successfully', 'deleted_count' => $stmt->rowCount()]);
