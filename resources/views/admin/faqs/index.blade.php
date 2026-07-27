@extends('admin.layouts.app')
@section('title','FAQs')
@section('header','FAQs')
@section('content')
<div class="bg-white rounded-xl border p-5 mb-6">
  <h2 class="font-black mb-3">Add FAQ</h2>
  <form method="POST" action="{{ route('admin.faqs.store') }}" class="space-y-2">
    @csrf
    <input type="text" name="question" placeholder="Question" required class="w-full border rounded-xl px-3 py-2 text-sm">
    <textarea name="answer" placeholder="Answer" required class="w-full border rounded-xl px-3 py-2 text-sm" rows="3"></textarea>
    <input type="text" name="category" placeholder="Category" class="w-full border rounded-xl px-3 py-2 text-sm" value="General">
    <button class="px-4 py-2 rounded-xl bg-[#17611f] text-white text-sm font-bold">Add</button>
  </form>
</div>
<div class="bg-white rounded-xl border p-5">
  @foreach($faqs as $faq)
    <div class="border-b py-3 flex justify-between">
      <div><p class="font-bold text-sm">{{ $faq->question }} <span class="text-xs text-[#5a7a5c]">({{ $faq->category }})</span></p><p class="text-xs text-[#5a7a5c] mt-1">{{ Str::limit($faq->answer,100) }}</p></div>
      <form method="POST" action="{{ route('admin.faqs.destroy',$faq->id) }}">@csrf @method('DELETE')<button class="text-xs text-red-500">Delete</button></form>
    </div>
  @endforeach
</div>
@endsection
