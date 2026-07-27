@extends('layouts.app')
@section('title','FAQ')
@section('content')
<main class="max-w-4xl mx-auto px-6 py-10">
  <h1 class="text-3xl font-black mb-6">Frequently Asked Questions</h1>
  @foreach($faqs as $cat => $items)
    <div class="mb-8">
      <h2 class="font-black text-lg mb-3">{{ $cat }}</h2>
      <div class="space-y-3">
        @foreach($items as $faq)
          <details class="bg-white rounded-xl border p-4"><summary class="font-bold cursor-pointer">{{ $faq->question }}</summary><p class="mt-3 text-sm text-[#5a7a5c] whitespace-pre-line">{{ $faq->answer }}</p></details>
        @endforeach
      </div>
    </div>
  @endforeach
</main>
@endsection
