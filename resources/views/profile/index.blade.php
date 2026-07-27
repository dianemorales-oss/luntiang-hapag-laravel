@extends('layouts.app')
@section('title','My Profile')
@section('content')
<main class="max-w-7xl mx-auto px-6 py-8">
  <h1 class="text-2xl font-black mb-6">My Profile</h1>

  <div class="grid lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl border p-5 h-fit">
      <div class="w-16 h-16 rounded-full bg-[#e8f5e9] flex items-center justify-center font-black text-xl text-[#17611f] mb-3">{{ strtoupper(substr($user->first_name,0,1)) }}</div>
      <p class="font-black">{{ $user->first_name }} {{ $user->last_name }}</p>
      <p class="text-sm text-[#5a7a5c]">{{ $user->email }}</p>
      <p class="text-sm text-[#5a7a5c]">{{ $user->phone }}</p>
      <p class="text-sm text-[#5a7a5c] mt-1">{{ $user->address }}</p>
      <div class="mt-4 space-y-2">
        <a href="{{ route('profile.edit') }}" class="block text-center py-2 rounded-xl border font-bold text-sm hover:bg-[#e8f5e9]">Edit Profile</a>
        <a href="{{ route('profile.change-password') }}" class="block text-center py-2 rounded-xl border font-bold text-sm hover:bg-[#e8f5e9]">Change Password</a>
        <a href="{{ route('orders.tracking') }}" class="block text-center py-2 rounded-xl bg-[#17611f] text-white font-bold text-sm">Track Orders</a>
      </div>
    </div>

    <div class="lg:col-span-2 space-y-6">
      <!-- Orders -->
      <div class="bg-white rounded-xl border p-5">
        <h2 class="font-black mb-3">My Orders ({{ $orders->count() }})</h2>
        @forelse($orders as $o)
          <div class="border rounded-xl p-3 mb-2 flex justify-between items-center">
            <div><p class="font-bold text-sm">{{ $o->order_number }} — {{ ucfirst($o->status) }}</p><p class="text-xs text-[#5a7a5c]">{{ $o->created_at->format('M j, Y') }} • P{{ number_format($o->total,2) }}</p></div>
            <a href="{{ route('orders.tracking', ['order'=>$o->order_number]) }}" class="text-xs font-bold text-[#17611f]">View</a>
          </div>
        @empty
          <p class="text-sm text-[#5a7a5c]">No orders yet.</p>
        @endforelse
      </div>

      <!-- Tickets -->
      <div class="bg-white rounded-xl border p-5">
        <div class="flex justify-between items-center mb-3"><h2 class="font-black">Support Tickets ({{ $tickets->count() }})</h2><a href="{{ route('tickets.create') }}" class="text-xs font-bold text-[#17611f]">+ New Ticket</a></div>
        @forelse($tickets as $t)
          <div class="border rounded-xl p-3 mb-2 flex justify-between">
            <div><p class="font-bold text-sm">{{ $t->subject }} — <span class="text-xs px-2 py-0.5 rounded-full bg-[#e8f5e9] text-[#17611f]">{{ $t->status }}</span></p><p class="text-xs text-[#5a7a5c]">{{ $t->created_at->format('M j, Y') }}</p></div>
            <a href="{{ route('tickets.show', $t->id) }}" class="text-xs font-bold text-[#17611f]">View</a>
          </div>
        @empty
          <p class="text-sm text-[#5a7a5c]">No tickets.</p>
        @endforelse
      </div>

      <!-- Returns -->
      <div class="bg-white rounded-xl border p-5">
        <div class="flex justify-between mb-3"><h2 class="font-black">Returns & Refunds ({{ $returns->count() }})</h2><a href="{{ route('returns.index') }}" class="text-xs font-bold text-[#17611f]">+ New Return</a></div>
        @forelse($returns as $r)
          <div class="border rounded-xl p-3 mb-2"><p class="font-bold text-sm">{{ $r->order_number }} — {{ $r->reason_category }}</p><p class="text-xs text-[#5a7a5c]">{{ $r->status }} • {{ $r->created_at->format('M j, Y') }}</p></div>
        @empty
          <p class="text-sm text-[#5a7a5c]">No return requests.</p>
        @endforelse
      </div>
    </div>
  </div>
</main>
@endsection
