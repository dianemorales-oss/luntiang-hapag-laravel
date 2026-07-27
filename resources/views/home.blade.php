@extends('layouts.app')
@section('title','Luntiang H.A.P.A.G. | Fresh Hydroponic Harvest-on-Demand Lettuce')
@section('content')
<section class="max-w-7xl mx-auto px-6 py-6">
  <div class="relative h-[340px] sm:h-[380px] overflow-hidden rounded-2xl mb-8">
    <img src="{{ asset('images/lettuce/hero-farm.png') }}" class="absolute inset-0 h-full w-full object-cover object-center" alt="Hydroponic Lettuce Farm">
    <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/50 to-black/10"></div>
    <div class="relative flex h-full flex-col justify-center px-6 sm:px-10 text-white">
      <span class="mb-3 inline-flex w-fit items-center gap-1.5 rounded-full bg-[#17611f]/85 px-3 py-1 text-xs font-black">100% Hydroponic · Harvest-on-Demand</span>
      <h1 class="max-w-[520px] text-[26px] sm:text-[32px] font-black leading-[1.2] tracking-[-.5px]">Harvested Only After You Order</h1>
      <p class="mt-3 max-w-[460px] text-sm sm:text-base text-white/90">Farm-to-table freshness — lettuce stays growing until your order is confirmed. Same-day harvest, pack, and delivery.</p>
      <div class="mt-5 flex flex-wrap gap-3">
        <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-white text-[#17611f] px-5 py-2.5 text-sm font-black hover:bg-[#e8f5e9] transition-colors">🛍️ Shop Now</a>
        <a href="{{ route('about') }}" class="inline-flex items-center gap-2 rounded-xl bg-white/15 text-white px-5 py-2.5 text-sm font-bold hover:bg-white/25 transition-colors">Learn More</a>
      </div>
    </div>
  </div>

  @if($activeCoupons->isNotEmpty())
  <div class="mb-10">
    <div class="flex items-center justify-between mb-4"><h2 class="text-xl font-black">🎟️ Claimable Coupons</h2></div>
    <div class="grid gap-4 sm:grid-cols-3" id="couponSection">
      @foreach($activeCoupons as $c)
        @php
          $alreadyClaimed = in_array($c->id, $claimedIds);
          $discountLabel = $c->discount_type === 'percentage' ? $c->discount_value.'% Off' : '₱'.number_format($c->discount_value,2).' Off';
          $expiry = $c->expires_at ? date('M j, Y', strtotime($c->expires_at)) : 'No expiry';
        @endphp
        <div class="rounded-2xl border p-5 bg-white hover:shadow-md transition-all">
          <div class="flex items-start justify-between mb-3">
            <div><p class="text-xs font-black uppercase text-[#17611f]">{{ $c->code }}</p><h3 class="mt-1 text-lg font-black text-[#1a2e1c]">{{ $discountLabel }}</h3></div>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#e8f5e9] text-[#17611f]">{{ $expiry }}</span>
          </div>
          <p class="text-sm text-[#5a7a5c] mb-1">{{ $c->description }}</p>
          @if($c->min_order > 0)<p class="text-xs text-[#9e9e9e] mb-3">Min. purchase: ₱{{ number_format($c->min_order,2) }}</p>@endif
          @if($isLoggedIn)
            <form method="POST" action="{{ route('coupons.claim') }}">
              @csrf
              <input type="hidden" name="promotion_id" value="{{ $c->id }}">
              <button type="submit" class="w-full mt-2 py-2 rounded-xl text-sm font-bold transition-colors {{ $alreadyClaimed?'bg-gray-100 text-[#9e9e9e] cursor-not-allowed':'bg-[#17611f] text-white hover:bg-[#14521a]' }}" {{ $alreadyClaimed?'disabled':'' }}>
                {{ $alreadyClaimed?'✓ Claimed':'🎟️ Claim Coupon' }}
              </button>
            </form>
          @else
            <a href="{{ route('login') }}" class="block w-full mt-2 py-2 rounded-xl bg-[#17611f] text-white text-sm font-bold text-center hover:bg-[#14521a]">🎟️ Claim Coupon</a>
          @endif
        </div>
      @endforeach
    </div>
  </div>
  @endif

  <section>
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-2xl font-black">Fresh Lettuce & Bundles</h2>
        <p class="text-sm text-[#5a7a5c] mt-1">All hydroponically grown — harvested only after you order</p>
      </div>
      <a href="{{ route('products.index') }}" class="text-sm font-bold text-[#17611f] hover:underline">View All →</a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
      @foreach($featured as $p)
        @php
          $pid = $p->id ?? 0;
          $pslug = $p->slug ?? \Str::slug($p->name);
          $pimg = $p->image ?? 'images/lettuce/hero-farm.png';
          $pname = $p->name ?? '';
          $pvariety = $p->variety ?? $p->unit ?? '';
          $pprice = (float)($p->price ?? 0);
          $pbest = $p->is_best_seller ?? false;
        @endphp
        <article class="product-card bg-white rounded-xl overflow-hidden border border-[rgba(27,94,32,0.08)]">
          <a href="{{ route('products.show', $pslug) }}" class="block relative overflow-hidden">
            <img src="{{ asset($pimg) }}" onerror="this.onerror=null;this.src='{{ asset('images/lettuce/hero-farm.png') }}';" class="product-image aspect-square w-full object-cover" alt="{{ $pname }}">
            @if($p->is_new ?? false)<b class="absolute left-2 top-2 rounded bg-[#17611f] px-2 py-1 text-[10px] font-black text-white">New</b>@elseif($pbest)<b class="absolute left-2 top-2 rounded bg-[#f9a825] px-2 py-1 text-[10px] font-black text-white">🏆 Best</b>@endif
          </a>
          <div class="p-3">
            <a href="{{ route('products.show', $pslug) }}" class="block"><p class="text-sm font-bold hover:text-[#17611f] transition-colors line-clamp-1">{{ $pname }}</p></a>
            <p class="text-xs text-[#5a7a5c] truncate">{{ $pvariety }}</p>
            <div class="flex items-center justify-between mt-2"><p class="font-black text-[#17611f]">₱{{ number_format($pprice, 2) }}</p></div>
            <button onclick="addToCart({{ $pid }})" class="block w-full mt-2 text-center text-xs font-bold py-1.5 rounded-lg bg-[#17611f] text-white hover:bg-[#14521a] transition-colors cursor-pointer">🛒 Add to Cart</button>
          </div>
        </article>
      @endforeach
    </div>
  </section>
</section>

<section class="bg-white border-y border-[rgba(27,94,32,0.06)] py-10 mt-10">
  <div class="max-w-7xl mx-auto px-6 text-center">
    <h2 class="text-2xl font-black mb-8">How Harvest-on-Demand Works</h2>
    <div class="flex flex-wrap items-start justify-center gap-2 lg:gap-3">
      @foreach([['🛒','You Order','Browse & place order'],['✅','Confirmed','Payment verified'],['✂️','Harvest','Cut within 1–3 hrs'],['📦','Pack','Freshly packed'],['🏠','Deliver','Same-day']] as $step)
        <div class="bg-[#f4faf5] rounded-xl p-4 text-center w-[100px] sm:w-[120px]">
          <div class="w-10 h-10 rounded-full bg-[#e8f5e9] flex items-center justify-center mx-auto mb-2 text-xl">{{ $step[0] }}</div>
          <p class="font-black text-xs">{{ $step[1] }}</p>
          <p class="text-[10px] text-[#5a7a5c] mt-0.5">{{ $step[2] }}</p>
        </div>
        @if($step[0] !== '🏠')<span class="self-center text-[#c8e6c9] text-xl font-black">→</span>@endif
      @endforeach
    </div>
  </div>
</section>

<section class="max-w-7xl mx-auto px-6 py-10">
  <div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-6">
      <h3 class="font-black text-lg mb-2">🌿 About Our Farm</h3>
      <p class="text-sm text-[#5a7a5c] leading-relaxed mb-3">Luntiang H.A.P.A.G. grows 8 hydroponic lettuce varieties in Nostalji Subdivision, Dasmariñas, Cavite. Chemical-free, soil-free, and harvested only after you order.</p>
      <a href="{{ route('about') }}" class="inline-flex text-sm font-bold text-[#17611f] hover:underline">Learn More →</a>
    </div>
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-6">
      <h3 class="font-black text-lg mb-2">📦 Delivery & Pick-Up</h3>
      <p class="text-sm text-[#5a7a5c] leading-relaxed mb-3">FREE delivery within Nostalji Subdivision. P50 fee for outside areas. Same-day delivery for orders before 2 PM. Pick-up ready in 1-3 hours.</p>
      <a href="{{ route('products.index') }}" class="inline-flex text-sm font-bold text-[#17611f] hover:underline">Shop Now →</a>
    </div>
  </div>
</section>

<script>
function showToast(msg,ok){let t=document.getElementById('cartToast');if(!t){t=document.createElement('div');t.id='cartToast';t.className='fixed top-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-lg text-sm font-bold transition-all duration-300 translate-x-[120%] opacity-0 pointer-events-none';document.body.appendChild(t);}t.textContent=msg;t.className='fixed top-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-lg text-sm font-bold transition-all duration-300 '+(ok?'bg-[#e8f5e9] text-[#17611f] border border-[#c8e6c9]':'bg-red-50 text-red-700 border border-red-100');t.classList.remove('translate-x-[120%]','opacity-0');t.classList.add('translate-x-0','opacity-100');clearTimeout(t._t);t._t=setTimeout(()=>{t.classList.add('translate-x-[120%]','opacity-0');},3000);}
function updateCartCount(count){let b=document.querySelector('a[href*="cart"] span');if(count>0){if(b){b.textContent=count}else{let a=document.querySelector('a[href*="cart"]');if(a){let s=document.createElement('span');s.className='absolute -top-1 -right-1 w-5 h-5 rounded-full bg-[#17611f] text-white text-[10px] font-bold flex items-center justify-center';s.textContent=count;a.appendChild(s)}}}else{if(b)b.remove()}}
async function addToCart(id,qty){qty=qty||1;try{let r=await fetch('{{ route('cart.ajax') }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({action:'add',id:id,qty:qty})});let d=await r.json();showToast(d.message,d.success);if(d.success)updateCartCount(d.count)}catch(e){showToast('Network error',false)}}
</script>
@endsection
