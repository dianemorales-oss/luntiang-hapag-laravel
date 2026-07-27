@extends('admin.layouts.app')
@section('title','Reports')
@section('header','Reports')
@section('content')
<div class="grid grid-cols-2 gap-4 mb-6">
  <div class="bg-white rounded-xl border p-5"><p class="text-xs text-[#5a7a5c]">Total Sales</p><p class="text-2xl font-black">P{{ number_format($totalSales,2) }}</p></div>
  <div class="bg-white rounded-xl border p-5"><p class="text-xs text-[#5a7a5c]">Total Orders</p><p class="text-2xl font-black">{{ $totalOrders }}</p></div>
</div>
<div class="bg-white rounded-xl border p-5">
  <h2 class="font-black mb-3">Top Products</h2>
  <table class="w-full text-sm"><thead><tr class="text-left text-[#5a7a5c]"><th>Product</th><th>Qty Sold</th><th>Sales</th></tr></thead>
  <tbody>@foreach($topProducts as $tp)<tr class="border-t"><td class="py-2">{{ $tp->product_name }}</td><td>{{ $tp->total_qty }}</td><td>P{{ number_format($tp->total_sales,2) }}</td></tr>@endforeach</tbody></table>
</div>
@endsection
