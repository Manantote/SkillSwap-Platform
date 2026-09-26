<?php
/**
 * block_user.php
 * POST: Blocks a user, preventing them from sending messages to the blocker.
 *
 * Required:
 *   X-Firebase-UID header
 *   blocked_uid 
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST method required.', 405);
}

$currentUID = requireUID();
$body       = json_decode(file_get_contents('php://input'), true) ?? [];
$blockedUID = trim($body['blocked_uid'] ?? $_POST['blocked_uid'] ?? '');

if (!$blockedUID) {
    jsonResponse(false, 'blocked_uid is required.', 400);
}
if ($currentUID === $blockedUID) {
    jsonResponse(false, 'You cannot block yourself.', 400);
}

$pdo = getDB();
$myId = getUserIdByUID($pdo, $currentUID);
$blockedId = getUserIdByUID($pdo, $blockedUID);

if (!$myId || !$blockedId) {
    jsonResponse(false, 'User not found.', 404);
}

// Ensure the block doesn't already exist
$stmt = $pdo->prepare('INSERT IGNORE INTO blocked_users (blocker_id, blocked_id) VALUES (?, ?)');
$stmt->execute([$myId, $blockedId]);

jsonResponse(true, ['message' => 'User blocked successfully']);
