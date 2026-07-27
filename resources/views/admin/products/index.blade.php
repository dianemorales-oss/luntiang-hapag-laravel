@extends('admin.layouts.app')
@section('title','Products')
@section('header','Products')
@section('content')
<div class="bg-white rounded-xl border p-5 mb-6">
  <h2 class="font-black mb-3">Add Product</h2>
  <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="grid grid-cols-2 gap-3">
    @csrf
    <input type="text" name="name" placeholder="Name" required class="border rounded-xl px-3 py-2 text-sm">
    <input type="number" step="0.01" name="price" placeholder="Price" required class="border rounded-xl px-3 py-2 text-sm">
    <input type="text" name="variety" placeholder="Variety" class="border rounded-xl px-3 py-2 text-sm">
    <input type="number" name="plants_available" placeholder="Stock" class="border rounded-xl px-3 py-2 text-sm">
    <select name="category_id" class="border rounded-xl px-3 py-2 text-sm"><option value="">Category</option>@foreach($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach</select>
    <input type="file" name="image" class="border rounded-xl px-3 py-2 text-sm">
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_best_seller"> Best Seller</label>
    <button type="submit" class="col-span-2 py-2 rounded-xl bg-[#17611f] text-white font-bold">Add Product</button>
  </form>
</div>
<div class="bg-white rounded-xl border p-5">
  <h2 class="font-black mb-3">All Products ({{ $products->count() }})</h2>
  <div class="overflow-x-auto">
    <table class="w-full text-sm"><thead><tr class="text-left text-[#5a7a5c]"><th class="p-2">Name</th><th>Price</th><th>Stock</th><th>Best</th><th>Action</th></tr></thead>
    <tbody>@foreach($products as $p)<tr class="border-t"><td class="p-2">{{ $p->name }}</td><td>P{{ number_format($p->price,2) }}</td><td>{{ $p->plants_available }}</td><td>{{ $p->is_best_seller?'Yes':'' }}</td><td><form method="POST" action="{{ route('admin.products.destroy',$p->id) }}">@csrf @method('DELETE')<button class="text-red-500 text-xs">Delete</button></form></td></tr>@endforeach</tbody></table>
  </div>
</div>
@endsection
