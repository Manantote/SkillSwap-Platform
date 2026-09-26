<?php
/**
 * add_notification.php
 * POST: Inserts a new notification for a specific user.
 *
 * Required:
 *   X-Firebase-UID header (sender)
 *   receiver_uid
 *   type (e.g. 'video_call', 'chat', 'request', 'review')
 *   message
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST method required.', 405);
}

$currentUID = requireUID(); // Validates sender is logged in
$body       = json_decode(file_get_contents('php://input'), true) ?? [];
$receiverUID = trim($body['receiver_uid'] ?? $_POST['receiver_uid'] ?? '');
$type       = trim($body['type'] ?? $_POST['type'] ?? 'general');
$message    = trim($body['message'] ?? $_POST['message'] ?? '');

if (!$receiverUID || !$message) {
    jsonResponse(false, 'receiver_uid and message are required.', 400);
}

// Ensure the receiver actually exists
$pdo = getDB();
$receiverId = getUserIdByUID($pdo, $receiverUID);

if (!$receiverId) {
    jsonResponse(false, 'Receiver not found.', 404);
}

// Prevent notifications if receiver has blocked the sender
$senderId = getUserIdByUID($pdo, $currentUID);
if ($senderId) {
    $blockCheck = $pdo->prepare('SELECT 1 FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?');
    $blockCheck->execute([$receiverId, $senderId]);
    if ($blockCheck->fetch()) {
        jsonResponse(false, 'Cannot notify blocked user.', 403);
    }
}

// Insert notification
$stmt = $pdo->prepare('INSERT INTO notifications (user_id, type, message) VALUES (?, ?, ?)');
$stmt->execute([$receiverId, $type, $message]);

jsonResponse(true, ['message' => 'Notification added successfully']);
