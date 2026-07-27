@extends('layouts.app')
@section('title','Reset Password')
@section('content')
<main class="flex-1 flex items-center justify-center px-6 py-16">
  <div class="w-full max-w-md bg-white rounded-2xl border p-8">
    <h1 class="text-2xl font-black mb-6">Reset Password</h1>
    @if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>@endif
    <form method="POST" action="{{ route('reset.password.submit') }}">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">
      <div class="mb-3"><label class="text-sm font-bold">New Password</label><input type="password" name="password" required class="w-full border rounded-xl px-4 py-3 text-sm mt-1"></div>
      <div class="mb-4"><label class="text-sm font-bold">Confirm Password</label><input type="password" name="confirm_password" required class="w-full border rounded-xl px-4 py-3 text-sm mt-1"></div>
      <button type="submit" class="w-full py-3 rounded-xl bg-[#17611f] text-white font-bold">Reset Password</button>
    </form>
  </div>
</main>
@endsection
