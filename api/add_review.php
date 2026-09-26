<?php
/**
 * add_review.php
 * POST: Submit a review for a swap partner.
 *
 * Required:
 *   X-Firebase-UID header (reviewer)
 *   reviewed_uid — firebase_uid of the user being reviewed
 *   rating       — integer 1-5
 * Optional:
 *   comment — text review
 *   tags    — comma-separated tag labels (e.g. "Knowledgeable,Patient")
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST method required.', 405);
}

$reviewerUID = requireUID();
$body        = json_decode(file_get_contents('php://input'), true) ?? [];

$reviewedUID = trim($body['reviewed_uid'] ?? $_POST['reviewed_uid'] ?? '');
$rating      = (int)($body['rating']      ?? $_POST['rating']       ?? 0);
$comment     = trim($body['comment']      ?? $_POST['comment']      ?? '');
$tags        = trim($body['tags']         ?? $_POST['tags']         ?? '');

if (!$reviewedUID)              jsonResponse(false, 'reviewed_uid is required.', 400);
if ($reviewerUID === $reviewedUID) jsonResponse(false, 'You cannot review yourself.', 400);
if ($rating < 1 || $rating > 5) jsonResponse(false, 'rating must be 1–5.', 400);

$pdo = getDB();

$reviewerId  = getUserIdByUID($pdo, $reviewerUID);
$reviewedId  = getUserIdByUID($pdo, $reviewedUID);

if (!$reviewerId)  jsonResponse(false, 'Reviewer not found.',      404);
if (!$reviewedId)  jsonResponse(false, 'Reviewed user not found.', 404);

// Prevent duplicate review from same reviewer
$dup = $pdo->prepare(
    'SELECT review_id FROM reviews WHERE reviewer_id = ? AND reviewed_user_id = ?'
);
$dup->execute([$reviewerId, $reviewedId]);
if ($dup->fetch()) {
    jsonResponse(false, 'You have already reviewed this user.', 409);
}

$stmt = $pdo->prepare(
    'INSERT INTO reviews (reviewer_id, reviewed_user_id, rating, comment, tags)
     VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([
    $reviewerId,
    $reviewedId,
    $rating,
    $comment ?: null,
    $tags    ?: null,
]);

jsonResponse(true, ['review_id' => (int)$pdo->lastInsertId()]);
