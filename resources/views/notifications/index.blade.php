@extends('layouts.app')
@section('title','Notifications | Luntiang H.A.P.A.G.')
@section('content')
<main class="max-w-4xl mx-auto px-6 py-8">
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-black">Notifications</h1>
      <p class="text-sm text-[#5a7a5c] mt-1">{{ $unreadCount }} unread • Real-time updates for orders, support tickets, and live chat</p>
    </div>
    @if($notifications->isNotEmpty())
      <form method="POST" action="{{ route('customer.notifications.markAllRead') }}">
        @csrf
        <button type="submit" class="px-4 py-2 rounded-full bg-[#17611f] text-white text-xs font-bold hover:bg-[#14521a]">Mark all as read</button>
      </form>
    @endif
  </div>

  @if($notifications->isEmpty())
    <div class="bg-white rounded-2xl border p-12 text-center">
      <p class="text-4xl mb-3">🔔</p>
      <p class="font-black text-[#1a2e1c]">No notifications yet</p>
      <p class="text-sm text-[#5a7a5c] mt-1">When your order status changes, support ticket updates, or live chat agent replies, you'll see them here with real-time badges and optional browser notifications.</p>
    </div>
  @else
    <div class="space-y-3">
      @foreach($notifications as $n)
        <div class="bg-white rounded-xl border p-4 flex gap-3 {{ !$n->is_read ? 'border-[#c8e6c9] bg-[#f4faf5]/50' : '' }}">
          <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center {{ $n->type==='order_status' ? 'bg-[#e8f5e9] text-[#17611f]' : ($n->type==='ticket_status' ? 'bg-amber-100 text-amber-700' : 'bg-blue-50 text-blue-600') }}">
            @if($n->type==='order_status') 📦 @elseif($n->type==='ticket_status') 🎫 @elseif($n->type==='chat_reply') 💬 @else 🔔 @endif
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <p class="font-bold text-sm text-[#1a2e1c]">{{ $n->title }}</p>
              @if(!$n->is_read) <span class="w-2 h-2 bg-[#17611f] rounded-full"></span> @endif
              <span class="text-[11px] text-[#9e9e9e] ml-auto">{{ $n->created_at->diffForHumans() }}</span>
            </div>
            <p class="text-sm text-[#5a7a5c] mt-1">{{ $n->message }}</p>
            <div class="flex items-center gap-2 mt-2">
              @if($n->link)
                <a href="{{ route('customer.notifications.open',['id'=>$n->id]) }}" class="text-xs font-bold text-[#17611f] hover:underline">View details →</a>
              @endif
              @if(!$n->is_read)
                <form method="POST" action="{{ route('customer.notifications.read',['id'=>$n->id]) }}" class="inline">
                  @csrf
                  <button type="submit" class="text-[11px] text-[#5a7a5c] hover:text-[#1a2e1c]">Mark as read</button>
                </form>
              @endif
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif

  <div class="mt-8 bg-[#e8f5e9] border border-[#c8e6c9] rounded-2xl p-5">
    <h3 class="font-black text-sm mb-2">🔔 Enable Browser Notifications</h3>
    <p class="text-xs text-[#5a7a5c] leading-relaxed">Allow browser notifications to get instant alerts when your order status changes, support ticket is updated, or a live chat agent replies – even when you're on another tab.</p>
    <button id="enableBrowserNotif" class="mt-3 px-4 py-2 rounded-full bg-[#17611f] text-white text-xs font-bold hover:bg-[#14521a]">Enable Notifications</button>
    <p id="notifPermStatus" class="text-[11px] text-[#5a7a5c] mt-2"></p>
  </div>
</main>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const btn = document.getElementById('enableBrowserNotif');
  const statusEl = document.getElementById('notifPermStatus');
  function updateStatus(){
    if(!('Notification' in window)){ statusEl.textContent='Browser notifications not supported in this browser.'; return; }
    if(Notification.permission==='granted'){ statusEl.textContent='✅ Browser notifications enabled – you will receive real-time alerts.'; btn.textContent='Notifications Enabled'; btn.disabled=true; }
    else if(Notification.permission==='denied'){ statusEl.textContent='❌ Notifications blocked. Please allow in browser settings to enable.'; }
    else { statusEl.textContent='Click Enable to allow browser notifications.'; }
  }
  updateStatus();
  if(btn){
    btn.addEventListener('click', async ()=>{
      if(!('Notification' in window)){ alert('Notifications not supported'); return; }
      const perm = await Notification.requestPermission();
      updateStatus();
      if(perm==='granted'){
        try{ new Notification('Notifications Enabled', { body: 'You will now receive real-time updates for orders, tickets, and chat replies.', icon: '/images/lettuce/logo-cropped.png' }); }catch(e){}
      }
    });
  }
});
</script>
@endpush
@endsection
