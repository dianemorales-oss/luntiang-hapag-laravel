@extends('layouts.app')
@section('title','Live Chat')
@section('content')
<main class="max-w-4xl mx-auto px-6 py-8">
  <h1 class="text-2xl font-black mb-4">Live Chat</h1>
  <div class="bg-white rounded-xl border flex flex-col h-[70vh]">
    <div id="chatMessages" class="flex-1 overflow-y-auto p-4 space-y-3">
      @foreach($messages as $msg)
        <div class="flex {{ $msg->sender==='customer' ? 'justify-end' : 'justify-start' }}">
          <div class="max-w-[70%] rounded-2xl px-4 py-2 text-sm {{ $msg->sender==='customer' ? 'bg-[#17611f] text-white' : ($msg->sender==='bot' ? 'bg-[#e8f5e9] border border-[#c8e6c9] text-[#1a2e1c]' : 'bg-gray-100 border text-[#1a2e1c]') }}">
            <p class="text-xs font-bold opacity-70 mb-1">{{ $msg->customer_name }}</p>
            <p class="whitespace-pre-line">{{ $msg->message }}</p>
            @if($msg->image_path)<img src="{{ asset($msg->image_path) }}" class="mt-2 rounded-lg max-h-40">@endif
          </div>
        </div>
      @endforeach
    </div>
    <form id="chatForm" class="p-4 border-t flex gap-2" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="gk" id="gkInput" value="{{ $chatKey }}">
      <input type="text" name="message" id="messageInput" placeholder="Type a message..." class="flex-1 border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
      <input type="file" name="image" id="imageInput" accept="image/*" class="hidden">
      <button type="button" onclick="document.getElementById('imageInput').click()" class="px-3 py-2.5 rounded-xl border text-sm">📎</button>
      <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold">Send</button>
    </form>
  </div>
</main>
<script>
(function(){
  const chatKey = "{{ $chatKey }}";
  const isLoggedIn = {{ $userId ? 'true' : 'false' }};
  if(!isLoggedIn){
    // guest key handling via sessionStorage
    let gk = sessionStorage.getItem('guest_chat_key');
    if(!gk){ gk = chatKey; sessionStorage.setItem('guest_chat_key', gk); }
    document.getElementById('gkInput').value = gk;
    // persist across reloads: use stored
    if(gk !== chatKey){
      // if stored is different, we need to reload messages? For simplicity, use stored
      document.getElementById('gkInput').value = gk;
    }
  }

  const messagesEl = document.getElementById('chatMessages');
  let lastId = {{ $messages->last()?->id ?? 0 }};

  function scrollBottom(){ messagesEl.scrollTop = messagesEl.scrollHeight; }
  scrollBottom();

  document.getElementById('chatForm').addEventListener('submit', async function(e){
    e.preventDefault();
    let msg = document.getElementById('messageInput').value.trim();
    let imgFile = document.getElementById('imageInput').files[0];
    if(!msg && !imgFile) return;

    let form = new FormData(this);
    // if guest, ensure gk is sessionStorage
    if(!isLoggedIn){
      form.set('gk', sessionStorage.getItem('guest_chat_key') || chatKey);
    }

    document.getElementById('messageInput').value = '';
    document.getElementById('imageInput').value = '';

    // optimistic UI
    let div = document.createElement('div');
    div.className = 'flex justify-end';
    div.innerHTML = `<div class="max-w-[70%] rounded-2xl px-4 py-2 text-sm bg-[#17611f] text-white"><p class="text-xs font-bold opacity-70 mb-1">You</p><p class="whitespace-pre-line">${msg.replace(/</g,'&lt;')}</p></div>`;
    messagesEl.appendChild(div);
    scrollBottom();

    try {
      let res = await fetch('{{ route('chat.send') }}', {method:'POST', body:form, headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}});
      let data = await res.json();
      if(data.ok){
        // if guest, store new chatKey if returned
        if(!isLoggedIn && data.chatKey){ sessionStorage.setItem('guest_chat_key', data.chatKey); document.getElementById('gkInput').value = data.chatKey; }
        if(data.botReplies){
          data.botReplies.forEach(bot=>{
            let d = document.createElement('div');
            d.className='flex justify-start';
            d.innerHTML=`<div class="max-w-[70%] rounded-2xl px-4 py-2 text-sm bg-[#e8f5e9] border border-[#c8e6c9] text-[#1a2e1c]"><p class="text-xs font-bold opacity-70 mb-1">Assistant</p><p class="whitespace-pre-line">${bot.message.replace(/</g,'&lt;')}</p></div>`;
            messagesEl.appendChild(d);
            lastId = bot.id;
          });
          scrollBottom();
        }
      }
    } catch(e){ console.error(e); }
  });

  // poll for new messages
  async function poll(){
    try {
      let gk = isLoggedIn ? '' : (sessionStorage.getItem('guest_chat_key') || chatKey);
      let url = '{{ route('chat.poll') }}?last_id='+lastId+'&gk='+encodeURIComponent(gk);
      let res = await fetch(url);
      let data = await res.json();
      if(data.messages && data.messages.length>0){
        data.messages.forEach(m=>{
          if(m.id <= lastId) return;
          lastId = m.id;
          let isCustomer = m.sender==='customer' && (isLoggedIn ? m.user_id == {{ $userId ?? 'null' }} : true);
          // Actually if we sent, we already showed, but for admin replies we need to show
          if(m.sender==='customer' && m.customer_name && m.customer_name.includes('Guest') && !isLoggedIn){
            // if it's our own message echoed, skip if we already showed? We'll still show remote if not duplicate
          }
          if(m.sender !== 'customer' || m.customer_name !== '{{ $customerName }}'){
            let div = document.createElement('div');
            div.className = m.sender==='customer' ? 'flex justify-end' : 'flex justify-start';
            let bg = m.sender==='customer' ? 'bg-[#17611f] text-white' : (m.sender==='bot' ? 'bg-[#e8f5e9] border border-[#c8e6c9] text-[#1a2e1c]' : 'bg-gray-100 border text-[#1a2e1c]');
            div.innerHTML = `<div class="max-w-[70%] rounded-2xl px-4 py-2 text-sm ${bg}"><p class="text-xs font-bold opacity-70 mb-1">${m.customer_name}</p><p class="whitespace-pre-line">${(m.message||'').replace(/</g,'&lt;')}</p>${m.image_path ? `<img src="/${m.image_path}" class="mt-2 rounded-lg max-h-40">` : ''}</div>`;
            messagesEl.appendChild(div);
            scrollBottom();
          }
        });
      }
    } catch(e){}
    setTimeout(poll, 3000);
  }
  poll();
})();
</script>
@endsection
