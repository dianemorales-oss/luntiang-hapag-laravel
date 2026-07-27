@extends('layouts.app')
@section('title','Ticket #'.$ticket->id)
@section('content')
<main class="max-w-3xl mx-auto px-6 py-10">
  <a href="{{ route('profile.index') }}" class="text-sm text-[#17611f]">← Back</a>
  <div class="bg-white rounded-xl border p-6 mt-4">
    <h1 class="text-xl font-black">{{ $ticket->subject }}</h1>
    <p class="text-xs text-[#5a7a5c] mt-1">{{ $ticket->category }} • {{ $ticket->priority }} • {{ $ticket->status }} • {{ $ticket->created_at->format('M j, Y') }}</p>
    <p class="mt-4 text-sm">{{ $ticket->issue_description }}</p>

    @if($ticket->attachment_path)
      @php $paths = \App\Helpers\FormHelper::decodeAttachmentPaths($ticket->attachment_path); @endphp
      <div class="mt-3 flex gap-2 flex-wrap">
        @foreach($paths as $path)<a href="{{ asset($path) }}" target="_blank" class="text-xs text-[#17611f] underline">📎 {{ basename($path) }}</a>@endforeach
      </div>
    @endif

    <div class="mt-6 border-t pt-4 space-y-3">
      <h3 class="font-bold">Conversation</h3>
      @foreach($ticket->replies as $reply)
        <div class="rounded-xl p-3 {{ $reply->sender_type==='admin' ? 'bg-[#e8f5e9] border border-[#c8e6c9]' : 'bg-gray-50 border' }}">
          <p class="text-xs font-bold {{ $reply->sender_type==='admin' ? 'text-[#17611f]' : 'text-[#5a7a5c]' }}">{{ ucfirst($reply->sender_type) }} • {{ \Carbon\Carbon::parse($reply->created_at)->format('M j, g:i A') }}</p>
          <p class="text-sm mt-1">{{ $reply->message }}</p>
        </div>
      @endforeach
    </div>

    @if($ticket->status !== 'closed')
    <form method="POST" action="{{ route('tickets.reply', $ticket->id) }}" class="mt-6">
      @csrf
      <textarea name="message" required rows="3" class="w-full border rounded-xl p-3 text-sm" placeholder="Type your reply..."></textarea>
      <div class="flex gap-2 mt-3">
        <button type="submit" class="px-5 py-2 rounded-xl bg-[#17611f] text-white text-sm font-bold">Reply</button>
        <a href="{{ route('tickets.close', $ticket->id) }}" onclick="return confirm('Close ticket?')" class="px-5 py-2 rounded-xl border text-sm font-bold">Close Ticket</a>
      </div>
    </form>
    @else
      <div class="mt-6"><a href="{{ route('tickets.reopen', $ticket->id) }}" class="px-5 py-2 rounded-xl bg-[#17611f] text-white text-sm font-bold">Reopen Ticket</a></div>
    @endif
  </div>
</main>
@endsection
