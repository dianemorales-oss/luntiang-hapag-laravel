@extends('admin.layouts.app')
@section('title','Reviews')
@section('header','Reviews')
@section('content')
<div class="bg-white rounded-xl border p-5">
  <h2 class="font-black mb-3">Reviews ({{ $reviews->count() }})</h2>
  @foreach($reviews as $r)
    <div class="border rounded-xl p-3 mb-2">
      <p class="font-bold text-sm">{{ $r->product->name ?? '' }} — {{ $r->user->first_name ?? '' }} — {{ $r->rating }}/5 @if($r->is_verified)<span class="text-xs bg-[#e8f5e9] px-1.5 py-0.5 rounded">Verified</span>@endif</p>
      <p class="text-sm text-[#5a7a5c] mt-1">{{ $r->comment }}</p>
      <form method="POST" action="{{ route('admin.reviews.update',$r->id) }}" class="mt-2 flex gap-2">
        @csrf @method('PUT')
        <input type="text" name="admin_reply" value="{{ $r->admin_reply }}" placeholder="Admin reply" class="flex-1 border rounded-lg px-2 py-1 text-xs">
        <label class="text-xs flex items-center gap-1"><input type="checkbox" name="is_approved" {{ $r->is_approved?'checked':'' }}> Approved</label>
        <button class="px-3 py-1 rounded-lg bg-[#17611f] text-white text-xs">Save</button>
      </form>
      <form method="POST" action="{{ route('admin.reviews.destroy',$r->id) }}" class="mt-1">@csrf @method('DELETE')<button class="text-xs text-red-500">Delete</button></form>
    </div>
  @endforeach
</div>
@endsection
