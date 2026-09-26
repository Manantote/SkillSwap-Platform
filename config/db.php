<?php
/**
 * Skill Swap — Database Configuration
 * Uses PDO with MySQL. Update credentials below.
 */

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'skillswap');
define('DB_USER', 'root');          
define('DB_PASS', '');              
define('DB_CHARSET', 'utf8mb4');

// Catch any unhandled PDOExceptions and output as valid JSON instead of generic 500
set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error'   => 'Server Exception: ' . $e->getMessage()
    ]);
    exit;
});

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
            exit;
        }
    }
    return $pdo;
}

// ── Helper functions ──────────────────────────────────────────────────────────

/**
 * Send a JSON response and exit.
 */
function jsonResponse(bool $success, mixed $data = null, int $status = 200): void {
    http_response_code($status);
    $key = $success ? 'data' : 'error';
    echo json_encode(['success' => $success, $key => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Get the Firebase UID from the request header or POST body.
 * Returns null if not present.
 */
function getFirebaseUID(): ?string {
    // Try header first (preferred)
    $uid = $_SERVER['HTTP_X_FIREBASE_UID'] ?? null;
    if ($uid) return trim($uid);

    // Fall back to POST body / GET param
    $uid = $_POST['firebase_uid'] ?? $_GET['firebase_uid'] ?? null;
    return $uid ? trim($uid) : null;
}

/**
 * Require a valid Firebase UID or abort with 401.
 */
function requireUID(): string {
    $uid = getFirebaseUID();
    if (!$uid) {
        jsonResponse(false, 'Authentication required.', 401);
    }
    return $uid;
}

/**
 * Get the internal user ID (users.id) from a firebase_uid.
 */
function getUserIdByUID(PDO $pdo, string $uid): ?int {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE firebase_uid = ?');
    $stmt->execute([$uid]);
    $row = $stmt->fetch();
    return $row ? (int)$row['id'] : null;
}

/**
 * Set CORS headers (allow access from the same origin or localhost).
 */
function setCORSHeaders(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Firebase-UID');
    header('Access-Control-Allow-Credentials: true');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
