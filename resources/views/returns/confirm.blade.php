@extends('layouts.app')
@section('title','Confirm Return')
@section('content')
<main class="max-w-2xl mx-auto px-6 py-10">
  <div class="bg-white rounded-3xl shadow-xl p-8">
    <h2 class="font-black text-2xl mb-4">Review Your Return Request</h2>
    <div class="space-y-3">
      <div class="bg-[#f8f6f2] rounded-xl p-3"><p class="text-xs text-gray-400">ORDER NUMBER</p><p class="font-bold">{{ $submittedData['order_number'] }}</p></div>
      <div class="bg-[#f8f6f2] rounded-xl p-3"><p class="text-xs text-gray-400">PRODUCT</p><p>{{ $submittedData['product_name'] }}</p></div>
      <div class="bg-[#f8f6f2] rounded-xl p-3"><p class="text-xs text-gray-400">REASON</p><p>{{ $submittedData['reason_category'] }} — {{ $submittedData['reason'] }}</p></div>
      <div class="bg-[#f8f6f2] rounded-xl p-3"><p class="text-xs text-gray-400">CONDITION</p><p>{{ $submittedData['product_condition'] }}</p></div>
    </div>
    <div class="flex gap-3 mt-6">
      <form method="POST" action="{{ route('returns.store') }}">@csrf<input type="hidden" name="confirm_submit" value="1"><button type="submit" class="px-6 py-3 rounded-full bg-[#17611f] text-white font-bold">Confirm & Submit</button></form>
      <a href="{{ route('returns.index') }}" class="px-6 py-3 rounded-full border font-bold">Edit Details</a>
    </div>
  </div>
</main>
@endsection
