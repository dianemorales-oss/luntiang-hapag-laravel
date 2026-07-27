@extends('admin.layouts.app')
@section('title','Ticket Detail')
@section('header','Ticket #'.$ticket->id)
@section('content')
<div class="bg-white rounded-xl border p-6">
  <h2 class="font-black text-lg">{{ $ticket->subject }}</h2>
  <p class="text-xs text-[#5a7a5c]">{{ $ticket->user->first_name }} {{ $ticket->user->last_name }} • {{ $ticket->user->email }} • {{ $ticket->category }} • {{ $ticket->priority }} • {{ $ticket->status }}</p>
  <p class="mt-4 text-sm">{{ $ticket->issue_description }}</p>
  @if($ticket->attachment_path)
    @php $paths = \App\Helpers\FormHelper::decodeAttachmentPaths($ticket->attachment_path); @endphp
    <div class="mt-2">@foreach($paths as $p)<a href="{{ asset($p) }}" target="_blank" class="text-xs text-[#17611f] underline">📎 {{ basename($p) }}</a> @endforeach</div>
  @endif

  <div class="mt-6 border-t pt-4">
    <h3 class="font-bold mb-2">Replies</h3>
    @foreach($ticket->replies as $r)
      <div class="rounded-xl p-3 mb-2 {{ $r->sender_type==='admin'?'bg-[#e8f5e9] border border-[#c8e6c9]':'bg-gray-50 border' }}">
        <p class="text-xs font-bold">{{ ucfirst($r->sender_type) }} • {{ $r->created_at }}</p><p class="text-sm mt-1">{{ $r->message }}</p>
      </div>
    @endforeach
  </div>

  <form method="POST" action="{{ route('admin.tickets.reply',$ticket->id) }}" class="mt-6">
    @csrf
    <textarea name="message" required rows="3" class="w-full border rounded-xl p-3 text-sm" placeholder="Reply..."></textarea>
    <div class="flex gap-2 mt-3">
      <select name="status" class="border rounded-xl px-3 py-2 text-sm">
        <option value="in_progress" {{ $ticket->status==='in_progress'?'selected':'' }}>In Progress</option>
        <option value="resolved" {{ $ticket->status==='resolved'?'selected':'' }}>Resolved</option>
        <option value="closed" {{ $ticket->status==='closed'?'selected':'' }}>Closed</option>
        <option value="open" {{ $ticket->status==='open'?'selected':'' }}>Open</option>
      </select>
      <button type="submit" class="px-5 py-2 rounded-xl bg-[#17611f] text-white text-sm font-bold">Reply</button>
    </div>
  </form>
</div>
@endsection
