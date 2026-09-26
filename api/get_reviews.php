<?php
/**
 * get_reviews.php
 * GET: Fetch reviews for a user (reviews they received).
 *
 * Required:
 *   uid — firebase_uid of the user whose reviews to fetch
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

$uid = trim($_GET['uid'] ?? '');
if (!$uid) {
    jsonResponse(false, 'uid parameter is required.', 400);
}

$pdo    = getDB();
$userId = getUserIdByUID($pdo, $uid);
if (!$userId) jsonResponse(false, 'User not found.', 404);

// Aggregate summary
$summary = $pdo->prepare(
    'SELECT
        ROUND(AVG(rating), 1) AS avg_rating,
        COUNT(*)              AS total,
        SUM(rating = 5)       AS five,
        SUM(rating = 4)       AS four,
        SUM(rating = 3)       AS three,
        SUM(rating = 2)       AS two,
        SUM(rating = 1)       AS one
     FROM reviews WHERE reviewed_user_id = ?'
);
$summary->execute([$userId]);
$stats = $summary->fetch();

// Individual reviews with reviewer info
$stmt = $pdo->prepare(
    'SELECT r.review_id, r.rating, r.comment, r.tags, r.created_at,
            u.name AS reviewer_name, u.firebase_uid AS reviewer_uid
     FROM reviews r
     JOIN users u ON u.id = r.reviewer_id
     WHERE r.reviewed_user_id = ?
     ORDER BY r.created_at DESC'
);
$stmt->execute([$userId]);
$reviews = array_map(function ($row) {
    $row['review_id'] = (int)$row['review_id'];
    $row['rating']    = (int)$row['rating'];
    $row['tags']      = $row['tags'] ? explode(',', $row['tags']) : [];
    return $row;
}, $stmt->fetchAll());

jsonResponse(true, [
    'summary' => [
        'avg_rating' => (float)($stats['avg_rating'] ?? 0),
        'total'      => (int)($stats['total']      ?? 0),
        'breakdown'  => [
            '5' => (int)($stats['five']  ?? 0),
            '4' => (int)($stats['four']  ?? 0),
            '3' => (int)($stats['three'] ?? 0),
            '2' => (int)($stats['two']   ?? 0),
            '1' => (int)($stats['one']   ?? 0),
        ],
    ],
    'reviews' => $reviews,
]);
