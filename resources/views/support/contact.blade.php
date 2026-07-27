@extends('layouts.app')
@section('title','Contact Support')
@section('content')
<main class="max-w-3xl mx-auto px-6 py-10">
  <h1 class="text-3xl font-black mb-2">Contact Support</h1>
  <p class="text-[#5a7a5c] mb-6">Reach us at 0998-572-1327 or leave a message below.</p>
  @if(session('success'))<div class="bg-[#e8f5e9] border border-[#c8e6c9] rounded-xl px-4 py-3 text-sm mb-4">{{ session('success') }}</div>@endif
  <form method="POST" action="{{ route('contact.submit') }}" class="bg-white rounded-xl border p-6 space-y-4">
    @csrf
    @if(!session()->has('user_id'))
      <div class="grid grid-cols-2 gap-3"><input type="text" name="name" placeholder="Your Name" required class="border rounded-xl px-3 py-2 text-sm"><input type="email" name="email" placeholder="Your Email" required class="border rounded-xl px-3 py-2 text-sm"></div>
    @endif
    <div><label class="text-sm font-bold">Subject</label><input type="text" name="subject" required class="w-full border rounded-xl px-3 py-2 text-sm mt-1"></div>
    <div><label class="text-sm font-bold">Rating</label><select name="rating" class="w-full border rounded-xl px-3 py-2 text-sm mt-1"><option value="5">5 - Excellent</option><option value="4">4 - Good</option><option value="3">3 - Average</option><option value="2">2 - Poor</option><option value="1">1 - Very Poor</option></select></div>
    <div><label class="text-sm font-bold">Message</label><textarea name="message" required rows="4" class="w-full border rounded-xl px-3 py-2 text-sm mt-1"></textarea></div>
    <button type="submit" class="w-full py-3 rounded-xl bg-[#17611f] text-white font-bold">Send Message</button>
  </form>
  <div class="mt-6 bg-white rounded-xl border p-4 text-sm">
    <p><strong>Phone:</strong> 0998-572-1327</p>
    <p><strong>Address:</strong> Nostalji Subd., Paliparan I, Dasmarinas, Cavite</p>
    <p><strong>Hours:</strong> Open Everyday</p>
  </div>
</main>
@endsection
