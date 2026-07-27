@extends('layouts.app')
@section('title', 'Contact Support | Luntiang H.A.P.A.G.')
@section('content')

@php
  $isLoggedIn = session()->has('user_id');
  $prefillName = $isLoggedIn ? trim(session('first_name') . ' ' . session('last_name')) : old('name');
  $prefillEmail = $isLoggedIn ? session('email') : old('email');
@endphp

<main class="flex-1 max-w-5xl w-full mx-auto px-6 py-14">
  <div class="text-center mb-8">
    <h1 class="text-3xl font-black mb-2">Contact Support</h1>
    <p class="text-[#5a7a5c]">We're here to help. Choose the best way to reach us.</p>
  </div>

  <!-- Contact Methods -->
  <div class="bg-white rounded-2xl border border-[rgba(27,94,32,0.08)] overflow-hidden mb-6">
    <div class="pt-4 pb-3">
      <p class="text-center text-[10px] font-semibold tracking-[0.18em] uppercase text-[#17611f]">Ways to Get in Touch</p>
    </div>
    <div class="grid md:grid-cols-3">
      <div class="border-r border-[rgba(27,94,32,0.12)] px-7 py-8 flex">
        <div class="grid grid-cols-[48px_1fr] gap-x-4 w-full">
          <div class="w-12 h-12 rounded-full bg-[#e8f5e9] flex items-center justify-center">
            <svg class="w-5 h-5 text-[#17611f]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
          </div>
          <div class="flex flex-col min-h-[200px]">
            <div>
              <h3 class="font-semibold text-lg">Call Us</h3>
              <p class="text-sm text-[#5a7a5c] mt-1">Speak with our support specialist.</p>
              <p class="font-semibold text-[#17611f] mt-6">0998-572-1327</p>
              <p class="text-xs text-[#5a7a5c] mt-3">Mon - Sat, 9:00 AM - 6:00 PM</p>
            </div>
            <a href="tel:09985721327" class="inline-flex w-fit mt-auto px-7 py-2 rounded-md border border-[#17611f] text-sm font-medium text-[#17611f] hover:bg-[#e8f5e9]">Call Now</a>
          </div>
        </div>
      </div>
      <div class="border-r border-[rgba(27,94,32,0.12)] px-7 py-8 flex">
        <div class="grid grid-cols-[48px_1fr] gap-x-4 w-full">
          <div class="w-12 h-12 rounded-full bg-[#e8f5e9] flex items-center justify-center">
            <svg class="w-5 h-5 text-[#17611f]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
          </div>
          <div class="flex flex-col min-h-[200px]">
            <div>
              <h3 class="font-semibold text-lg">Email Us</h3>
              <p class="text-sm text-[#5a7a5c] mt-1">We'll respond within 24 hours.</p>
              <p class="font-semibold text-[#17611f] mt-6">support@luntianghapag.com</p>
              <p class="text-xs text-[#5a7a5c] mt-3">Typically within one business day.</p>
            </div>
            <a href="mailto:support@luntianghapag.com" class="inline-flex w-fit mt-auto px-7 py-2 rounded-md border border-[#17611f] text-sm font-medium text-[#17611f] hover:bg-[#e8f5e9]">Send Email</a>
          </div>
        </div>
      </div>
      <div class="px-7 py-8 flex">
        <div class="grid grid-cols-[48px_1fr] gap-x-4 w-full">
          <div class="w-12 h-12 rounded-full bg-[#e8f5e9] flex items-center justify-center">
            <svg class="w-5 h-5 text-[#17611f]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
          </div>
          <div class="flex flex-col min-h-[200px]">
            <div>
              <h3 class="font-semibold text-lg">Live Chat</h3>
              <p class="text-sm text-[#5a7a5c] mt-1">Chat with our support agent.</p>
              <p class="font-semibold text-green-600 mt-6">Available</p>
              <p class="text-xs text-[#5a7a5c] mt-3">Mon - Sat, 9:00 AM - 6:00 PM</p>
            </div>
            <a href="{{ route('chat.index') }}" class="inline-flex w-fit mt-auto px-7 py-2 rounded-md border border-[#17611f] text-sm font-medium text-[#17611f] hover:bg-[#e8f5e9]">Start Chat</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Feedback + Topics -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Left: Feedback form -->
    <div class="bg-white rounded-2xl border border-[rgba(27,94,32,0.08)] p-7">
      <h3 class="font-bold mb-1">Send Us Your Feedback</h3>
      <p class="text-sm text-[#5a7a5c] mb-5">We value your opinion.</p>

      @if (session('success'))
        <div class="mb-4 rounded-xl px-4 py-3 text-sm bg-[#e8f5e9] text-[#17611f] border border-[#c8e6c9] shadow-sm">
          {{ session('success') }}
        </div>
      @endif

      <form class="space-y-4" method="POST" action="{{ route('contact.submit') }}" id="feedbackForm">
        @csrf
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-xs font-bold text-[#5a7a5c]">Full Name</label>
            <input type="text" name="name" required value="{{ $prefillName }}" {{ $isLoggedIn ? 'readonly' : '' }} class="w-full rounded-lg border border-[rgba(27,94,32,0.12)] px-3.5 py-2.5 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
          </div>
          <div>
            <label class="text-xs font-bold text-[#5a7a5c]">Email</label>
            <input type="email" name="email" required value="{{ $prefillEmail }}" {{ $isLoggedIn ? 'readonly' : '' }} class="w-full rounded-lg border border-[rgba(27,94,32,0.12)] px-3.5 py-2.5 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
          </div>
        </div>
        <div>
          <label class="text-xs font-bold text-[#5a7a5c]">Subject</label>
          <select name="subject" class="w-full rounded-lg border border-[rgba(27,94,32,0.12)] px-3.5 py-2.5 text-sm mt-1">
            <option value="">Select a subject</option>
            <option {{ old('subject') === 'General Feedback' ? 'selected' : '' }}>General Feedback</option>
            <option {{ old('subject') === 'Website Feedback' ? 'selected' : '' }}>Website Feedback</option>
            <option {{ old('subject') === 'Other' ? 'selected' : '' }}>Other</option>
          </select>
        </div>
        <div>
          <label class="text-xs font-bold text-[#5a7a5c]">Your Feedback</label>
          <textarea rows="4" name="message" placeholder="Share your thoughts..." class="w-full rounded-lg border border-[rgba(27,94,32,0.12)] px-3.5 py-2.5 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">{{ old('message') }}</textarea>
        </div>
        <div>
          <label class="text-xs font-bold text-[#5a7a5c]">Rate Your Experience</label>
          <div id="starRating" class="flex gap-1.5 mt-1">
            @for ($i = 1; $i <= 5; $i++)
              <button type="button" data-star="{{ $i }}" class="star-btn text-2xl text-gray-300 hover:text-amber-400 transition-colors">★</button>
            @endfor
          </div>
          <input type="hidden" name="rating" id="ratingInput" value="{{ old('rating') }}">
        </div>
        <button type="submit" class="px-6 py-3 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a]">Submit Feedback</button>
      </form>
    </div>

    <!-- Right: Topics list -->
    <div class="bg-white rounded-2xl border border-[rgba(27,94,32,0.08)] p-7">
      <h3 class="font-bold mb-1">Common Support Topics</h3>
      <p class="text-sm text-[#5a7a5c] mb-5">Find help with our most common services.</p>
      <div class="divide-y divide-[rgba(27,94,32,0.08)]">
        <a href="{{ route('tickets.create') }}" class="flex items-center gap-4 py-3.5 group">
          <div class="w-9 h-9 rounded-lg bg-[#fff8e1] flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-[#f9a825]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
          </div>
          <div class="flex-1">
            <p class="text-sm font-medium">Submit a Support Ticket</p>
            <p class="text-xs text-[#9e9e9e]">Report an issue or ask a question</p>
          </div>
          <svg class="w-4 h-4 text-gray-300 group-hover:text-[#5a7a5c] flex-shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
          </svg>
        </a>

        <a href="{{ route('returns.index') }}" class="flex items-center gap-4 py-3.5 group">
          <div class="w-9 h-9 rounded-lg bg-[#e8f5e9] flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8h-1"/>
            </svg>
          </div>
          <div class="flex-1">
            <p class="text-sm font-medium">Return & Refund Request</p>
            <p class="text-xs text-[#9e9e9e]">Request a return or refund</p>
          </div>
          <svg class="w-4 h-4 text-gray-300 group-hover:text-[#5a7a5c] flex-shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
          </svg>
        </a>

        <a href="{{ route('profile.index') }}" class="flex items-center gap-4 py-3.5 group">
          <div class="w-9 h-9 rounded-lg bg-purple-50 flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
            </svg>
          </div>
          <div class="flex-1">
            <p class="text-sm font-medium">Track Existing Requests</p>
            <p class="text-xs text-[#9e9e9e]">Check the status of your requests</p>
          </div>
          <svg class="w-4 h-4 text-gray-300 group-hover:text-[#5a7a5c] flex-shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
          </svg>
        </a>

        <a href="{{ route('faq') }}" class="flex items-center gap-4 py-3.5 group">
          <div class="w-9 h-9 rounded-lg bg-pink-50 flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0l-3.5 5H7.5L4 13m16 0H4"/>
            </svg>
          </div>
          <div class="flex-1">
            <p class="text-sm font-medium">FAQ</p>
            <p class="text-xs text-[#9e9e9e]">Frequently asked questions</p>
          </div>
          <svg class="w-4 h-4 text-gray-300 group-hover:text-[#5a7a5c] flex-shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
          </svg>
        </a>

        <a href="{{ route('chat.index') }}" class="flex items-center gap-4 py-3.5 group">
          <div class="w-9 h-9 rounded-lg bg-teal-50 flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
          </div>
          <div class="flex-1">
            <p class="text-sm font-medium">Live Chat</p>
            <p class="text-xs text-[#9e9e9e]">Chat with us in real-time</p>
          </div>
          <svg class="w-4 h-4 text-gray-300 group-hover:text-[#5a7a5c] flex-shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
          </svg>
        </a>
      </div>
    </div>
  </div>
</main>

@push('scripts')
<script>
  const starBtns = document.querySelectorAll('.star-btn');
  const ratingInput = document.getElementById('ratingInput');
  let sr = parseInt(ratingInput.value) || 0;

  function setStars(rating) {
    starBtns.forEach((s, i) => {
      s.classList.toggle('text-amber-400', i < rating);
      s.classList.toggle('text-gray-300', i >= rating);
    });
  }

  if (sr > 0) {
    setStars(sr);
  }

  starBtns.forEach(b => {
    b.addEventListener('click', () => {
      sr = parseInt(b.dataset.star);
      ratingInput.value = sr;
      setStars(sr);
    });
  });

  document.getElementById('feedbackForm')?.addEventListener('submit', e => {
    if (!ratingInput.value) {
      e.preventDefault();
      alert('Please choose a star rating.');
    }
  });
</script>
@endpush

@endsection
