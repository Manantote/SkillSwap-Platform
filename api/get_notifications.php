<?php
/**
 * get_notifications.php
 * GET: Fetch recent notifications for the logged-in user.
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'GET method required.', 405);
}

$currentUID = requireUID();
$pdo = getDB();
$userId = getUserIdByUID($pdo, $currentUID);

if (!$userId) {
    jsonResponse(false, 'User not found.', 404);
}

// Fetch up to 30 recent notifications
$stmt = $pdo->prepare('
    SELECT id, type, message, is_read, created_at 
    FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 30
');
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count unread
$unreadStmt = $pdo->prepare('SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0');
$unreadStmt->execute([$userId]);
$unreadCount = $unreadStmt->fetch(PDO::FETCH_ASSOC)['unread_count'];

jsonResponse(true, [
    'notifications' => $notifications,
    'unread_count' => (int)$unreadCount
]);
