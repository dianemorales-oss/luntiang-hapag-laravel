<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin-auth.php';

$activePage = 'live-chat';
$pageTitle = 'Live Chat';

// Null-safe wrapper so e() never receives null
// (PHP 8.1+ deprecates passing null to a non-nullable string parameter).
if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$message = "";
$messageType = "";

// Plain form-submit fallback for when JavaScript is unavailable —
// the panel itself works via AJAX (see the script below).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chatKey = $_POST['chat_key'] ?? '';
    $text = trim($_POST['message'] ?? '');

    if ($chatKey === '') {
        $message = "Missing conversation.";
        $messageType = "error";
    } elseif ($text === '') {
        $message = "Please write a message before sending.";
        $messageType = "error";
    } elseif (mb_strlen($text) > 250) {
        $message = "Messages are limited to 250 characters.";
        $messageType = "error";
    } else {
        try {
            $insert = $conn->prepare("
                INSERT INTO live_chat_messages (chat_key, user_id, customer_name, sender, message)
                VALUES (?, NULL, 'Luntiang H.A.P.A.G. Support', 'admin', ?)
            ");
            $insert->execute([$chatKey, $text]);

            // A human agent has now joined this conversation — stop the
            // AI assistant from replying to further messages in it.
            $conn->prepare("
                INSERT INTO chat_bot_state (chat_key, bot_active) VALUES (?, 0)
                ON DUPLICATE KEY UPDATE bot_active = 0, pending_intent = NULL, pending_context = NULL
            ")->execute([$chatKey]);

            header("Location: admin-live-chat.php?chat=" . urlencode($chatKey));
            exit();
        } catch (PDOException $e) {
            $message = "Something went wrong sending this reply. Please try again.";
            $messageType = "error";
        }
    }
}

// Conversation list: one row per chat_key, showing the latest message.
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

$activeChatKey = $_GET['chat'] ?? ($conversations[0]['chat_key'] ?? '');

$activeMessages = [];
if ($activeChatKey !== '') {
    $stmt = $conn->prepare("SELECT * FROM live_chat_messages WHERE chat_key = ? ORDER BY created_at ASC, id ASC");
    $stmt->execute([$activeChatKey]);
    $activeMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$activeLastId = !empty($activeMessages) ? (int)end($activeMessages)['id'] : 0;

// The first row in the thread is often the bot's auto-greeting (sender
// = 'bot', customer_name = 'Luntiang H.A.P.A.G. Assistant'), so we can't just use
// $activeMessages[0]['customer_name'] for the header — that shows the
// bot's name instead of the customer's. Grab the name from the first
// actual customer message instead, same as the sidebar query does.
$activeCustomerName = 'Customer';
foreach ($activeMessages as $m) {
    if ($m['sender'] === 'customer') {
        $activeCustomerName = $m['customer_name'];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Live Chat | Luntiang H.A.P.A.G. Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Nunito', sans-serif; }
    .font-black { font-family: 'Nunito', serif; }
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-thumb { background: #d8cfbd; border-radius: 8px; }
  </style>
</head>
<body class="h-screen overflow-hidden bg-[#f4faf5] text-[#1a2e1c]">
  <div class="flex h-screen">
    <?php require_once __DIR__ . '/includes/admin-sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 min-h-0">
      <?php require_once __DIR__ . '/includes/admin-topbar.php'; ?>

      <main class="flex-1 min-h-0 overflow-hidden flex">

        <!-- Conversation list -->
        <div id="conversationList" class="w-80 flex-shrink-0 border-r border-[rgba(27,94,32,0.12)] bg-white flex flex-col overflow-hidden">
          <div class="p-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-[#1a2e1c]">Conversations (<span id="conversationCount"><?= count($conversations) ?></span>)</h2>
          </div>
          <div id="conversationItems" class="flex-1 overflow-y-auto">
            <?php if (empty($conversations)): ?>
              <p class="p-4 text-sm text-[#9e9e9e]" id="noConversations">No chat conversations yet.</p>
            <?php else: foreach ($conversations as $c):
                $isActive = $c['chat_key'] === $activeChatKey;
            ?>
              <div class="relative group border-b border-gray-50 conversation-row <?= $isActive ? 'bg-[#e8f5e9]' : 'hover:bg-gray-50' ?>" data-chat-key="<?= e($c['chat_key']) ?>">
                <a href="admin-live-chat.php?chat=<?= urlencode($c['chat_key']) ?>" class="block px-4 py-3 pr-10 conversation-link">
                  <div class="flex items-center justify-between mb-1">
                    <p class="text-[13px] font-semibold text-[#1a2e1c] truncate"><?= e($c['customer_name']) ?></p>
                    <p class="text-[11px] text-[#9e9e9e] flex-shrink-0"><?= date('g:i A', strtotime($c['last_message_at'])) ?></p>
                  </div>
                  <p class="text-[12px] text-[#5a7a5c] truncate"><?= e($c['last_message']) ?></p>
                </a>
                <button type="button" class="delete-conversation-btn absolute top-1/2 right-3 -translate-y-1/2 p-1.5 rounded-full text-gray-300 hover:text-red-500 hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-opacity" data-chat-key="<?= e($c['chat_key']) ?>" title="Delete conversation">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h10" /></svg>
                </button>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>

        <!-- Active conversation -->
        <div id="activeConversationPanel" class="flex-1 flex flex-col min-w-0 min-h-0">
          <?php if ($activeChatKey === '' || empty($activeMessages)): ?>
            <div class="flex-1 flex items-center justify-center text-sm text-[#9e9e9e]">Select a conversation to view messages.</div>
          <?php else: ?>
            <div class="px-6 py-4 border-b border-[rgba(27,94,32,0.12)] bg-white">
              <p class="text-sm font-semibold text-[#1a2e1c]"><?= e($activeCustomerName) ?></p>
              <p class="text-[12px] text-[#9e9e9e]">Chat ID: <?= e(substr($activeChatKey, 0, 12)) ?>…</p>
            </div>
            <div id="chatThread" class="flex-1 min-h-0 overflow-y-auto p-6 space-y-4 bg-[#f4faf5]" data-chat-key="<?= e($activeChatKey) ?>" data-last-id="<?= $activeLastId ?>">
              <?php foreach ($activeMessages as $m):
                  $isAdmin = $m['sender'] === 'admin';
                  $isBot = $m['sender'] === 'bot';
                  $label = $isAdmin ? 'You' : ($isBot ? 'Luntiang H.A.P.A.G. Assistant 🌿' : e($m['customer_name']));
                  $hasImage = !empty($m['image_path']);
              ?>
                <div class="flex <?= $isAdmin ? 'justify-end' : 'justify-start' ?>">
                  <div class="max-w-[65%]">
                    <div class="rounded-2xl px-4 py-2.5 text-[14px] leading-relaxed <?= $isAdmin ? 'bg-[#17611f] text-white rounded-br-sm' : ($isBot ? 'bg-[#e8f5e9] border border-[#c8e6c9] text-[#1a2e1c] rounded-bl-sm' : 'bg-white border border-[rgba(27,94,32,0.12)] text-[#1a2e1c] rounded-bl-sm') ?>">
                      <?= $m['message'] ? nl2br(e($m['message'])) : '' ?>
                      <?php if ($hasImage): ?>
                        <img src="<?= e($m['image_path']) ?>" class="mt-2 rounded-lg max-w-full max-h-64 object-cover" alt="Shared image">
                      <?php endif; ?>
                    </div>
                    <p class="text-[11px] text-[#9e9e9e] mt-1 <?= $isAdmin ? 'text-right' : 'text-left' ?>">
                      <?= $label ?> · <?= date('M j, g:i A', strtotime($m['created_at'])) ?>
                    </p>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <form id="chatForm" method="POST" enctype="multipart/form-data" class="p-4 border-t border-[rgba(27,94,32,0.12)] bg-white">
              <?php if ($message): ?>
                <p class="text-[12px] mb-2 <?= $messageType === 'error' ? 'text-red-500' : 'text-green-600' ?>"><?= e($message) ?></p>
              <?php endif; ?>
              <div class="flex items-center gap-3">
                <label for="adminChatImageInput" class="cursor-pointer flex-shrink-0 w-10 h-10 rounded-full border border-[rgba(27,94,32,0.12)] flex items-center justify-center hover:bg-[#e8f5e9] transition-colors" title="Attach image">
                  <svg class="w-5 h-5 text-[#5a7a5c]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </label>
                <input type="file" id="adminChatImageInput" name="chat_image" accept="image/*" class="hidden" onchange="previewAdminChatImage(this)">
                <input type="hidden" name="chat_key" id="chatKeyInput" value="<?= e($activeChatKey) ?>" />
                <input type="text" name="message" id="chatInput" placeholder="Type your reply..." maxlength="250" class="flex-1 rounded-full border border-[rgba(27,94,32,0.12)] px-5 py-3 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
                <button type="submit" id="chatSendBtn" class="px-6 py-3 rounded-full bg-[#17611f] text-white text-sm font-medium hover:bg-[#14521a] transition-colors flex-shrink-0">Send</button>
              </div>
              <div id="adminImagePreviewContainer" class="hidden mt-2 relative inline-block">
                <img id="adminImagePreview" src="" class="h-20 w-20 object-cover rounded-lg border border-[rgba(27,94,32,0.12)]">
                <button type="button" onclick="removeAdminChatImage()" class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-red-500 text-white text-xs flex items-center justify-center">×</button>
              </div>
              <div class="flex items-center justify-between mt-2 px-1">
                <p id="chatValidation" class="text-[12px] text-red-500"></p>
                <p id="chatCharCount" class="text-[11px] text-[#9e9e9e] ml-auto">0/250</p>
              </div>
            </form>
          <?php endif; ?>
        </div>

      </main>
</div>
    </div>
  </div>

  <!-- Delete Conversation confirmation modal -->
  <div id="deleteConfirmModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 px-4">
    <div class="bg-white rounded-2xl shadow-lg max-w-sm w-full p-6">
      <h3 class="text-base font-semibold text-[#1a2e1c] mb-2">Delete Conversation?</h3>
      <p class="text-[13px] text-[#5a7a5c] leading-relaxed mb-2">This action will permanently delete the selected chat conversation and all of its messages.</p>
      <p class="text-[13px] text-[#5a7a5c] leading-relaxed mb-4">Customer account information, profile details, tickets, warranty requests, return requests, feedback, and other records will <span class="font-semibold">NOT</span> be deleted.</p>
      <p class="text-[12px] text-red-500 font-medium mb-6">This action cannot be undone.</p>
      <div class="flex items-center justify-end gap-3">
        <button type="button" id="deleteConfirmCancel" class="px-5 py-2.5 rounded-full border border-gray-300 text-[#1a2e1c] text-sm font-medium hover:bg-gray-100 transition-colors">Cancel</button>
        <button type="button" id="deleteConfirmSubmit" class="px-5 py-2.5 rounded-full bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition-colors">Delete Conversation</button>
      </div>
    </div>
  </div>

  <!-- Toast notification -->
  <div id="toast" class="hidden fixed bottom-6 right-6 z-50 px-4 py-3 rounded-xl shadow-lg text-sm font-medium"></div>

  <script>
    const thread = document.getElementById('chatThread');
    const form = document.getElementById('chatForm');

    // Tracks which conversation is currently open, independent of the
    // URL's ?chat= param (the default landing view has no ?chat= at
    // all — it just shows the most recent conversation — so relying
    // on the query string alone to detect "is the deleted conversation
    // the one I'm looking at?" misses that case).
    let currentChatKey = <?= json_encode($activeChatKey) ?>;
    let messagePollNunitoval = null;

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

    // ---------------------------------------------------------------
    // Toast notification
    // ---------------------------------------------------------------
    const toastEl = document.getElementById('toast');
    let toastTimer = null;
    function showToast(text, isError) {
      clearTimeout(toastTimer);
      toastEl.textContent = text;
      toastEl.className = 'fixed bottom-6 right-6 z-50 px-4 py-3 rounded-xl shadow-lg text-sm font-medium ' +
        (isError ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-green-50 text-green-700 border border-green-100');
      toastTimer = setTimeout(() => { toastEl.className += ' hidden'; }, 3500);
    }

    // ---------------------------------------------------------------
    // Delete Conversation
    // ---------------------------------------------------------------
    const deleteModal = document.getElementById('deleteConfirmModal');
    const deleteCancelBtn = document.getElementById('deleteConfirmCancel');
    const deleteSubmitBtn = document.getElementById('deleteConfirmSubmit');
    let pendingDeleteKey = null;

    function openDeleteModal(chatKey) {
      pendingDeleteKey = chatKey;
      deleteModal.classList.remove('hidden');
      deleteModal.classList.add('flex');
    }

    function closeDeleteModal() {
      pendingDeleteKey = null;
      deleteModal.classList.add('hidden');
      deleteModal.classList.remove('flex');
    }

    deleteCancelBtn.addEventListener('click', closeDeleteModal);
    deleteModal.addEventListener('click', (e) => {
      if (e.target === deleteModal) closeDeleteModal();
    });

    // Event delegation: conversation rows get re-rendered by
    // refreshConversationList, so bind once on the shared container.
    document.getElementById('conversationItems').addEventListener('click', (e) => {
      const btn = e.target.closest('.delete-conversation-btn');
      if (!btn) return;
      e.preventDefault();
      e.stopPropagation();
      openDeleteModal(btn.dataset.chatKey);
    });

    deleteSubmitBtn.addEventListener('click', async () => {
      if (!pendingDeleteKey) return;
      const chatKey = pendingDeleteKey;
      deleteSubmitBtn.disabled = true;
      try {
        const res = await fetch('chat-delete.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ chat_key: chatKey })
        });
        const data = await res.json();
        if (data.success) {
          closeDeleteModal();
          showToast('✅ Conversation deleted successfully.', false);

          // Remove the row from the sidebar immediately.
          const row = document.querySelector('.conversation-row[data-chat-key="' + CSS.escape(chatKey) + '"]');
          if (row) row.remove();
          const remaining = document.querySelectorAll('.conversation-row').length;
          const countEl = document.getElementById('conversationCount');
          if (countEl) countEl.textContent = remaining;
          const container = document.getElementById('conversationItems');
          if (remaining === 0 && container) {
            container.innerHTML = '<p class="p-4 text-sm text-[#9e9e9e]">No chat conversations yet.</p>';
          }

          // If the deleted conversation was the one currently open,
          // stop polling it and replace the panel with an empty state.
          if (currentChatKey === chatKey) {
            currentChatKey = '';

            if (messagePollNunitoval) {
              clearNunitoval(messagePollNunitoval);
              messagePollNunitoval = null;
            }

            const panel = document.getElementById('activeConversationPanel');
            if (panel) {
              panel.innerHTML = remaining === 0
                ? `<div class="flex-1 flex flex-col items-center justify-center text-center px-6">
                     <div class="text-4xl mb-3">💬</div>
                     <p class="text-sm font-semibold text-[#1a2e1c] mb-1">No Conversations Available</p>
                     <p class="text-[13px] text-[#9e9e9e] max-w-xs">There are currently no customer conversations. New conversations will appear here once customers start chatting.</p>
                   </div>`
                : `<div class="flex-1 flex flex-col items-center justify-center text-center px-6">
                     <div class="text-4xl mb-3">🗑️</div>
                     <p class="text-sm font-semibold text-[#1a2e1c] mb-1">Conversation Deleted</p>
                     <p class="text-[13px] text-[#9e9e9e] max-w-xs">This conversation has been deleted successfully. Select another conversation from the list to continue assisting customers.</p>
                   </div>`;
            }

            const url = new URL(window.location.href);
            url.searchParams.delete('chat');
            window.history.replaceState({}, '', url);
          }
        } else {
          showToast(data.error || 'Something went wrong deleting this conversation.', true);
        }
      } catch (err) {
        showToast('Network error — please try again.', true);
      } finally {
        deleteSubmitBtn.disabled = false;
      }
    });

    if (thread && form) {
      const input = document.getElementById('chatInput');
      const sendBtn = document.getElementById('chatSendBtn');
      const validationEl = document.getElementById('chatValidation');
      const charCountEl = document.getElementById('chatCharCount');
      const chatKeyInput = document.getElementById('chatKeyInput');
      const MAX_LEN = 250;
      let lastId = parseInt(thread.dataset.lastId || '0', 10);
      let polling = false;

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

      function appendMessage(m) {
        const isAdmin = m.sender === 'admin';
        const isBot = m.sender === 'bot';
        const label = isAdmin ? 'You' : (isBot ? 'Luntiang H.A.P.A.G. Assistant 🌿' : escapeHtml(m.customer_name));
        const bubbleClass = isAdmin
          ? 'bg-[#17611f] text-white rounded-br-sm'
          : (isBot ? 'bg-[#e8f5e9] border border-[#c8e6c9] text-[#1a2e1c] rounded-bl-sm' : 'bg-white border border-[rgba(27,94,32,0.12)] text-[#1a2e1c] rounded-bl-sm');
        let imageHtml = '';
        if (m.image_path) {
          imageHtml = `<img src="${escapeHtml(m.image_path)}" class="mt-2 rounded-lg max-w-full max-h-64 object-cover" alt="Shared image">`;
        }
        const wrap = document.createElement('div');
        wrap.className = 'flex ' + (isAdmin ? 'justify-end' : 'justify-start');
        wrap.innerHTML = `
          <div class="max-w-[65%]">
            <div class="rounded-2xl px-4 py-2.5 text-[14px] leading-relaxed ${bubbleClass}">
              ${m.message ? escapeHtml(m.message).replace(/\\n/g, '<br>') : ''}${imageHtml}
            </div>
            <p class="text-[11px] text-[#9e9e9e] mt-1 ${isAdmin ? 'text-right' : 'text-left'}">
              ${label} · ${formatTime(m.created_at)}
            </p>
          </div>`;
        thread.appendChild(wrap);
        lastId = Math.max(lastId, parseInt(m.id, 10));
        scrollToBottom();
      }

      let pendingAdminImage = null;

      window.previewAdminChatImage = function(input) {
        if (input.files && input.files[0]) {
          pendingAdminImage = input.files[0];
          const reader = new FileReader();
          reader.onload = function(e) {
            document.getElementById('adminImagePreview').src = e.target.result;
            document.getElementById('adminImagePreviewContainer').classList.remove('hidden');
          };
          reader.readAsDataURL(input.files[0]);
        }
      };

      window.removeAdminChatImage = function() {
        pendingAdminImage = null;
        document.getElementById('adminChatImageInput').value = '';
        document.getElementById('adminImagePreviewContainer').classList.add('hidden');
      };

      async function sendMessage(text) {
        sendBtn.disabled = true;
        try {
          let res;
          if (pendingAdminImage) {
            const formData = new FormData();
            formData.append('message', text || '');
            formData.append('chat_key', chatKeyInput.value);
            formData.append('chat_image', pendingAdminImage);
            res = await fetch('chat-send.php', { method: 'POST', body: formData });
            pendingAdminImage = null;
            document.getElementById('adminImagePreviewContainer').classList.add('hidden');
            document.getElementById('adminChatImageInput').value = '';
          } else {
            res = await fetch('chat-send.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ chat_key: chatKeyInput.value, message: text })
            });
          }
          const data = await res.json();
          if (data.success) {
            appendMessage(data.message);
            input.value = '';
            updateCharCount();
            validationEl.textContent = '';
          } else {
            validationEl.textContent = data.error || 'Something went wrong sending this reply.';
          }
        } catch (err) {
          validationEl.textContent = 'Network error — please try again.';
        } finally {
          sendBtn.disabled = false;
        }
      }

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
      });

      async function pollMessages() {
        if (polling) return;
        polling = true;
        try {
          const res = await fetch('chat-poll.php?action=messages&chat_key=' + encodeURIComponent(chatKeyInput.value) + '&after_id=' + lastId);
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

      messagePollNunitoval = setNunitoval(pollMessages, 4000);
    }

    // Refresh the conversation sidebar in place, without touching
    // whatever the admin is currently doing in the active thread.
    async function refreshConversationList() {
      try {
        const res = await fetch('chat-poll.php?action=conversations');
        const data = await res.json();
        if (!data.success || !Array.isArray(data.conversations)) return;

        const params = new URLSearchParams(window.location.search);
        const activeKey = params.get('chat') || '';

        document.getElementById('conversationCount').textContent = data.conversations.length;

        const container = document.getElementById('conversationItems');
        if (data.conversations.length === 0) {
          container.innerHTML = '<p class="p-4 text-sm text-[#9e9e9e]">No chat conversations yet.</p>';
          return;
        }

        container.innerHTML = data.conversations.map(c => {
          const isActive = c.chat_key === activeKey;
          const time = formatTime(c.last_message_at).split(', ')[1] || '';
          return `
            <div class="relative group border-b border-gray-50 conversation-row ${isActive ? 'bg-[#e8f5e9]' : 'hover:bg-gray-50'}" data-chat-key="${escapeHtml(c.chat_key)}">
              <a href="admin-live-chat.php?chat=${encodeURIComponent(c.chat_key)}" class="block px-4 py-3 pr-10 conversation-link">
                <div class="flex items-center justify-between mb-1">
                  <p class="text-[13px] font-semibold text-[#1a2e1c] truncate">${escapeHtml(c.customer_name)}</p>
                  <p class="text-[11px] text-[#9e9e9e] flex-shrink-0">${time}</p>
                </div>
                <p class="text-[12px] text-[#5a7a5c] truncate">${escapeHtml(c.last_message)}</p>
              </a>
              <button type="button" class="delete-conversation-btn absolute top-1/2 right-3 -translate-y-1/2 p-1.5 rounded-full text-gray-300 hover:text-red-500 hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-opacity" data-chat-key="${escapeHtml(c.chat_key)}" title="Delete conversation">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h10" /></svg>
              </button>
            </div>`;
        }).join('');
      } catch (err) {
        // Silently retry on the next interval.
      }
    }

    setNunitoval(refreshConversationList, 6000);
  </script>
</body>
</html>