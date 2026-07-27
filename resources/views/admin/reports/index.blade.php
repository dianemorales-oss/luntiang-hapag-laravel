@extends('admin.layouts.app')
@section('title', 'Sales Analytics | Admin')
@section('header', 'Sales')
@section('content')

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-black mb-1">Sales Analytics</h1>
      <p class="text-sm text-[#5a7a5c]">
        {{ date('F j, Y', $ts) }} &nbsp;·&nbsp; Week: {{ date('M j', strtotime($weekStart)) }} – {{ date('M j, Y', strtotime($weekEnd)) }}
      </p>
    </div>
    <form method="GET" action="{{ route('admin.reports.index') }}" class="flex items-center gap-2">
      <input type="date" name="date" value="{{ $selectedDate }}" max="{{ date('Y-m-d') }}"
             class="border border-[rgba(27,94,32,0.12)] rounded-xl px-4 py-2.5 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white"
             onchange="this.form.submit()" />
    </form>
  </div>

  <!-- KPI Cards -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-[#5a7a5c] font-bold">Today's Sales</p>
      <p class="text-2xl font-black text-[#17611f]">₱{{ number_format($daySales, 2) }}</p>
      <p class="text-[10px] text-[#9e9e9e] mt-1">{{ $dayOrders }} order(s)</p>
    </div>
    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-[#5a7a5c] font-bold">This Week</p>
      <p class="text-2xl font-black text-[#17611f]">₱{{ number_format($weekSales, 2) }}</p>
      <p class="text-[10px] text-[#9e9e9e] mt-1">{{ date('M j', strtotime($weekStart)) }} – {{ date('M j', strtotime($weekEnd)) }}</p>
    </div>
    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-[#5a7a5c] font-bold">This Month</p>
      <p class="text-2xl font-black text-[#17611f]">₱{{ number_format($monthSales, 2) }}</p>
      <p class="text-[10px] text-[#9e9e9e] mt-1">{{ date('F Y', $ts) }}</p>
    </div>
    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-[#5a7a5c] font-bold">Total Orders</p>
      <p class="text-2xl font-black">{{ $totalOrders }}</p>
      <p class="text-[10px] text-[#9e9e9e] mt-1">All time</p>
    </div>
    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-[#5a7a5c] font-bold">Avg Order Value</p>
      <p class="text-2xl font-black">₱{{ number_format($avgOrder, 2) }}</p>
    </div>
    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-[#5a7a5c] font-bold">🚚 Delivery</p>
      <p class="text-2xl font-black">{{ $deliveryCount }}</p>
      <p class="text-[10px] text-[#9e9e9e] mt-1">{{ date('F', $ts) }}</p>
    </div>
    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-[#5a7a5c] font-bold">🛍️ Pick-Up</p>
      <p class="text-2xl font-black">{{ $pickupCount }}</p>
      <p class="text-[10px] text-[#9e9e9e] mt-1">{{ date('F', $ts) }}</p>
    </div>
    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-[#5a7a5c] font-bold">Customers</p>
      <p class="text-2xl font-black">{{ $totalCust }} <span class="text-sm text-green-600">+{{ $newCust }} new</span></p>
    </div>
  </div>

  <div class="grid md:grid-cols-2 gap-6">
    <!-- 7-Day Revenue Chart -->
    <div class="bg-white rounded-xl border p-5">
      <h2 class="font-black text-lg mb-4">📈 7-Day Revenue (ending {{ date('M j', strtotime($chartEnd)) }})</h2>
      <div class="space-y-2">
        @php
          $revs = $dailyData->pluck('rev')->toArray();
          $maxRev = !empty($revs) ? max($revs) : 1;
        @endphp
        @if ($dailyData->isEmpty())
          <p class="text-sm text-[#9e9e9e] py-4 text-center">No sales data for this period.</p>
        @else
          @foreach ($dailyData as $d)
            @php
              $pct = $maxRev > 0 ? ($d->rev / $maxRev) * 100 : 0;
            @endphp
            <div class="flex items-center gap-3">
              <span class="text-xs text-[#5a7a5c] w-14">{{ date('D M j', strtotime($d->d)) }}</span>
              <div class="flex-1 bg-[#e8f5e9] rounded-full h-6 overflow-hidden">
                <div class="bg-[#17611f] h-full rounded-full flex items-center pl-2 text-xs text-white font-bold animate-pulse" style="width:{{ max($pct, 10) }}%">₱{{ number_format($d->rev, 0) }}</div>
              </div>
              <span class="text-xs text-[#9e9e9e]">{{ $d->cnt }} orders</span>
            </div>
          @endforeach
        @endif
      </div>
    </div>

    <!-- Best Sellers -->
    <div class="bg-white rounded-xl border p-5">
      <h2 class="font-black text-lg mb-4">🏆 Best Selling Products</h2>
      @foreach ($bestSellers as $i => $bs)
        <div class="flex items-center justify-between py-2 border-b border-[rgba(27,94,32,0.05)]">
          <div class="flex items-center gap-3">
            <span class="w-7 h-7 rounded-full bg-[#e8f5e9] flex items-center justify-center font-black text-sm text-[#17611f]">{{ $i + 1 }}</span>
            <span class="text-sm font-bold">{{ $bs->product_name }}</span>
          </div>
          <div class="text-right">
            <p class="font-bold text-sm">{{ $bs->total_qty }} sold</p>
            <p class="text-xs text-[#5a7a5c]">₱{{ number_format($bs->revenue, 2) }}</p>
          </div>
        </div>
      @endforeach
    </div>

    <!-- Delivery vs Pick-Up -->
    <div class="bg-white rounded-xl border p-5">
      <h2 class="font-black text-lg mb-4">🚚 Delivery vs 🛍️ Pick-Up ({{ date('F', $ts) }})</h2>
      @php
        $delPct = ($deliveryCount + $pickupCount) > 0 ? round(($deliveryCount / ($deliveryCount + $pickupCount)) * 100) : 0;
      @endphp
      <div class="flex items-center gap-4 mb-2">
        <div class="flex-1 bg-gray-200 rounded-full h-8 overflow-hidden">
          <div class="bg-[#17611f] h-full rounded-full flex items-center justify-center text-xs text-white font-bold" style="width:{{ $delPct }}%">{{ $delPct > 15 ? '🚚 Delivery ' . $delPct . '%' : '' }}</div>
        </div>
        <span class="text-sm font-bold">{{ 100 - $delPct }}% 🛍️ Pick-Up</span>
      </div>
      <div class="flex justify-between text-sm text-[#5a7a5c] mt-4">
        <span>🚚 Delivery: <strong>{{ $deliveryCount }}</strong></span>
        <span>🛍️ Pick-Up: <strong>{{ $pickupCount }}</strong></span>
      </div>
    </div>

    <!-- Customer Stats -->
    <div class="bg-white rounded-xl border p-5">
      <h2 class="font-black text-lg mb-4">👥 Customer Overview</h2>
      <div class="space-y-3">
        <div class="flex justify-between"><span class="text-sm text-[#5a7a5c]">Total Customers</span><span class="font-bold">{{ $totalCust }}</span></div>
        <div class="flex justify-between"><span class="text-sm text-[#5a7a5c]">New on {{ date('M j', $ts) }}</span><span class="font-bold text-green-600">+{{ $newCust }}</span></div>
        <div class="flex justify-between"><span class="text-sm text-[#5a7a5c]">Total Orders (All Time)</span><span class="font-bold">{{ $totalOrders }}</span></div>
        <div class="flex justify-between"><span class="text-sm text-[#5a7a5c]">Avg. Order Value</span><span class="font-bold">₱{{ number_format($avgOrder, 2) }}</span></div>
      </div>
    </div>
  </div>

@endsection
