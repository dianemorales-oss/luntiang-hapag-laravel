@extends('admin.layouts.app')
@section('title','Chat '.$chatKey)
@section('header','Chat '.$chatKey)
@section('content')
<div class="bg-white rounded-xl border flex flex-col h-[70vh]">
  <div class="flex-1 overflow-y-auto p-4 space-y-3" id="chatMessages">
    @foreach($messages as $m)
      <div class="flex {{ $m->sender==='admin' ? 'justify-end' : 'justify-start' }}">
        <div class="max-w-[70%] rounded-xl px-3 py-2 text-sm {{ $m->sender==='admin' ? 'bg-[#17611f] text-white' : ($m->sender==='bot' ? 'bg-[#e8f5e9] border' : 'bg-gray-100 border') }}">
          <p class="text-xs font-bold opacity-70">{{ $m->customer_name }} • {{ $m->created_at }}</p>
          <p>{{ $m->message }}</p>
          @if($m->image_path)<img src="{{ asset($m->image_path) }}" class="mt-2 max-h-32 rounded">@endif
        </div>
      </div>
    @endforeach
  </div>
  <form method="POST" action="{{ route('admin.live-chat.send',$chatKey) }}" class="p-4 border-t flex gap-2">
    @csrf
    <input type="text" name="message" required placeholder="Reply..." class="flex-1 border rounded-xl px-4 py-2 text-sm">
    <button type="submit" class="px-5 py-2 rounded-xl bg-[#17611f] text-white text-sm font-bold">Send</button>
    <a href="{{ route('admin.live-chat.delete',$chatKey) }}" onclick="return confirm('Delete?')" class="px-3 py-2 rounded-xl border text-sm text-red-500">Delete</a>
  </form>
</div>
@endsection
