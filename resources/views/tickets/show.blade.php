@extends('layouts.app')
@section('title','Ticket #LH-'.str_pad($ticket->id,4,'0',STR_PAD_LEFT))

@push('styles')
<style>
  body { background: #F3F0E4 !important; }
  .thread-scroll::-webkit-scrollbar { width: 6px; }
  .thread-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 6px; }
</style>
@endpush

@section('content')
<main class="flex-1 max-w-3xl w-full mx-auto px-6 py-12">
  <a href="{{ route('profile.index') }}?section=support" class="inline-flex items-center gap-2 text-sm text-[#17611f] hover:text-[#14521a] font-bold mb-8 transition-colors">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    Back to My Profile
  </a>

  @php
    $statusColors = [
      'open' => 'bg-blue-50 text-blue-700 border-blue-100',
      'in_progress' => 'bg-amber-50 text-amber-700 border-amber-100',
      'resolved' => 'bg-green-50 text-green-700 border-green-100',
      'closed' => 'bg-gray-100 text-gray-600 border-gray-200',
    ];
    $statusLabel = match($ticket->status){
      'open' => 'Open',
      'in_progress' => 'In Progress',
      'resolved' => 'Resolved',
      'closed' => 'Closed',
      default => ucfirst($ticket->status)
    };
  @endphp

  <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="p-8 pb-5 border-b border-gray-100">
      <div class="flex items-start justify-between gap-3 mb-2">
        <div>
          <p class="text-[11px] font-bold tracking-widest text-[#9e9e9e] uppercase mb-1">Ticket #LH-{{ str_pad($ticket->id,4,'0',STR_PAD_LEFT) }}</p>
          <h1 class="font-black text-2xl text-[#1a2e1c] leading-tight">{{ $ticket->subject ?: 'Lettuce' }}</h1>
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-black border {{ $statusColors[$ticket->status] ?? 'bg-gray-50 text-gray-600 border-gray-100' }}">{{ $statusLabel }}</span>
      </div>

      <div class="flex flex-wrap gap-x-6 gap-y-1.5 text-[13px] text-[#5a7a5c] mt-3">
        <span>Category: <span class="font-bold text-[#1a2e1c]">{{ $ticket->category ?: 'Product Defect' }}</span></span>
        <span>Priority: <span class="font-bold {{ $ticket->priority==='High' ? 'text-red-600' : ($ticket->priority==='Medium' ? 'text-amber-600' : 'text-green-600') }}">{{ $ticket->priority ?: 'High' }}</span></span>
        @if(!empty($ticket->order_number))
          <span>Order #: <span class="font-bold font-mono text-[#1a2e1c]">{{ $ticket->order_number }}</span></span>
        @endif
        <span>Submitted: <span class="font-bold text-[#1a2e1c]">{{ $ticket->created_at->format('M j, Y g:i A') }}</span></span>
      </div>

      @php $paths = \App\Helpers\FormHelper::decodeAttachmentPaths($ticket->attachment_path); @endphp
      @if(!empty($paths))
        <div class="flex flex-col gap-1.5 mt-4">
          @foreach($paths as $i=>$attPath)
            <a href="{{ asset($attPath) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-[13px] font-bold text-[#17611f] hover:text-[#14521a] hover:underline">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
              View Your Attachment{{ count($paths)>1 ? ' '.($i+1) : '' }} – {{ basename($attPath) }}
            </a>
          @endforeach
        </div>
      @endif
    </div>

    @if(session('success'))<div class="mx-6 mt-5 rounded-xl bg-[#e8f5e9] border border-[#c8e6c9] px-4 py-3 text-sm text-[#17611f]">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mx-6 mt-5 rounded-xl bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>@endif

    @if($ticket->status === 'resolved')
      <div class="mx-6 mt-5 p-5 border border-green-200 bg-green-50/80 rounded-2xl space-y-3">
        <div class="flex items-start gap-3">
          <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
            <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          </div>
          <div>
            <p class="text-sm font-bold text-[#1a2e1c]">✅ Our support team believes your issue has been resolved.</p>
            <p class="text-sm text-[#5a7a5c] mt-0.5">Please let us know if everything has been resolved successfully.</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-3 pt-1 pl-11">
          <form method="GET" action="{{ route('tickets.close',['id'=>$ticket->id]) }}" onsubmit="return confirm('Close this ticket?\n\nOnce closed, no additional replies can be added.');">
            <button type="submit" class="px-5 py-2.5 rounded-full bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a] transition-colors flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
              Yes, Close Ticket
            </button>
          </form>
          <form method="GET" action="{{ route('tickets.reopen',['id'=>$ticket->id]) }}" onsubmit="return confirm('Reopen this ticket?');">
            <button type="submit" class="px-5 py-2.5 rounded-full border border-gray-300 text-[#1a2e1c] text-sm font-bold hover:bg-gray-50 transition-colors">No, I Still Need Help</button>
          </form>
        </div>
      </div>
    @endif

    <div class="p-6 space-y-4 max-h-[440px] overflow-y-auto bg-[#FBF9F4] thread-scroll" id="threadContainer">
      <div class="flex justify-end">
        <div class="max-w-[80%]">
          <div class="rounded-2xl px-4 py-2.5 text-[14px] leading-relaxed bg-[#17611f] text-white rounded-br-sm shadow-sm whitespace-pre-line">{{ $ticket->issue_description }}</div>
          <p class="text-[11px] text-[#9e9e9e] mt-1.5 text-right">You · {{ $ticket->created_at->format('M j, Y g:i A') }}</p>
        </div>
      </div>

      @foreach($ticket->replies as $r)
        @php $isCustomer = $r->sender_type==='customer'; $isSystem = str_starts_with($r->message, '✅') || str_starts_with($r->message, '❌'); @endphp
        <div class="flex {{ $isCustomer ? 'justify-end' : 'justify-start' }}">
          <div class="max-w-[80%]">
            <div class="rounded-2xl px-4 py-2.5 text-[14px] leading-relaxed whitespace-pre-line shadow-sm
              {{ $isCustomer ? ($isSystem ? 'bg-blue-50 border border-blue-200 text-blue-800 rounded-br-sm' : 'bg-[#17611f] text-white rounded-br-sm') : 'bg-white border border-gray-200 text-[#1a2e1c] rounded-bl-sm' }}">
              {{ $r->message }}
            </div>
            <p class="text-[11px] text-[#9e9e9e] mt-1.5 {{ $isCustomer ? 'text-right' : 'text-left' }}">
              {{ $isCustomer ? ($isSystem ? 'You (System)' : 'You') : 'Luntiang H.A.P.A.G. Support' }} · {{ \Carbon\Carbon::parse($r->created_at)->format('M j, Y g:i A') }}
            </p>
          </div>
        </div>
      @endforeach
    </div>

    @if($ticket->status === 'closed')
      <div class="p-5 border-t border-gray-100 text-center bg-gray-50/50">
        <div class="inline-flex items-center gap-2 text-sm text-[#5a7a5c] bg-white border rounded-xl px-4 py-3 shadow-sm">
          <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          This ticket has been closed and no longer accepts new replies.
        </div>
        <p class="text-xs text-[#9e9e9e] mt-2">If you experience another issue, please <a href="{{ route('tickets.create') }}" class="text-[#17611f] font-bold hover:underline">submit a new support ticket</a>.</p>
      </div>
    @else
      <form method="POST" action="{{ route('tickets.reply', $ticket->id) }}" class="p-5 border-t border-gray-100 bg-white">
        @csrf
        <div class="flex items-end gap-3">
          <div class="flex-1">
            <textarea name="message" id="replyMessage" rows="2" maxlength="2000" required placeholder="Type your reply..." class="w-full rounded-2xl border border-gray-200 px-5 py-3 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#17611f]/30 focus:border-[#17611f] transition-colors resize-none"></textarea>
            <div class="flex justify-between mt-1.5">
              <p class="text-[11px] text-[#9e9e9e]">Be clear and concise for faster resolution</p>
              <p class="text-[11px] text-[#9e9e9e]"><span id="replyCharCount">0</span> / 2000 characters</p>
            </div>
          </div>
          <button type="submit" class="px-6 py-3 rounded-full bg-[#17611f] text-white text-sm font-black hover:bg-[#14521a] active:scale-[0.98] transition-all shadow-sm flex-shrink-0">Send</button>
        </div>
      </form>
    @endif
  </div>

  {{-- Recent Requests – Woodcraft style --}}
  <div class="mt-6 bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <h3 class="font-black text-sm mb-4">Recent Requests</h3>
    @php
      $recent = \App\Models\Ticket::where('user_id', $ticket->user_id)->where('id','!=',$ticket->id)->orderByDesc('created_at')->take(3)->get();
    @endphp
    @if($recent->isEmpty())
      <p class="text-xs text-[#9e9e9e]">No other recent requests.</p>
    @else
      <div class="space-y-3">
        @foreach($recent as $rt)
          <div class="flex items-start gap-3 p-3 rounded-xl hover:bg-[#f4faf5] border border-transparent hover:border-[rgba(27,94,32,0.06)] transition-colors">
            <div class="w-8 h-8 rounded-lg bg-[#fff8e1] flex items-center justify-center flex-shrink-0"><span class="text-[10px]">📦</span></div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span class="text-[10px] font-bold text-[#9e9e9e] tracking-wide">TICKET</span>
                <span class="text-xs font-black truncate">Order {{ $rt->order_number ?: 'LH-'.str_pad($rt->id,4,'0',STR_PAD_LEFT) }}</span>
                <span class="ml-auto px-2 py-0.5 rounded-full text-[10px] font-bold {{ $rt->status==='open'?'bg-blue-50 text-blue-600':'bg-green-50 text-green-600' }}">{{ ucfirst(str_replace('_',' ',$rt->status)) }}</span>
              </div>
              <p class="text-xs font-bold text-[#1a2e1c] mt-1 truncate">{{ \Str::limit($rt->subject, 30) }}</p>
              <p class="text-[11px] text-[#5a7a5c] truncate">{{ \Str::limit($rt->issue_description, 50) }}</p>
              <p class="text-[10px] text-[#9e9e9e] mt-1">{{ $rt->created_at->format('M j, Y g:i A') }}</p>
            </div>
            <a href="{{ route('tickets.show',['id'=>$rt->id]) }}" class="text-[11px] font-bold text-[#17611f] hover:underline flex-shrink-0">View Conversation →</a>
          </div>
        @endforeach
      </div>
    @endif
  </div>
</main>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const thread = document.getElementById('threadContainer');
  if(thread) thread.scrollTop = thread.scrollHeight;

  const reply = document.getElementById('replyMessage');
  const count = document.getElementById('replyCharCount');
  if(reply && count){
    const update = ()=>{ count.textContent = reply.value.length; count.classList.toggle('text-red-500', reply.value.length>1800); };
    reply.addEventListener('input', update);
    update();
  }
});
</script>
@endpush
@endsection
