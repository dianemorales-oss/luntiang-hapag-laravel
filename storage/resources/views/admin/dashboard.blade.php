@extends('admin.layouts.app')
@section('title', 'Dashboard | Admin')
@section('header', 'Dashboard')
@section('content')

  <h1 class="text-xl font-black mb-1">Dashboard</h1>
  <p class="text-sm text-[#5a7a5c] mb-6">Luntiang H.A.P.A.G. - Hydroponic Harvest-on-Demand Operations</p>

  <!-- KPI counters -->
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-4">
      <p class="text-xs text-[#5a7a5c] font-bold">Today Revenue</p>
      <p class="text-xl font-black text-[#17611f]">₱{{ number_format($todayRevenue, 0) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-4">
      <p class="text-xs text-[#5a7a5c] font-bold">Orders Today</p>
      <p class="text-xl font-black">{{ $todayOrders }}</p>
    </div>
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-4">
      <p class="text-xs text-[#5a7a5c] font-bold">Customers</p>
      <p class="text-xl font-black">{{ $totalCust }} <span class="text-xs text-green-600">+{{ $newCustToday }} new</span></p>
    </div>
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-4">
      <p class="text-xs text-[#5a7a5c] font-bold">Open Tickets</p>
      <p class="text-xl font-black text-blue-600">{{ $openTickets }}</p>
    </div>
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-4">
      <p class="text-xs text-[#5a7a5c] font-bold">Pending Returns</p>
      <p class="text-xl font-black text-amber-600">{{ $pendingReturns }}</p>
    </div>
  </div>

  <div class="grid lg:grid-cols-2 gap-6 mb-6">
    <!-- Operations -->
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-5">
      <h2 class="font-black text-sm mb-3">Operations</h2>
      <div class="space-y-2">
        <div class="flex justify-between text-sm">
          <span>Preparing</span>
          <span class="font-bold text-amber-600">{{ $preparingCount }}</span>
        </div>
        <div class="flex justify-between text-sm">
          <span>Ready</span>
          <span class="font-bold text-[#17611f]">{{ $readyCount }}</span>
        </div>
        <div class="flex justify-between text-sm">
          <span>Delivered</span>
          <span class="font-bold text-green-600">{{ $deliveredCount }}</span>
        </div>
        <div class="flex justify-between text-sm">
          <span>Completed</span>
          <span class="font-bold text-blue-600">{{ $completedCount }}</span>
        </div>
        <div class="flex justify-between text-sm">
          <span>Cancelled</span>
          <span class="font-bold text-red-600">{{ $cancelledCount }}</span>
        </div>
      </div>
    </div>

    <!-- Delivery & Support -->
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-5">
      <h2 class="font-black text-sm mb-3">Delivery & Support</h2>
      @php
        $dpTot = max(1, $deliveryCount + $pickupCount);
        $dpPct = round(($deliveryCount / $dpTot) * 100);
      @endphp
      <p class="text-xs text-[#5a7a5c] mb-2">Delivery vs Pick-Up</p>
      <div class="flex items-center gap-3 mb-4">
        <div class="flex-1 bg-gray-200 rounded-full h-6 overflow-hidden">
          <div class="bg-[#17611f] h-full rounded-full flex items-center justify-center text-[10px] text-white font-bold" style="width:{{ $dpPct }}%">
            {{ $dpPct > 15 ? 'Delivery ' . $dpPct . '%' : '' }}
          </div>
        </div>
        <span class="text-xs font-bold">{{ 100 - $dpPct }}% Pick-Up</span>
      </div>
      <div class="grid grid-cols-2 gap-2 text-xs mb-3">
        <div class="text-[#5a7a5c]">Delivery: <b>{{ $deliveryCount }}</b></div>
        <div class="text-[#5a7a5c]">Pick-Up: <b>{{ $pickupCount }}</b></div>
        <div class="text-[#5a7a5c]">Free Delivery: <b>{{ $freeDeliveryCount }}</b></div>
      </div>
      <p class="text-xs text-[#5a7a5c] mb-2">Support Overview</p>
      <div class="grid grid-cols-3 gap-2 text-xs">
        <div class="bg-[#f4faf5] rounded-lg p-2 text-center">
          <p class="font-black">{{ $openTickets }}</p>
          <p class="text-[10px] text-[#5a7a5c]">Tickets</p>
        </div>
        <div class="bg-[#f4faf5] rounded-lg p-2 text-center">
          <p class="font-black">{{ $pendingReturns }}</p>
          <p class="text-[10px] text-[#5a7a5c]">Returns</p>
        </div>
        <div class="bg-[#f4faf5] rounded-lg p-2 text-center">
          <p class="font-black">{{ $todayOrders }}</p>
          <p class="text-[10px] text-[#5a7a5c]">Today</p>
        </div>
      </div>
    </div>
  </div>

@endsection
