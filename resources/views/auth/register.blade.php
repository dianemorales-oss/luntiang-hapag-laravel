@extends('layouts.app')
@section('title','Register | Luntiang H.A.P.A.G.')
@section('content')
<main class="flex-1 flex items-center justify-center px-6 py-16">
  <div class="w-full max-w-lg bg-white rounded-2xl border shadow-sm p-9">
    <span class="inline-block text-[11px] font-semibold tracking-wide text-[#17611f] bg-[#e8f5e9] rounded-full px-3 py-1 mb-5">REGISTER</span>
    <h1 class="text-3xl font-black mb-2">Create account 🌱</h1>
    <p class="text-[#5a7a5c] text-sm mb-8">Join Luntiang H.A.P.A.G. for fresh harvest-on-demand lettuce.</p>

    @if(session('error'))<div class="mb-6 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>@endif

    <form method="POST" action="{{ route('register.submit') }}" class="space-y-4">
      @csrf
      <div class="grid grid-cols-2 gap-3">
        <div><label class="text-sm font-bold">First Name</label><input type="text" name="first_name" required value="{{ old('first_name') }}" class="w-full rounded-xl border px-3 py-2.5 text-sm mt-1"></div>
        <div><label class="text-sm font-bold">Last Name</label><input type="text" name="last_name" required value="{{ old('last_name') }}" class="w-full rounded-xl border px-3 py-2.5 text-sm mt-1"></div>
      </div>
      <div><label class="text-sm font-bold">Email</label><input type="email" name="email" required value="{{ old('email') }}" class="w-full rounded-xl border px-3 py-2.5 text-sm mt-1"></div>
      <div><label class="text-sm font-bold">Phone (11 digits)</label><input type="text" name="phone" required value="{{ old('phone') }}" class="w-full rounded-xl border px-3 py-2.5 text-sm mt-1" placeholder="09123456789"></div>
      <div><label class="text-sm font-bold">Street</label><input type="text" name="street" required value="{{ old('street') }}" class="w-full rounded-xl border px-3 py-2.5 text-sm mt-1"></div>
      <div class="grid grid-cols-3 gap-3">
        <div><label class="text-sm font-bold">City</label><input type="text" name="city" required value="{{ old('city') }}" class="w-full rounded-xl border px-3 py-2.5 text-sm mt-1"></div>
        <div><label class="text-sm font-bold">Province</label><input type="text" name="province" required value="{{ old('province') }}" class="w-full rounded-xl border px-3 py-2.5 text-sm mt-1"></div>
        <div><label class="text-sm font-bold">ZIP</label><input type="text" name="zip" required value="{{ old('zip') }}" class="w-full rounded-xl border px-3 py-2.5 text-sm mt-1" placeholder="4114"></div>
      </div>
      <div><label class="text-sm font-bold">Password</label><input type="password" name="password" required class="w-full rounded-xl border px-3 py-2.5 text-sm mt-1"></div>
      <div><label class="text-sm font-bold">Confirm Password</label><input type="password" name="confirm_password" required class="w-full rounded-xl border px-3 py-2.5 text-sm mt-1"></div>
      <div class="flex items-center gap-2"><input type="checkbox" name="accept_terms" required><label class="text-xs">I agree to <a href="{{ route('terms') }}" class="text-[#17611f] underline">Terms</a> and <a href="{{ route('privacy') }}" class="text-[#17611f] underline">Privacy</a></label></div>
      <button type="submit" class="w-full rounded-xl bg-[#17611f] text-white py-3 font-bold">Create Account</button>
      <p class="text-sm text-center">Already have an account? <a href="{{ route('login') }}" class="text-[#17611f] font-bold">Login</a></p>
    </form>
  </div>
</main>
@endsection
