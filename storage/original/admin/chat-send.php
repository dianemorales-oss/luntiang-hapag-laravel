<?php
/**
 * admin/chat-send.php
 * ------------------------------------------------------------------
 * AJAX endpoint used by admin-live-chat.php. Inserts one admin
 * message into live_chat_messages for a given chat_key and returns
 * it as JSON, so the admin panel can append it without a full page
 * reload (which used to run every 8 seconds via meta refresh and
 * would wipe out anything the admin was mid-typing).
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

// Handle multipart form data (for image upload) or JSON
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$isMultipart = strpos($contentType, 'multipart/form-data') !== false;

if ($isMultipart) {
    $chatKey = trim((string)($_POST['chat_key'] ?? ''));
    $text = trim((string)($_POST['message'] ?? ''));
} else {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    $chatKey = trim((string)($data['chat_key'] ?? $_POST['chat_key'] ?? ''));
    $text = trim((string)($data['message'] ?? $_POST['message'] ?? ''));
}

$imagePath = null;

// Handle image upload
if ($isMultipart && isset($_FILES['chat_image']) && $_FILES['chat_image']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($_FILES['chat_image']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowed) && $_FILES['chat_image']['size'] <= 5 * 1024 * 1024) {
        $destDir = __DIR__ . '/../uploads/chat';
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);
        $safeName = bin2hex(random_bytes(8)) . '.' . $ext;
        $destPath = $destDir . '/' . $safeName;
        if (move_uploaded_file($_FILES['chat_image']['tmp_name'], $destPath)) {
            $imagePath = 'uploads/chat/' . $safeName;
        }
    }
}

if ($chatKey === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Missing conversation.']);
    exit();
}

if ($text === '' && !$imagePath) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Message cannot be empty.']);
    exit();
}

if ($text !== '' && mb_strlen($text) > 250) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Messages are limited to 250 characters.']);
    exit();
}

try {

    // Make sure this chat_key actually exists before replying to it.
    $check = $conn->prepare("SELECT COUNT(*) FROM live_chat_messages WHERE chat_key = ?");
    $check->execute([$chatKey]);
    if ((int)$check->fetchColumn() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Conversation not found.']);
        exit();
    }

    $insert = $conn->prepare("
        INSERT INTO live_chat_messages (chat_key, user_id, customer_name, sender, message, image_path)
        VALUES (?, NULL, 'Luntiang H.A.P.A.G. Support', 'admin', ?, ?)
    ");
    $insert->execute([$chatKey, $text ?: null, $imagePath]);
    $newId = (int)$conn->lastInsertId();

    // A human agent has now joined this conversation — stop the AI
    // assistant from replying to further messages in it.
    $conn->prepare("
        INSERT INTO chat_bot_state (chat_key, bot_active) VALUES (?, 0)
        ON DUPLICATE KEY UPDATE bot_active = 0, pending_intent = NULL, pending_context = NULL
    ")->execute([$chatKey]);

    $stmt = $conn->prepare("SELECT * FROM live_chat_messages WHERE id = ?");
    $stmt->execute([$newId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'message' => $row]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Something went wrong sending this reply. Please try again.']);
}
