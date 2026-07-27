@extends('layouts.app')
@section('title','Change Password')
@section('content')
<main class="max-w-lg mx-auto px-6 py-10">
  <h1 class="text-2xl font-black mb-6">Change Password</h1>
  <form method="POST" action="{{ route('profile.change-password.submit') }}" class="bg-white rounded-xl border p-6 space-y-4">
    @csrf
    <div><label class="text-sm font-bold">Current Password</label><input type="password" name="current_password" required class="w-full border rounded-xl px-3 py-2 text-sm mt-1"></div>
    <div><label class="text-sm font-bold">New Password</label><input type="password" name="new_password" required class="w-full border rounded-xl px-3 py-2 text-sm mt-1"></div>
    <div><label class="text-sm font-bold">Confirm New Password</label><input type="password" name="confirm_password" required class="w-full border rounded-xl px-3 py-2 text-sm mt-1"></div>
    <button type="submit" class="w-full py-3 rounded-xl bg-[#17611f] text-white font-bold">Change Password</button>
  </form>
</main>
@endsection
