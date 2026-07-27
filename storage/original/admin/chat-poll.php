<?php
/**
 * admin/chat-poll.php
 * ------------------------------------------------------------------
 * AJAX endpoint used by admin-live-chat.php.
 *
 *   ?action=messages&chat_key=X&after_id=N
 *       Returns messages newer than after_id for one conversation.
 *
 *   ?action=conversations
 *       Returns the full conversation list (for the sidebar), so it
 *       can be refreshed in place without reloading the page and
 *       losing whatever the admin is currently typing.
 * ------------------------------------------------------------------
 */

session_start();
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit();
}

$action = $_GET['action'] ?? 'messages';

if ($action === 'conversations') {

    $conversations = $conn->query("
        SELECT lcm.chat_key,
               MAX(lcm.created_at) AS last_message_at,
               (SELECT customer_name FROM live_chat_messages x
                WHERE x.chat_key = lcm.chat_key AND x.sender = 'customer'
                ORDER BY x.created_at ASC, x.id ASC
                LIMIT 1) AS customer_name,
               (SELECT message FROM live_chat_messages x WHERE x.chat_key = lcm.chat_key ORDER BY x.created_at DESC LIMIT 1) AS last_message,
               SUM(CASE WHEN lcm.sender = 'customer' THEN 1 ELSE 0 END) AS customer_message_count
        FROM live_chat_messages lcm
        GROUP BY lcm.chat_key
        ORDER BY last_message_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'conversations' => $conversations]);
    exit();

}

// Default: messages for one conversation
$chatKey = trim($_GET['chat_key'] ?? '');
$afterId = (int)($_GET['after_id'] ?? 0);

if ($chatKey === '') {
    echo json_encode(['success' => true, 'messages' => []]);
    exit();
}

$stmt = $conn->prepare("SELECT * FROM live_chat_messages WHERE chat_key = ? AND id > ? ORDER BY created_at ASC, id ASC");
$stmt->execute([$chatKey, $afterId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'messages' => $messages]);
