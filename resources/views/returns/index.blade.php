@extends('layouts.app')
@section('title','Return & Refund')
@section('content')
<main class="flex-1 max-w-3xl mx-auto px-6 py-16">
  <a href="{{ route('profile.index') }}" class="inline-flex items-center gap-2 text-sm text-[#17611f] mb-8">← Back to Dashboard</a>
  <div class="bg-white rounded-3xl border p-10">
    <span class="inline-block text-[11px] font-semibold tracking-wide text-[#17611f] bg-[#e8f5e9] rounded-full px-3 py-1 mb-5">QUICK SUPPORT</span>
    <h1 class="font-black text-3xl mb-4">Return & Refund</h1>
    <p class="text-[#5a7a5c] text-[15px] mb-6">Initiate a return for items purchased within 30 days.</p>
    @if(session('error'))<div class="rounded-xl bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700 mb-4">{{ session('error') }}</div>@endif
    <form method="POST" action="{{ route('returns.store') }}" enctype="multipart/form-data" class="space-y-5">
      @csrf
      <div><label class="text-sm font-medium">Order Number</label><input type="text" name="order_number" required value="{{ $formData['order_number'] }}" placeholder="LH-0000" class="w-full rounded-xl border px-4 py-3 text-sm mt-1"></div>
      <div><label class="text-sm font-medium">Product Name</label><input type="text" name="product_name" required value="{{ $formData['product_name'] }}" class="w-full rounded-xl border px-4 py-3 text-sm mt-1"></div>
      <div><label class="text-sm font-medium">Purchase Date</label><input type="date" name="purchase_date" required value="{{ $formData['purchase_date'] }}" class="w-full rounded-xl border px-4 py-3 text-sm mt-1"></div>
      <div><label class="text-sm font-medium">Reason for Return</label><select name="reason_category" required class="w-full rounded-xl border px-4 py-3 text-sm mt-1">@foreach($reasons as $r)<option value="{{ $r }}" {{ $formData['reason_category']===$r?'selected':'' }}>{{ $r }}</option>@endforeach</select></div>
      <div><label class="text-sm font-medium">Detailed Explanation</label><textarea name="reason" required rows="4" class="w-full rounded-xl border px-4 py-3 text-sm mt-1">{{ $formData['reason'] }}</textarea></div>
      <div><label class="text-sm font-medium">Product Condition</label><select name="product_condition" required class="w-full rounded-xl border px-4 py-3 text-sm mt-1">@foreach($conditions as $c)<option value="{{ $c }}" {{ $formData['product_condition']===$c?'selected':'' }}>{{ $c }}</option>@endforeach</select></div>
      <div><label class="text-sm font-medium">Proof of Purchase</label><input type="file" name="proof_of_purchase[]" required multiple accept=".jpg,.jpeg,.png,.pdf" class="w-full border rounded-xl p-2 text-sm mt-1"></div>
      <div><label class="text-sm font-medium">Damage Photo (optional)</label><input type="file" name="damage_photo[]" multiple accept=".jpg,.jpeg,.png" class="w-full border rounded-xl p-2 text-sm mt-1"></div>
      <button type="submit" class="w-full py-3 rounded-xl bg-[#17611f] text-white font-bold">Submit Return Request</button>
    </form>
  </div>
</main>
@endsection
