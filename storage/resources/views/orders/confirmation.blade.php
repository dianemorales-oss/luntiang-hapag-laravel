@extends('layouts.app')
@section('title','Order Confirmation')
@section('content')
<main class="max-w-3xl mx-auto px-6 py-16 text-center">
  <div class="bg-white rounded-2xl border p-10">
    <p class="text-5xl mb-4">✅</p>
    <h1 class="text-3xl font-black mb-2">Order Confirmed!</h1>
    <p class="text-[#5a7a5c] mb-4">Your order <span class="font-bold text-[#17611f]">{{ $order->order_number }}</span> has been placed.</p>
    <div class="text-left bg-[#f4faf5] rounded-xl p-4 mt-6 space-y-2 text-sm">
      <div class="flex justify-between"><span>Subtotal</span><span class="font-bold">P{{ number_format($order->subtotal,2) }}</span></div>
      <div class="flex justify-between"><span>Delivery Fee</span><span class="font-bold">{{ $order->delivery_fee==0?'FREE':'P'.number_format($order->delivery_fee,2) }}</span></div>
      @if($order->discount>0)<div class="flex justify-between"><span>Discount</span><span class="font-bold text-red-500">-P{{ number_format($order->discount,2) }}</span></div>@endif
      <div class="flex justify-between font-black text-lg border-t pt-2 mt-2"><span>Total</span><span class="text-[#17611f]">P{{ number_format($order->total,2) }}</span></div>
    </div>
    <p class="text-xs text-[#9e9e9e] mt-4">Estimated harvest: 1-3 hours after confirmation.</p>
    <div class="mt-6 flex justify-center gap-3">
      <a href="{{ route('orders.tracking', ['order'=>$order->order_number]) }}" class="px-5 py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold">Track Order</a>
      <a href="{{ route('home') }}" class="px-5 py-2.5 rounded-xl border text-sm font-bold">Continue Shopping</a>
    </div>
  </div>
</main>
@endsection
