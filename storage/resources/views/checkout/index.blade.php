@extends('layouts.app')
@section('title','Checkout | Luntiang H.A.P.A.G.')
@section('content')
<main class="max-w-5xl mx-auto px-6 py-8">
  <div class="flex items-center justify-between gap-3 mb-6">
    <h1 class="text-2xl font-black">Checkout</h1>
    <a href="{{ route('cart.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border text-sm font-bold hover:bg-[#e8f5e9]">← Back to Cart</a>
  </div>

  <form method="POST" action="{{ route('checkout.store') }}" class="grid lg:grid-cols-3 gap-6" id="checkoutForm">
    @csrf
    <div class="lg:col-span-2 space-y-5">
      <div class="bg-white rounded-xl border p-5">
        <h2 class="font-black text-lg mb-3">Delivery Method</h2>
        <div class="grid grid-cols-2 gap-3">
          <label class="delivery-option flex items-center gap-3 p-4 rounded-xl border-2 border-[#17611f] bg-[#e8f5e9] cursor-pointer"><input type="radio" name="delivery_method" value="delivery" checked onchange="toggleAddr()" class="accent-[#17611f]"><div><p class="font-bold text-sm">Delivery</p><p class="text-xs text-[#5a7a5c]">Same-day delivery</p></div></label>
          <label class="delivery-option flex items-center gap-3 p-4 rounded-xl border-2 border-[rgba(27,94,32,0.12)] cursor-pointer hover:bg-[#e8f5e9]"><input type="radio" name="delivery_method" value="pickup" onchange="toggleAddr()" class="accent-[#17611f]"><div><p class="font-bold text-sm">Pick-Up</p><p class="text-xs text-[#5a7a5c]">Free, ready in 1-3 hours</p></div></label>
        </div>
      </div>

      <div class="bg-white rounded-xl border p-5" id="addressSection">
        <h2 class="font-black text-lg mb-3">Delivery Address</h2>
        @if($savedAddresses->isNotEmpty())
        <div class="mb-3 space-y-2">
          @foreach($savedAddresses as $sa)
          <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer hover:bg-[#e8f5e9] text-sm">
            <input type="radio" name="saved_address_id" value="{{ $sa->id }}" class="accent-[#17611f]" onchange="fillAddr(this)" data-address="{{ $sa->address }}" data-city="{{ $sa->city }}" data-province="{{ $sa->province }}" data-zip="{{ $sa->zip }}">
            <div><span class="font-bold">{{ $sa->label }}:</span> {{ $sa->address }}, {{ $sa->city }}</div>
          </label>
          @endforeach
        </div>
        @endif
        <div class="space-y-3">
          <textarea name="address" rows="2" class="w-full border rounded-xl px-3 py-2 text-sm" placeholder="Street address" required>{{ old('address', $defaultAddress) }}</textarea>
          <div class="grid grid-cols-2 gap-3"><input name="city" class="w-full border rounded-xl px-3 py-2 text-sm" placeholder="City" required value="{{ old('city', $defaultCity) }}"><input name="province" class="w-full border rounded-xl px-3 py-2 text-sm" placeholder="Province" required value="{{ old('province', $defaultProvince) }}"></div>
          <input name="zip" class="w-full border rounded-xl px-3 py-2 text-sm" placeholder="ZIP Code" value="{{ old('zip', $defaultZip) }}">
        </div>
      </div>

      <div class="bg-white rounded-xl border p-5">
        <h2 class="font-black text-lg mb-3">Additional Information</h2>
        <div class="space-y-3">
          <div><label class="text-xs font-bold text-[#5a7a5c]">Preferred Delivery Time</label><select name="preferred_time" class="w-full border rounded-xl px-3 py-2 text-sm mt-1"><option value="">As soon as possible</option><option>Morning (8 AM - 12 PM)</option><option>Afternoon (12 PM - 4 PM)</option><option>Late Afternoon (4 PM - 6 PM)</option></select></div>
          <div><label class="text-xs font-bold text-[#5a7a5c]">Delivery Notes</label><textarea name="delivery_notes" rows="2" class="w-full border rounded-xl px-3 py-2 text-sm mt-1" placeholder="Special instructions...">{{ old('delivery_notes') }}</textarea></div>
          <div><label class="text-xs font-bold text-[#5a7a5c]">Gift Note</label><textarea name="gift_note" rows="2" class="w-full border rounded-xl px-3 py-2 text-sm mt-1" placeholder="Gift message...">{{ old('gift_note') }}</textarea></div>
        </div>
      </div>

      <div class="bg-white rounded-xl border p-5">
        <h2 class="font-black text-lg mb-3">Payment Method</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
          <label class="payment-option flex items-center gap-3 p-3 rounded-xl border-2 border-[#17611f] bg-[#e8f5e9] cursor-pointer text-sm"><input type="radio" name="payment_method" value="cod" checked onchange="togglePaymentReference()" class="accent-[#17611f]"><span class="w-9 h-9 rounded-lg bg-green-100 flex items-center justify-center font-black text-green-700">₱</span><span class="font-bold">Cash on Delivery</span></label>
          <label class="payment-option flex items-center gap-3 p-3 rounded-xl border cursor-pointer hover:bg-[#e8f5e9] text-sm"><input type="radio" name="payment_method" value="gcash" onchange="togglePaymentReference()" class="accent-[#17611f]"><span class="w-9 h-9 rounded-lg bg-blue-600 text-white flex items-center justify-center font-black">G</span><span class="font-bold">GCash</span></label>
          <label class="payment-option flex items-center gap-3 p-3 rounded-xl border cursor-pointer hover:bg-[#e8f5e9] text-sm"><input type="radio" name="payment_method" value="maya" onchange="togglePaymentReference()" class="accent-[#17611f]"><span class="w-9 h-9 rounded-lg bg-emerald-500 text-white flex items-center justify-center font-black">M</span><span class="font-bold">Maya</span></label>
          <label class="payment-option flex items-center gap-3 p-3 rounded-xl border cursor-pointer hover:bg-[#e8f5e9] text-sm"><input type="radio" name="payment_method" value="bank_transfer" onchange="togglePaymentReference()" class="accent-[#17611f]"><span class="w-9 h-9 rounded-lg bg-slate-700 text-white flex items-center justify-center font-black">🏦</span><span class="font-bold">Bank Transfer</span></label>
        </div>
        <div id="paymentReferenceWrap" class="hidden mt-4">
          <label id="paymentReferenceLabel" class="text-xs font-bold text-[#5a7a5c]">Account / Mobile Number</label>
          <input type="text" name="payment_reference" id="paymentReference" value="{{ old('payment_reference') }}" class="w-full border rounded-xl px-3 py-2 text-sm mt-1" inputmode="numeric" placeholder="Enter account/mobile number">
          <p class="text-[11px] text-[#9e9e9e] mt-1">Required for GCash, Maya, and Bank Transfer payments.</p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl border p-5 h-fit sticky top-24">
      <h2 class="font-black text-lg mb-4">Order Summary</h2>
      @foreach($cartItems as $ci)
        <div class="flex justify-between text-sm mb-2"><span>{{ $ci['name'] }} <span class="text-xs text-[#5a7a5c]">× {{ $ci['qty'] }}</span></span><span class="font-bold">P{{ number_format($ci['line_total'],2) }}</span></div>
      @endforeach
      <hr class="my-3">
      <div class="space-y-1 text-sm mb-3">
        <div class="flex justify-between"><span class="text-[#5a7a5c]">Subtotal</span><span class="font-bold">P{{ number_format($subtotal,2) }}</span></div>
        <div class="flex justify-between"><span class="text-[#5a7a5c]">Delivery Fee</span><span class="font-bold {{ $deliveryFee==0?'text-green-600':'' }}" id="delFeeDisp">{{ $deliveryFee==0?'FREE':'P'.number_format($deliveryFee,2) }}</span></div>
        @if($discount>0)<div class="flex justify-between"><span class="text-[#5a7a5c]">Discount</span><span class="font-bold text-red-500">-P{{ number_format($discount,2) }}</span></div>@endif
      </div>
      <div class="flex justify-between font-black text-lg border-t pt-3 mb-4"><span>Total</span><span class="text-[#17611f]" id="totalDisp">P{{ number_format($total,2) }}</span></div>
      <div class="p-3 rounded-xl bg-[#e8f5e9] mb-4 text-center text-xs"><p class="font-black">Harvest-on-Demand</p><p class="text-[#5a7a5c]">Estimated harvest: 1-3 hours</p></div>
      <button type="submit" id="placeOrderBtn" class="w-full py-3 rounded-xl bg-[#17611f] text-white font-bold hover:bg-[#14521a]">Place Order</button>
    </div>
  </form>
</main>
<script>
function setActiveOptions(){
  document.querySelectorAll('.payment-option').forEach(l=>{const r=l.querySelector('input');l.classList.toggle('border-[#17611f]',r.checked);l.classList.toggle('bg-[#e8f5e9]',r.checked);});
  document.querySelectorAll('.delivery-option').forEach(l=>{const r=l.querySelector('input');l.classList.toggle('border-[#17611f]',r.checked);l.classList.toggle('bg-[#e8f5e9]',r.checked);});
}
function togglePaymentReference(){
  const method=document.querySelector('input[name=payment_method]:checked').value;
  const wrap=document.getElementById('paymentReferenceWrap');
  const input=document.getElementById('paymentReference');
  const label=document.getElementById('paymentReferenceLabel');
  const needs=method!=='cod';
  wrap.classList.toggle('hidden',!needs);
  input.required=needs;
  input.placeholder=method==='bank_transfer'?'Enter bank account number':'Enter 11-digit mobile number';
  label.textContent=method==='bank_transfer'?'Bank Account Number':'Mobile Number';
  input.maxLength=method==='bank_transfer'?30:11;
  setActiveOptions();
}
function toggleAddr(){
  let m=document.querySelector('input[name=delivery_method]:checked').value;
  let s=document.getElementById('addressSection');
  s.style.opacity=m==='pickup'?'0.5':'1';
  s.querySelectorAll('input,textarea').forEach(e=>{if(e.type!=='radio')e.required=m!=='pickup';});
  let isFree={{ $isFreeZone?'true':'false' }};
  let d=m==='pickup'?0:(isFree?0:50);
  document.getElementById('delFeeDisp').textContent=d===0?'FREE':'P'+d.toFixed(2);
  document.getElementById('totalDisp').textContent='P'+Math.max(0,{{ $subtotal }}+d-{{ $discount }}).toFixed(2);
  setActiveOptions();
}
function fillAddr(r){document.querySelector('textarea[name=address]').value=r.dataset.address;document.querySelector('input[name=city]').value=r.dataset.city;document.querySelector('input[name=province]').value=r.dataset.province;document.querySelector('input[name=zip]').value=r.dataset.zip||'';}
document.getElementById('checkoutForm').addEventListener('submit',function(){const b=document.getElementById('placeOrderBtn');b.disabled=true;b.textContent='Placing Order...';});
toggleAddr();togglePaymentReference();
</script>
@endsection
