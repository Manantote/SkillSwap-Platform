<?php
/**
 * report_user.php
 * POST: Submits a safety report against a user.
 *
 * Required:
 *   X-Firebase-UID header
 *   reported_uid
 *   reason
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST method required.', 405);
}

$currentUID = requireUID();
$body       = json_decode(file_get_contents('php://input'), true) ?? [];
$reportedUID = trim($body['reported_uid'] ?? $_POST['reported_uid'] ?? '');
$reason     = trim($body['reason'] ?? $_POST['reason'] ?? '');

if (!$reportedUID) {
    jsonResponse(false, 'reported_uid is required.', 400);
}
if (!$reason) {
    jsonResponse(false, 'reason is required.', 400);
}

$pdo = getDB();
$myId = getUserIdByUID($pdo, $currentUID);
$reportedId = getUserIdByUID($pdo, $reportedUID);

if (!$myId || !$reportedId) {
    jsonResponse(false, 'User not found.', 404);
}

$stmt = $pdo->prepare('INSERT INTO reports (reporter_id, reported_id, reason) VALUES (?, ?, ?)');
$stmt->execute([$myId, $reportedId, $reason]);

jsonResponse(true, ['message' => 'User reported successfully', 'report_id' => $pdo->lastInsertId()]);
