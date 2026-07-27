@extends('layouts.app')
@section('title','Order Tracking')
@section('content')
<main class="max-w-5xl mx-auto px-6 py-8">
  <h1 class="text-2xl font-black mb-6">Order Tracking</h1>

  @if($single)
  <div class="bg-white rounded-xl border p-6 mb-6">
    <h2 class="font-black text-lg">{{ $single->order_number }} — {{ ucfirst($single->status) }}</h2>
    <p class="text-sm text-[#5a7a5c]">{{ $single->created_at->format('M j, Y g:i A') }} • P{{ number_format($single->total,2) }}</p>
    <div class="mt-4 flex gap-2">
      @foreach(['preparing','ready','delivered','completed'] as $step)
        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $single->status===$step ? 'bg-[#17611f] text-white' : (array_search($single->status, ['preparing','ready','delivered','completed']) >= array_search($step, ['preparing','ready','delivered','completed']) ? 'bg-[#e8f5e9] text-[#17611f]' : 'bg-gray-100 text-[#9e9e9e]') }}">{{ ucfirst($step) }}</span>
      @endforeach
    </div>
    <div class="mt-4">
      @foreach($single->items as $item)
        <div class="flex justify-between text-sm py-2 border-b"><span>{{ $item->product_name }} × {{ $item->quantity }}</span><span class="font-bold">P{{ number_format($item->price*$item->quantity,2) }}</span></div>
      @endforeach
    </div>
  </div>
  @endif

  <div class="bg-white rounded-xl border p-5">
    <h2 class="font-black mb-4">All Orders</h2>
    @forelse($orders as $o)
      <div class="border rounded-xl p-4 mb-3 flex justify-between items-center">
        <div><p class="font-bold text-sm">{{ $o->order_number }} — {{ ucfirst($o->status) }}</p><p class="text-xs text-[#5a7a5c]">{{ $o->created_at->format('M j, Y') }} • P{{ number_format($o->total,2) }} • {{ count($o->items) }} items</p></div>
        <a href="{{ route('orders.tracking', ['order'=>$o->order_number]) }}" class="text-xs font-bold text-[#17611f] border px-3 py-1.5 rounded-lg">Track</a>
      </div>
    @empty
      <p class="text-sm text-[#5a7a5c]">No orders.</p>
    @endforelse
  </div>
</main>
@endsection
