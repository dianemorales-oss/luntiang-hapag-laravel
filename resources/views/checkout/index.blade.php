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
        <div class="flex items-center justify-between mb-3">
          <h2 class="font-black text-lg">Delivery Address</h2>
          @if($defaultAddr)
            <button type="button" onclick="toggleEditAddress()" id="editAddressBtn" class="text-xs font-bold text-[#17611f] border border-[#c8e6c9] bg-[#e8f5e9] px-3 py-1.5 rounded-lg hover:bg-[#c8e6c9] transition-colors">✏️ Edit Address</button>
          @endif
        </div>

        @if($savedAddresses->isNotEmpty())
        <div class="mb-4 space-y-2">
          @foreach($savedAddresses as $sa)
          <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer hover:bg-[#e8f5e9] text-sm {{ ($sa->is_default || $loop->first) ? 'border-[#17611f] bg-[#e8f5e9]/40' : '' }}">
            <input type="radio" name="saved_address_id" value="{{ $sa->id }}" class="accent-[#17611f]" {{ ($sa->is_default || $loop->first) ? 'checked' : '' }} onchange="fillAddr(this)" data-address="{{ $sa->address }}" data-city="{{ $sa->city }}" data-province="{{ $sa->province }}" data-zip="{{ $sa->zip }}">
            <div><span class="font-bold">{{ $sa->label }}:</span> {{ $sa->address }}, {{ $sa->city }}, {{ $sa->province }} {{ $sa->zip }}</div>
          </label>
          @endforeach
        </div>
        @endif

        <!-- Default Address Display Card (shown initially if default exists) -->
        <div id="displayAddressCard" class="bg-[#f4faf5] border border-[#c8e6c9] rounded-xl p-4 text-sm {{ $defaultAddr ? '' : 'hidden' }}">
          <p class="font-bold text-[#1a2e1c]" id="dispAddrText">{{ $defaultAddress }}, {{ $defaultCity }}, {{ $defaultProvince }} {{ $defaultZip }}</p>
          <p class="text-xs text-[#5a7a5c] mt-1">✓ Set as default delivery address</p>
        </div>

        <!-- Editable Address Form (hidden by default unless edit is clicked) -->
        <div id="editableAddressForm" class="space-y-3 {{ $defaultAddr ? 'hidden mt-3' : '' }}">
          <textarea name="address" rows="2" class="w-full border rounded-xl px-3 py-2 text-sm" placeholder="Street address" required>{{ old('address', $defaultAddress) }}</textarea>
          <div class="grid grid-cols-2 gap-3">
            <input name="city" class="w-full border rounded-xl px-3 py-2 text-sm" placeholder="City" required value="{{ old('city', $defaultCity) }}">
            <input name="province" class="w-full border rounded-xl px-3 py-2 text-sm" placeholder="Province" required value="{{ old('province', $defaultProvince) }}">
          </div>
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
          <input type="text" name="payment_reference" id="paymentReference" value="{{ old('payment_reference') }}" class="w-full border rounded-xl px-3 py-2 text-sm mt-1" inputmode="numeric" placeholder="Enter account/mobile number" oninput="validatePaymentReference(this)">
          <p id="paymentReferenceHint" class="text-[11px] text-[#9e9e9e] mt-1">Required for GCash, Maya, and Bank Transfer payments (11 digits for GCash/Maya, numbers only).</p>
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
  const hint=document.getElementById('paymentReferenceHint');
  const needs=method!=='cod';
  wrap.classList.toggle('hidden',!needs);
  input.required=needs;

  if(method==='bank_transfer'){
    label.textContent='Bank Account Number';
    input.placeholder='Enter bank account number (numbers only)';
    hint.textContent='Bank transfer requires numbers only (no character limit restriction).';
    input.setAttribute('inputmode', 'numeric');
  } else if(method==='gcash' || method==='maya'){
    label.textContent= (method==='gcash'?'GCash':'Maya') + ' Mobile Number';
    input.placeholder='Enter 11-digit mobile number';
    hint.textContent='Must be exactly 11 digits (numbers only, e.g. 09123456789).';
    input.setAttribute('inputmode', 'numeric');
    input.maxLength = 11;
  }
  setActiveOptions();
}

function validatePaymentReference(el){
  const method=document.querySelector('input[name=payment_method]:checked').value;
  // Numbers only check
  el.value = el.value.replace(/\D/g, '');
  if(method === 'gcash' || method === 'maya'){
    if(el.value.length > 11){
      el.value = el.value.slice(0, 11);
    }
  }
}

function toggleEditAddress(){
  const card = document.getElementById('displayAddressCard');
  const form = document.getElementById('editableAddressForm');
  const btn = document.getElementById('editAddressBtn');
  if(form.classList.contains('hidden')){
    form.classList.remove('hidden');
    if(card) card.classList.add('hidden');
    btn.textContent = '✓ Done Editing';
  } else {
    form.classList.add('hidden');
    if(card) card.classList.remove('hidden');
    btn.textContent = '✏️ Edit Address';
  }
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

function fillAddr(r){
  const addr = r.dataset.address;
  const city = r.dataset.city;
  const prov = r.dataset.province;
  const zip = r.dataset.zip || '';
  
  document.querySelector('textarea[name=address]').value = addr;
  document.querySelector('input[name=city]').value = city;
  document.querySelector('input[name=province]').value = prov;
  document.querySelector('input[name=zip]').value = zip;

  const disp = document.getElementById('dispAddrText');
  if(disp) disp.textContent = addr + ', ' + city + ', ' + prov + ' ' + zip;

  // Highlight selected address radio container
  document.querySelectorAll('input[name=saved_address_id]').forEach(inp=>{
    const lbl = inp.closest('label');
    if(lbl){
      lbl.classList.toggle('border-[#17611f]', inp.checked);
      lbl.classList.toggle('bg-[#e8f5e9]/40', inp.checked);
    }
  });
}

document.getElementById('checkoutForm').addEventListener('submit',function(e){
  const method=document.querySelector('input[name=payment_method]:checked').value;
  const ref=document.getElementById('paymentReference').value.trim();
  if((method==='gcash'||method==='maya') && ref.length!==11){
    e.preventDefault();
    alert('Please enter a valid 11-digit mobile number for ' + method.toUpperCase() + '.');
    return false;
  }
  const b=document.getElementById('placeOrderBtn');
  b.disabled=true;
  b.textContent='Placing Order...';
});
toggleAddr();togglePaymentReference();
</script>
@endsection
