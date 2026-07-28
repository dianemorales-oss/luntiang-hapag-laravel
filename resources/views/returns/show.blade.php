@extends('layouts.app')
@section('title','Return Request Details')
@section('content')
<main class="max-w-3xl mx-auto px-6 py-10">
 <a href="{{ route('profile.index',['section'=>'support']) }}" class="text-sm font-bold text-[#17611f] hover:underline">← Back to Support Requests</a>
 <section class="bg-white rounded-2xl border border-[rgba(27,94,32,0.10)] shadow-sm p-6 mt-5">
  <div class="flex items-start justify-between gap-4 border-b pb-4"><div><p class="text-xs font-bold text-[#5a7a5c]">RETURN & REFUND REQUEST #R-{{ str_pad($return->id,4,'0',STR_PAD_LEFT) }}</p><h1 class="text-xl font-black mt-1">{{ $return->product_name }}</h1></div><span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700">{{ ucwords(str_replace('_',' ',$return->status)) }}</span></div>
  <dl class="grid sm:grid-cols-2 gap-x-8 gap-y-5 mt-6 text-sm"><div><dt class="text-[#5a7a5c] text-xs">Order Number</dt><dd class="font-bold mt-1">{{ $return->order_number }}</dd></div><div><dt class="text-[#5a7a5c] text-xs">Submitted</dt><dd class="font-bold mt-1">{{ $return->created_at->format('M j, Y g:i A') }}</dd></div><div><dt class="text-[#5a7a5c] text-xs">Purchase Date</dt><dd class="font-bold mt-1">{{ $return->purchase_date }}</dd></div><div><dt class="text-[#5a7a5c] text-xs">Reason Category</dt><dd class="font-bold mt-1">{{ $return->reason_category }}</dd></div><div><dt class="text-[#5a7a5c] text-xs">Product Condition</dt><dd class="font-bold mt-1">{{ $return->product_condition }}</dd></div><div class="sm:col-span-2"><dt class="text-[#5a7a5c] text-xs">Your Description</dt><dd class="mt-1 whitespace-pre-line">{{ $return->reason }}</dd></div>@if($return->admin_note)<div class="sm:col-span-2 rounded-xl bg-[#f4faf5] p-4"><dt class="text-[#5a7a5c] text-xs font-bold">Support Team Update</dt><dd class="mt-1">{{ $return->admin_note }}</dd></div>@endif</dl>
 </section>
</main>
@endsection
