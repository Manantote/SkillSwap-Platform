<?php
/**
 * reject_request.php
 * POST: Reject a pending skill swap request.
 *
 * Required:
 *   X-Firebase-UID header (must be the receiver)
 *   request_id
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST method required.', 405);
}

$uid  = requireUID();
$body = json_decode(file_get_contents('php://input'), true) ?? [];

$requestId = (int)($body['request_id'] ?? $_POST['request_id'] ?? 0);
if (!$requestId) {
    jsonResponse(false, 'request_id is required.', 400);
}

$pdo    = getDB();
$userId = getUserIdByUID($pdo, $uid);
if (!$userId) jsonResponse(false, 'User not found.', 404);

// Verify this user is the receiver
$stmt = $pdo->prepare(
    'SELECT request_id FROM skill_requests
     WHERE request_id = ? AND receiver_id = ? AND status = "pending"'
);
$stmt->execute([$requestId, $userId]);
if (!$stmt->fetch()) {
    jsonResponse(false, 'Request not found or already processed.', 404);
}

$upd = $pdo->prepare(
    'UPDATE skill_requests SET status = "rejected" WHERE request_id = ?'
);
$upd->execute([$requestId]);

jsonResponse(true, ['request_id' => $requestId, 'status' => 'rejected']);
