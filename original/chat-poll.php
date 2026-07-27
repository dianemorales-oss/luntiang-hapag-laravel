<?php
/**
 * chat-poll.php
 * ------------------------------------------------------------------
 * AJAX endpoint used by live-chat.php to fetch any messages newer
 * than ?after_id=N for the current visitor's chat_key, so the page
 * can pick up admin replies without a disruptive full-page reload.
 * ------------------------------------------------------------------
 */

session_start();
require 'config.php';

header('Content-Type: application/json');

// Resolves $chatKey (and $customerName / $userId, unused here). For
// guests this depends entirely on a ?gk=... the client sends back
// (see includes/chat-session.php) — if it's missing, there's no
// existing conversation to poll for, so bail out early instead of
// resolving (and thereby minting) a brand-new, empty one.
if (!isset($_SESSION['user_id']) && empty($_GET['gk'])) {
    echo json_encode(['success' => true, 'messages' => []]);
    exit();
}
require __DIR__ . '/includes/chat-session.php';

$afterId = (int)($_GET['after_id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM live_chat_messages WHERE chat_key = ? AND id > ? ORDER BY created_at ASC, id ASC");
$stmt->execute([$chatKey, $afterId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'messages' => $messages]);