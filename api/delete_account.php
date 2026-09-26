<?php
/**
 * delete_account.php
 * POST: Cascade deletes a user explicitly out of MySQL entirely.
 *
 * Required:
 *   X-Firebase-UID header
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST method required.', 405);
}

$currentUID = requireUID();
$pdo = getDB();
$userId = getUserIdByUID($pdo, $currentUID);

if (!$userId) {
    jsonResponse(false, 'User not found.', 404);
}

// ON DELETE CASCADE on MySQL handles relations.
$stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
$stmt->execute([$userId]);

jsonResponse(true, ['message' => 'Account cascade deleted from server']);
