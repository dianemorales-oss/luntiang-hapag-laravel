@extends('layouts.app')
@section('title','Order Tracking')
@section('content')
<main class="max-w-5xl mx-auto px-6 py-8">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
      <h1 class="text-3xl font-black text-[#1a2e1c] flex items-center gap-2">📦 Order Tracking</h1>
      <p class="text-sm text-[#5a7a5c] mt-1">Real-time status updates from harvest to doorstep delivery</p>
    </div>
    <div class="relative w-full sm:w-80">
      <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[#9e9e9e]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
      <input id="allOrdersSearch" type="text" placeholder="Search Order ID, product, status..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-[#c8e6c9] bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#17611f]/30 shadow-sm" />
    </div>
  </div>

  @if($single)
  <div class="bg-gradient-to-br from-[#e8f5e9]/70 to-white rounded-3xl border border-[#c8e6c9] p-7 mb-8 shadow-md">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-4 border-b border-[#c8e6c9]/60">
      <div>
        <span class="inline-block text-xs font-bold uppercase tracking-wider text-[#17611f] bg-white px-3 py-1 rounded-full shadow-sm mb-2">Selected Order</span>
        <h2 class="text-2xl font-black text-[#1a2e1c]">{{ $single->order_number }}</h2>
        <p class="text-sm text-[#5a7a5c] mt-0.5">{{ $single->created_at->format('M j, Y g:i A') }} • <span class="font-black text-[#17611f]">₱{{ number_format($single->total,2) }}</span></p>
      </div>
      @if($single->status === 'active')
        <form method="POST" action="{{ route('orders.cancel', $single->id) }}" onsubmit="return confirm('Cancel order #{{ $single->order_number }}? This cannot be undone.');">
          @csrf
          <button type="submit" class="px-5 py-2.5 rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm font-bold hover:bg-red-100 transition-colors shadow-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            Cancel Order
          </button>
        </form>
      @endif
    </div>

    <!-- Stepper Timeline -->
    <div class="py-6">
      @php
        $steps = ['active' => 'Confirmed', 'preparing' => 'Preparing', 'harvesting' => 'Harvesting', 'packing' => 'Packing', 'ready' => 'Ready', 'out_for_delivery' => 'Out for Delivery', 'completed' => 'Completed'];
        $orderStepsList = array_keys($steps);
        $currIndex = array_search($single->status, $orderStepsList);
        if ($single->status === 'delivered') $currIndex = count($orderStepsList)-1;
        if ($single->status === 'cancelled') $currIndex = -1;
      @endphp
      @if($single->status === 'cancelled')
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 text-center text-red-700 font-bold text-sm">
          ⚠️ This order has been cancelled.
        </div>
      @else
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2">
          @foreach($steps as $key => $label)
            @php
              $idx = array_search($key, $orderStepsList);
              $isDone = $currIndex !== false && $idx <= $currIndex;
              $isCurrent = $single->status === $key;
            @endphp
            <div class="flex flex-col items-center text-center p-2 rounded-xl {{ $isCurrent ? 'bg-[#17611f] text-white shadow-md scale-105' : ($isDone ? 'bg-[#e8f5e9] text-[#17611f]' : 'bg-white/80 text-[#9e9e9e] border border-gray-100') }} transition-all">
              <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-xs mb-1 {{ $isCurrent ? 'bg-white text-[#17611f]' : ($isDone ? 'bg-[#17611f] text-white' : 'bg-gray-200 text-gray-500') }}">
                @if($isDone && !$isCurrent) ✓ @else {{ $loop->iteration }} @endif
              </div>
              <p class="text-[11px] font-bold leading-tight">{{ $label }}</p>
            </div>
          @endforeach
        </div>
      @endif
    </div>

    <div class="bg-white rounded-2xl p-5 border border-[#c8e6c9]/60 shadow-sm mt-4">
      <h3 class="font-black text-sm text-[#1a2e1c] mb-3">Order Items</h3>
      <div class="space-y-2">
        @foreach($single->items as $item)
          <div class="flex justify-between items-center text-sm py-2 border-b border-gray-100 last:border-0">
            <span class="font-medium text-[#1a2e1c]">{{ $item->product_name }} <span class="text-xs text-[#5a7a5c]">× {{ $item->quantity }}</span></span>
            <span class="font-black text-[#17611f]">₱{{ number_format($item->price*$item->quantity,2) }}</span>
          </div>
        @endforeach
      </div>
    </div>
  </div>
  @endif

  <!-- All Orders Section -->
  <div class="bg-white rounded-3xl border border-[rgba(27,94,32,0.08)] p-6 shadow-sm">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5 pb-4 border-b border-gray-100">
      <h2 class="text-xl font-black text-[#1a2e1c] flex items-center gap-2">
        All Orders <span class="text-xs font-bold text-[#17611f] bg-[#e8f5e9] px-2.5 py-1 rounded-full">{{ $orders->count() }} total</span>
      </h2>
      <!-- Filter Tabs -->
      <div class="flex flex-wrap gap-1.5 text-xs font-bold">
        <button class="order-filter-btn px-3 py-1.5 rounded-lg bg-[#17611f] text-white transition-all shadow-sm" data-filter="all">All</button>
        <button class="order-filter-btn px-3 py-1.5 rounded-lg bg-gray-100 text-[#5a7a5c] hover:bg-gray-200 transition-all" data-filter="active">Active</button>
        <button class="order-filter-btn px-3 py-1.5 rounded-lg bg-gray-100 text-[#5a7a5c] hover:bg-gray-200 transition-all" data-filter="preparing">Preparing</button>
        <button class="order-filter-btn px-3 py-1.5 rounded-lg bg-gray-100 text-[#5a7a5c] hover:bg-gray-200 transition-all" data-filter="completed">Completed</button>
        <button class="order-filter-btn px-3 py-1.5 rounded-lg bg-gray-100 text-[#5a7a5c] hover:bg-gray-200 transition-all" data-filter="cancelled">Cancelled</button>
      </div>
    </div>

    <div id="trackingOrdersList" class="space-y-4">
      @forelse($orders as $o)
        <div class="order-track-card border border-gray-200/80 rounded-2xl p-5 transition-all hover:shadow-lg bg-white" data-order-id="{{ $o->order_number }}" data-status="{{ $o->status }}" data-date="{{ $o->created_at->format('M j, Y') }}" data-products="{{ $o->items->pluck('product_name')->implode(' ') }}">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-2xl bg-[#e8f5e9] flex items-center justify-center text-[#17611f] font-black text-lg flex-shrink-0">
                🥬
              </div>
              <div>
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-black text-base text-[#1a2e1c]">{{ $o->order_number }}</span>
                  <span class="text-xs px-2.5 py-0.5 rounded-full font-bold {{ $o->status==='completed'?'bg-green-100 text-green-700':($o->status==='cancelled'?'bg-red-100 text-red-700':($o->status==='active'?'bg-blue-100 text-blue-700':'bg-amber-100 text-amber-700')) }}">
                    {{ ucfirst(str_replace('_',' ', $o->status)) }}
                  </span>
                </div>
                <p class="text-xs text-[#5a7a5c] mt-1">{{ $o->created_at->format('M j, Y g:i A') }} • <span class="font-black text-[#17611f]">₱{{ number_format($o->total,2) }}</span> • {{ count($o->items) }} item(s)</p>
              </div>
            </div>
            <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
              <button class="track-expand-btn text-xs font-bold text-[#17611f] border border-[#c8e6c9] bg-[#e8f5e9] px-4 py-2 rounded-xl hover:bg-[#c8e6c9] transition-all shadow-sm flex items-center gap-1.5" data-target="detail-{{ $o->order_number }}">
                <span>📍 Track Order</span>
              </button>
              @if($o->status === 'active')
                <form method="POST" action="{{ route('orders.cancel', $o->id) }}" onsubmit="return confirm('Cancel order #{{ $o->order_number }}? This cannot be undone.');">
                  @csrf
                  <button type="submit" class="px-4 py-2 rounded-xl border border-red-200 bg-red-50 text-red-700 text-xs font-bold hover:bg-red-100 transition-colors">Cancel</button>
                </form>
              @endif
            </div>
          </div>

          <!-- Expandable Detail Section -->
          <div id="detail-{{ $o->order_number }}" class="hidden mt-5 pt-5 border-t border-gray-100 space-y-4">
            <h4 class="font-black text-xs uppercase tracking-wider text-[#5a7a5c]">Live Progress Journey</h4>
            <!-- Mini Stepper inside expand -->
            @php
              $stepsMini = ['active' => 'Confirmed', 'preparing' => 'Preparing', 'harvesting' => 'Harvesting', 'packing' => 'Packing', 'ready' => 'Ready', 'out_for_delivery' => 'Delivery', 'completed' => 'Completed'];
              $orderStepsListMini = array_keys($stepsMini);
              $currIdxMini = array_search($o->status, $orderStepsListMini);
              if ($o->status === 'delivered') $currIdxMini = count($orderStepsListMini)-1;
            @endphp
            @if($o->status === 'cancelled')
              <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-center text-red-700 font-bold text-xs">Order cancelled</div>
            @else
              <div class="grid grid-cols-3 sm:grid-cols-7 gap-1.5">
                @foreach($stepsMini as $mk => $ml)
                  @php
                    $midx = array_search($mk, $orderStepsListMini);
                    $doneM = $currIdxMini !== false && $midx <= $currIdxMini;
                    $isCurM = $o->status === $mk;
                  @endphp
                  <div class="p-2 rounded-lg text-center {{ $isCurM ? 'bg-[#17611f] text-white font-bold' : ($doneM ? 'bg-[#e8f5e9] text-[#17611f]' : 'bg-gray-50 text-gray-400') }}">
                    <p class="text-[10px]">{{ $ml }}</p>
                    <p class="text-[11px] font-black">{{ $doneM ? '✓' : '•' }}</p>
                  </div>
                @endforeach
              </div>
            @endif

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-gray-50 p-4 rounded-2xl text-xs">
              <div><p class="text-[#5a7a5c]">Order Date</p><p class="font-bold text-[#1a2e1c]">{{ $o->created_at->format('M j, Y g:i A') }}</p></div>
              <div><p class="text-[#5a7a5c]">Delivery Method</p><p class="font-bold text-[#1a2e1c]">{{ ucfirst($o->delivery_method) }} ({{ strtoupper($o->payment_method) }})</p></div>
              <div><p class="text-[#5a7a5c]">Total Amount</p><p class="font-black text-[#17611f]">₱{{ number_format($o->total,2) }}</p></div>
              <div><p class="text-[#5a7a5c]">Current Status</p><p class="font-bold text-[#1a2e1c]">{{ ucfirst(str_replace('_',' ',$o->status)) }}</p></div>
            </div>

            @if($o->delivery_address)
              <div class="text-xs bg-[#e8f5e9]/50 border border-[#c8e6c9] p-3.5 rounded-xl">
                <p class="font-bold text-[#17611f] mb-0.5">📍 Delivery Address:</p>
                <p class="text-[#1a2e1c]">{{ $o->delivery_address }}, {{ $o->delivery_city }}, {{ $o->delivery_province }} {{ $o->delivery_zip }}</p>
              </div>
            @endif

            <div>
              <p class="text-xs font-black text-[#1a2e1c] mb-2 uppercase tracking-wider">Ordered Items</p>
              <div class="bg-white border rounded-xl divide-y divide-gray-100">
                @foreach($o->items as $it)
                  <div class="flex justify-between items-center text-xs p-3">
                    <span class="font-medium text-[#1a2e1c]">{{ $it->product_name }} <span class="text-[#5a7a5c]">× {{ $it->quantity }}</span></span>
                    <span class="font-bold text-[#17611f]">₱{{ number_format($it->price*$it->quantity,2) }}</span>
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      @empty
        <div class="text-center py-12">
          <div class="w-16 h-16 bg-[#e8f5e9] text-[#17611f] rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">🌱</div>
          <p class="font-black text-lg text-[#1a2e1c]">No orders found</p>
          <p class="text-sm text-[#5a7a5c] mt-1 mb-4">You haven't placed any orders yet. Experience our farm-fresh hydroponic lettuce!</p>
          <a href="{{ route('products.index') }}" class="inline-flex px-6 py-3 rounded-xl bg-[#17611f] text-white font-bold text-sm hover:bg-[#14521a] transition-all shadow-sm">Shop Fresh Produce →</a>
        </div>
      @endforelse
    </div>
    <p id="noTrackingFound" class="hidden text-center text-sm text-[#5a7a5c] py-12">No orders match your search or filter.</p>
  </div>
</main>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  // Expand row for tracking
  document.querySelectorAll('.track-expand-btn').forEach(btn=>{
    btn.addEventListener('click', function(){
      const targetId = this.dataset.target;
      const detail = document.getElementById(targetId);
      if(!detail) return;
      const isHidden = detail.classList.contains('hidden');
      // Close others for neatness
      document.querySelectorAll('[id^="detail-"]').forEach(d=>{ if(d!==detail) d.classList.add('hidden'); });
      document.querySelectorAll('.track-expand-btn').forEach(b=>{ if(b!==this) b.innerHTML='<span>📍 Track Order</span>'; });
      if(isHidden){
        detail.classList.remove('hidden');
        this.innerHTML='<span>▲ Hide Details</span>';
        detail.scrollIntoView({ behavior:'smooth', block:'nearest' });
      } else {
        detail.classList.add('hidden');
        this.innerHTML='<span>📍 Track Order</span>';
      }
    });
  });

  // Filter Buttons
  const filterBtns = document.querySelectorAll('.order-filter-btn');
  let currentFilter = 'all';

  filterBtns.forEach(btn=>{
    btn.addEventListener('click', function(){
      filterBtns.forEach(b=>{ b.classList.remove('bg-[#17611f]', 'text-white', 'shadow-sm'); b.classList.add('bg-gray-100', 'text-[#5a7a5c]'); });
      this.classList.remove('bg-gray-100', 'text-[#5a7a5c]');
      this.classList.add('bg-[#17611f]', 'text-white', 'shadow-sm');
      currentFilter = this.dataset.filter;
      applySearchAndFilter();
    });
  });

  // Search Orders
  const searchInput = document.getElementById('allOrdersSearch');
  if(searchInput){
    searchInput.addEventListener('input', function(){
      applySearchAndFilter();
    });
  }

  function applySearchAndFilter(){
    const term = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const cards = document.querySelectorAll('.order-track-card');
    let visible=0;
    cards.forEach(card=>{
      const status = (card.dataset.status||'').toLowerCase();
      const id = (card.dataset.orderId||'').toLowerCase();
      const date = (card.dataset.date||'').toLowerCase();
      const products = (card.dataset.products||'').toLowerCase();
      const text = card.textContent.toLowerCase();

      const matchFilter = currentFilter==='all' || status===currentFilter;
      const matchSearch = term==='' || id.includes(term) || status.includes(term) || date.includes(term) || products.includes(term) || text.includes(term);

      if(matchFilter && matchSearch){
        card.style.display = '';
        visible++;
      } else {
        card.style.display = 'none';
      }
    });
    const noFound = document.getElementById('noTrackingFound');
    if(noFound) noFound.classList.toggle('hidden', visible!==0);
  }
});
</script>
@endpush
@endsection
