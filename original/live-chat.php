<?php
session_start();
require 'config.php';

// Resolves $chatKey / $customerName / $userId. Logged-in customers
// get a permanent, account-based chat_key so their history survives
// logging out and back in — see includes/chat-session.php.
require __DIR__ . '/includes/chat-session.php';

// The AI Customer Service Assistant that triages every conversation
// before a human agent joins in — see includes/chatbot-engine.php.
require __DIR__ . '/includes/chatbot-engine.php';

$message = "";
$messageType = "";

// If this is a brand-new conversation, seed it with the assistant's
// greeting so the bot is always the customer's first point of contact.
$countStmt = $conn->prepare("SELECT COUNT(*) FROM live_chat_messages WHERE chat_key = ?");
$countStmt->execute([$chatKey]);
if ((int)$countStmt->fetchColumn() === 0) {
    $conn->prepare("
        INSERT INTO live_chat_messages (chat_key, user_id, customer_name, sender, message)
        VALUES (?, NULL, 'Luntiang H.A.P.A.G. Assistant', 'bot', ?)
    ")->execute([$chatKey, ChatbotEngine::greeting()]);
}

// Plain form-submit fallback for when JavaScript is unavailable —
// the chat itself works via AJAX (see the script below), but this
// keeps the page functional either way. Mirrors the same validation
// as chat-send.php.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $text = trim($_POST['message'] ?? '');

    if ($text === '') {
        $message = "Please write a message before sending.";
        $messageType = "error";
    } elseif (mb_strlen($text) > 250) {
        $message = "Messages are limited to 250 characters.";
        $messageType = "error";
    } else {
        try {
            $insert = $conn->prepare("
                INSERT INTO live_chat_messages (chat_key, user_id, customer_name, sender, message)
                VALUES (?, ?, ?, 'customer', ?)
            ");
            $insert->execute([$chatKey, $userId, $customerName, $text]);

            $engine = new ChatbotEngine($conn, $chatKey, isset($_SESSION['user_id']));
            $botResult = $engine->respond($text);
            foreach ($botResult['replies'] as $botReply) {
                $conn->prepare("
                    INSERT INTO live_chat_messages (chat_key, user_id, customer_name, sender, message)
                    VALUES (?, NULL, 'Luntiang H.A.P.A.G. Assistant', 'bot', ?)
                ")->execute([$chatKey, $botReply]);
            }

            header("Location: live-chat.php");
            exit();
        } catch (PDOException $e) {
            $message = "Something went wrong sending your message. Please try again.";
            $messageType = "error";
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM live_chat_messages WHERE chat_key = ? ORDER BY created_at ASC, id ASC");
$stmt->execute([$chatKey]);
$chatMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
$lastId = !empty($chatMessages) ? (int)end($chatMessages)['id'] : 0;

// Quick action buttons shown above the composer, sourced from the
// same knowledge base the assistant matches against.
$chatbotKb = require __DIR__ . '/includes/chatbot-knowledge.php';
$quickActionsPrimary = $chatbotKb['quick_actions']['primary'];
$quickActionsMore = $chatbotKb['quick_actions']['more'];

/**
 * Detects a bulleted list of *suggested questions* inside a bot
 * message (e.g. the FAQ overview reply) so it can be rendered as
 * clickable suggestion chips instead of plain bullet text.
 *
 * Only bullet blocks that are themselves phrased as questions
 * qualify — this is what keeps a "have ready: • Subject • Category…"
 * requirements list (not something a customer would ever "ask") from
 * being turned into clickable chips, without needing any changes to
 * the knowledge base content itself.
 *
 * Returns the list of questions, or null if this message doesn't
 * contain a suggestion list.
 */
function extractQuestionSuggestions(string $text): ?array
{
    $lines = preg_split('/\r\n|\r|\n/', $text);
    $bullets = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (mb_strpos($trimmed, '• ') === 0) {
            $bullets[] = trim(mb_substr($trimmed, 2));
        }
    }
    if (empty($bullets)) {
        return null;
    }
    $questionCount = 0;
    foreach ($bullets as $b) {
        if (substr(rtrim($b), -1) === '?') {
            $questionCount++;
        }
    }
    return ($questionCount / count($bullets)) >= 0.5 ? $bullets : null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Live Chat | Luntiang H.A.P.A.G.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Nunito', sans-serif; }
    .font-black { font-family: 'Nunito', serif; }

    /* Suggested Questions bar — horizontal, no wrap, hidden scrollbar
       but still natively scrollable (mouse wheel handled in JS, touch
       swipe works via native overflow-x). */
    #suggestionChips {
      display: flex;
      flex-wrap: nowrap;
      overflow-x: auto;
      scroll-behavior: smooth;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: none;
      gap: 0.5rem;
      padding: 0.25rem 0.125rem;
      -webkit-mask-image: linear-gradient(to right, transparent, black 10px, black 90%, transparent);
      mask-image: linear-gradient(to right, transparent, black 10px, black 90%, transparent);
    }
    #suggestionChips::-webkit-scrollbar { display: none; }
    .suggestion-chip {
      flex: 0 0 auto;
      white-space: nowrap;
      cursor: pointer;
      user-select: none;
      transition: all 0.15s ease;
      min-height: 44px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .suggestion-chip:hover {
      transform: translateY(-1px);
      box-shadow: 0 2px 8px rgba(107, 66, 38, 0.15);
    }
    .suggestion-chip:active {
      transform: scale(0.96);
    }
    
    /* Scroll indicators for the suggestion bar */
    #suggestionScrollLeft,
    #suggestionScrollRight {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: 24px;
      height: 24px;
      background: white;
      border-radius: 50%;
      border: 1px solid #e5e7eb;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s ease;
      z-index: 5;
      opacity: 0;
      pointer-events: none;
    }
    #suggestionScrollLeft.show,
    #suggestionScrollRight.show {
      opacity: 1;
      pointer-events: auto;
    }
    #suggestionScrollLeft:hover,
    #suggestionScrollRight:hover {
      background: #f3f0e4;
      border-color: #6B4226;
    }
    #suggestionScrollLeft { left: -8px; }
    #suggestionScrollRight { right: -8px; }
    
    /* Suggestion bar wrapper for scroll indicators */
    #suggestionBar {
      position: relative;
      padding: 0.75rem 1.25rem 0.5rem;
    }
    #suggestionChipsWrapper {
      position: relative;
      overflow: hidden;
      padding: 0 4px;
    }

    @media (max-width: 640px) {
      #suggestionScrollLeft,
      #suggestionScrollRight {
        display: none;
      }
    }
  </style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c] min-h-screen flex flex-col"
      data-chat-key="<?= htmlspecialchars($chatKey) ?>"
      data-logged-in="<?= isset($_SESSION['user_id']) ? '1' : '0' ?>">

  <!-- Header -->
  <?php include __DIR__ . '/includes/header.php'; ?>

  <!-- Main Content -->
  <main class="flex-1 max-w-3xl w-full mx-auto px-6 py-16">
    <a href="<?= isset($_SESSION['user_id']) ? 'my-profile.php?section=support' : 'index.php' ?>" class="inline-flex items-center gap-2 text-sm text-[#17611f] hover:text-[#14521a] transition-colors mb-8">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
      Back to Dashboard
    </a>
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="p-8 pb-5 border-b border-gray-100">
        <span class="inline-block text-[11px] font-semibold tracking-wide text-[#17611f] bg-[#e8f5e9] rounded-full px-3 py-1 mb-5">QUICK SUPPORT</span>
        <h1 class="font-black text-3xl font-semibold text-[#1a2e1c] mb-2">Live Chat</h1>
        <p class="text-[#5a7a5c] text-[15px]">Chatting as <span class="font-medium text-[#1a2e1c]"><?= htmlspecialchars($customerName) ?></span>. Our team typically replies within a few minutes during business hours.</p>
      </div>

      <?php if ($message): ?>
        <div class="mx-6 mt-5 rounded-xl px-4 py-3 text-sm <?= $messageType === 'error' ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-green-50 text-green-700 border border-green-100' ?>">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>

      <!-- Quick actions -->
      <div id="quickActions" class="px-6 pt-5 pb-2">
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
          <?php foreach ($quickActionsPrimary as $qa): ?>
            <button type="button" class="quick-action-btn flex items-center justify-center text-center text-[13px] font-bold text-[#17611f] bg-[#e8f5e9] hover:bg-[#c8e6c9] active:scale-[0.98] border border-[#c8e6c9] rounded-xl px-3 py-3 leading-snug transition-all" data-message="<?=htmlspecialchars($qa['message'])?>"><?=htmlspecialchars($qa['label'])?></button>
          <?php endforeach; ?>
        </div>
        <button type="button" id="moreOptionsToggle" class="flex items-center gap-1.5 text-[12.5px] font-medium text-[#5a7a5c] hover:text-[#17611f] mt-3 mb-1 transition-colors">
          <span>More Options</span>
          <svg id="moreOptionsChevron" class="w-3.5 h-3.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div id="moreOptionsPanel" class="hidden grid grid-cols-2 sm:grid-cols-3 gap-2.5 pt-1 pb-3">
          <?php foreach ($quickActionsMore as $qa): ?>
            <button type="button" class="quick-action-btn flex items-center justify-center text-center text-[13px] font-bold text-[#17611f] bg-[#e8f5e9] hover:bg-[#c8e6c9] active:scale-[0.98] border border-[#c8e6c9] rounded-xl px-3 py-3 leading-snug transition-all" data-message="<?=htmlspecialchars($qa['message'])?>"><?=htmlspecialchars($qa['label'])?></button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Message thread -->
      <div id="chatThread" class="p-6 space-y-4 max-h-[420px] overflow-y-auto bg-[#f4faf5]" data-last-id="<?= $lastId ?>">
        <p id="chatEmptyState" class="text-center text-sm text-[#9e9e9e] py-10" <?= empty($chatMessages) ? '' : 'style="display:none;"' ?>>
          No messages yet — say hello below and the Luntiang H.A.P.A.G. Assistant will jump in.
        </p>
        <?php foreach ($chatMessages as $m):
            $isCustomer = $m['sender'] === 'customer';
            $isBot = $m['sender'] === 'bot';
            $label = $isCustomer ? 'You' : ($isBot ? 'Luntiang H.A.P.A.G. Assistant 🌿' : 'Luntiang H.A.P.A.G. Support');
            $suggestions = $isBot ? extractQuestionSuggestions($m['message']) : null;
            $hasImage = !empty($m['image_path']);
        ?>
          <div class="flex <?= $isCustomer ? 'justify-end' : 'justify-start' ?>">
            <div class="max-w-[75%]">
              <?php if ($suggestions !== null): ?>
                <div class="rounded-2xl px-4 py-2.5 text-[14px] leading-relaxed bg-[#e8f5e9] border border-[#c8e6c9] text-[#1a2e1c] rounded-bl-sm">
                  <p>Here are some questions customers commonly ask.</p>
                  <p class="mt-1">Simply click any question below if that's what you'd like to ask.</p>
                </div>
                <div class="mt-2 flex flex-col gap-1.5" role="list">
                  <?php foreach ($suggestions as $q): ?>
                    <button type="button" class="chat-suggestion-btn text-left text-[13.5px] font-medium text-[#17611f] bg-white border border-[#c8e6c9] rounded-xl px-3.5 py-2 hover:bg-[#e8f5e9] hover:border-orange-300 active:scale-[0.98] cursor-pointer transition-all focus:outline-none focus:ring-2 focus:ring-[#6B4226]/40" data-message="<?= htmlspecialchars($q) ?>"><?= htmlspecialchars($q) ?></button>
                  <?php endforeach; ?>
                </div>
                <p class="text-[12px] text-[#9e9e9e] mt-2">If your question isn't listed, you can always type your own message below.</p>
              <?php else: ?>
                <div class="rounded-2xl px-4 py-2.5 text-[14px] leading-relaxed <?= $isCustomer ? 'bg-[#17611f] text-white rounded-br-sm' : ($isBot ? 'bg-[#e8f5e9] border border-[#c8e6c9] text-[#1a2e1c] rounded-bl-sm' : 'bg-white border border-[rgba(27,94,32,0.12)] text-[#1a2e1c] rounded-bl-sm') ?>">
                  <?= $m['message'] ? nl2br(htmlspecialchars($m['message'])) : '' ?>
                  <?php if ($hasImage): ?>
                    <img src="<?= htmlspecialchars($m['image_path']) ?>" class="mt-2 rounded-lg max-w-full max-h-64 object-cover" alt="Shared image">
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              <p class="text-[11px] text-[#9e9e9e] mt-1 <?= $isCustomer ? 'text-right' : 'text-left' ?>">
                <?= htmlspecialchars($label) ?> · <?= date('M j, g:i A', strtotime($m['created_at'])) ?>
              </p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Suggested Questions -->
      <div id="suggestionBar" class="border-t border-gray-100 bg-white">
        <p class="text-[11px] font-semibold tracking-wide text-[#9e9e9e] uppercase mb-2 px-5 pt-3">Suggested Questions</p>
        <div id="suggestionChipsWrapper" class="relative px-5 pb-3">
          <button id="suggestionScrollLeft" class="absolute left-1 top-1/2 transform -translate-y-1/2 bg-white rounded-full shadow-md border border-[rgba(27,94,32,0.12)] w-7 h-7 flex items-center justify-center hover:bg-gray-50 transition-all z-10 opacity-0 pointer-events-none">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>
          <div id="suggestionChips" class="flex flex-nowrap overflow-x-auto scroll-smooth gap-2 pb-1 -webkit-overflow-scrolling-touch scrollbar-none" style="scrollbar-width: none; -ms-overflow-style: none;">
            <!-- Chips rendered by JavaScript -->
          </div>
          <button id="suggestionScrollRight" class="absolute right-1 top-1/2 transform -translate-y-1/2 bg-white rounded-full shadow-md border border-[rgba(27,94,32,0.12)] w-7 h-7 flex items-center justify-center hover:bg-gray-50 transition-all z-10 opacity-0 pointer-events-none">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Send message -->
      <form id="chatForm" method="POST" enctype="multipart/form-data" class="p-5">
        <?php if (!isset($_SESSION['user_id'])): ?>
          <input type="hidden" name="gk" value="<?= htmlspecialchars($chatKey) ?>" />
        <?php endif; ?>
        <div class="flex items-center gap-3">
          <label for="chatImageInput" class="cursor-pointer flex-shrink-0 w-10 h-10 rounded-full border border-[rgba(27,94,32,0.12)] flex items-center justify-center hover:bg-[#e8f5e9] transition-colors" title="Attach image">
            <svg class="w-5 h-5 text-[#5a7a5c]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          </label>
          <input type="file" id="chatImageInput" name="chat_image" accept="image/*" class="hidden" onchange="previewChatImage(this)">
          <input type="text" id="chatInput" name="message" placeholder="Type your message..." autofocus maxlength="250"
                 class="flex-1 rounded-full border border-[rgba(27,94,32,0.12)] px-5 py-3 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
          <button type="submit" id="chatSendBtn" class="px-6 py-3 rounded-full bg-[#17611f] text-white text-sm font-medium hover:bg-[#14521a] transition-colors flex-shrink-0">Send</button>
        </div>
        <div id="imagePreviewContainer" class="hidden mt-2 relative inline-block">
          <img id="imagePreview" src="" class="h-20 w-20 object-cover rounded-lg border border-[rgba(27,94,32,0.12)]">
          <button type="button" onclick="removeChatImage()" class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-red-500 text-white text-xs flex items-center justify-center">×</button>
        </div>
        <div class="flex items-center justify-between mt-2 px-1">
          <p id="chatValidation" class="text-[12px] text-red-500"></p>
          <p id="chatCharCount" class="text-[11px] text-[#9e9e9e] ml-auto">0/250</p>
        </div>
      </form>
    </div>
    <p class="text-center text-[12px] text-[#9e9e9e] mt-4">New replies appear here automatically — no need to refresh.</p>
  </main>

  <!-- Footer -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script>
    // ------------------------------------------------------------------
    // Guest chat key bootstrap
    // ------------------------------------------------------------------
    // Logged-in customers don't need any of this — their chat_key is
    // the permanent account-based one. For guests, the key lives in
    // sessionStorage instead of a cookie/PHP session on purpose: it's
    // scoped to *this tab*, so it survives a reload of the tab but
    // disappears the moment the tab is closed. GUEST_CHAT_KEY below is
    // what every AJAX call (send/poll) sends back to the server as
    // ?gk=... so it keeps landing on the same conversation.
    const isLoggedIn = document.body.dataset.loggedIn === '1';
    let GUEST_CHAT_KEY = null;

    if (!isLoggedIn) {
        const phpKey = document.body.dataset.chatKey;
        const urlKey = new URLSearchParams(location.search).get('gk');
        const storedKey = sessionStorage.getItem('guest_chat_key');

        if (urlKey && urlKey === phpKey) {
            // Normal case: the page was loaded (or reloaded) with the
            // gk this tab already had, and PHP used it to load history.
            sessionStorage.setItem('guest_chat_key', phpKey);
            GUEST_CHAT_KEY = phpKey;
        } else if (storedKey && storedKey !== phpKey) {
            // This tab already has a conversation key, but this
            // particular request didn't send it (e.g. a plain nav link
            // without ?gk=), so the server rendered a brand-new one.
            // Reload once with the tab's real key so history loads
            // correctly.
            const url = new URL(location.href);
            url.searchParams.set('gk', storedKey);
            location.replace(url.toString());
        } else {
            // Brand-new tab: nothing in sessionStorage yet, so adopt
            // whatever key PHP just minted for this render, and reflect
            // it in the URL (without adding a new history entry) so a
            // manual reload of this tab sends it straight back.
            sessionStorage.setItem('guest_chat_key', phpKey);
            GUEST_CHAT_KEY = phpKey;
            const url = new URL(location.href);
            url.searchParams.set('gk', phpKey);
            history.replaceState(null, '', url.toString());
        }
    }

    // Appends the guest key to a same-page AJAX URL. No-op for logged-in
    // customers and for the redirect-in-progress case above (that request
    // will be aborted by location.replace anyway).
    function withGuestKey(url) {
        if (isLoggedIn || !GUEST_CHAT_KEY) return url;
        const u = new URL(url, location.href);
        u.searchParams.set('gk', GUEST_CHAT_KEY);
        return u.toString();
    }

    const thread = document.getElementById('chatThread');
    const emptyState = document.getElementById('chatEmptyState');
    const form = document.getElementById('chatForm');
    const input = document.getElementById('chatInput');
    const sendBtn = document.getElementById('chatSendBtn');
    const validationEl = document.getElementById('chatValidation');
    const charCountEl = document.getElementById('chatCharCount');
    const MAX_LEN = 250;
    let lastId = parseInt(thread.dataset.lastId || '0', 10);
    let polling = false;

    // ------------------------------------------------------------------
    // SUGGESTION SYSTEM - Maps topics to actual knowledge base questions
    // that have guaranteed matches in chatbot-knowledge.php
    // ------------------------------------------------------------------
    const SUGGESTION_MAP = {
        general: [
            'Tell me about your lettuce varieties',
            'I have a general question',
            'How do I store my lettuce?',
            'Tell me about shipping and delivery',
            'I need help with my account',
        ],
        faq: [
            'How do I place an order?',
            'Can I cancel or modify my order?',
            'How long does shipping take?',
            'What payment methods do you accept?',
            'How does harvest-on-demand work?',
            'Can I return or exchange lettuce?',
            'How does the freshness guarantee work?',
        ],
        account_help: [
            'How do I create an account?',
            'How do I log in?',
            'How do I reset my password?',
            'How do I change my password?',
            'How do I update my profile?',
        ],
        product_info: [
            'Tell me about your lettuce varieties',
            'How is the lettuce grown?',
            'What varieties do you have?',
            'What lettuce is available?',
            'What bundle options are available?',
            'How does harvest-on-demand work?',
        ],
        support_tickets: [
            'How do I submit a ticket?',
            'How long for a ticket reply?',
            'Ticket Status Meaning',
            'What is the support ticket process?',
        ],
        return_refund: [
            'How do I submit a return request?',
            'Am I eligible for a return?',
            'How long does a return take?',
            'Return Processing Time',
            'Do I get a refund for returns?',
            'Can I get a replacement instead?',
        ],
        contact_support: [
            'How do I contact support?',
            'What are your business hours?',
            'Submit a Ticket',
            'Live Chat',
            'Response Time',
        ],
    };

    // Map display labels to the exact message that matches KB keywords
    const SUGGESTION_MESSAGE_MAP = {
        'How do I place an order?': 'How do I place an order',
        'Can I cancel or modify my order?': 'Can I cancel or modify my order',
        'How long does shipping take?': 'How long does shipping take',
        'What payment methods do you accept?': 'What payment methods do you accept',
        'How does harvest-on-demand work?': 'How does harvest-on-demand work',
        'Can I return or exchange lettuce?': 'Can I return or exchange lettuce',
        'How does the freshness guarantee work?': 'How does the freshness guarantee work',
        'How do I create an account?': 'How do I create an account',
        'How do I log in?': 'How do I log in',
        'How do I reset my password?': 'How do I reset my password',
        'How do I change my password?': 'How do I change my password',
        'How do I update my profile?': 'How do I update my profile',
        'Tell me about your lettuce varieties': 'Tell me about your lettuce varieties',
        'How is the lettuce grown?': 'How is the lettuce grown',
        'What varieties do you have?': 'What varieties do you have',
        'What lettuce is available?': 'What lettuce is available',
        'What bundle options are available?': 'What color options are available',
        'How do I submit a ticket?': 'How do I submit a ticket',
        'How long for a ticket reply?': 'How long for a ticket reply',
        'Ticket Status Meaning': 'Ticket Status Meaning',
        'What is the support ticket process?': 'How do I submit a ticket',
        'How do I submit a return request?': 'How do I submit a return request',
        'Am I eligible for a return?': 'Am I eligible for a return',
        'How long does a return take?': 'How long does a return take',
        'Return Processing Time': 'Return Processing Time',
        'Do I get a refund for returns?': 'Do I get a refund for returns',
        'Can I get a replacement instead?': 'Can I get a replacement instead',
        'How do I contact support?': 'How do I contact support',
        'What are your business hours?': 'What are your business hours',
        'Submit a Ticket': 'How do I submit a ticket',
        'Live Chat': 'Live Chat',
        'Response Time': 'Ticket response time',
        'How do I store my lettuce?': 'How do I store my lettuce',
        'Tell me about shipping and delivery': 'Tell me about shipping and delivery',
        'I need help with my account': 'I need help with my account',
        'I have a general question': 'I have a general question',
    };

    // ------------------------------------------------------------------
    // Suggested Questions bar (horizontally scrollable, positioned above
    // the composer). Purely a presentational/UX layer on top of the
    // existing chatbot: it never adds a new message-processing path —
    // every chip is sent through the exact same sendMessage() flow as
    // typing a message or clicking one of the in-message suggestion
    // chips, so it stays perfectly in sync with the chatbot's own logic.
    // ------------------------------------------------------------------
    const suggestionChips = document.getElementById('suggestionChips');
    const scrollLeftBtn = document.getElementById('suggestionScrollLeft');
    const scrollRightBtn = document.getElementById('suggestionScrollRight');
    const chipsWrapper = document.getElementById('suggestionChipsWrapper');

    function renderSuggestionChips(list) {
        suggestionChips.innerHTML = list.map((label) => {
            const message = SUGGESTION_MESSAGE_MAP[label] || label;
            return `<button type="button" class="suggestion-chip text-[13px] font-medium text-[#17611f] bg-[#e8f5e9] hover:bg-[#c8e6c9] active:scale-[0.98] border border-[#c8e6c9] rounded-full px-4 py-2 transition-all" data-message="${escapeHtml(message)}" data-label="${escapeHtml(label)}">${escapeHtml(label)}</button>`;
        }).join('');
        suggestionChips.scrollLeft = 0;
        updateScrollButtons();
    }

    function updateScrollButtons() {
        if (!scrollLeftBtn || !scrollRightBtn) return;
        const hasScroll = suggestionChips.scrollWidth > suggestionChips.clientWidth;
        if (!hasScroll) {
            scrollLeftBtn.classList.remove('show');
            scrollRightBtn.classList.remove('show');
            return;
        }
        const atStart = suggestionChips.scrollLeft <= 5;
        const atEnd = suggestionChips.scrollLeft >= suggestionChips.scrollWidth - suggestionChips.clientWidth - 5;
        scrollLeftBtn.classList.toggle('show', !atStart);
        scrollRightBtn.classList.toggle('show', !atEnd);
    }

    function scrollSuggestions(direction) {
        const scrollAmount = suggestionChips.clientWidth * 0.7;
        suggestionChips.scrollBy({ left: direction * scrollAmount, behavior: 'smooth' });
    }

    // Scroll button click handlers
    if (scrollLeftBtn) {
        scrollLeftBtn.addEventListener('click', () => scrollSuggestions(-1));
    }
    if (scrollRightBtn) {
        scrollRightBtn.addEventListener('click', () => scrollSuggestions(1));
    }

    // Update scroll buttons on scroll and resize
    suggestionChips.addEventListener('scroll', updateScrollButtons);
    window.addEventListener('resize', updateScrollButtons);

    // Enhanced topic detection
    function detectTopicFromMessage(text) {
        const lower = text.toLowerCase();
        
        // Check for specific topic indicators
        const topicPatterns = [
            { topic: 'account_help', patterns: ['account', 'password', 'login', 'log in', 'profile', 'register', 'sign up', 'forgot', 'create an account', 'reset my password', 'change my password', 'update my profile'] },
            { topic: 'freshness', patterns: ['freshness', 'quality issue', 'wilted', 'damaged', 'not fresh', 'freshness request', 'freshness coverage', 'guarantee'] },
            { topic: 'return_refund', patterns: ['return', 'refund', 'exchange', 'wrong item', 'damaged on delivery', 'missing parts', 'return request', 'return eligibility', 'refund for returns'] },
            { topic: 'support_tickets', patterns: ['ticket', 'support ticket', 'submit a ticket', 'raise a ticket', 'ticket reply', 'ticket status'] },
            { topic: 'contact_support', patterns: ['contact support', 'business hours', 'phone number', 'email address', 'contact information'] },
            { topic: 'product_info', patterns: ['lettuce types', 'products', 'materials', 'dimensions', 'in stock', 'availability', 'colors', 'lettuce varieties'] },
            { topic: 'faq', patterns: ['faq', 'frequently asked', 'general question', 'place an order', 'payment methods', 'harvest-on-demand'] },
        ];
        
        for (const entry of topicPatterns) {
            if (entry.patterns.some((p) => lower.includes(p))) {
                return entry.topic;
            }
        }
        
        // Check for product categories
        const productCategories = ['romaine, batavia, bianca, butterhead, red leaf, estrosa, olmetie, mixed greens'];
        if (productCategories.some((cat) => lower.includes(cat))) {
            return 'product_info';
        }
        
        return null;
    }

    function setTopic(topic) {
        const suggestions = SUGGESTION_MAP[topic] || SUGGESTION_MAP.general;
        renderSuggestionChips(suggestions);
        // Small delay to ensure scroll buttons update after render
        setTimeout(updateScrollButtons, 50);
    }

    function handleSuggestionClick(text) {
        sendMessage(text);
        const topic = detectTopicFromMessage(text);
        if (topic) setTopic(topic);
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function formatTime(iso) {
        const d = new Date(iso.replace(' ', 'T'));
        if (isNaN(d.getTime())) return '';
        return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) + ', ' +
               d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
    }

    // Client-side mirror of the PHP extractQuestionSuggestions() above
    function extractQuestionSuggestions(text) {
        const lines = text.split(/\r\n|\r|\n/);
        const bullets = [];
        lines.forEach((line) => {
            const trimmed = line.trim();
            if (trimmed.startsWith('• ')) bullets.push(trimmed.slice(2).trim());
        });
        if (bullets.length === 0) return null;
        const questionCount = bullets.filter((b) => b.trim().endsWith('?')).length;
        if ((questionCount / bullets.length) >= 0.5) {
            // Update topic based on the suggestions
            const topic = detectTopicFromMessage(bullets[0] || '');
            if (topic) setTopic(topic);
            return bullets;
        }
        return null;
    }

    function buildSuggestionsBlock(bullets) {
        const buttons = bullets.map((q) => `
          <button type="button" class="chat-suggestion-btn text-left text-[13.5px] font-medium text-[#17611f] bg-white border border-[#c8e6c9] rounded-xl px-3.5 py-2 hover:bg-[#e8f5e9] hover:border-orange-300 active:scale-[0.98] cursor-pointer transition-all focus:outline-none focus:ring-2 focus:ring-[#6B4226]/40" data-message="${escapeHtml(q)}">${escapeHtml(q)}</button>`).join('');
        return `
        <div class="rounded-2xl px-4 py-2.5 text-[14px] leading-relaxed bg-[#e8f5e9] border border-[#c8e6c9] text-[#1a2e1c] rounded-bl-sm">
          <p>Here are some questions customers commonly ask.</p>
          <p class="mt-1">Simply click any question below if that's what you'd like to ask.</p>
        </div>
        <div class="mt-2 flex flex-col gap-1.5" role="list">${buttons}</div>
        <p class="text-[12px] text-[#9e9e9e] mt-2">If your question isn't listed, you can always type your own message below.</p>`;
    }

    function appendMessage(m) {
        if (emptyState) emptyState.style.display = 'none';
        const isCustomer = m.sender === 'customer';
        const isBot = m.sender === 'bot';
        const label = isCustomer ? 'You' : (isBot ? 'Luntiang H.A.P.A.G. Assistant 🌿' : 'Luntiang H.A.P.A.G. Support');
        const bubbleClass = isCustomer
            ? 'bg-[#17611f] text-white rounded-br-sm'
            : (isBot ? 'bg-[#e8f5e9] border border-[#c8e6c9] text-[#1a2e1c] rounded-bl-sm' : 'bg-white border border-[rgba(27,94,32,0.12)] text-[#1a2e1c] rounded-bl-sm');
        const suggestions = isBot ? extractQuestionSuggestions(m.message) : null;
        if (suggestions) {
            renderSuggestionChips(suggestions);
        } else if (isBot) {
            // Check if bot's response indicates a topic
            const topic = detectTopicFromMessage(m.message);
            if (topic) setTopic(topic);
        }
        // Build image HTML if present
        let imageHtml = '';
        if (m.image_path) {
            imageHtml = `<img src="${escapeHtml(m.image_path)}" class="mt-2 rounded-lg max-w-full max-h-64 object-cover" alt="Shared image">`;
        }
        const bodyHtml = suggestions
            ? buildSuggestionsBlock(suggestions)
            : `<div class="rounded-2xl px-4 py-2.5 text-[14px] leading-relaxed ${bubbleClass}">${m.message ? escapeHtml(m.message).replace(/\\n/g, '<br>') : ''}${imageHtml}</div>`;
        const wrap = document.createElement('div');
        wrap.className = 'flex ' + (isCustomer ? 'justify-end' : 'justify-start');
        wrap.innerHTML = `
        <div class="max-w-[75%]">
          ${bodyHtml}
          <p class="text-[11px] text-[#9e9e9e] mt-1 ${isCustomer ? 'text-right' : 'text-left'}">
            ${label} · ${formatTime(m.created_at)}
          </p>
        </div>`;
        thread.appendChild(wrap);
        lastId = Math.max(lastId, parseInt(m.id, 10));
        scrollToBottom();
    }

    let pendingImage = null;

    function previewChatImage(input) {
        if (input.files && input.files[0]) {
            pendingImage = input.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').src = e.target.result;
                document.getElementById('imagePreviewContainer').classList.remove('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function removeChatImage() {
        pendingImage = null;
        document.getElementById('chatImageInput').value = '';
        document.getElementById('imagePreviewContainer').classList.add('hidden');
    }

    async function sendMessage(text) {
        sendBtn.disabled = true;
        try {
            let res;
            if (pendingImage) {
                const formData = new FormData();
                formData.append('message', text || '');
                formData.append('chat_image', pendingImage);
                if (GUEST_CHAT_KEY) formData.append('gk', GUEST_CHAT_KEY);
                res = await fetch(withGuestKey('chat-send.php'), {
                    method: 'POST',
                    body: formData
                });
                pendingImage = null;
                document.getElementById('imagePreviewContainer').classList.add('hidden');
                document.getElementById('chatImageInput').value = '';
            } else {
                res = await fetch(withGuestKey('chat-send.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: text, gk: GUEST_CHAT_KEY })
                });
            }
            const data = await res.json();
            if (data.success) {
                const toAppend = Array.isArray(data.messages) ? data.messages : [data.message];
                toAppend.forEach(appendMessage);
                input.value = '';
                updateCharCount();
                validationEl.textContent = '';
            } else {
                validationEl.textContent = data.error || 'Something went wrong sending your message.';
            }
        } catch (err) {
            validationEl.textContent = 'Network error — please try again.';
        } finally {
            sendBtn.disabled = false;
        }
    }

    function scrollToBottom() {
        thread.scrollTop = thread.scrollHeight;
    }
    scrollToBottom();

    function updateCharCount() {
        const len = input.value.length;
        charCountEl.textContent = len + '/' + MAX_LEN;
        charCountEl.classList.toggle('text-red-500', len > MAX_LEN);
        charCountEl.classList.toggle('text-[#9e9e9e]', len <= MAX_LEN);
    }
    input.addEventListener('input', updateCharCount);
    updateCharCount();

    // Event listeners for suggestion chips (delegated)
    suggestionChips.addEventListener('click', (e) => {
        const chip = e.target.closest('.suggestion-chip');
        if (!chip) return;
        const text = chip.dataset.message;
        if (!text) return;
        handleSuggestionClick(text);
    });

    // Mouse wheel support for horizontal scrolling
    suggestionChips.addEventListener('wheel', (e) => {
        if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
            e.preventDefault();
            suggestionChips.scrollLeft += e.deltaY;
            // Update scroll buttons after manual scroll
            setTimeout(updateScrollButtons, 50);
        }
    }, { passive: false });

    // Touch support - native overflow-x handles this, but we need to update buttons
    suggestionChips.addEventListener('touchmove', () => {
        setTimeout(updateScrollButtons, 50);
    }, { passive: true });

    // In-message suggestion chips
    thread.addEventListener('click', (e) => {
        const chip = e.target.closest('.chat-suggestion-btn');
        if (!chip) return;
        const text = chip.dataset.message;
        if (!text) return;
        handleSuggestionClick(text);
    });

    // Quick action buttons
    document.querySelectorAll('.quick-action-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const text = btn.dataset.message;
            if (!text) return;
            sendMessage(text);
            const topic = detectTopicFromMessage(text);
            if (topic) setTopic(topic);
        });
    });

    // More Options toggle
    const moreToggle = document.getElementById('moreOptionsToggle');
    const morePanel = document.getElementById('moreOptionsPanel');
    const moreChevron = document.getElementById('moreOptionsChevron');
    if (moreToggle && morePanel) {
        moreToggle.addEventListener('click', () => {
            const h = morePanel.classList.contains('hidden');
            if (h) { morePanel.classList.remove('hidden'); moreChevron.classList.add('rotate-180'); moreToggle.querySelector('span').textContent = 'Fewer Options'; }
            else { morePanel.classList.add('hidden'); moreChevron.classList.remove('rotate-180'); moreToggle.querySelector('span').textContent = 'More Options'; }
        });
    }

    // Form submit
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const text = input.value.trim();
        if (text === '') {
            validationEl.textContent = 'Please write a message before sending.';
            return;
        }
        if (text.length > MAX_LEN) {
            validationEl.textContent = `Messages are limited to ${MAX_LEN} characters.`;
            return;
        }
        validationEl.textContent = '';
        sendMessage(text);
        const topic = detectTopicFromMessage(text);
        if (topic) setTopic(topic);
    });

    // Polling for new messages
    async function poll() {
        if (polling) return;
        polling = true;
        try {
            const res = await fetch(withGuestKey('chat-poll.php?after_id=' + lastId));
            const data = await res.json();
            if (data.success && Array.isArray(data.messages)) {
                data.messages.forEach(appendMessage);
            }
        } catch (err) {
            // Silently retry on the next interval.
        } finally {
            polling = false;
        }
    }

    // Start with general suggestions
    setTopic('general');
    setInterval(poll, 4000);
    
    // Initial scroll button update after render
    setTimeout(updateScrollButtons, 100);
  </script>

</body>
</html>