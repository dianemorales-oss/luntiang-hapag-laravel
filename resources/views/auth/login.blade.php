@extends('layouts.app')
@section('title','Login | Luntiang H.A.P.A.G.')
@section('content')
<main class="flex-1 flex items-center justify-center px-6 py-16">
  <div class="w-full max-w-md bg-white rounded-2xl border border-[rgba(27,94,32,0.08)] shadow-sm p-9">
    <span class="inline-block text-[11px] font-semibold tracking-wide text-[#17611f] bg-[#e8f5e9] rounded-full px-3 py-1 mb-5">LOGIN</span>
    <h1 class="text-3xl font-black text-[#1a2e1c] mb-2">Welcome back 🌿</h1>
    <p class="text-[#5a7a5c] text-sm mb-8">Sign in to manage your orders and support requests.</p>

    @if(session('success'))<div class="mb-6 rounded-xl bg-[#e8f5e9] border border-[#c8e6c9] px-4 py-3 text-sm text-[#1b5e20]">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-6 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>@endif

    <form class="space-y-5" method="POST" action="{{ route('login.submit') }}">
      @csrf
      <div>
        <label class="block text-sm font-bold mb-2">Email or Mobile Number</label>
        <input type="text" name="login" placeholder="your@email.com or 09123456789" required value="{{ old('login') }}" class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" />
      </div>
      <div>
        <label class="block text-sm font-bold mb-2">Password</label>
        <input type="password" name="password" placeholder="••••••••" required class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" />
      </div>
      <button type="submit" class="w-full rounded-xl bg-[#17611f] text-white text-sm font-black py-3.5 hover:bg-[#14521a]">Sign In</button>
      <div class="flex items-center justify-between pt-1">
        <a href="{{ route('forgot.password') }}" class="text-sm text-[#17611f] font-semibold">Forgot password?</a>
        <a href="{{ route('register') }}" class="text-sm text-[#17611f] font-semibold">Create account →</a>
      </div>
    </form>
  </div>
</main>
@endsection
