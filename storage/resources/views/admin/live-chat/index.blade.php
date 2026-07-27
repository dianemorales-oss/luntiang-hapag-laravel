@extends('admin.layouts.app')
@section('title', 'Live Chat | Admin')
@section('header', 'Live Chat')
@section('content')

  @push('styles')
  <style>
    /* Custom Scrollbars inside Live Chat */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 6px; }
    #activeChatArea { height: calc(100vh - 16rem); }
  </style>
  @endpush

  <main class="flex-1 min-h-0 overflow-hidden flex border rounded-2xl bg-white shadow-sm" id="activeChatArea">
    <!-- Conversation List Sidebar -->
    <div id="conversationList" class="w-80 flex-shrink-0 border-r border-[rgba(27,94,32,0.12)] flex flex-col overflow-hidden">
      <div class="p-4 border-b border-gray-100 bg-gray-50/50">
        <h2 class="text-sm font-bold text-[#1a2e1c]">Conversations (<span id="conversationCount">{{ $conversations->count() }}</span>)</h2>
      </div>
      <div id="conversationItems" class="flex-1 overflow-y-auto divide-y divide-gray-50">
        @if ($conversations->isEmpty())
          <p class="p-4 text-sm text-[#9e9e9e]" id="noConversations">No chat conversations yet.</p>
        @else
          @foreach ($conversations as $c)
            @php $isActive = $c->chat_key === $activeChatKey; @endphp
            <div class="relative group conversation-row {{ $isActive ? 'bg-[#e8f5e9]' : 'hover:bg-gray-50/70' }} transition-colors" data-chat-key="{{ $c->chat_key }}">
              <a href="?chat={{ urlencode($c->chat_key) }}" class="block px-4 py-3.5 pr-10 conversation-link">
                <div class="flex items-center justify-between mb-1">
                  <p class="text-[13px] font-bold text-[#1a2e1c] truncate">{{ $c->customer_name }}</p>
                  <p class="text-[11px] text-[#9e9e9e] font-semibold flex-shrink-0">
                    {{ date('g:i A', strtotime($c->last_message_at)) }}
                  </p>
                </div>
                <p class="text-[12px] text-[#5a7a5c] truncate font-medium">{{ $c->last_message }}</p>
              </a>
              <button type="button" class="delete-conversation-btn absolute top-1/2 right-3 -translate-y-1/2 p-1.5 rounded-full text-gray-300 hover:text-red-500 hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-all duration-200" data-chat-key="{{ $c->chat_key }}" title="Delete conversation">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h10" />
                </svg>
              </button>
            </div>
          @endforeach
        @endif
      </div>
    </div>

    <!-- Active Conversation Chat Thread -->
    <div id="activeConversationPanel" class="flex-1 flex flex-col min-w-0 min-h-0 bg-gray-50/30">
      @if ($activeChatKey === '' || empty($activeMessages))
        <div class="flex-1 flex flex-col items-center justify-center text-center p-6 text-sm text-[#9e9e9e] font-semibold">
          <span class="text-3xl mb-2">💬</span>
          Select a conversation to view messages.
        </div>
      @else
        <div class="px-6 py-4 border-b border-[rgba(27,94,32,0.12)] bg-white shadow-sm z-10 flex items-center justify-between">
          <div>
            <p class="text-sm font-bold text-[#1a2e1c]">{{ $activeCustomerName }}</p>
            <p class="text-[11px] text-[#9e9e9e] font-semibold mt-0.5">Chat ID: {{ substr($activeChatKey, 0, 12) }}…</p>
          </div>
        </div>

        <div id="chatThread" class="flex-1 min-h-0 overflow-y-auto p-6 space-y-4 bg-gray-50/50" data-chat-key="{{ $activeChatKey }}" data-last-id="{{ $activeLastId }}">
          @foreach ($activeMessages as $m)
            @php
              $isBot = $m->sender === 'bot' || str_contains($m->customer_name ?? '', 'Assistant');
              $isAdmin = $m->sender === 'admin' && !$isBot;
              $label = $isAdmin ? 'You' : ($isBot ? 'Luntiang H.A.P.A.G. Assistant 🌿' : $m->customer_name);
              $hasImage = !empty($m->image_path);
            @endphp
            <div class="flex {{ $isAdmin ? 'justify-end' : 'justify-start' }}">
              <div class="max-w-[65%]">
                <div class="rounded-2xl px-4 py-2.5 text-[14px] leading-relaxed shadow-sm {{ $isAdmin ? 'bg-[#17611f] text-white rounded-br-sm' : ($isBot ? 'bg-[#e8f5e9] border border-[#c8e6c9] text-[#1a2e1c] rounded-bl-sm' : 'bg-white border border-[rgba(27,94,32,0.12)] text-[#1a2e1c] rounded-bl-sm') }}">
                  @if ($m->message)
                    {!! nl2br(e($m->message)) !!}
                  @endif
                  @if ($hasImage)
                    <img src="{{ asset($m->image_path) }}" class="mt-2 rounded-lg max-w-full max-h-64 object-cover" alt="Shared image">
                  @endif
                </div>
                <p class="text-[11px] text-[#9e9e9e] font-semibold mt-1 {{ $isAdmin ? 'text-right' : 'text-left' }}">
                  {{ $label }} · {{ $m->created_at->format('M j, g:i A') }}
                </p>
              </div>
            </div>
          @endforeach
        </div>

        <!-- Chat Input Form -->
        <form id="chatForm" method="POST" enctype="multipart/form-data" class="p-4 border-t border-[rgba(27,94,32,0.12)] bg-white shadow-[0_-1px_3px_rgba(0,0,0,0.02)] z-10">
          @csrf
          <div class="flex items-center gap-3">
            <label for="adminChatImageInput" class="cursor-pointer flex-shrink-0 w-10 h-10 rounded-full border border-[rgba(27,94,32,0.12)] flex items-center justify-center hover:bg-[#e8f5e9] hover:border-[#17611f] transition-all" title="Attach image">
              <svg class="w-5 h-5 text-[#5a7a5c]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </label>
            <input type="file" id="adminChatImageInput" name="chat_image" accept="image/*" class="hidden" onchange="previewAdminChatImage(this)">
            <input type="hidden" name="chat_key" id="chatKeyInput" value="{{ $activeChatKey }}" />
            <input type="text" name="message" id="chatInput" placeholder="Type your reply..." maxlength="250" class="flex-1 rounded-full border border-[rgba(27,94,32,0.12)] px-5 py-3 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors bg-white" />
            <button type="submit" id="chatSendBtn" class="px-6 py-3 rounded-full bg-[#17611f] text-white text-sm font-bold shadow-sm hover:bg-[#14521a] transition-colors flex-shrink-0">Send</button>
          </div>
          <div id="adminImagePreviewContainer" class="hidden mt-2 relative inline-block">
            <img id="adminImagePreview" src="" class="h-20 w-20 object-cover rounded-lg border border-[rgba(27,94,32,0.12)] shadow-sm">
            <button type="button" onclick="removeAdminChatImage()" class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-red-500 text-white text-xs flex items-center justify-center font-bold shadow">×</button>
          </div>
          <div class="flex items-center justify-between mt-2 px-1">
            <p id="chatValidation" class="text-[12px] text-red-500 font-semibold"></p>
            <p id="chatCharCount" class="text-[11px] text-[#9e9e9e] font-semibold ml-auto">0/250</p>
          </div>
        </form>
      @endif
    </div>
  </main>

  <!-- Delete Conversation Confirmation Modal -->
  <div id="deleteConfirmModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 px-4">
    <div class="bg-white rounded-2xl shadow-lg max-w-sm w-full p-6 border">
      <h3 class="text-base font-bold text-[#1a2e1c] mb-2">Delete Conversation?</h3>
      <p class="text-[13px] text-[#5a7a5c] leading-relaxed mb-2 font-medium">This action will permanently delete the selected chat conversation and all of its messages.</p>
      <p class="text-[13px] text-[#5a7a5c] leading-relaxed mb-4 font-medium">Customer account information, profile details, tickets, and other records will <span class="font-bold">NOT</span> be deleted.</p>
      <p class="text-[12px] text-red-500 font-bold mb-6">This action cannot be undone.</p>
      <div class="flex items-center justify-end gap-3">
        <button type="button" id="deleteConfirmCancel" class="px-5 py-2.5 rounded-full border border-gray-300 text-[#1a2e1c] text-sm font-bold hover:bg-gray-100 transition-colors">Cancel</button>
        <button type="button" id="deleteConfirmSubmit" class="px-5 py-2.5 rounded-full bg-red-600 text-white text-sm font-bold hover:bg-red-700 transition-colors">Delete Conversation</button>
      </div>
    </div>
  </div>

  <!-- Toast Notifications -->
  <div id="toast" class="hidden fixed bottom-6 right-6 z-50 px-4 py-3 rounded-xl shadow-lg text-sm font-bold transition-all duration-300"></div>

