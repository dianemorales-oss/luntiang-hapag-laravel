@extends('admin.layouts.app')
@section('title','Orders')
@section('header','Orders')
@section('content')
<div class="bg-white rounded-xl border p-5">
  <h2 class="font-black mb-4">Orders ({{ $orders->count() }})</h2>
  <div class="space-y-3">
    @foreach($orders as $o)
      <div class="border rounded-xl p-4 flex justify-between items-center">
        <div>
          <p class="font-bold text-sm">{{ $o->order_number }} — {{ ucfirst($o->status) }}</p>
          <p class="text-xs text-[#5a7a5c]">{{ $o->customer_name }} • P{{ number_format($o->total,2) }} • {{ $o->created_at->format('M j, Y') }}</p>
          <p class="text-xs">{{ $o->delivery_method }} • {{ $o->delivery_address }}, {{ $o->delivery_city }}</p>
        </div>
        <form method="POST" action="{{ route('admin.orders.update',$o->id) }}" class="flex gap-2">
          @csrf
          <select name="status" class="border rounded-lg px-2 py-1 text-xs">
            <option value="preparing" {{ $o->status==='preparing'?'selected':'' }}>Preparing</option>
            <option value="ready" {{ $o->status==='ready'?'selected':'' }}>Ready</option>
            <option value="delivered" {{ $o->status==='delivered'?'selected':'' }}>Delivered</option>
            <option value="completed" {{ $o->status==='completed'?'selected':'' }}>Completed</option>
            <option value="cancelled" {{ $o->status==='cancelled'?'selected':'' }}>Cancelled</option>
          </select>
          <button class="px-3 py-1 rounded-lg bg-[#17611f] text-white text-xs">Update</button>
        </form>
      </div>
    @endforeach
  </div>
</div>
@endsection
