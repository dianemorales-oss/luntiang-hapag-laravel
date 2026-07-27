@extends('admin.layouts.app')
@section('title','Tickets')
@section('header','Support Tickets')
@section('content')
<div class="bg-white rounded-xl border p-5">
  <h2 class="font-black mb-3">Tickets ({{ $tickets->count() }})</h2>
  <div class="space-y-2">
    @foreach($tickets as $t)
      <a href="{{ route('admin.tickets.show',$t->id) }}" class="block border rounded-xl p-3 hover:bg-[#f4faf5]">
        <div class="flex justify-between"><p class="font-bold text-sm">{{ $t->subject }} — {{ $t->user->first_name ?? '' }}</p><span class="text-xs px-2 py-0.5 rounded-full bg-[#e8f5e9]">{{ $t->status }}</span></div>
        <p class="text-xs text-[#5a7a5c]">{{ $t->category }} • {{ $t->created_at->format('M j, Y') }}</p>
      </a>
    @endforeach
  </div>
</div>
@endsection
