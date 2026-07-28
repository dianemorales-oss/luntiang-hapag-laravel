@extends('layouts.app')
@section('title','Cart | Luntiang H.A.P.A.G.')
@section('content')
@php
  // Defensive defaults so every Cart link works even with an empty/stale session.
  $cartItems = $cartItems ?? [];
  $claimedCoupons = collect($claimedCoupons ?? []);
  $promo = $promo instanceof \App\Models\Promotion ? $promo : null;
  $selectedCount = $selectedCount ?? count($cartItems);
  $selectedSubtotal = $selectedSubtotal ?? 0;
  $deliveryFee = $deliveryFee ?? 0;
  $discount = $discount ?? 0;
  $total = $total ?? 0;
  $isFreeDeliveryZone = $isFreeDeliveryZone ?? false;
@endphp
<main class="max-w-7xl mx-auto px-6 py-8">
  <h1 class="text-2xl font-black mb-6">Shopping Cart</h1>
  @if(empty($cartItems))
    <div class="bg-white rounded-xl border p-10 text-center">
      <p class="text-4xl mb-3">🛒</p><p class="font-bold">Your cart is empty</p><a href="{{ route('products.index') }}" class="inline-block mt-4 px-5 py-2 rounded-xl bg-[#17611f] text-white text-sm font-bold">Shop Now</a>
    </div>
  @else
  <form method="GET" action="{{ route('checkout.index') }}" class="grid lg:grid-cols-3 gap-6" id="cartCheckoutForm">
    <div class="lg:col-span-2 space-y-4">
      <div class="bg-white rounded-xl border p-4 flex items-center gap-2">
        <input type="checkbox" id="selectAll" onchange="toggleAll(this)" class="w-4 h-4 accent-[#17611f]">
        <label for="selectAll" class="font-bold text-sm">Select All ({{ count($cartItems) }} items)</label>
      </div>
      @foreach($cartItems as $ci)
      <div class="bg-white rounded-xl border p-4 flex gap-4 items-start" id="cart-item-{{ $ci['id'] }}">
        <input type="checkbox" name="sel[]" value="{{ $ci['id'] }}" {{ !empty($ci['selected']) ? 'checked' : '' }} onchange="recalc()" class="mt-1 w-4 h-4 accent-[#17611f] item-cb">
        <img src="{{ asset($ci['image'] ?: 'images/lettuce/hero-farm.png') }}" onerror="this.onerror=null;this.src='{{ asset('images/lettuce/hero-farm.png') }}';" class="w-20 h-20 rounded-lg object-cover">
        <div class="flex-1">
          <a href="{{ route('products.show', $ci['slug']) }}" class="font-bold text-sm hover:text-[#17611f]">{{ $ci['name'] }}</a>
          <p class="text-xs text-[#5a7a5c]">Harvest time: {{ $ci['harvest_time'] ?? '1-3 hours' }}</p>
          <p class="font-black text-[#17611f] text-sm mt-1">P{{ number_format($ci['price'],2) }} each</p>
          <div class="flex items-center gap-3 mt-2">
            <span class="inline-flex items-center border rounded-lg overflow-hidden">
              <button type="button" class="px-3 py-1.5 font-black text-sm hover:bg-[#e8f5e9]" onclick="updateQty({{ $ci['id'] }},-1)">−</button>
              <span class="px-3 py-1.5 text-sm font-bold" id="qty-{{ $ci['id'] }}">{{ $ci['qty'] }}</span>
              <button type="button" class="px-3 py-1.5 font-black text-sm hover:bg-[#e8f5e9]" onclick="updateQty({{ $ci['id'] }},1)">+</button>
            </span>
            <button type="button" onclick="removeItem({{ $ci['id'] }})" class="px-3 py-1 rounded-lg border border-red-200 text-xs font-bold text-red-500 hover:bg-red-50">Remove</button>
          </div>
        </div>
        <p class="font-black text-[#17611f]" id="line-{{ $ci['id'] }}">P{{ number_format($ci['line_total'],2) }}</p>
      </div>
      @endforeach
      <a href="{{ route('cart.clear') }}" onclick="return confirm('Clear all?')" class="inline-flex px-4 py-2 rounded-xl border border-red-200 text-sm font-bold text-red-500 hover:bg-red-50">Clear Cart</a>
    </div>

    <div class="bg-white rounded-xl border p-5 h-fit sticky top-24">
      <h2 class="font-black text-lg mb-4">Order Summary</h2>
      <div class="space-y-2 text-sm mb-4">
        <div class="flex justify-between"><span class="text-[#5a7a5c]">Selected Items</span><span class="font-bold" id="selCount">{{ $selectedCount }}</span></div>
        <div class="flex justify-between"><span class="text-[#5a7a5c]">Subtotal</span><span class="font-bold" id="subtotalDisplay">P{{ number_format($selectedSubtotal,2) }}</span></div>
        <div class="flex justify-between"><span class="text-[#5a7a5c]">Delivery Fee</span><span class="font-bold {{ $deliveryFee==0?'text-green-600':'' }}" id="delFeeDisplay">{{ $deliveryFee==0?'FREE':'P'.number_format($deliveryFee,2) }}</span></div>
        <div class="flex justify-between" id="discRow" {{ $promo?'':'style=display:none' }}>
          <span>Discount <span class="text-[11px] font-bold text-[#17611f] bg-[#e8f5e9] px-2 py-0.5 rounded-full ml-1" id="appliedPromoBadge">{{ $promo->code ?? '' }}</span></span>
          <span class="font-bold text-red-500" id="discDisplay">@if($promo)-P{{ number_format($discount,2) }}@endif</span>
        </div>
      </div>
      <div class="flex justify-between font-black text-lg border-t pt-3 mb-4"><span>Total</span><span class="text-[#17611f]" id="totalDisplay">P{{ number_format($total,2) }}</span></div>
      <details class="mb-4"><summary class="text-sm font-bold text-[#17611f] cursor-pointer">Apply Coupon</summary>
        @if($claimedCoupons->isNotEmpty())
          <div class="space-y-1 mt-2">
          @foreach($claimedCoupons as $cc)
            @php $label = $cc->discount_type==='percentage' ? $cc->discount_value.'% Off' : 'P'.$cc->discount_value.' Off'; @endphp
            <button type="button" onclick="applyCoupon('{{ $cc->code }}')" class="w-full flex justify-between px-3 py-2.5 rounded-lg text-xs font-bold border hover:bg-[#e8f5e9]">{{ $cc->code }} — {{ $label }}</button>
          @endforeach
          </div>
        @endif
      </details>
      @if(session()->has('user_id'))
        <button type="submit" class="w-full py-3 rounded-xl bg-[#17611f] text-white font-bold hover:bg-[#14521a]">Proceed to Checkout</button>
      @else
        <a href="{{ route('login', ['redirect' => 'cart']) }}" class="block text-center w-full py-3 rounded-xl bg-[#17611f] text-white font-bold">Login to Checkout</a>
      @endif
      <a href="{{ route('products.index') }}" class="block text-center w-full py-2.5 mt-2 rounded-xl border text-sm font-bold hover:bg-[#e8f5e9]">Continue Shopping</a>
    </div>
  </form>
  @endif
