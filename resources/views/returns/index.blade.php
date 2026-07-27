@extends('layouts.app')
@section('title','Return & Refund')
@section('content')
<main class="flex-1 max-w-3xl mx-auto px-6 py-16">
  <a href="{{ route('profile.index') }}" class="inline-flex items-center gap-2 text-sm text-[#17611f] mb-8">← Back to Dashboard</a>
  <div class="bg-white rounded-3xl border p-10">
    <span class="inline-block text-[11px] font-semibold tracking-wide text-[#17611f] bg-[#e8f5e9] rounded-full px-3 py-1 mb-5">QUICK SUPPORT</span>
    <h1 class="font-black text-3xl mb-4">Return & Refund</h1>
    <p class="text-[#5a7a5c] text-[15px] mb-6">Initiate a return within 24 hours after receiving your order.</p>
    @if(session('error'))<div class="rounded-xl bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700 mb-4">{{ session('error') }}</div>@endif

    @if(($eligibleOrders ?? collect())->isEmpty())
      <div class="rounded-2xl border border-amber-100 bg-amber-50 p-5 text-sm text-amber-800">
        You do not have any delivered or completed orders eligible for return right now. Return requests must be submitted within 24 hours after receiving your order.
      </div>
    @else
    <form method="POST" action="{{ route('returns.store') }}" enctype="multipart/form-data" class="space-y-5" id="returnForm">
      @csrf
      <div>
        <label class="text-sm font-medium">Order Number</label>
        <select name="order_number" id="returnOrderSelect" required class="w-full rounded-xl border px-4 py-3 text-sm mt-1">
          <option value="" disabled {{ empty($formData['order_number']) ? 'selected' : '' }}>Select a completed or delivered order</option>
          @foreach($eligibleOrders as $order)
            <option value="{{ $order->order_number }}"
                    data-date="{{ $order->return_purchase_date }}"
                    data-products='@json($order->items->pluck("product_name")->filter()->values())'
                    {{ $formData['order_number'] === $order->order_number ? 'selected' : '' }}
                    {{ $order->return_expired ? 'disabled' : '' }}>
              {{ $order->order_number }} — {{ $order->created_at->format('M j, Y') }}{{ $order->return_expired ? ' (Return window expired)' : '' }}
            </option>
          @endforeach
        </select>
        <p class="text-[11px] text-[#9e9e9e] mt-1">Only your completed/delivered orders are listed. Expired orders cannot be selected.</p>
      </div>
      <div>
        <label class="text-sm font-medium">Product Name</label>
        <select name="product_name" id="returnProductSelect" required class="w-full rounded-xl border px-4 py-3 text-sm mt-1">
          <option value="" disabled selected>Select an order first</option>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium">Purchase Date</label>
        <input type="date" name="purchase_date" id="purchaseDateInput" required readonly value="{{ $formData['purchase_date'] }}" class="w-full rounded-xl border px-4 py-3 text-sm mt-1 bg-gray-50 cursor-not-allowed">
        <p class="text-[11px] text-[#9e9e9e] mt-1">This date is automatically taken from your selected order.</p>
      </div>
      <div><label class="text-sm font-medium">Reason for Return</label><select name="reason_category" required class="w-full rounded-xl border px-4 py-3 text-sm mt-1">@foreach($reasons as $r)<option value="{{ $r }}" {{ $formData['reason_category']===$r?'selected':'' }}>{{ $r }}</option>@endforeach</select></div>
      <div><label class="text-sm font-medium">Detailed Explanation</label><textarea name="reason" required rows="4" class="w-full rounded-xl border px-4 py-3 text-sm mt-1">{{ $formData['reason'] }}</textarea></div>
      <div><label class="text-sm font-medium">Product Condition</label><select name="product_condition" required class="w-full rounded-xl border px-4 py-3 text-sm mt-1">@foreach($conditions as $c)<option value="{{ $c }}" {{ $formData['product_condition']===$c?'selected':'' }}>{{ $c }}</option>@endforeach</select></div>
      <div><label class="text-sm font-medium">Proof of Purchase</label><input type="file" name="proof_of_purchase[]" required multiple accept=".jpg,.jpeg,.png,.pdf" class="w-full border rounded-xl p-2 text-sm mt-1"></div>
      <div><label class="text-sm font-medium">Damage Photo (optional)</label><input type="file" name="damage_photo[]" multiple accept=".jpg,.jpeg,.png" class="w-full border rounded-xl p-2 text-sm mt-1"></div>
      <button type="submit" class="w-full py-3 rounded-xl bg-[#17611f] text-white font-bold">Submit Return Request</button>
    </form>
    @endif
  </div>
</main>
@endsection

@push('scripts')
<script>
(function(){
  const orderSelect = document.getElementById('returnOrderSelect');
  const productSelect = document.getElementById('returnProductSelect');
  const purchaseDateInput = document.getElementById('purchaseDateInput');
  const oldProduct = @json($formData['product_name'] ?? '');

  function refreshReturnFields() {
    if (!orderSelect || !productSelect || !purchaseDateInput) return;
    const opt = orderSelect.options[orderSelect.selectedIndex];
    const date = opt ? opt.dataset.date || '' : '';
    let products = [];
    try { products = opt && opt.dataset.products ? JSON.parse(opt.dataset.products) : []; } catch(e) { products = []; }

    purchaseDateInput.value = date;
    productSelect.innerHTML = '';
    if (!products.length) {
      productSelect.innerHTML = '<option value="" disabled selected>No products found for selected order</option>';
      return;
    }

    productSelect.insertAdjacentHTML('beforeend', '<option value="" disabled>Select product from this order</option>');
    products.forEach(function(name){
      const option = document.createElement('option');
      option.value = name;
      option.textContent = name;
      if (oldProduct && oldProduct === name) option.selected = true;
      productSelect.appendChild(option);
    });
    if (!productSelect.value) productSelect.selectedIndex = 1;
  }

  orderSelect?.addEventListener('change', refreshReturnFields);
  refreshReturnFields();
})();
</script>
@endpush
