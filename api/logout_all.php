<?php
/**
 * logout_all.php
 * POST: Invalidates server-side logic by forcibly crushing the last_seen TTL footprint. Keep in mind Firebase requires Node Admin verification to revoke refresh arrays globally, so this is a structural stub mimicking the invalidation flow synchronously.
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

// Set offline explicitly.
$stmt = $pdo->prepare("UPDATE users SET last_seen = '2000-01-01 00:00:00' WHERE id = ?");
$stmt->execute([$userId]);

jsonResponse(true, ['message' => 'Logged out of all simulated environments']);