</main>
@php
  $promoPayload = $promo instanceof \App\Models\Promotion
      ? [
          'code' => $promo->code,
          'discount_type' => $promo->discount_type,
          'discount_value' => (float) $promo->discount_value,
          'is_free_delivery' => (bool) $promo->is_free_delivery,
        ]
      : null;

  $cartPayload = collect($cartItems ?? [])->map(function ($c) {
      return [
          'id' => (int) ($c['id'] ?? 0),
          'price' => (float) ($c['price'] ?? 0),
          'qty' => (int) ($c['qty'] ?? 0),
      ];
  })->values();
@endphp
<script>
const items = @json($cartPayload);
let currentPromo = @json($promoPayload);
function recalc(){
  let st=0,cnt=0;
  const cbs = document.querySelectorAll('.item-cb');
  cbs.forEach(cb=>{if(cb.checked){let id=parseInt(cb.value);let it=items.find(i=>i.id===id);if(it){st+=it.price*it.qty;cnt++;}}});
  const selectAll = document.getElementById('selectAll');
  if(selectAll){selectAll.checked = cbs.length > 0 && cnt === cbs.length;}
  let df={{ $isFreeDeliveryZone?1:0 }}?0:(cnt===0?0:50);
  if(currentPromo && currentPromo.is_free_delivery) df=0;
  let d=0;
  if(currentPromo){d=currentPromo.discount_type==='percentage'?st*(currentPromo.discount_value/100):currentPromo.discount_value;}
  if(cnt===0) df=0;
  document.getElementById('selCount').textContent=cnt;
  document.getElementById('subtotalDisplay').textContent='P'+st.toFixed(2);
  document.getElementById('delFeeDisplay').textContent=df===0?'FREE':'P'+df.toFixed(2);
  document.getElementById('totalDisplay').textContent='P'+Math.max(0,st+df-d).toFixed(2);
  let dr=document.getElementById('discRow'), dd=document.getElementById('discDisplay'), badge=document.getElementById('appliedPromoBadge');
  if(currentPromo){
    dr.style.display='';
    dd.textContent='-P'+d.toFixed(2);
    if(badge) badge.textContent = currentPromo.code;
  } else {
    dr.style.display='none';
  }
}
function toggleAll(el){document.querySelectorAll('.item-cb').forEach(cb=>cb.checked=el.checked);recalc();}
async function updateQty(id,delta){
  let it=items.find(i=>i.id===id);if(!it)return;let q=it.qty+delta;if(q<1)return;
  let r=await fetch('{{ route('cart.ajax') }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({action:'update',id:id,qty:q})});
  let d=await r.json();if(d.success){it.qty=d.qty;document.getElementById('qty-'+id).textContent=d.qty;document.getElementById('line-'+id).textContent='P'+d.line_total;recalc();}
}
async function removeItem(id){
  if(!confirm('Remove?'))return;
  let r=await fetch('{{ route('cart.ajax') }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({action:'remove',id:id})});
  let d=await r.json();if(d.success){document.getElementById('cart-item-'+id).remove();let idx=items.findIndex(i=>i.id===id);if(idx>-1)items.splice(idx,1);recalc();if(items.length===0)location.reload();}
}
async function applyCoupon(code){
  let r=await fetch('{{ route('cart.ajax') }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({action:'select_promo',promo_code:code})});
  let d=await r.json();if(d.success){currentPromo=d.promo;recalc();}else{alert(d.message);}
}
document.addEventListener('DOMContentLoaded', recalc);
document.getElementById('cartCheckoutForm')?.addEventListener('submit', function(e){
  if(document.querySelectorAll('.item-cb:checked').length === 0){
    e.preventDefault();
    alert('Please select at least one item before checkout.');
  }
});
</script>
@endsection
