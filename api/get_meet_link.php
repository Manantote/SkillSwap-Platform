<?php
/**
 * get_meet_link.php
 * GET or POST: Returns a persistent Google Meet link between two users.
 * Creates one if it doesn't exist yet.
 * 
 * Required:
 *   X-Firebase-UID header (current user)
 *   partner_uid — firebase_uid of the chat partner
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

$currentUID = requireUID();
$partnerUID = $_REQUEST['partner_uid'] ?? '';
$body       = json_decode(file_get_contents('php://input'), true) ?? [];
if (!$partnerUID && isset($body['partner_uid'])) {
    $partnerUID = trim($body['partner_uid']);
}

if (!$partnerUID) {
    jsonResponse(false, 'partner_uid is required.', 400);
}

$pdo = getDB();

$myId = getUserIdByUID($pdo, $currentUID);
$partnerId = getUserIdByUID($pdo, $partnerUID);

if (!$myId || !$partnerId) {
    jsonResponse(false, 'User not found.', 404);
}

// Order IDs to make standard lookups easy
$u1 = min($myId, $partnerId);
$u2 = max($myId, $partnerId);

// Check if meeting exists
$stmt = $pdo->prepare('SELECT meet_link FROM meetings WHERE user1_id = ? AND user2_id = ? LIMIT 1');
$stmt->execute([$u1, $u2]);
$existing = $stmt->fetchColumn();

if ($existing) {
    jsonResponse(true, ['meet_link' => $existing]);
    exit;
}

// Generate new random Google Meet code format (aaa-bbbb-ccc)
function randStr($len) {
    $s = '';
    for ($i=0; $i<$len; $i++) $s .= chr(mt_rand(97, 122));
    return $s;
}
$link = 'https://meet.google.com/' . randStr(3) . '-' . randStr(4) . '-' . randStr(3);

$insert = $pdo->prepare('INSERT INTO meetings (user1_id, user2_id, meet_link) VALUES (?, ?, ?)');
$insert->execute([$u1, $u2, $link]);

jsonResponse(true, ['meet_link' => $link]);
