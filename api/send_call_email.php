<?php
/**
 * send_call_email.php
 * POST: Sends an automated email notification for an incoming video call.
 *
 * Required:
 *   X-Firebase-UID header (sender)
 *   receiver_uid — firebase_uid of the recipient
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST method required.', 405);
}

$senderUID = requireUID();
$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$receiverUID = trim($body['receiver_uid'] ?? $_POST['receiver_uid'] ?? '');

if (!$receiverUID) {
    jsonResponse(false, 'receiver_uid is required.', 400);
}

$pdo = getDB();

// 1. Get Sender Info
$stmtSender = $pdo->prepare('SELECT name FROM users WHERE firebase_uid = ?');
$stmtSender->execute([$senderUID]);
$sender = $stmtSender->fetch();
if (!$sender) {
    jsonResponse(false, 'Sender not found.', 404);
}

// 2. Get Receiver Info
$stmtReceiver = $pdo->prepare('SELECT email, name FROM users WHERE firebase_uid = ?');
$stmtReceiver->execute([$receiverUID]);
$receiver = $stmtReceiver->fetch();
if (!$receiver) {
    jsonResponse(false, 'Receiver not found.', 404);
}

// 3. Send Email
$to      = $receiver['email'];
$subject = 'Incoming Video Call on Skill Swap';
$message = 'Hello ' . htmlspecialchars($receiver['name']) . ",\r\n\r\n" .
           htmlspecialchars($sender['name']) . " is inviting you to a video call right now!\r\n\r\n" .
           "Join here: https://meet.google.com/new\r\n\r\n" .
           "Happy Swapping,\r\nThe Skill Swap Team";

$headers = "From: noreply@skillswap.com" . "\r\n" .
           "Reply-To: noreply@skillswap.com" . "\r\n" .
           "X-Mailer: PHP/" . phpversion();

// PHP mail() function requires a properly configured mail server (like sendmail/postfix on Linux)
// For local XAMPP environments, this might fail unless sendmail path is configured in php.ini.
// But we will attempt it as requested.
$mailSent = @mail($to, $subject, $message, $headers);

jsonResponse(true, [
    'message' => 'Email notification processed.',
    'mail_sent' => $mailSent
]);
