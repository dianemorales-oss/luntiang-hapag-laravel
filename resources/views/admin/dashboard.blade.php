@extends('admin.layouts.app')
@section('title','Dashboard')
@section('header','Dashboard')
@section('content')
<div class="grid grid-cols-3 gap-4 mb-6">
  <div class="bg-white rounded-xl border p-5"><p class="text-xs text-[#5a7a5c]">Orders</p><p class="text-2xl font-black">{{ $stats['orders'] }}</p></div>
  <div class="bg-white rounded-xl border p-5"><p class="text-xs text-[#5a7a5c]">Customers</p><p class="text-2xl font-black">{{ $stats['customers'] }}</p></div>
  <div class="bg-white rounded-xl border p-5"><p class="text-xs text-[#5a7a5c]">Open Tickets</p><p class="text-2xl font-black">{{ $stats['tickets'] }}</p></div>
  <div class="bg-white rounded-xl border p-5"><p class="text-xs text-[#5a7a5c]">Products</p><p class="text-2xl font-black">{{ $stats['products'] }}</p></div>
  <div class="bg-white rounded-xl border p-5"><p class="text-xs text-[#5a7a5c]">Pending Freshness</p><p class="text-2xl font-black">{{ $stats['warranty_pending'] }}</p></div>
  <div class="bg-white rounded-xl border p-5"><p class="text-xs text-[#5a7a5c]">Pending Returns</p><p class="text-2xl font-black">{{ $stats['returns_pending'] }}</p></div>
</div>
<div class="grid grid-cols-2 gap-6">
  <div class="bg-white rounded-xl border p-5"><h2 class="font-black mb-3">Recent Orders</h2>@forelse($recentOrders as $o)<div class="flex justify-between text-sm py-2 border-b"><span>{{ $o->order_number }}</span><span class="font-bold">P{{ number_format($o->total,2) }}</span></div>@empty<p class="text-sm text-[#5a7a5c]">No orders</p>@endforelse</div>
  <div class="bg-white rounded-xl border p-5"><h2 class="font-black mb-3">Recent Tickets</h2>@forelse($recentTickets as $t)<div class="flex justify-between text-sm py-2 border-b"><span>{{ $t->subject }}</span><span class="text-xs px-2 py-0.5 rounded-full bg-[#e8f5e9]">{{ $t->status }}</span></div>@empty<p class="text-sm text-[#5a7a5c]">No tickets</p>@endforelse</div>
</div>
@endsection
