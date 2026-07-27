@extends('admin.layouts.app')
@section('title', 'Product Reviews | Admin')
@section('header', 'Product Reviews')
@section('content')

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border p-4 shadow-sm">
      <p class="text-xs text-[#5a7a5c] font-bold">Total Reviews</p>
      <p class="text-2xl font-black mt-1 text-[#1a2e1c]">{{ $totalReviews }}</p>
    </div>
    <div class="bg-white rounded-xl border p-4 shadow-sm">
      <p class="text-xs text-[#5a7a5c] font-bold">Average Rating</p>
      <p class="text-2xl font-black mt-1 text-amber-500">{{ $avgRating }} ★</p>
    </div>
    <div class="bg-white rounded-xl border p-4 shadow-sm">
      <p class="text-xs text-[#5a7a5c] font-bold">Needs Reply</p>
      <p class="text-2xl font-black mt-1 text-blue-600">{{ $pendingReplies }}</p>
    </div>
  </div>

  <!-- Top Rated Products -->
  @if ($topProducts->isNotEmpty())
    <div class="mb-6 bg-white rounded-xl border p-5 shadow-sm">
      <h3 class="font-black text-sm mb-3 text-[#1a2e1c]">Top Rated Products</h3>
      <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-xs">
        @foreach ($topProducts as $tp)
          <div class="bg-[#f4faf5] rounded-lg p-3 text-center border border-[rgba(27,94,32,0.04)]">
            <p class="font-bold text-[#1a2e1c] truncate">{{ $tp->name }}</p>
            <p class="text-amber-500 font-black text-sm mt-1">{{ $tp->avg_rating }} ★</p>
            <p class="text-[#9e9e9e] font-semibold mt-0.5">{{ $tp->cnt }} reviews</p>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  <!-- Filters -->
  <div class="bg-white rounded-xl border p-4 mb-4 flex flex-wrap items-center gap-3 shadow-sm">
    <form method="GET" action="{{ route('admin.reviews.index') }}" class="flex flex-wrap items-center gap-3 flex-1">
      <select name="product" class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white text-[#5a7a5c] font-bold" onchange="this.form.submit()">
        <option value="">All Products</option>
        @foreach ($allProducts as $ap)
          <option value="{{ $ap->id }}" {{ $productId == $ap->id ? 'selected' : '' }}>{{ $ap->name }}</option>
        @endforeach
      </select>
      
      <input name="search" value="{{ $search }}" placeholder="Search reviews..." class="border rounded-lg px-3 py-2 text-sm w-48 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white">
      
      <select name="rating" class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white text-[#5a7a5c] font-bold" onchange="this.form.submit()">
        <option value="">All Ratings</option>
        @for ($i = 5; $i >= 1; $i--)
          <option value="{{ $i }}" {{ $ratingFilter == (string)$i ? 'selected' : '' }}>{{ $i }} ★</option>
        @endfor
      </select>
      
      <select name="replied" class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white text-[#5a7a5c] font-bold" onchange="this.form.submit()">
        <option value="">All Replies</option>
        <option value="no" {{ $filterReplied === 'no' ? 'selected' : '' }}>Not Replied</option>
        <option value="yes" {{ $filterReplied === 'yes' ? 'selected' : '' }}>Replied</option>
      </select>
      
      <button type="submit" class="px-4 py-2 rounded-lg bg-[#17611f] text-white text-xs font-bold hover:bg-[#14521a] shadow-sm">Filter</button>
      <a href="{{ route('admin.reviews.index') }}" class="px-4 py-2 rounded-lg border text-xs font-bold hover:bg-gray-50">Clear</a>
    </form>
  </div>

  <!-- Reviews List -->
  @if ($reviews->isEmpty())
    <div class="text-center py-16 bg-white rounded-xl border shadow-sm">
      <p class="text-[#5a7a5c] font-semibold">No reviews found.</p>
    </div>
  @else
    <div class="space-y-4">
      @foreach ($reviews as $r)
        @php
          $revPhotos = !empty($r->photos) ? json_decode($r->photos, true) : [];
        @endphp
        <div class="bg-white rounded-xl border p-5 shadow-sm">
          <div class="flex items-start justify-between mb-3 flex-wrap gap-2">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-[#e8f5e9] flex items-center justify-center font-bold text-sm text-[#17611f]">
                {{ strtoupper(substr($r->user->first_name ?? 'A', 0, 1)) }}
              </div>
              <div>
                <p class="font-bold text-sm text-[#1a2e1c]">{{ $r->user->first_name ?? 'Anonymous' }} {{ $r->user->last_name ?? '' }}</p>
                <div class="flex items-center gap-2 mt-0.5">
                  <span class="text-amber-400 text-xs">
                    @for ($i = 1; $i <= 5; $i++)
                      {{ $i <= $r->rating ? '★' : '☆' }}
                    @endfor
                  </span>
                  @if ($r->is_verified)
                    <span class="text-[10px] bg-[#e8f5e9] text-[#17611f] px-1.5 py-0.5 rounded font-bold">✓ Verified</span>
                  @endif
                </div>
              </div>
            </div>
            <div class="text-right text-xs text-[#9e9e9e]">
              <p class="font-bold text-[#1a2e1c]">{{ $r->product->name ?? 'Unknown Product' }}</p>
              @if ($r->order)
                <p class="mt-0.5">Order: {{ $r->order->order_number }}</p>
              @endif
              <p class="mt-0.5">{{ $r->created_at->format('M j, Y g:i A') }}</p>
            </div>
          </div>
          
          @if ($r->comment)
            <p class="text-sm text-[#5a7a5c] mb-2">{!! nl2br(e($r->comment)) !!}</p>
          @endif
          
          @if (!empty($revPhotos))
            <div class="flex gap-2 mb-3 flex-wrap">
              @foreach ($revPhotos as $photo)
                <img src="{{ asset($photo) }}" class="w-16 h-16 object-cover rounded-lg border">
              @endforeach
            </div>
          @endif

          <!-- Admin reply -->
          @if (!empty($r->admin_reply))
            <div class="mt-3 pl-4 border-l-2 border-[#17611f] bg-[#f4faf5] rounded-r-lg p-3">
              <p class="text-xs font-bold text-[#17611f] mb-1">🌱 Your Reply:</p>
              <p class="text-sm text-[#5a7a5c]">{!! nl2br(e($r->admin_reply)) !!}</p>
              <p class="text-[11px] text-[#9e9e9e] mt-1">{{ date('M j, Y g:i A', strtotime($r->admin_replied_at)) }}</p>
              <div class="flex gap-2 mt-2">
                <button onclick="toggleReplyForm({{ $r->id }},'edit')" class="text-xs text-[#17611f] font-bold hover:underline">Edit</button>
                <form method="POST" action="{{ route('admin.reviews.index') }}" class="inline" onsubmit="return confirm('Delete this reply?')">
                  @csrf
                  <input type="hidden" name="review_id" value="{{ $r->id }}">
                  <button type="submit" name="delete_reply" value="1" class="text-xs text-red-500 font-bold hover:underline">Delete</button>
                </form>
              </div>
            </div>
          @else
            <div class="mt-3">
              <button onclick="toggleReplyForm({{ $r->id }},'new')" class="text-sm font-bold text-[#17611f] hover:underline">💬 Reply to this review</button>
            </div>
          @endif

          <!-- Reply form -->
          <div id="replyForm-{{ $r->id }}" class="hidden mt-3 border-t border-[rgba(27,94,32,0.08)] pt-3">
            <form method="POST" action="{{ route('admin.reviews.index') }}">
              @csrf
              <input type="hidden" name="review_id" value="{{ $r->id }}">
              <textarea name="admin_reply" rows="2" class="w-full border rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white" placeholder="Write your reply...">{{ $r->admin_reply }}</textarea>
              <div class="flex gap-2 mt-2">
                <button type="submit" name="save_reply" value="1" class="px-4 py-1.5 rounded-lg bg-[#17611f] text-white text-xs font-bold hover:bg-[#14521a]">Save Reply</button>
                <button type="button" onclick="toggleReplyForm({{ $r->id }},'hide')" class="px-4 py-1.5 rounded-lg border text-xs font-bold hover:bg-[#e8f5e9]">Cancel</button>
              </div>
            </form>
          </div>

          <div class="mt-3 text-right">
            <form method="POST" action="{{ route('admin.reviews.index') }}" class="inline" onsubmit="return confirm('Delete this review?')">
              @csrf
              <input type="hidden" name="review_id" value="{{ $r->id }}">
              <button type="submit" name="delete_review" value="1" class="text-xs text-red-400 hover:text-red-600">🗑 Delete Review</button>
            </form>
          </div>
        </div>
      @endforeach
    </div>
  @endif

@endsection

@push('scripts')
<script>
function toggleReplyForm(id,action){
  var f=document.getElementById('replyForm-'+id);
  if(action==='hide'){f.classList.add('hidden')}else{f.classList.toggle('hidden')}
}
</script>
@endpush
