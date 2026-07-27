@extends('layouts.app')
@section('title', 'Products | Luntiang H.A.P.A.G.')
@section('content')

<main class="max-w-7xl mx-auto px-6 py-6">
  <div class="mb-5">
    <h1 class="text-2xl font-black">Fresh Hydroponic Lettuce</h1>
    <p class="text-sm text-[#5a7a5c] mt-1">All harvested-on-demand -- your lettuce stays growing until you order</p>
  </div>

  <!-- Search and Sort Bar -->
  <div class="flex items-center gap-3 mb-6">
    <form method="GET" action="{{ route('products.index') }}" class="flex-1 max-w-md">
      <div class="relative">
        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[#9e9e9e]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>
        </svg>
        <input name="search" value="{{ $search }}" placeholder="Search lettuce..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-[rgba(27,94,32,0.12)] text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
        @if ($category)
          <input type="hidden" name="category" value="{{ $category }}">
        @endif
        @if ($filter)
          <input type="hidden" name="filter" value="{{ $filter }}">
        @endif
      </div>
    </form>

    <select onchange="window.location.href='{{ route('products.index') }}?{{ http_build_query(request()->except('sort')) }}&sort='+this.value" class="px-4 py-2.5 rounded-xl text-sm font-bold border border-[rgba(27,94,32,0.12)] bg-white text-[#5a7a5c] cursor-pointer">
      <option value="featured" {{ $sort === 'featured' ? 'selected' : '' }}>Featured</option>
      <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }}>Price: Low - High</option>
      <option value="price_desc" {{ $sort === 'price_desc' ? 'selected' : '' }}>Price: High - Low</option>
      <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest</option>
      <option value="name" {{ $sort === 'name' ? 'selected' : '' }}>Name A-Z</option>
    </select>
    
    @if ($search || $category || $filter)
      <a href="{{ route('products.index') }}" class="text-sm font-bold text-[#17611f] hover:underline whitespace-nowrap">Clear</a>
    @endif
  </div>

  <!-- Recent Searches Row -->
  @if (!empty($recentSearches))
    <div class="mb-4 flex flex-wrap items-center gap-2 text-xs">
      <span class="text-[#9e9e9e] font-semibold">Recent:</span>
      @foreach ($recentSearches as $rs)
        <a href="?search={{ urlencode($rs) }}" class="px-2.5 py-1 rounded-full bg-[#e8f5e9] text-[#17611f] hover:bg-[#c8e6c9] font-semibold">{{ $rs }}</a>
      @endforeach
    </div>
  @endif

  <!-- Products Grid -->
  @if ($products->isEmpty())
    <div class="text-center py-16">
      <p class="text-xl font-bold mb-2">No products found</p>
      <p class="text-[#5a7a5c] mb-4">Try a different search term.</p>
      <a href="{{ route('products.index') }}" class="inline-flex px-6 py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a]">View All Products</a>
    </div>
  @else
    <p class="text-sm text-[#5a7a5c] mb-4">{{ $products->count() }} product{{ $products->count() !== 1 ? 's' : '' }} found</p>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
      @foreach ($products as $p)
        <article class="product-card bg-white rounded-xl overflow-hidden border border-[rgba(27,94,32,0.08)]">
          <a href="{{ route('products.show', $p->slug) }}" class="block relative overflow-hidden">
            <img src="{{ asset($p->image ?: 'images/lettuce/hero-farm.png') }}" class="product-image aspect-square w-full object-cover" alt="{{ $p->name }}">
            @if ($p->is_best_seller)
              <b class="absolute left-2 top-2 rounded bg-[#f9a825] px-2 py-1 text-[10px] font-black text-white">Best Seller</b>
            @endif
            @if ($p->plants_available > 0 && $p->plants_available <= 20)
              <span class="absolute right-2 bottom-2 rounded bg-red-500/85 px-2 py-1 text-[10px] font-black text-white">Limited</span>
            @endif
          </a>
          <div class="p-3.5">
            <a href="{{ route('products.show', $p->slug) }}" class="block">
              <p class="text-sm font-bold text-[#1a2e1c] hover:text-[#17611f]">{{ $p->name }}</p>
            </a>
            <p class="text-xs text-[#5a7a5c]">{{ $p->variety ?: $p->unit }}</p>
            
            @if ($p->avg_rating > 0)
              <div class="flex items-center gap-1 mt-1">
                <span class="text-amber-400 text-[10px]">
                  @for ($i = 1; $i <= 5; $i++)
                    {{ $i <= round($p->avg_rating) ? '★' : '☆' }}
                  @endfor
                </span>
                <span class="text-[10px] text-[#9e9e9e]">({{ $p->review_count }})</span>
              </div>
            @endif
            
            <div class="flex items-center justify-between mt-2 mb-1">
              <p class="font-black text-[#17611f]">₱{{ number_format((float)$p->price, 2) }}</p>
            </div>
            <button onclick="addToCart({{ $p->id }})" class="block w-full text-center text-xs font-bold py-1.5 rounded-lg bg-[#17611f] text-white hover:bg-[#14521a] cursor-pointer">Add to Cart</button>
          </div>
        </article>
      @endforeach
    </div>
  @endif
</main>

@push('scripts')
<script>
// Scroll Position Memory
(function(){
  var key = 'lh_scroll_' + location.pathname;
  window.addEventListener('beforeunload', function(){ sessionStorage.setItem(key, window.scrollY); });
  var sy = sessionStorage.getItem(key);
  if (sy) { window.scrollTo(0, parseInt(sy)); sessionStorage.removeItem(key); }
})();

// AJAX Add to Cart
var toast = document.createElement('div');
toast.id = 'cartToast';
toast.className = 'fixed top-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-lg text-sm font-bold transition-all duration-300 translate-x-[120%] opacity-0 pointer-events-none';
document.body.appendChild(toast);

function showToast(msg, ok) {
  toast.textContent = msg;
  toast.className = 'fixed top-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-lg text-sm font-bold transition-all duration-300 ' + (ok ? 'bg-[#e8f5e9] text-[#17611f] border border-[#c8e6c9]' : 'bg-red-50 text-red-700 border border-red-100');
  toast.classList.remove('translate-x-[120%]', 'opacity-0');
  toast.classList.add('translate-x-0', 'opacity-100');
  clearTimeout(toast._t);
  toast._t = setTimeout(function(){
    toast.classList.add('translate-x-[120%]', 'opacity-0');
  }, 3000);
}

function updateCartCount(count) {
  var badge = document.querySelector('a[href*="cart"] span');
  if (count > 0) {
    if (badge) {
      badge.textContent = count;
    } else {
      var a = document.querySelector('a[href*="cart"]');
      if (a) {
        var s = document.createElement('span');
        s.className = 'absolute -top-1 -right-1 w-5 h-5 rounded-full bg-[#17611f] text-white text-[10px] font-bold flex items-center justify-center';
        s.textContent = count;
        a.appendChild(s);
      }
    }
  } else {
    if (badge) badge.remove();
  }
}

async function addToCart(id, qty) {
  qty = qty || 1;
  try {
    var r = await fetch('{{ route('cart.ajax') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({action: 'add', id: id, qty: qty})
    });
    var d = await r.json();
    showToast(d.message, d.success);
    if (d.success) {
      updateCartCount(d.count);
    }
  } catch(e) {
    showToast('Network error', false);
  }
}
</script>
@endpush

@endsection
