@extends('admin.layouts.app')
@section('title', 'Feedback Management')
@section('header', 'Feedback Management')
@section('content')

<div class="space-y-5">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap items-center gap-2">
      <a href="{{ route('admin.feedback.index') }}"
         class="px-5 py-2.5 rounded-full text-[13px] font-semibold transition-colors {{ $ratingFilter === 'all' ? 'bg-[#17611f] text-white shadow-sm' : 'bg-white border border-[rgba(27,94,32,0.12)] text-[#5a7a5c] hover:bg-gray-50' }}">
        All
      </a>

      @for ($rating = 5; $rating >= 1; $rating--)
        <a href="{{ route('admin.feedback.index', ['rating' => $rating]) }}"
           class="px-5 py-2.5 rounded-full text-[13px] font-semibold transition-colors {{ (string) $ratingFilter === (string) $rating ? 'bg-[#17611f] text-white shadow-sm' : 'bg-white border border-[rgba(27,94,32,0.12)] text-[#5a7a5c] hover:bg-gray-50' }}">
          {{ $rating }}★
        </a>
      @endfor
    </div>

    <div class="text-[13px] text-[#5a7a5c]">
      {{ $totalCount }} total · avg
      <span class="font-semibold text-[#1a2e1c]">{{ $totalCount > 0 ? number_format($avgRating, 1) : '—' }}</span> ★
    </div>
  </div>

  <div class="grid grid-cols-1 gap-4">
    @if ($feedbacks->isEmpty())
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center text-sm text-[#9e9e9e]">
        No feedback submitted yet.
      </div>
    @else
      @foreach ($feedbacks as $f)
        @php
          $isGuest = empty($f->user_id);
          $displayName = $isGuest
              ? ($f->guest_name ?: 'Guest')
              : trim(($f->user->first_name ?? '') . ' ' . ($f->user->last_name ?? ''));
          $displayName = $displayName !== '' ? $displayName : 'Customer';
          $displayEmail = $isGuest ? ($f->guest_email ?: '—') : ($f->user->email ?? '—');
          $rating = max(1, min(5, (int) $f->rating));
        @endphp

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
          <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-3 mb-2 flex-wrap">
                <p class="text-[12px] text-[#9e9e9e]">
                  {{ optional($f->created_at)->format('M j, Y') }}
                </p>
                <span class="text-amber-400 text-sm tracking-wide">
                  {{ str_repeat('★', $rating) }}{{ str_repeat('☆', 5 - $rating) }}
                </span>
                @if ($isGuest)
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 text-[#5a7a5c]">
                    Guest
                  </span>
                @endif
              </div>

              @if (!empty($f->subject))
                <h3 class="font-semibold text-[#1a2e1c] mb-1">{{ $f->subject }}</h3>
              @endif

              <p class="text-[13px] text-[#5a7a5c] mb-3 leading-relaxed">
                @if (!empty($f->comments))
                  {!! nl2br(e($f->comments)) !!}
                @else
                  <span class="text-[#9e9e9e] italic">No comment left.</span>
                @endif
              </p>

              <p class="text-[12px] text-[#5a7a5c]">
                From: <span class="font-medium text-[#1a2e1c]">{{ $displayName }}</span> · {{ $displayEmail }}
              </p>
            </div>

            <form method="POST"
                  action="{{ route('admin.feedback.destroy', $f->id) }}"
                  onsubmit="return confirm('Delete this feedback entry? This cannot be undone.');">
              @csrf
              @method('DELETE')
              <button type="submit" class="px-4 py-2 rounded-full border border-red-200 text-red-600 text-[13px] font-medium hover:bg-red-50 transition-colors">
                Delete
              </button>
            </form>
          </div>
        </div>
      @endforeach
    @endif
  </div>
</div>

@endsection
