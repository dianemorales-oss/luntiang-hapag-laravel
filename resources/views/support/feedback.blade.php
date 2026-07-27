@extends('layouts.app')
@section('title','Feedback')
@section('content')
<main class="max-w-2xl mx-auto px-6 py-10">
  <h1 class="text-2xl font-black mb-6">Feedback</h1>
  <form method="POST" action="{{ route('feedback.submit') }}" class="bg-white rounded-xl border p-6 space-y-4">
    @csrf
    <div><label class="text-sm font-bold">Rating</label><select name="rating" class="w-full border rounded-xl px-3 py-2 text-sm mt-1"><option value="5">5 - Excellent</option><option value="4">4 - Good</option><option value="3">3 - Average</option><option value="2">2 - Poor</option><option value="1">1 - Very Poor</option></select></div>
    <div><label class="text-sm font-bold">Comments</label><textarea name="comments" rows="4" class="w-full border rounded-xl px-3 py-2 text-sm mt-1" placeholder="Your feedback..."></textarea></div>
    <button type="submit" class="w-full py-3 rounded-xl bg-[#17611f] text-white font-bold">Submit Feedback</button>
  </form>
</main>
@endsection
