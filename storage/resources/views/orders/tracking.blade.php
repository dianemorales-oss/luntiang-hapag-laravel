@extends('layouts.app')
@section('title','Order Tracking')
@section('content')
<main class="max-w-5xl mx-auto px-6 py-8">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <div>
      <h1 class="text-2xl font-black">Order Tracking</h1>
      <p class="text-sm text-[#5a7a5c] mt-1">Track your orders – click Track to expand details below</p>
    </div>
    <div class="relative w-full sm:w-80">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#9e9e9e]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
      <input id="allOrdersSearch" type="text" placeholder="Search Order ID, Product, Date, Status..." class="w-full pl-10 pr-4 py-2 rounded-full border bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" />
    </div>
  </div>

  @if($single)
  <div class="bg-white rounded-xl border p-6 mb-6 shadow-sm">
    <h2 class="font-black text-lg">{{ $single->order_number }} — {{ ucfirst($single->status) }}</h2>
    <p class="text-sm text-[#5a7a5c]">{{ $single->created_at->format('M j, Y g:i A') }} • ₱{{ number_format($single->total,2) }}</p>
    <div class="mt-4 flex gap-2 flex-wrap">
      @foreach(['preparing','ready','delivered','completed'] as $step)
        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $single->status===$step ? 'bg-[#17611f] text-white' : (array_search($single->status, ['preparing','ready','delivered','completed']) >= array_search($step, ['preparing','ready','delivered','completed']) ? 'bg-[#e8f5e9] text-[#17611f]' : 'bg-gray-100 text-[#9e9e9e]') }}">{{ ucfirst($step) }}</span>
      @endforeach
    </div>
    <div class="mt-4">
      @foreach($single->items as $item)
        <div class="flex justify-between text-sm py-2 border-b"><span>{{ $item->product_name }} × {{ $item->quantity }}</span><span class="font-bold">₱{{ number_format($item->price*$item->quantity,2) }}</span></div>
      @endforeach
    </div>
  </div>
  @endif

  <div class="bg-white rounded-xl border p-5 shadow-sm">
    <h2 class="font-black mb-4 flex items-center justify-between">All Orders <span class="text-xs font-normal text-[#5a7a5c] bg-[#f4faf5] px-2 py-1 rounded-full">{{ $orders->count() }} orders</span></h2>
    <div id="trackingOrdersList" class="space-y-3">
      @forelse($orders as $o)
        <div class="order-track-card border rounded-xl p-4 transition-all hover:shadow-md bg-white" data-order-id="{{ $o->order_number }}" data-status="{{ $o->status }}" data-date="{{ $o->created_at->format('M j, Y') }}" data-products="{{ $o->items->pluck('product_name')->implode(' ') }}">
          <div class="flex justify-between items-center">
            <div>
              <p class="font-bold text-sm flex items-center gap-2">{{ $o->order_number }} <span class="text-[11px] px-2 py-0.5 rounded-full {{ $o->status==='completed'?'bg-green-100 text-green-700': ($o->status==='cancelled'?'bg-red-100 text-red-700':'bg-amber-100 text-amber-700') }}">{{ ucfirst($o->status) }}</span></p>
              <p class="text-xs text-[#5a7a5c] mt-0.5">{{ $o->created_at->format('M j, Y') }} • ₱{{ number_format($o->total,2) }} • {{ count($o->items) }} items</p>
            </div>
            <button class="track-expand-btn text-xs font-bold text-[#17611f] border border-[#c8e6c9] bg-[#e8f5e9] px-3 py-1.5 rounded-lg hover:bg-[#c8e6c9] transition-colors" data-target="detail-{{ $o->order_number }}">📍 Track</button>
          </div>

          <div id="detail-{{ $o->order_number }}" class="hidden mt-4 pt-4 border-t border-[rgba(27,94,32,0.08)]">
            <h4 class="font-black text-sm mb-3">Tracking Details</h4>
            <div class="flex gap-1.5 mb-4 flex-wrap">
              @foreach(['preparing'=>'Preparing','ready'=>'Ready','delivered'=>'Delivered','completed'=>'Completed'] as $k=>$label)
                @php $orderSteps=['preparing','ready','delivered','completed']; $curr=array_search($o->status,$orderSteps); $step=array_search($k,$orderSteps); $done=$curr!==false && $step!==false && $step<=$curr; $isCurrent=$o->status===$k; @endphp
                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $isCurrent ? 'bg-[#17611f] text-white' : ($done ? 'bg-[#e8f5e9] text-[#17611f]' : 'bg-gray-100 text-[#9e9e9e]') }}">{{ $label }} {{ $done?'✓':'' }}</span>
              @endforeach
            </div>
            <div class="grid grid-cols-2 gap-3 text-xs mb-3">
              <div><p class="text-[#5a7a5c]">Order Date</p><p class="font-bold">{{ $o->created_at->format('M j, Y g:i A') }}</p></div>
              <div><p class="text-[#5a7a5c]">Delivery</p><p class="font-bold">{{ ucfirst($o->delivery_method) }} • {{ strtoupper($o->payment_method) }}</p></div>
              <div><p class="text-[#5a7a5c]">Total</p><p class="font-black text-[#17611f]">₱{{ number_format($o->total,2) }}</p></div>
              <div><p class="text-[#5a7a5c]">Status</p><p class="font-bold">{{ ucfirst($o->status) }}</p></div>
            </div>
            @if($o->delivery_address)
              <div class="text-xs bg-[#f4faf5] p-3 rounded-xl mb-3"><p class="text-[#5a7a5c]">Delivery Address:</p><p class="font-bold">{{ $o->delivery_address }}, {{ $o->delivery_city }}, {{ $o->delivery_province }} {{ $o->delivery_zip }}</p></div>
            @endif
            <div>
              <p class="text-xs font-bold mb-2">Items:</p>
              @foreach($o->items as $it)
                <div class="flex justify-between text-xs py-2 border-b border-[rgba(27,94,32,0.05)] last:border-0"><span>{{ $it->product_name }} × {{ $it->quantity }}</span><span class="font-bold">₱{{ number_format($it->price*$it->quantity,2) }}</span></div>
              @endforeach
            </div>
          </div>
        </div>
      @empty
        <p class="text-sm text-[#5a7a5c] text-center py-8">No orders yet. <a href="{{ route('products.index') }}" class="text-[#17611f] font-bold underline">Shop now</a></p>
      @endforelse
    </div>
    <p id="noTrackingFound" class="hidden text-center text-sm text-[#5a7a5c] py-8">No orders match your search.</p>
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
      // Close others
      document.querySelectorAll('[id^="detail-"]').forEach(d=>{ if(d!==detail) d.classList.add('hidden'); });
      document.querySelectorAll('.track-expand-btn').forEach(b=>{ if(b!==this) b.textContent='📍 Track'; });
      if(isHidden){
        detail.classList.remove('hidden');
        this.textContent='▲ Hide';
        detail.scrollIntoView({ behavior:'smooth', block:'nearest' });
      } else {
        detail.classList.add('hidden');
        this.textContent='📍 Track';
      }
    });
  });

  // Search Orders - by ID, Product Name, Date, Status
  const searchInput = document.getElementById('allOrdersSearch');
  const list = document.getElementById('trackingOrdersList');
  const noFound = document.getElementById('noTrackingFound');
  if(searchInput && list){
    searchInput.addEventListener('input', function(){
      const term = this.value.toLowerCase().trim();
      const cards = list.querySelectorAll('.order-track-card');
      let visible=0;
      cards.forEach(card=>{
        const id = (card.dataset.orderId||'').toLowerCase();
        const status = (card.dataset.status||'').toLowerCase();
        const date = (card.dataset.date||'').toLowerCase();
        const products = (card.dataset.products||'').toLowerCase();
        const text = card.textContent.toLowerCase();
        const match = term==='' || id.includes(term) || status.includes(term) || date.includes(term) || products.includes(term) || text.includes(term);
        card.style.display = match ? '' : 'none';
        if(match) visible++;
      });
      if(noFound) noFound.classList.toggle('hidden', visible!==0 || term==='');
    });
  }
});
</script>
@endpush
@endsection
