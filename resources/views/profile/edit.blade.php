@extends('layouts.app')
@section('title','Edit Profile')
@section('content')
<main class="max-w-2xl mx-auto px-6 py-10">
  <h1 class="text-2xl font-black mb-6">Edit Profile</h1>
  <form method="POST" action="{{ route('profile.update') }}" class="bg-white rounded-xl border p-6 space-y-4">
    @csrf
    <div class="grid grid-cols-2 gap-3">
      <div><label class="text-sm font-bold">First Name</label><input type="text" name="first_name" value="{{ $user->first_name }}" required class="w-full border rounded-xl px-3 py-2 text-sm mt-1"></div>
      <div><label class="text-sm font-bold">Last Name</label><input type="text" name="last_name" value="{{ $user->last_name }}" required class="w-full border rounded-xl px-3 py-2 text-sm mt-1"></div>
    </div>
    <div><label class="text-sm font-bold">Phone</label><input type="text" name="phone" value="{{ $user->phone }}" required class="w-full border rounded-xl px-3 py-2 text-sm mt-1"></div>
    <div><label class="text-sm font-bold">Address</label><textarea name="address" required class="w-full border rounded-xl px-3 py-2 text-sm mt-1" rows="3">{{ $user->address }}</textarea></div>
    <button type="submit" class="w-full py-3 rounded-xl bg-[#17611f] text-white font-bold">Update Profile</button>
  </form>
</main>
@endsection
