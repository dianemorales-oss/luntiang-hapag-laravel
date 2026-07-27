@extends('admin.layouts.app')
@section('title','Promotions')
@section('header','Promotions')
@section('content')
<div class="bg-white rounded-xl border p-5 mb-6">
  <h2 class="font-black mb-3">Add Promotion</h2>
  <form method="POST" action="{{ route('admin.promotions.store') }}" class="grid grid-cols-2 gap-2">
    @csrf
    <input type="text" name="code" placeholder="CODE" required class="border rounded-xl px-3 py-2 text-sm">
    <input type="text" name="description" placeholder="Description" class="border rounded-xl px-3 py-2 text-sm">
    <select name="discount_type" class="border rounded-xl px-3 py-2 text-sm"><option value="percentage">Percentage</option><option value="fixed">Fixed</option></select>
    <input type="number" step="0.01" name="discount_value" placeholder="Discount Value" required class="border rounded-xl px-3 py-2 text-sm">
    <input type="number" step="0.01" name="min_order" placeholder="Min Order" class="border rounded-xl px-3 py-2 text-sm">
    <input type="date" name="expires_at" class="border rounded-xl px-3 py-2 text-sm">
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_free_delivery"> Free Delivery</label>
    <button class="col-span-2 py-2 rounded-xl bg-[#17611f] text-white font-bold text-sm">Create</button>
  </form>
</div>
<div class="bg-white rounded-xl border p-5">
  <h2 class="font-black mb-3">Promotions</h2>
  @foreach($promotions as $p)
    <div class="border-b py-3 flex justify-between">
      <div><p class="font-bold text-sm">{{ $p->code }} — {{ $p->discount_value }}{{ $p->discount_type==='percentage'?'%':'₱' }} @if($p->is_free_delivery) + Free Delivery @endif</p><p class="text-xs text-[#5a7a5c]">{{ $p->description }} • Expires: {{ $p->expires_at ?? 'No expiry' }}</p></div>
      <form method="POST" action="{{ route('admin.promotions.destroy',$p->id) }}">@csrf @method('DELETE')<button class="text-xs text-red-500">Delete</button></form>
    </div>
  @endforeach
</div>
@endsection
