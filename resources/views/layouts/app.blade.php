<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@yield('title', 'Luntiang H.A.P.A.G. | Fresh Hydroponic Harvest-on-Demand Lettuce')</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/design-system.css') }}">
  <style>
    body { font-family: 'Nunito', sans-serif; background: #f4faf5; }
    html { scroll-behavior: smooth; }
    .product-card { transition: all .25s ease; }
    .product-card:hover { box-shadow: 0 8px 28px rgba(27,94,32,.1); transform: translateY(-3px); }
    .product-image { transition: transform .35s ease; }
    .product-card:hover .product-image { transform: scale(1.06); }
  </style>
  @stack('styles')
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c] min-h-screen flex flex-col">
  <!-- Top Announcement Bar -->
  <div class="bg-[#17611f] px-4 py-[7px] text-center text-xs font-bold text-white">
    Message us if you want us to be your supplier &nbsp;|&nbsp; Harvest-on-Demand - Same-Day Delivery &nbsp;|&nbsp; Luntiang H.A.P.A.G.
  </div>

  <!-- Header -->
  <header class="bg-white border-b border-[rgba(27,94,32,0.12)] sticky top-0 z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 h-[86px] flex items-center gap-4">
      <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3">
        <img src="{{ asset('images/lettuce/logo-cropped.png') }}" alt="Luntiang H.A.P.A.G." class="h-[60px] w-auto object-contain">
      </a>
      <span class="hidden h-5 border-l border-[rgba(27,94,32,0.12)] lg:block"></span>
      <span class="hidden shrink-0 text-sm font-semibold text-[#5a7a5c] lg:block">100% Hydroponic Lettuce Farm</span>

      <nav class="hidden md:flex items-center gap-6 text-[15px] font-semibold text-[#1a2e1c] ml-auto mr-2">
        <a href="{{ route('home') }}" class="hover:text-[#17611f] transition-colors">Home</a>
        <a href="{{ route('products.index') }}" class="hover:text-[#17611f] transition-colors">Shop</a>
        <a href="{{ route('faq') }}" class="hover:text-[#17611f] transition-colors">FAQ</a>
        <a href="{{ route('contact') }}" class="hover:text-[#17611f] transition-colors">Contact</a>
        <a href="{{ route('about') }}" class="hover:text-[#17611f] transition-colors">About</a>
      </nav>

      <div class="flex items-center gap-3">
        <a href="{{ route('cart.index') }}" class="relative p-2 rounded-xl hover:bg-[#e8f5e9] transition-colors" title="Shopping Cart">
          <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3h2l2.4 11.4a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 7H6"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
          @php $cartCount = count(array_unique(array_column(session('cart', []), 'id'))); @endphp
          @if($cartCount > 0)
            <span class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-[#17611f] text-white text-[10px] font-bold flex items-center justify-center">{{ $cartCount }}</span>
          @endif
        </a>

        @if(session()->has('user_id'))
          <a href="{{ route('profile.index') }}" class="hidden sm:inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl border border-[rgba(27,94,32,0.12)] text-sm font-bold text-[#1a2e1c] hover:bg-[#e8f5e9] transition-colors">
             {{ session('first_name') }}
          </a>
          <a href="{{ route('logout') }}" class="px-5 py-2.5 rounded-2xl bg-[#17611f] text-white text-sm font-bold hover:opacity-90 transition-opacity">Logout</a>
        @else
          <a href="{{ route('login') }}" class="hidden sm:inline-block px-5 py-2.5 rounded-2xl border border-[rgba(27,94,32,0.12)] text-sm font-bold text-[#1a2e1c] hover:bg-[#e8f5e9] transition-colors">Login</a>
          <a href="{{ route('register') }}" class="px-5 py-2.5 rounded-2xl bg-[#17611f] text-white text-sm font-bold hover:opacity-90 transition-opacity">Register</a>
        @endif
      </div>
    </div>
  </header>

  <main class="flex-1">
    @if(session('success'))
      <div class="max-w-7xl mx-auto px-6 pt-4"><div class="rounded-xl bg-[#e8f5e9] border border-[#c8e6c9] px-4 py-3 text-sm text-[#1b5e20]">{{ session('success') }}</div></div>
    @endif
    @if(session('error'))
      <div class="max-w-7xl mx-auto px-6 pt-4"><div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div></div>
    @endif
    @yield('content')
  </main>

  <footer class="mt-16 bg-[#17611f] px-6 pb-8 pt-14 text-white">
    <div class="mx-auto grid max-w-7xl gap-10 sm:grid-cols-3">
      <div>
        <img src="{{ asset('images/lettuce/logo-cropped.png') }}" class="h-[68px] w-[190px] rounded-xl bg-white p-1 object-contain" alt="Luntiang Hapag">
        <p class="mt-3 text-sm text-white/60">Health Awareness and Professional Advisory Group</p>
        <p class="mt-1 text-sm text-white/60">Hydroponic Harvest-on-Demand Lettuce Farm</p>
        <div class="flex items-center gap-3 mt-4">
          <a href="https://www.facebook.com/elijaspride.kennel" target="_blank" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition-colors" title="Facebook">
            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
          </a>
          <a href="https://maps.app.goo.gl/mZ2NZzbCeGwh2M27A" target="_blank" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition-colors" title="Google Maps">
            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
          </a>
        </div>
      </div>
      <div>
        <p class="mb-3 font-black text-sm">QUICK LINKS</p>
        <div class="space-y-1.5">
          <a href="{{ route('home') }}" class="block text-sm text-white/70 hover:text-white">Home</a>
          <a href="{{ route('products.index') }}" class="block text-sm text-white/70 hover:text-white">Shop Products</a>
          <a href="{{ route('about') }}" class="block text-sm text-white/70 hover:text-white">About Our Farm</a>
          <a href="{{ route('faq') }}" class="block text-sm text-white/70 hover:text-white">FAQ</a>
          <a href="{{ route('contact') }}" class="block text-sm text-white/70 hover:text-white">Contact Support</a>
          <a href="{{ route('privacy') }}" class="block text-sm text-white/70 hover:text-white">Privacy Policy</a>
          <a href="{{ route('terms') }}" class="block text-sm text-white/70 hover:text-white">Terms of Service</a>
        </div>
      </div>
      <div>
        <p class="mb-3 font-black text-sm">GET IN TOUCH</p>
        <div class="space-y-1.5 text-sm text-white/70">
          <p>📞 0998-572-1327</p>
          <a href="https://maps.app.goo.gl/mZ2NZzbCeGwh2M27A" target="_blank" class="block hover:text-white">📍 Nostalji Subd., Paliparan I, Dasmarinas, Cavite</a>
          <p>🕐 Open Everyday</p>
        </div>
      </div>
    </div>
    <div class="mx-auto mt-8 flex max-w-7xl justify-between border-t border-white/10 pt-5 text-xs text-white/40">
      <span>2026 Luntiang H.A.P.A.G. - Fresh Harvested Daily</span>
      <a href="{{ route('admin.login') }}" class="hover:text-white/70 transition-colors">Admin Portal</a>
    </div>
  </footer>

  @if(request()->routeIs('home'))
  <a href="{{ route('chat.index') }}" id="chatWidget" class="fixed bottom-8 right-8 z-[9998] flex items-center gap-3 bg-[#17611f] text-white px-5 py-3 rounded-full shadow-lg hover:opacity-90 hover:scale-105 transition-all duration-300" aria-label="Chat with us">
    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
    <span class="hidden md:inline text-sm font-semibold">Chat with us</span>
    <span class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-green-400 border-2 border-white rounded-full animate-pulse"></span>
  </a>
  <script>document.addEventListener('DOMContentLoaded',function(){const w=document.getElementById('chatWidget');if(!w)return;let l=window.scrollY,v=1;window.addEventListener('scroll',function(){const s=window.scrollY;s>500&&s>l?(v&&(w.style.opacity='0',w.style.transform='translateY(20px) scale(.9)',w.style.pointerEvents='none',v=0)):(s<l||s<500)&&(!v&&(w.style.opacity='1',w.style.transform='translateY(0) scale(1)',w.style.pointerEvents='auto',v=1));l=s},{passive:1})});</script>
  @endif

  @stack('scripts')
</body>
</html>
