<?php
/**
 * get_skills.php
 * GET: Return all skills in the global catalogue.
 *
 * Optional:
 *   q — search query filter
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

$pdo = getDB();
$q   = trim($_GET['q'] ?? '');

if ($q) {
    $stmt = $pdo->prepare(
        'SELECT skill_id, skill_name FROM skills
         WHERE skill_name LIKE ? ORDER BY skill_name LIMIT 30'
    );
    $stmt->execute(['%' . $q . '%']);
} else {
    $stmt = $pdo->query(
        'SELECT skill_id, skill_name FROM skills ORDER BY skill_name'
    );
}

jsonResponse(true, $stmt->fetchAll());
