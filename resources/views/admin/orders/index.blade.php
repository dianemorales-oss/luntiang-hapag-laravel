@extends('admin.layouts.app')
@section('title', 'Order Management | Admin')
@section('header', 'Orders')
@section('content')

  @php
    $flowLabels = [
        'active' => 'Active',
        'preparing' => 'Preparing Order',
        'ready' => 'Ready',
        'delivered' => 'Delivered / Picked Up',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled'
    ];
  @endphp

  <h1 class="text-2xl font-black mb-1">Order Management</h1>
  <p class="text-sm text-[#5a7a5c] mb-6">5-Step Flow: Active (cancel allowed) → Preparing → Ready → Delivered → Completed</p>

  <!-- KPI Cards -->
  <div class="grid grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-xl border p-4">
      <p class="text-xs text-[#5a7a5c] font-bold">Today</p>
      <p class="text-2xl font-black">{{ $todayOrders }}</p>
    </div>
    <div class="bg-white rounded-xl border p-4">
      <p class="text-xs text-[#5a7a5c] font-bold">Revenue</p>
      <p class="text-2xl font-black text-[#17611f]">₱{{ number_format($todayRevenue, 2) }}</p>
    </div>
    <div class="bg-white rounded-xl border p-4">
      <p class="text-xs text-[#5a7a5c] font-bold">Active</p>
      <p class="text-2xl font-black text-blue-600">{{ $activeCount }}</p>
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
  <div class="flex flex-wrap gap-1.5 mb-5">
    @foreach (['all'=>'All','active'=>'Active','preparing'=>'Preparing','ready'=>'Ready','delivered'=>'Delivered','completed'=>'Completed','cancelled'=>'Cancelled'] as $k=>$l)
      <a href="?filter={{ $k }}" class="px-3.5 py-1.5 rounded-full text-xs font-bold {{ $filter === $k ? 'bg-[#17611f] text-white shadow-sm' : 'bg-white border text-[#5a7a5c] hover:bg-[#e8f5e9]' }} transition-colors">{{ $l }}</a>
    @endforeach
  </div>

  <!-- Orders Table with improved spacing and dropdown + Save -->
  <div class="bg-white rounded-2xl border border-[rgba(27,94,32,0.08)] overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-[#f4faf5] text-[#5a7a5c] text-xs uppercase border-b">
            <th class="p-4 text-left font-bold">Order #</th>
            <th class="p-4 text-left font-bold">Customer</th>
            <th class="p-4 text-left font-bold">Items</th>
            <th class="p-4 text-left font-bold">Total</th>
            <th class="p-4 text-left font-bold">Method</th>
            <th class="p-4 text-left font-bold">Current Status</th>
            <th class="p-4 text-left font-bold min-w-[280px]">Order Status – Change & Save</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[rgba(27,94,32,0.05)]">
          @foreach ($allOrders as $o)
            @php $isEnd = in_array($o->status, ['completed', 'cancelled']); @endphp
            <tr class="hover:bg-[#f8fcf9]/80 transition-colors">
              <td class="p-4">
                <span class="font-black text-sm text-[#1a2e1c]">{{ $o->order_number }}</span>
                <span class="block text-[11px] text-[#9e9e9e] font-medium mt-0.5">{{ $o->created_at->format('M j, Y g:i A') }}</span>
              </td>
              <td class="p-4">
                <p class="text-sm font-bold text-[#1a2e1c]">{{ $o->customer_name }}</p>
                <p class="text-[11px] text-[#5a7a5c]">{{ $o->customer_phone ?? '' }}</p>
              </td>
              <td class="p-4 text-xs text-[#5a7a5c] max-w-[200px]">
                <div class="flex flex-wrap gap-1">
                  @foreach ($o->items as $item)
                    <span class="inline-block bg-[#f4faf5] rounded-full px-2 py-0.5 text-[11px]">{{ $item->product_name }} x{{ $item->quantity }}</span>
                  @endforeach
                </div>
              </td>
              <td class="p-4 font-black text-[#17611f] text-sm">₱{{ number_format($o->total, 2) }}</td>
              <td class="p-4">
                <span class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#e8f5e9] text-[#17611f]">{{ $o->delivery_method === 'pickup' ? 'Pick-Up' : 'Delivery' }}</span>
              </td>
              <td class="p-4">
                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold {{ $o->status==='active' ? 'bg-blue-100 text-blue-700 border border-blue-200' : (in_array($o->status, ['completed', 'delivered']) ? 'bg-green-100 text-green-700 border border-green-200' : ($o->status === 'cancelled' ? 'bg-red-100 text-red-700 border border-red-200' : 'bg-amber-100 text-amber-700 border border-amber-200')) }}">
                  {{ $flowLabels[$o->status] ?? $o->status }}
                </span>
              </td>
              <td class="p-4">
                @if (!$isEnd)
                  <form method="POST" action="{{ route('admin.orders.update', $o->id) }}" class="flex items-end gap-2 bg-[#f4faf5] rounded-xl p-3 border border-[rgba(27,94,32,0.06)] shadow-sm">
                    @csrf
                    <div class="flex-1">
                      <label class="block text-[10px] font-black text-[#5a7a5c] uppercase tracking-wider mb-1.5">Order Status</label>
                      <select name="status" class="w-full rounded-lg border border-[rgba(27,94,32,0.15)] bg-white px-3 py-2.5 text-xs font-bold text-[#1a2e1c] focus:outline-none focus:ring-2 focus:ring-[#52b788]/30 focus:border-[#17611f] shadow-sm">
                        @foreach(['active'=>'Active (cancel allowed)','preparing'=>'Preparing Order','ready'=>'Ready','delivered'=>'Delivered / Picked Up','completed'=>'Completed','cancelled'=>'Cancelled'] as $val=>$label)
                          <option value="{{ $val }}" {{ $o->status===$val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                      </select>
                    </div>
                    <button type="submit" class="h-[40px] px-5 rounded-lg bg-[#17611f] text-white text-xs font-black hover:bg-[#14521a] active:scale-[0.98] transition-all shadow-sm flex items-center gap-1.5">
                      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                      Save
                    </button>
                  </form>
                @else
                  <div class="bg-gray-50 rounded-xl p-3 text-center border border-gray-100">
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold {{ $o->status==='completed' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200' }}">{{ $flowLabels[$o->status] ?? $o->status }}</span>
                    <p class="text-[10px] text-[#9e9e9e] mt-1.5 font-medium">Final status – no further changes</p>
                  </div>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

@endsection
