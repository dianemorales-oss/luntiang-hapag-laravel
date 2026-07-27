<?php
/**
 * admin/chat-delete.php
 * ------------------------------------------------------------------
 * AJAX endpoint used by admin-live-chat.php. Permanently deletes one
 * Live Chat conversation: every row in live_chat_messages for the
 * given chat_key, plus its chat_bot_state row (bot on/off state is
 * per-conversation, not customer data).
 *
 * Nothing else is touched — customer accounts, tickets, warranty
 * requests, return requests, feedback, and every other customer
 * record are left completely intact.
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit();
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$chatKey = trim((string)($data['chat_key'] ?? $_POST['chat_key'] ?? ''));

if ($chatKey === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Missing conversation.']);
    exit();
}

try {

    $check = $conn->prepare("SELECT COUNT(*) FROM live_chat_messages WHERE chat_key = ?");
    $check->execute([$chatKey]);
    if ((int)$check->fetchColumn() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Conversation not found.']);
        exit();
    }

    $conn->beginTransaction();

    $deleteMessages = $conn->prepare("DELETE FROM live_chat_messages WHERE chat_key = ?");
    $deleteMessages->execute([$chatKey]);

    // Per-conversation bot state only — no customer data lives here.
    $deleteBotState = $conn->prepare("DELETE FROM chat_bot_state WHERE chat_key = ?");
    $deleteBotState->execute([$chatKey]);

    $conn->commit();

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Something went wrong deleting this conversation. Please try again.']);
}
