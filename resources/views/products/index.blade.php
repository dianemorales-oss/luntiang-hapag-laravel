@extends('layouts.app')
@section('title','Shop | Luntiang H.A.P.A.G.')
@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
  <h1 class="text-2xl font-black mb-2">Fresh Lettuce & Bundles</h1>
  <p class="text-sm text-[#5a7a5c] mb-6">All hydroponically grown — harvested only after you order</p>

  <div class="flex flex-wrap gap-3 mb-6">
    <form method="GET" class="flex gap-2">
      <input type="text" name="search" value="{{ $search }}" placeholder="Search lettuce..." class="border border-[rgba(27,94,32,0.12)] rounded-xl px-4 py-2 text-sm w-64 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
      <button type="submit" class="px-4 py-2 rounded-xl bg-[#17611f] text-white text-sm font-bold">Search</button>
    </form>
    <div class="flex flex-wrap gap-2">
      <a href="{{ route('products.index') }}" class="px-3 py-1.5 rounded-full text-xs font-bold border {{ !$category ? 'bg-[#17611f] text-white' : 'bg-white hover:bg-[#e8f5e9]' }}">All</a>
      @foreach($categories as $cat)
        <a href="{{ route('products.index', ['category'=>$cat->slug]) }}" class="px-3 py-1.5 rounded-full text-xs font-bold border {{ $category===$cat->slug ? 'bg-[#17611f] text-white' : 'bg-white hover:bg-[#e8f5e9]' }}">{{ $cat->name }}</a>
      @endforeach
    </div>
  </div>

  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
    @forelse($products as $p)
      <article class="product-card bg-white rounded-xl overflow-hidden border border-[rgba(27,94,32,0.08)]">
        <a href="{{ route('products.show', $p->slug) }}" class="block relative overflow-hidden">
          <img src="{{ asset($p->image ?: 'images/lettuce/hero-farm.png') }}" class="product-image aspect-square w-full object-cover" alt="{{ $p->name }}">
          @if($p->is_best_seller)<b class="absolute left-2 top-2 rounded bg-[#f9a825] px-2 py-1 text-[10px] font-black text-white">🏆 Best</b>@endif
        </a>
        <div class="p-3">
          <a href="{{ route('products.show', $p->slug) }}" class="block"><p class="text-sm font-bold hover:text-[#17611f] line-clamp-1">{{ $p->name }}</p></a>
          <p class="text-xs text-[#5a7a5c] truncate">{{ $p->variety }}</p>
          <p class="font-black text-[#17611f] mt-2">₱{{ number_format($p->price,2) }}</p>
          <button onclick="addToCart({{ $p->id }})" class="w-full mt-2 text-center text-xs font-bold py-1.5 rounded-lg bg-[#17611f] text-white hover:bg-[#14521a]">🛒 Add to Cart</button>
        </div>
      </article>
    @empty
      <p class="col-span-full text-center text-[#5a7a5c] py-10">No products found.</p>
    @endforelse
  </div>
</div>
<script>
function showToast(msg,ok){let t=document.getElementById('cartToast');if(!t){t=document.createElement('div');t.id='cartToast';t.className='fixed top-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-lg text-sm font-bold transition-all duration-300 translate-x-[120%] opacity-0 pointer-events-none';document.body.appendChild(t);}t.textContent=msg;t.className='fixed top-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-lg text-sm font-bold transition-all duration-300 '+(ok?'bg-[#e8f5e9] text-[#17611f] border border-[#c8e6c9]':'bg-red-50 text-red-700 border border-red-100');t.classList.remove('translate-x-[120%]','opacity-0');t.classList.add('translate-x-0','opacity-100');clearTimeout(t._t);t._t=setTimeout(()=>{t.classList.add('translate-x-[120%]','opacity-0');},3000);}
async function addToCart(id,qty){qty=qty||1;try{let r=await fetch('{{ route('cart.ajax') }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({action:'add',id:id,qty:qty})});let d=await r.json();showToast(d.message,d.success);if(d.success)location.reload();}catch(e){showToast('Network error',false)}}
</script>
@endsection
