@extends('layouts.app')
@section('title', $product->name.' | Luntiang H.A.P.A.G.')
@section('content')
<main class="max-w-7xl mx-auto px-6 py-8">
  <nav class="mb-6 text-sm"><a href="{{ route('home') }}" class="text-[#17611f] hover:underline">Home</a> / <a href="{{ route('products.index') }}" class="text-[#17611f] hover:underline">Shop</a> / <span class="text-[#5a7a5c]">{{ $product->name }}</span></nav>

  <div class="grid lg:grid-cols-2 gap-8">
    <div class="bg-white rounded-2xl overflow-hidden border">
      <img src="{{ asset($product->image ?: 'images/lettuce/hero-farm.png') }}" class="w-full aspect-square object-cover" alt="{{ $product->name }}">
    </div>
    <div>
      <h1 class="text-3xl font-black">{{ $product->name }}</h1>
      <p class="text-sm text-[#5a7a5c] mt-1">{{ $product->variety }} • {{ $product->unit }}</p>
      <p class="text-2xl font-black text-[#17611f] mt-4">₱{{ number_format($product->price,2) }}</p>
      <p class="text-sm text-[#5a7a5c] mt-2">{{ $product->plants_available }} plants available</p>
      <div class="mt-6 flex gap-3">
        <button onclick="addToCart({{ $product->id }})" class="px-6 py-3 rounded-xl bg-[#17611f] text-white font-bold hover:bg-[#14521a]">🛒 Add to Cart</button>
        <a href="{{ route('cart.index') }}" class="px-6 py-3 rounded-xl border font-bold hover:bg-[#e8f5e9]">View Cart</a>
      </div>

      @if($product->description)
      <div class="mt-6 bg-white rounded-xl border p-4">
        <h3 class="font-bold mb-2">Description</h3>
        <p class="text-sm text-[#5a7a5c]">{{ $product->description }}</p>
      </div>
      @endif

      <div class="mt-4 space-y-3">
        @if($product->best_for)
        <details class="bg-white rounded-xl border"><summary class="px-4 py-3 font-bold text-sm cursor-pointer">🌱 Best For</summary><div class="px-4 pb-3 text-sm text-[#5a7a5c]">{{ $product->best_for }}</div></details>
        @endif
        @if($product->storage_instructions)
        <details class="bg-white rounded-xl border"><summary class="px-4 py-3 font-bold text-sm cursor-pointer">❄️ Storage</summary><div class="px-4 pb-3 text-sm text-[#5a7a5c]">{{ $product->storage_instructions }}</div></details>
        @endif
      </div>
    </div>
  </div>

  <section id="reviews" class="mt-10">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-black">Customer Reviews @if($avg)<span class="font-normal text-sm text-[#5a7a5c] ml-2">{{ number_format($avg,1) }}/5 ({{ $totalReviews }})</span>@endif</h2>
    </div>

    @auth
    @if($canReview)
    <div class="bg-white rounded-xl border p-5 mb-4">
      <form method="POST" action="{{ route('reviews.store') }}" enctype="multipart/form-data" class="space-y-3">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <div><label class="text-sm font-bold">Rating</label>
          <div class="flex gap-1 mt-1" id="starRating">
            @for($i=1;$i<=5;$i++)<button type="button" data-star="{{ $i }}" class="star-btn text-2xl text-gray-300">★</button>@endfor
          </div>
          <input type="hidden" name="rating" id="ratingInput" value="5">
        </div>
        <textarea name="comment" rows="3" class="w-full border rounded-xl p-3 text-sm" placeholder="Share your experience..."></textarea>
        <input type="file" name="review_photos[]" multiple accept=".jpg,.jpeg,.png" class="w-full border rounded-xl p-2 text-sm">
        <button type="submit" class="px-5 py-2 rounded-xl bg-[#17611f] text-white text-sm font-bold">Submit Review</button>
      </form>
    </div>
    @endif
    @endauth

    <div class="space-y-3">
      @forelse($productReviews as $rev)
        <div class="bg-white rounded-xl border p-5">
          <div class="flex items-center gap-3 mb-2">
            <div class="w-9 h-9 rounded-full bg-[#e8f5e9] flex items-center justify-center font-bold text-sm text-[#17611f]">{{ strtoupper(substr($rev->first_name,0,1)) }}</div>
            <div><p class="font-bold text-sm">{{ $rev->first_name }}</p><span class="text-amber-400 text-xs">{{ str_repeat('★',$rev->rating).str_repeat('☆',5-$rev->rating) }}</span></div>
            <span class="ml-auto text-xs text-[#9e9e9e]">{{ $rev->created_at->format('M j, Y') }}</span>
          </div>
          @if($rev->comment)<p class="text-sm text-[#5a7a5c]">{{ $rev->comment }}</p>@endif
        </div>
      @empty
        <p class="text-center text-[#5a7a5c] py-6">No reviews yet.</p>
      @endforelse
    </div>
  </section>

  @if($relatedProducts->isNotEmpty())
  <section class="mt-10">
    <h2 class="text-xl font-black mb-4">You Might Also Like</h2>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      @foreach($relatedProducts as $rp)
        <a href="{{ route('products.show', $rp->slug) }}" class="bg-white rounded-xl border overflow-hidden">
          <img src="{{ asset($rp->image ?: 'images/lettuce/hero-farm.png') }}" class="w-full aspect-square object-cover">
          <div class="p-3"><p class="text-sm font-bold truncate">{{ $rp->name }}</p><p class="font-black text-[#17611f] text-sm">₱{{ number_format($rp->price,2) }}</p></div>
        </a>
      @endforeach
    </div>
  </section>
  @endif
</main>
<script>
document.querySelectorAll('.star-btn').forEach(b=>{b.addEventListener('click',()=>{let v=parseInt(b.dataset.star);document.getElementById('ratingInput').value=v;document.querySelectorAll('.star-btn').forEach((s,i)=>{s.classList.toggle('text-amber-400',i<v);s.classList.toggle('text-gray-300',i>=v);});});});
function showToast(msg,ok){let t=document.getElementById('cartToast');if(!t){t=document.createElement('div');t.id='cartToast';t.className='fixed top-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-lg text-sm font-bold transition-all duration-300 translate-x-[120%] opacity-0 pointer-events-none';document.body.appendChild(t);}t.textContent=msg;t.className='fixed top-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-lg text-sm font-bold transition-all duration-300 '+(ok?'bg-[#e8f5e9] text-[#17611f] border border-[#c8e6c9]':'bg-red-50 text-red-700 border border-red-100');t.classList.remove('translate-x-[120%]','opacity-0');t.classList.add('translate-x-0','opacity-100');clearTimeout(t._t);t._t=setTimeout(()=>{t.classList.add('translate-x-[120%]','opacity-0');},3000);}
async function addToCart(id,qty){qty=qty||1;try{let r=await fetch('{{ route('cart.ajax') }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({action:'add',id:id,qty:qty})});let d=await r.json();showToast(d.message,d.success);if(d.success)location.reload();}catch(e){showToast('Network error',false)}}
</script>
@endsection
