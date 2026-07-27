@extends('admin.layouts.app')
@section('title', 'Order Management | Admin')
@section('header', 'Orders')
@section('content')

  @php
    $flowLabels = [
        'preparing' => '🌱 Preparing Order',
        'ready' => 'Ready',
        'delivered' => 'Delivered/Picked Up',
        'completed' => '🎉 Completed',
        'cancelled' => '❌ Cancelled'
    ];

    // 4-step flow
    $flowNext = ['preparing' => 'ready', 'ready' => 'delivered', 'delivered' => 'completed'];
    $flowPrev = ['ready' => 'preparing', 'delivered' => 'ready', 'completed' => 'delivered'];
  @endphp

  <h1 class="text-2xl font-black mb-1">Order Management</h1>
  <p class="text-sm text-[#5a7a5c] mb-6">4-Step Order Flow: Preparing → Ready → Delivered → Completed</p>

  <!-- KPI Cards -->
  <div class="grid grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border p-4">
      <p class="text-xs text-[#5a7a5c] font-bold">Today</p>
      <p class="text-2xl font-black">{{ $todayOrders }}</p>
    </div>
    <div class="bg-white rounded-xl border p-4">
      <p class="text-xs text-[#5a7a5c] font-bold">Revenue</p>
      <p class="text-2xl font-black text-[#17611f]">₱{{ number_format($todayRevenue, 2) }}</p>
    </div>
    <div class="bg-white rounded-xl border p-4">
      <p class="text-xs text-[#5a7a5c] font-bold">Preparing</p>
      <p class="text-2xl font-black text-amber-600">{{ $preparingCount }}</p>
    </div>
    <div class="bg-white rounded-xl border p-4">
      <p class="text-xs text-[#5a7a5c] font-bold">Ready</p>
      <p class="text-2xl font-black text-[#17611f]">{{ $readyCount }}</p>
    </div>
  </div>

  <!-- Filter Pills -->
  <div class="flex flex-wrap gap-1.5 mb-4">
    @foreach (['all'=>'All','preparing'=>'Preparing','ready'=>'Ready','delivered'=>'Delivered','completed'=>'Completed','cancelled'=>'Cancelled'] as $k=>$l)
      <a href="?filter={{ $k }}" class="px-3 py-1 rounded-full text-xs font-bold {{ $filter === $k ? 'bg-[#17611f] text-white' : 'bg-white border text-[#5a7a5c]' }}">{{ $l }}</a>
    @endforeach
  </div>

  <!-- Orders Table -->
  <div class="bg-white rounded-xl border overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-[#f4faf5] text-[#5a7a5c] text-xs uppercase border-b">
            <th class="p-3 text-left">Order #</th>
            <th class="p-3 text-left">Customer</th>
            <th class="p-3 text-left">Items</th>
            <th class="p-3 text-left">Total</th>
            <th class="p-3 text-left">Method</th>
            <th class="p-3 text-left">Status</th>
            <th class="p-3 text-left">Process</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($allOrders as $o)
            @php
              $prev = $flowPrev[$o->status] ?? null;
              $next = $flowNext[$o->status] ?? null;
              $isEnd = in_array($o->status, ['completed', 'cancelled']);
            @endphp
            <tr class="border-t border-[rgba(27,94,32,0.05)] hover:bg-gray-50/50 transition-colors">
              <td class="p-3 font-bold text-sm">
                {{ $o->order_number }}<br>
                <span class="text-xs text-[#9e9e9e] font-normal">{{ $o->created_at->format('M j, g:i A') }}</span>
              </td>
              <td class="p-3 text-sm font-semibold text-[#1a2e1c]">{{ $o->customer_name }}</td>
              <td class="p-3 text-xs text-[#5a7a5c]">
                @foreach ($o->items as $idx => $item)
                  {{ $item->product_name }} x{{ $item->quantity }}{{ $idx < count($o->items)-1 ? ', ' : '' }}
                @endforeach
              </td>
              <td class="p-3 font-bold text-[#17611f]">₱{{ number_format($o->total, 2) }}</td>
              <td class="p-3 text-xs font-semibold text-[#5a7a5c]">{{ $o->delivery_method === 'pickup' ? 'Pick-Up' : 'Delivery' }}</td>
              <td class="p-3">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ in_array($o->status, ['completed', 'delivered']) ? 'bg-green-100 text-green-700' : ($o->status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                  {{ $flowLabels[$o->status] ?? $o->status }}
                </span>
              </td>
              <td class="p-3">
                @if (!$isEnd)
                  <div class="flex items-center gap-1">
                    @if ($prev)
                      <form method="POST" action="{{ route('admin.orders.update', $o->id) }}" class="inline">
                        @csrf
                        <input type="hidden" name="status" value="{{ $prev }}">
                        <button type="submit" class="px-2.5 py-1 rounded-l-lg border border-[rgba(27,94,32,0.12)] text-xs font-bold text-[#5a7a5c] hover:bg-[#e8f5e9] transition-colors">← Prev</button>
                      </form>
                    @endif
                    <span class="px-2 py-1 text-[10px] font-bold text-[#5a7a5c] bg-gray-50 border rounded">{{ $flowLabels[$o->status] ?? $o->status }}</span>
                    @if ($next)
                      <form method="POST" action="{{ route('admin.orders.update', $o->id) }}" class="inline">
                        @csrf
                        <input type="hidden" name="status" value="{{ $next }}">
                        <button type="submit" class="px-2.5 py-1 rounded-r-lg border border-[rgba(27,94,32,0.12)] text-xs font-bold text-[#17611f] hover:bg-[#e8f5e9] transition-colors">Next →</button>
                      </form>
                    @endif
                  </div>
                @else
                  <span class="text-xs text-[#9e9e9e] font-semibold">—</span>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

@endsection
