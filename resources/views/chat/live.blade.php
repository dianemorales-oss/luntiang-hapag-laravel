@extends('layouts.app')
@section('title', 'Live Chat | Luntiang H.A.P.A.G.')
@section('content')
<main class="max-w-4xl mx-auto px-6 py-8">
  <div class="flex items-center justify-between mb-4">
    <div>
      <h1 class="text-2xl font-black">Live Chat</h1>
      <p class="text-sm text-[#5a7a5c] mt-1">Chat with the Luntiang H.A.P.A.G. support assistant.</p>
    </div>
    <a href="{{ route('contact') }}" class="hidden sm:inline-flex px-4 py-2 rounded-xl border text-sm font-bold hover:bg-[#e8f5e9]">Back to Support</a>
  </div>

  <div class="bg-white rounded-2xl border border-[rgba(27,94,32,0.10)] flex flex-col h-[70vh] shadow-sm overflow-hidden">
    <!-- Conversation Modes - Cleaner interface with only two buttons -->
    <div class="p-3 border-b border-[rgba(27,94,32,0.08)] bg-white flex items-center justify-between gap-2">
      <div class="flex items-center gap-2">
        <span class="text-[11px] font-black tracking-widest text-[#5a7a5c] uppercase">Mode:</span>
        <div class="flex items-center bg-[#f4faf5] rounded-full p-1 border">
          <button type="button" id="modeAssistantBtn" class="mode-btn px-4 py-1.5 rounded-full text-xs font-bold transition-all {{ ($isAgentMode ?? false) ? 'bg-white border text-[#5a7a5c]' : 'bg-[#17611f] text-white shadow-sm' }}">🤖 Talk to Assistant</button>
          <button type="button" id="modeAgentBtn" class="mode-btn px-4 py-1.5 rounded-full text-xs font-bold transition-all {{ ($isAgentMode ?? false) ? 'bg-[#17611f] text-white shadow-sm' : 'bg-white border text-[#5a7a5c]' }}">👤 Talk to Agent</button>
        </div>
      </div>
      <span class="text-[10px] text-[#9e9e9e] hidden sm:inline">Chatbot maintains context • Agent takes over when selected</span>
    </div>

    <div id="chatMessages" class="flex-1 overflow-y-auto p-4 space-y-3 bg-[#f8fcf9]">
      @foreach($messages as $msg)
        @php
          $isAssistant = $msg->sender === 'bot' || str_contains($msg->customer_name ?? '', 'Assistant');
          $isCustomer = $msg->sender === 'customer';
          $bubbleClass = $isCustomer
              ? 'bg-[#17611f] text-white'
              : ($isAssistant ? 'bg-[#e8f5e9] border border-[#c8e6c9] text-[#1a2e1c]' : 'bg-white border border-[rgba(27,94,32,0.10)] text-[#1a2e1c]');
          $label = $isCustomer && $msg->customer_name === $customerName ? 'You' : ($isAssistant ? 'Assistant' : ($msg->customer_name ?: 'Support'));
        @endphp
        <div class="flex {{ $isCustomer ? 'justify-end' : 'justify-start' }}">
          <div class="max-w-[70%] rounded-2xl px-4 py-2 text-sm {{ $bubbleClass }}">
            <p class="text-xs font-bold opacity-70 mb-1">{{ $label }}</p>
            @if($msg->message !== '')
              <p class="whitespace-pre-line">{{ $msg->message }}</p>
            @endif
            @if($msg->image_path)
              <img src="{{ asset($msg->image_path) }}" class="mt-2 rounded-lg max-h-40" alt="Shared image">
            @endif
          </div>
        </div>
      @endforeach
    </div>

    <!-- Suggested Questions - Moved above message input field -->
    <div class="p-3 border-t border-[rgba(27,94,32,0.06)] bg-[#f8fcf9]">
      <p class="text-[11px] font-black tracking-widest text-[#5a7a5c] uppercase mb-2">💡 Suggested Questions</p>
      <div class="flex flex-wrap gap-1.5" id="suggestedQuestions">
        @foreach(($suggestedQuestions ?? []) as $q)
          <button type="button" class="suggested-q px-3 py-1.5 rounded-full bg-white border border-[rgba(27,94,32,0.12)] text-xs font-semibold text-[#1a2e1c] hover:bg-[#e8f5e9] hover:border-[#17611f]/30 hover:text-[#17611f] transition-all" data-message="{{ $q['message'] }}">{{ $q['label'] }}</button>
        @endforeach
        {{-- Primary suggestions are supplied by the knowledge base; no duplicate hard-coded prompts. --}}
      </div>
      @if(!empty($moreQuestions))
        <details class="mt-2">
          <summary class="text-[11px] font-bold text-[#17611f] cursor-pointer hover:underline">More questions...</summary>
          <div class="flex flex-wrap gap-1.5 mt-2">
            @foreach($moreQuestions as $q)
              <button type="button" class="suggested-q px-3 py-1.5 rounded-full bg-white border border-[rgba(27,94,32,0.10)] text-[11px] font-medium text-[#5a7a5c] hover:bg-[#e8f5e9] hover:text-[#17611f]" data-message="{{ $q['message'] }}">{{ $q['label'] }}</button>
            @endforeach
          </div>
        </details>
      @endif
    </div>

    <div id="imagePreviewContainer" class="hidden p-3 border-t border-[rgba(27,94,32,0.10)] bg-[#f8fcf9]">
      <div class="flex items-start gap-3">
        <img id="imagePreview" src="" alt="Preview" class="w-20 h-20 object-cover rounded-xl border">
        <div class="flex-1 min-w-0">
          <p id="imagePreviewName" class="text-xs font-bold truncate"></p>
          <p class="text-[11px] text-[#5a7a5c] mt-0.5">Preview – image will be sent with your message</p>
          <div class="flex gap-2 mt-2">
            <button type="button" id="removeImageBtn" class="px-3 py-1 rounded-full bg-red-50 text-red-600 text-xs font-bold hover:bg-red-100">Remove</button>
            <button type="button" id="sendImageOnlyBtn" class="px-3 py-1 rounded-full bg-[#17611f] text-white text-xs font-bold hover:bg-[#14521a]">Send Image</button>
          </div>
        </div>
      </div>
    </div>

    <form id="chatForm" class="p-4 border-t border-[rgba(27,94,32,0.10)] flex gap-2 bg-white" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="gk" id="gkInput" value="{{ $chatKey }}">
      <input type="text" name="message" id="messageInput" placeholder="Type a message..." class="flex-1 border border-[rgba(27,94,32,0.12)] rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
      <input type="file" name="image" id="imageInput" accept="image/*" class="hidden">
      <button type="button" id="attachImageBtn" class="px-3 py-2.5 rounded-xl border text-sm hover:bg-[#e8f5e9] transition-colors" title="Upload image">📎</button>
      <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a] transition-colors">Send</button>
    </form>
  </div>
