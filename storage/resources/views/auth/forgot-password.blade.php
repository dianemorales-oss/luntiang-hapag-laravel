@extends('layouts.app')
@section('title','Forgot Password')
@section('content')
<main class="flex-1 flex items-center justify-center px-6 py-16">
  <div class="w-full max-w-md bg-white rounded-2xl border p-8">
    <h1 class="text-2xl font-black mb-2">Forgot Password</h1>
    <p class="text-sm text-[#5a7a5c] mb-6">Enter your email to receive a reset link.</p>
    @if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>@endif
    @if(isset($success) && $success)
      <div class="bg-[#e8f5e9] border border-[#c8e6c9] rounded-xl p-4 text-sm">
        <p class="font-bold mb-2">Development Email Preview</p>
        <p>Email: {{ $email }}</p>
        <p class="mt-2">Reset link: <a href="{{ route('reset.password', $token) }}" class="text-[#17611f] underline">Reset Password</a></p>
      </div>
    @else
      <form method="POST" action="{{ route('forgot.password.submit') }}">
        @csrf
        <input type="email" name="email" required placeholder="your@email.com" class="w-full border rounded-xl px-4 py-3 text-sm mb-4">
        <button type="submit" class="w-full py-3 rounded-xl bg-[#17611f] text-white font-bold">Send Reset Link</button>
      </form>
    @endif
  </div>
</main>
@endsection
