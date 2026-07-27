@extends('admin.layouts.app')
@section('title','Live Chat')
@section('header','Live Chat Conversations')
@section('content')
<div class="bg-white rounded-xl border p-5">
  <h2 class="font-black mb-3">Conversations ({{ $conversations->count() }})</h2>
  @foreach($conversations as $c)
    <a href="{{ route('admin.live-chat.show',$c['chat_key']) }}" class="block border rounded-xl p-3 mb-2 hover:bg-[#f4faf5]">
      <p class="font-bold text-sm">{{ $c['customer_name'] }} • {{ $c['chat_key'] }}</p>
      <p class="text-xs text-[#5a7a5c]">Last: {{ $c['last_message'] }} • {{ $c['last_at'] }} • {{ $c['count'] }} msgs</p>
    </a>
  @endforeach
</div>
@endsection
