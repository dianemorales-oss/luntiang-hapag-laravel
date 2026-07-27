@extends('admin.layouts.app')
@section('title','Notifications')
@section('header','Notifications')
@section('content')
<div class="flex justify-between mb-4">
  <h2 class="font-black">All Notifications ({{ $notifications->count() }})</h2>
  <form method="POST" action="{{ route('admin.notifications.markAll') }}">@csrf<button class="px-3 py-1 rounded-lg border text-xs">Mark all as read</button></form>
</div>
<div class="bg-white rounded-xl border p-5 space-y-2">
  @foreach($notifications as $n)
    <a href="{{ route('admin.notifications.open',$n->id) }}" class="block border rounded-xl p-3 {{ $n->is_read ? 'bg-white' : 'bg-[#e8f5e9] border-[#c8e6c9]' }} hover:bg-[#f4faf5]">
      <p class="font-bold text-sm">{{ $n->title }} @if(!$n->is_read)<span class="text-xs bg-[#17611f] text-white px-1.5 py-0.5 rounded-full">New</span>@endif</p>
      <p class="text-xs text-[#5a7a5c]">{{ $n->type }} • {{ $n->customer_name }} • {{ $n->created_at->format('M j, Y g:i A') }}</p>
      <p class="text-sm mt-1">{{ $n->message }}</p>
    </a>
  @endforeach
</div>
@endsection
