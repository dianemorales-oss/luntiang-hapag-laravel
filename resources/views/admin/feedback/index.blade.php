@extends('admin.layouts.app')
@section('title','Feedback')
@section('header','Feedback')
@section('content')
<div class="bg-white rounded-xl border p-5">
  <h2 class="font-black mb-3">Feedback ({{ $feedbacks->count() }})</h2>
  @foreach($feedbacks as $f)
    <div class="border rounded-xl p-3 mb-2"><p class="font-bold text-sm">{{ $f->user->first_name ?? $f->guest_name ?? 'Guest' }} — {{ $f->rating }}/5</p><p class="text-xs text-[#5a7a5c]">{{ $f->created_at->format('M j, Y') }}</p><p class="text-sm mt-1">{{ $f->comments ?? $f->subject }}</p>
    <form method="POST" action="{{ route('admin.feedback.destroy',$f->id) }}" class="mt-2">@csrf @method('DELETE')<button class="text-xs text-red-500">Delete</button></form></div>
  @endforeach
</div>
@endsection