@endsection

@push('scripts')
  <script>
    const thread = document.getElementById('chatThread');
    const form = document.getElementById('chatForm');

    let currentChatKey = @json($activeChatKey);
    let messagePollInterval = null;

    const adminSendUrlTemplate = @json(route('admin.live-chat.send', ['chatKey' => '__CHAT_KEY__']));
    const adminPollUrlTemplate = @json(route('admin.live-chat.poll', ['chatKey' => '__CHAT_KEY__']));
    const adminConversationPollUrl = @json(route('admin.live-chat.poll', ['chatKey' => '__conversations__']));

    function adminChatUrl(template, chatKey) {
      return template.replace('__CHAT_KEY__', encodeURIComponent(chatKey));
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

    // Toast Alerts
    const toastEl = document.getElementById('toast');
    let toastTimer = null;
    function showToast(text, isError) {
      clearTimeout(toastTimer);
      toastEl.textContent = text;
      toastEl.className = 'fixed bottom-6 right-6 z-50 px-4 py-3 rounded-xl shadow-lg text-sm font-bold ' +
        (isError ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-green-50 text-green-700 border border-green-100');
      toastEl.classList.remove('hidden');
      toastTimer = setTimeout(() => { toastEl.classList.add('hidden'); }, 3500);
    }

    // Delete Conversation Modal
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
        const res = await fetch('{{ route('admin.live-chat.index') }}/' + encodeURIComponent(chatKey) + '/delete', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
          body: JSON.stringify({ chat_key: chatKey })
        });
        const data = await res.json();
        if (data.success) {
          closeDeleteModal();
          showToast('✅ Conversation deleted successfully.', false);

          const row = document.querySelector('.conversation-row[data-chat-key="' + CSS.escape(chatKey) + '"]');
          if (row) row.remove();
          const remaining = document.querySelectorAll('.conversation-row').length;
          const countEl = document.getElementById('conversationCount');
          if (countEl) countEl.textContent = remaining;
          const container = document.getElementById('conversationItems');
          if (remaining === 0 && container) {
            container.innerHTML = '<p class="p-4 text-sm text-[#9e9e9e] font-semibold">No chat conversations yet.</p>';
          }

          if (currentChatKey === chatKey) {
            currentChatKey = '';

            if (messagePollInterval) {
              clearInterval(messagePollInterval);
              messagePollInterval = null;
            }

            const panel = document.getElementById('activeConversationPanel');
            if (panel) {
              panel.innerHTML = `<div class="flex-1 flex flex-col items-center justify-center text-center px-6">
                     <div class="text-4xl mb-3">💬</div>
                     <p class="text-sm font-bold text-[#1a2e1c] mb-1">Conversation Deleted</p>
                     <p class="text-[13px] text-[#9e9e9e] font-medium max-w-xs">This conversation has been deleted successfully. Select another conversation from the list to continue.</p>
                   </div>`;
            }

            const url = new URL(window.location.href);
            url.searchParams.delete('chat');
            window.history.replaceState({}, '', url);
          }
        } else {
          showToast('Something went wrong deleting this conversation.', true);
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
        const isBot = m.sender === 'bot' || ((m.customer_name || '').includes('Assistant'));
        const isAdmin = m.sender === 'admin' && !isBot;
        const label = isAdmin ? 'You' : (isBot ? 'Luntiang H.A.P.A.G. Assistant 🌿' : escapeHtml(m.customer_name || 'Customer'));
        const bubbleClass = isAdmin
          ? 'bg-[#17611f] text-white rounded-br-sm shadow'
          : (isBot ? 'bg-[#e8f5e9] border border-[#c8e6c9] text-[#1a2e1c] rounded-bl-sm shadow-sm' : 'bg-white border border-[rgba(27,94,32,0.12)] text-[#1a2e1c] rounded-bl-sm shadow-sm');
        let imageHtml = '';
        if (m.image_path) {
          imageHtml = `<img src="{{ asset('') }}${escapeHtml(m.image_path)}" class="mt-2 rounded-lg max-w-full max-h-64 object-cover border shadow-sm" alt="Shared image">`;
        }
        const wrap = document.createElement('div');
        wrap.className = 'flex ' + (isAdmin ? 'justify-end' : 'justify-start');
        wrap.innerHTML = `
          <div class="max-w-[65%]">
            <div class="rounded-2xl px-4 py-2.5 text-[14px] leading-relaxed ${bubbleClass}">
              ${m.message ? escapeHtml(m.message).replace(/\n/g, '<br>') : ''}${imageHtml}
            </div>
            <p class="text-[11px] text-[#9e9e9e] font-semibold mt-1 ${isAdmin ? 'text-right' : 'text-left'}">
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
            res = await fetch(adminChatUrl(adminSendUrlTemplate, chatKeyInput.value), {
              method: 'POST',
              headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
              body: formData
            });
            pendingAdminImage = null;
            document.getElementById('adminImagePreviewContainer').classList.add('hidden');
            document.getElementById('adminChatImageInput').value = '';
          } else {
            res = await fetch(adminChatUrl(adminSendUrlTemplate, chatKeyInput.value), {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
              body: JSON.stringify({ chat_key: chatKeyInput.value, message: text })
            });
          }
          const data = await res.json();
          if (data.success && data.message) {
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
        if (text === '' && !pendingAdminImage) {
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
          const res = await fetch(adminChatUrl(adminPollUrlTemplate, chatKeyInput.value) + '?after_id=' + lastId);
          const data = await res.json();
          if (data.messages && Array.isArray(data.messages)) {
            data.messages.forEach(appendMessage);
          }
        } catch (err) {
          // Silently retry
        } finally {
          polling = false;
        }
      }

      messagePollInterval = setInterval(pollMessages, 4000);
    }

    async function refreshConversationList() {
      try {
        const res = await fetch(adminConversationPollUrl + '?action=conversations');
        const data = await res.json();
        if (!data.success || !Array.isArray(data.conversations)) return;

        const params = new URLSearchParams(window.location.search);
        const activeKey = params.get('chat') || '';

        document.getElementById('conversationCount').textContent = data.conversations.length;

        const container = document.getElementById('conversationItems');
        if (data.conversations.length === 0) {
          container.innerHTML = '<p class="p-4 text-sm text-[#9e9e9e] font-semibold">No chat conversations yet.</p>';
          return;
        }

        container.innerHTML = data.conversations.map(c => {
          const isActive = c.chat_key === activeKey;
          const time = formatTime(c.last_message_at).split(', ')[1] || '';
          return `
            <div class="relative group border-b border-gray-50 conversation-row ${isActive ? 'bg-[#e8f5e9]' : 'hover:bg-gray-50/75'} transition-colors" data-chat-key="${escapeHtml(c.chat_key)}">
              <a href="?chat=${encodeURIComponent(c.chat_key)}" class="block px-4 py-3.5 pr-10 conversation-link">
                <div class="flex items-center justify-between mb-1">
                  <p class="text-[13px] font-bold text-[#1a2e1c] truncate">${escapeHtml(c.customer_name)}</p>
                  <p class="text-[11px] text-[#9e9e9e] font-semibold flex-shrink-0">${time}</p>
                </div>
                <p class="text-[12px] text-[#5a7a5c] truncate font-medium">${escapeHtml(c.last_message)}</p>
              </a>
              <button type="button" class="delete-conversation-btn absolute top-1/2 right-3 -translate-y-1/2 p-1.5 rounded-full text-gray-300 hover:text-red-500 hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-all duration-200" data-chat-key="${escapeHtml(c.chat_key)}" title="Delete conversation">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h10" /></svg>
              </button>
            </div>`;
        }).join('');
      } catch (err) {
        // Silently retry
      }
    }

    setInterval(refreshConversationList, 6000);
  </script>
@endpush
