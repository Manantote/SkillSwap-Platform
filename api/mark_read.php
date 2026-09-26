<?php
/**
 * mark_read.php
 * POST: Marks a specific notification or all notifications as read.
 *
 * Required:
 *   X-Firebase-UID header
 *   Optional: notification_id (if missed, marks all as read)
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST method required.', 405);
}

$currentUID = requireUID();
$body       = json_decode(file_get_contents('php://input'), true) ?? [];
$notifId    = $body['notification_id'] ?? $_POST['notification_id'] ?? null;

$pdo = getDB();
$userId = getUserIdByUID($pdo, $currentUID);

if (!$userId) {
    jsonResponse(false, 'User not found.', 404);
}

if ($notifId) {
    // Mark specific notification as read
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $stmt->execute([(int)$notifId, $userId]);
} else {
    // Mark all as read
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
    $stmt->execute([$userId]);
}

jsonResponse(true, ['message' => 'Notifications updated']);
