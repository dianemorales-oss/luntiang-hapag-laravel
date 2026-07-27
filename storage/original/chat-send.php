<?php
/**
 * chat-send.php
 * ------------------------------------------------------------------
 * AJAX endpoint used by live-chat.php. Inserts one customer message
 * into live_chat_messages and returns it as JSON so the page can
 * append it to the thread without a full reload (which is what was
 * wiping out anything the customer had typed after only a word or
 * two under the old meta-refresh version of this page).
 * ------------------------------------------------------------------
 */

session_start();
require 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit();
}

// Resolves $chatKey / $customerName / $userId. Logged-in customers
// get a permanent, account-based chat_key so their history survives
// logging out and back in — see includes/chat-session.php.
require __DIR__ . '/includes/chat-session.php';

// The AI Customer Service Assistant that triages every conversation
// before a human agent joins in — see includes/chatbot-engine.php.
require __DIR__ . '/includes/chatbot-engine.php';

// Handle multipart form data (for image upload) or JSON
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$isMultipart = strpos($contentType, 'multipart/form-data') !== false;

if ($isMultipart) {
    $text = trim((string)($_POST['message'] ?? ''));
} else {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    $text = trim((string)($data['message'] ?? $_POST['message'] ?? ''));
}

$imagePath = null;

// Handle image upload
if ($isMultipart && isset($_FILES['chat_image']) && $_FILES['chat_image']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($_FILES['chat_image']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowed) && $_FILES['chat_image']['size'] <= 5 * 1024 * 1024) {
        $destDir = __DIR__ . '/uploads/chat';
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);
        $safeName = bin2hex(random_bytes(8)) . '.' . $ext;
        $destPath = $destDir . '/' . $safeName;
        if (move_uploaded_file($_FILES['chat_image']['tmp_name'], $destPath)) {
            $imagePath = 'uploads/chat/' . $safeName;
        }
    }
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

    $insert = $conn->prepare("
        INSERT INTO live_chat_messages (chat_key, user_id, customer_name, sender, message, image_path)
        VALUES (?, ?, ?, 'customer', ?, ?)
    ");
    $insert->execute([$chatKey, $userId, $customerName, $text ?: null, $imagePath]);
    $newId = (int)$conn->lastInsertId();

    $stmt = $conn->prepare("SELECT * FROM live_chat_messages WHERE id = ?");
    $stmt->execute([$newId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $messages = [$row];

    // Let the AI assistant respond right after the customer's message,
    // unless a human agent has already taken over this conversation.
    $engine = new ChatbotEngine($conn, $chatKey, isset($_SESSION['user_id']));
    $botResult = $engine->respond($text);

    foreach ($botResult['replies'] as $botReply) {
        $botInsert = $conn->prepare("
            INSERT INTO live_chat_messages (chat_key, user_id, customer_name, sender, message)
            VALUES (?, NULL, 'Luntiang H.A.P.A.G. Assistant 🌿', 'bot', ?)
        ");
        $botInsert->execute([$chatKey, $botReply]);
        $botId = (int)$conn->lastInsertId();

        $botStmt = $conn->prepare("SELECT * FROM live_chat_messages WHERE id = ?");
        $botStmt->execute([$botId]);
        $messages[] = $botStmt->fetch(PDO::FETCH_ASSOC);
    }

    echo json_encode(['success' => true, 'message' => $row, 'messages' => $messages, 'escalate' => $botResult['escalate']]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Something went wrong sending your message. Please try again.']);
}