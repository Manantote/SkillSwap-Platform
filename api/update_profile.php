<?php
/**
 * update_profile.php
 * POST: Updates the user's profile information (name) in MySQL.
 *
 * Required:
 *   X-Firebase-UID header
 *   name
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST method required.', 405);
}

$currentUID = requireUID();
$body       = json_decode(file_get_contents('php://input'), true) ?? [];
$name       = trim($body['name'] ?? $_POST['name'] ?? '');

if (!$name) {
    jsonResponse(false, 'Name is required.', 400);
}

$pdo = getDB();
$userId = getUserIdByUID($pdo, $currentUID);

if (!$userId) {
    jsonResponse(false, 'User not found.', 404);
}

// Update name in DB
$stmt = $pdo->prepare('UPDATE users SET name = ? WHERE id = ?');
$stmt->execute([$name, $userId]);

jsonResponse(true, ['message' => 'Profile updated successfully']);