</main>
@endsection

@push('scripts')
<script>
(function(){
  const chatKey = @json($chatKey);
  const customerName = @json($customerName);
  const isLoggedIn = {{ $userId ? 'true' : 'false' }};
  const userId = @json($userId);
  const csrfToken = @json(csrf_token());
  const sendUrl = @json(route('chat.send'));
  const pollUrl = @json(route('chat.poll'));

  if(!isLoggedIn){
    let gk = sessionStorage.getItem('guest_chat_key');
    if(!gk){
      gk = chatKey;
      sessionStorage.setItem('guest_chat_key', gk);
    }
    document.getElementById('gkInput').value = gk;
  }

  const messagesEl = document.getElementById('chatMessages');
  let lastId = {{ $messages->last()?->id ?? 0 }};

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
  }

  function scrollBottom(){
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }
  scrollBottom();

  function appendMessage(m, optimistic = false) {
    const isAssistant = m.sender === 'bot' || ((m.customer_name || '').includes('Assistant'));
    const isCustomer = m.sender === 'customer';
    const isMine = optimistic || (isCustomer && (isLoggedIn ? Number(m.user_id) === Number(userId) : (m.customer_name === customerName)));
    const bubbleClass = isCustomer
      ? 'bg-[#17611f] text-white'
      : (isAssistant ? 'bg-[#e8f5e9] border border-[#c8e6c9] text-[#1a2e1c]' : 'bg-white border border-[rgba(27,94,32,0.10)] text-[#1a2e1c]');
    const label = isMine ? 'You' : (isAssistant ? 'Assistant' : (m.customer_name || 'Support'));
    const imageHtml = m.image_path ? `<img src="/${escapeHtml(m.image_path)}" class="mt-2 rounded-lg max-h-40" alt="Shared image">` : '';

    const div = document.createElement('div');
    div.className = 'flex ' + (isCustomer ? 'justify-end' : 'justify-start');
    div.innerHTML = `<div class="max-w-[70%] rounded-2xl px-4 py-2 text-sm ${bubbleClass}">
      <p class="text-xs font-bold opacity-70 mb-1">${escapeHtml(label)}</p>
      ${m.message ? `<p class="whitespace-pre-line">${escapeHtml(m.message)}</p>` : ''}
      ${imageHtml}
    </div>`;
    messagesEl.appendChild(div);
    if (m.id) lastId = Math.max(lastId, parseInt(m.id, 10));
    scrollBottom();
  }

  // Image Upload with Preview
  const imageInput = document.getElementById('imageInput');
  const attachBtn = document.getElementById('attachImageBtn');
  const previewContainer = document.getElementById('imagePreviewContainer');
  const previewImg = document.getElementById('imagePreview');
  const previewName = document.getElementById('imagePreviewName');
  const removeImageBtn = document.getElementById('removeImageBtn');
  const sendImageOnlyBtn = document.getElementById('sendImageOnlyBtn');

  attachBtn.addEventListener('click', ()=> imageInput.click());

  imageInput.addEventListener('change', function(){
    const file = this.files[0];
    if(!file) return;
    if(!file.type.startsWith('image/')){
      alert('Please select an image file.');
      this.value=''; return;
    }
    if(file.size > 5*1024*1024){
      alert('Image must be less than 5MB.');
      this.value=''; return;
    }
    const reader = new FileReader();
    reader.onload = e=>{
      previewImg.src = e.target.result;
      previewName.textContent = file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)';
      previewContainer.classList.remove('hidden');
    };
    reader.readAsDataURL(file);
  });

  function clearImagePreview(){
    imageInput.value='';
    previewImg.src='';
    previewName.textContent='';
    previewContainer.classList.add('hidden');
  }

  removeImageBtn.addEventListener('click', clearImagePreview);
  sendImageOnlyBtn.addEventListener('click', ()=>{ document.getElementById('chatForm').dispatchEvent(new Event('submit')); });

  document.getElementById('chatForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const messageInput = document.getElementById('messageInput');
    const msg = messageInput.value.trim();
    const imgFile = imageInput.files[0];
    if(!msg && !imgFile) return;

    const form = new FormData(this);
    if(!isLoggedIn){
      form.set('gk', sessionStorage.getItem('guest_chat_key') || chatKey);
    }

    const tempImageUrl = imgFile ? URL.createObjectURL(imgFile) : null;
    const optimisticMessage = msg;
    const optimisticImage = tempImageUrl;

    messageInput.value = '';
    // Don't clear image immediately, clear after optimistic append then clear preview
    const hadImage = !!imgFile;

    if (msg || hadImage) {
      appendMessage({sender:'customer', customer_name:customerName, message:optimisticMessage, image_path: optimisticImage ? optimisticImage.replace(/^.*\/public\//,'').replace(/blob:.*/, '') : null}, true);
      // For optimistic image, show preview url directly
      if(hadImage && tempImageUrl){
        const lastBubble = messagesEl.lastElementChild;
        if(lastBubble){
          const existingImg = lastBubble.querySelector('img');
          if(existingImg) existingImg.src = tempImageUrl;
          else {
            const bubble = lastBubble.querySelector('div');
            if(bubble){
              const imgEl = document.createElement('img');
              imgEl.src = tempImageUrl;
              imgEl.className = 'mt-2 rounded-lg max-h-40';
              imgEl.alt = 'Uploading...';
              bubble.appendChild(imgEl);
            }
          }
        }
      }
    }

    clearImagePreview();

    try {
      const res = await fetch(sendUrl, { method:'POST', body:form, headers:{ 'X-CSRF-TOKEN': csrfToken } });
      const data = await res.json();
      if(data.ok){
        if(data.customerMessage && data.customerMessage.id) {
          lastId = Math.max(lastId, parseInt(data.customerMessage.id, 10));
          // Replace optimistic blob url with real path if needed
          if(data.customerMessage.image_path){
            const lastImgs = messagesEl.querySelectorAll('img');
            if(lastImgs.length){
              const lastImg = lastImgs[lastImgs.length-1];
              if(lastImg.src.startsWith('blob:')){
                lastImg.src = '/' + data.customerMessage.image_path;
              }
            }
          }
        }
        if(!isLoggedIn && data.chatKey){
          sessionStorage.setItem('guest_chat_key', data.chatKey);
          document.getElementById('gkInput').value = data.chatKey;
        }
        if(Array.isArray(data.botReplies)){
          data.botReplies.forEach(bot => appendMessage(bot));
        }
      }
    } catch(err) {
      console.error(err);
    }
  });

  // Suggested Questions - click to instantly ask
  document.querySelectorAll('.suggested-q').forEach(btn=>{
    btn.addEventListener('click', function(){
      const msg = this.dataset.message;
      if(!msg) return;
      document.getElementById('messageInput').value = msg;
      // Auto send
      document.getElementById('chatForm').dispatchEvent(new Event('submit'));
    });
  });

  // Conversation Modes - Cleaner interface with only Talk to Assistant and Talk to Agent buttons
  const modeAssistantBtn = document.getElementById('modeAssistantBtn');
  const modeAgentBtn = document.getElementById('modeAgentBtn');
  const modeUrl = @json(route('chat.mode'));

  function updateModeUI(isAgent){
    if(isAgent){
      modeAssistantBtn.className='mode-btn px-4 py-1.5 rounded-full text-xs font-bold transition-all bg-white border text-[#5a7a5c] hover:bg-[#f4faf5]';
      modeAgentBtn.className='mode-btn px-4 py-1.5 rounded-full text-xs font-bold transition-all bg-[#17611f] text-white shadow-sm';
    } else {
      modeAssistantBtn.className='mode-btn px-4 py-1.5 rounded-full text-xs font-bold transition-all bg-[#17611f] text-white shadow-sm';
      modeAgentBtn.className='mode-btn px-4 py-1.5 rounded-full text-xs font-bold transition-all bg-white border text-[#5a7a5c] hover:bg-[#f4faf5]';
    }
  }

  async function switchMode(mode){
    try{
      const formData = new FormData();
      formData.append('mode', mode);
      formData.append('gk', isLoggedIn ? '' : (sessionStorage.getItem('guest_chat_key') || chatKey));
      const res = await fetch(modeUrl, { method:'POST', body: formData, headers:{ 'X-CSRF-TOKEN': csrfToken } });
      const data = await res.json();
      if(data.ok){
        updateModeUI(mode==='agent');
        setTimeout(poll, 500);
      }
    }catch(e){ console.error(e); alert('Unable to change chat mode. Please try again.'); }
  }

  if(modeAssistantBtn) modeAssistantBtn.addEventListener('click', ()=> switchMode('assistant'));
  if(modeAgentBtn) modeAgentBtn.addEventListener('click', ()=> switchMode('agent'));

  // Initialize mode UI from server - default Talk to Assistant
  let initialAgentMode = {{ ($isAgentMode ?? false) ? 'true' : 'false' }};
  updateModeUI(initialAgentMode);

  async function poll(){
    try {
      const gk = isLoggedIn ? '' : (sessionStorage.getItem('guest_chat_key') || chatKey);
      const res = await fetch(pollUrl + '?last_id=' + lastId + '&gk=' + encodeURIComponent(gk));
      const data = await res.json();
      if(Array.isArray(data.messages)){
        data.messages.forEach(m => {
          if(m.id <= lastId) return;
          lastId = parseInt(m.id, 10);
          const ownCustomerMessage = m.sender === 'customer' && (isLoggedIn ? Number(m.user_id) === Number(userId) : m.customer_name === customerName);
          if(!ownCustomerMessage){
            appendMessage(m);
            // If agent replied, ensure UI shows agent mode
            if(m.sender==='admin' && !m.customer_name.includes('Assistant')){
              // Agent replied, keep agent mode indicator
              // Don't auto switch, but show notification
            }
          }
        });
      }
      // Also check bot state to update UI if admin switched via backend
      // Could poll separate endpoint, but we use chat state inferred
    } catch(err) {
      // retry silently
    }
    setTimeout(poll, 3000);
  }
  poll();
})();
</script>
@endpush
