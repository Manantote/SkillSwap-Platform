<?php
/**
 * set_offline.php
 * POST: Marks the logged-in user as offline.
 * Updates is_online = 0 and last_seen = NOW()
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST method required.', 405);
}

$uid = requireUID();
$pdo = getDB();
$userId = getUserIdByUID($pdo, $uid);

if (!$userId) {
    jsonResponse(false, 'User not found.', 404);
}

$stmt = $pdo->prepare('UPDATE users SET is_online = 0, last_seen = NOW() WHERE id = ?');
$stmt->execute([$userId]);

jsonResponse(true, ['message' => 'Status set to offline']);
