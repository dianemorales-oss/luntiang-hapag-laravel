@extends('layouts.app')
@section('title', 'About Our Farm | Luntiang H.A.P.A.G.')
@section('content')

<main class="flex-1 max-w-4xl w-full mx-auto px-6 py-16">
  <div class="bg-white rounded-3xl border border-[rgba(27,94,32,0.08)] p-10 mb-8 shadow-sm">
    <span class="inline-block text-[11px] font-semibold tracking-wide text-[#17611f] bg-[#e8f5e9] rounded-xl px-4 py-1 mb-4">🌿 OUR FARM</span>
    <h1 class="text-3xl font-black mb-4">About Luntiang H.A.P.A.G.</h1>
    <div class="text-[#5a7a5c] leading-relaxed space-y-4">
      <p><strong>Luntiang H.A.P.A.G.</strong> (Health Awareness and Professional Advisory Group) is a hydroponic lettuce farm located at Nostalji Subdivision, Paliparan I, Dasmariñas, Cavite — operating every day to bring you the freshest lettuce possible.</p>
      <p>We grow <strong>8 premium hydroponic lettuce varieties</strong> using clean, soil-free systems. Our farm uses no chemicals or pesticides — just pure water, nutrients, and sunlight to produce consistently crisp, clean, and nutritious lettuce.</p>
    </div>
  </div>

  <!-- Customer Creations – Products Made Possible by Our Lettuces (Moved into place of Growing Community) -->
  <div class="bg-white rounded-3xl border border-[rgba(27,94,32,0.08)] p-8 sm:p-10 mb-8 shadow-sm overflow-hidden">
    <div class="mb-8">
      <span class="inline-flex items-center gap-1.5 text-[11px] font-black tracking-[0.14em] uppercase text-[#17611f] bg-[#e8f5e9] rounded-full px-4 py-1.5">🍃 Customer Creations</span>
      <h2 class="text-[26px] sm:text-[30px] font-black leading-[1.1] tracking-[-0.5px] mt-4 mb-3">Products Made Possible<br class="hidden sm:block"/> by Our Lettuces</h2>
      <p class="text-sm sm:text-[15px] text-[#5a7a5c] leading-relaxed max-w-2xl">From samgyup nights to healthy salads, here’s how our community uses Luntiang H.A.P.A.G. hydroponic lettuce. 100% fresh, chemical-free, and harvested only after you order.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <article class="group relative rounded-[20px] overflow-hidden bg-[#f4faf5] border border-[rgba(27,94,32,0.08)] hover:shadow-[0_12px_32px_rgba(23,97,31,0.12)] hover:-translate-y-1 transition-all duration-300">
        <div class="relative aspect-[4/3] overflow-hidden">
          <img src="{{ asset('images/creations/samgyup-lettuce-wrap.jpg') }}" alt="Samgyup lettuce wrap with kimchi" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
          <span class="absolute left-3 top-3 inline-flex rounded-full bg-white/90 backdrop-blur px-2.5 py-1 text-[10px] font-black tracking-wide text-[#17611f] shadow-sm">K-BBQ Favorite</span>
        </div>
        <div class="p-4">
          <h3 class="font-black text-[15px] text-[#1a2e1c]">Samgyup Lettuce Wraps</h3>
          <p class="mt-1 text-[12px] leading-relaxed text-[#5a7a5c]">Crisp Batavia & Romaine cups holding grilled pork, kimchi, and garlic — the perfect bite that made our lettuce a samgyup staple.</p>
          <p class="mt-3 text-[10px] font-bold uppercase tracking-widest text-[#9e9e9e]">Perfect with: <span class="text-[#17611f]">Romaine & Batavia</span></p>
        </div>
      </article>

      <article class="group relative rounded-[20px] overflow-hidden bg-[#f4faf5] border border-[rgba(27,94,32,0.08)] hover:shadow-[0_12px_32px_rgba(23,97,31,0.12)] hover:-translate-y-1 transition-all duration-300">
        <div class="relative aspect-[4/3] overflow-hidden bg-white flex items-center justify-center p-2">
          <img src="{{ asset('images/creations/caesar-parmesan-salad.jpg') }}" alt="Caesar salad with parmesan croutons" class="h-full w-full object-contain transition-transform duration-700 group-hover:scale-105">
          <span class="absolute left-3 top-3 inline-flex rounded-full bg-white/90 backdrop-blur px-2.5 py-1 text-[10px] font-black tracking-wide text-[#17611f] shadow-sm">Classic Fresh</span>
        </div>
        <div class="p-4">
          <h3 class="font-black text-[15px] text-[#1a2e1c]">Caesar Parmesan Salad</h3>
          <p class="mt-1 text-[12px] leading-relaxed text-[#5a7a5c]">Olmetie & Estrosa lettuce tossed with parmesan croutons and creamy dressing — crisp, clean flavor thanks to soil-free hydroponics.</p>
          <p class="mt-3 text-[10px] font-bold uppercase tracking-widest text-[#9e9e9e]">Perfect with: <span class="text-[#17611f]">Olmetie & Lalique</span></p>
        </div>
      </article>

      <article class="group relative rounded-[20px] overflow-hidden bg-[#f4faf5] border border-[rgba(27,94,32,0.08)] hover:shadow-[0_12px_32px_rgba(23,97,31,0.12)] hover:-translate-y-1 transition-all duration-300">
        <div class="relative aspect-[4/3] overflow-hidden">
          <img src="{{ asset('images/creations/sisig-lettuce-cups.jpg') }}" alt="Sisig in lettuce cups" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
          <span class="absolute left-3 top-3 inline-flex rounded-full bg-white/90 backdrop-blur px-2.5 py-1 text-[10px] font-black tracking-wide text-[#17611f] shadow-sm">Filipino Fusion</span>
        </div>
        <div class="p-4">
          <h3 class="font-black text-[15px] text-[#1a2e1c]">Savory Lettuce Cups</h3>
          <p class="mt-1 text-[12px] leading-relaxed text-[#5a7a5c]">Bianca butterhead lettuce as edible cups for sizzling sisig & mushroom sisig — low-carb, high crunch, zero soil residue.</p>
          <p class="mt-3 text-[10px] font-bold uppercase tracking-widest text-[#9e9e9e]">Perfect with: <span class="text-[#17611f]">Bianca & Dabi</span></p>
        </div>
      </article>

      <article class="group relative rounded-[20px] overflow-hidden bg-[#f4faf5] border border-[rgba(27,94,32,0.08)] hover:shadow-[0_12px_32px_rgba(23,97,31,0.12)] hover:-translate-y-1 transition-all duration-300">
        <div class="relative aspect-[4/3] overflow-hidden">
          <img src="{{ asset('images/creations/crispy-lettuce-tacos.jpg') }}" alt="Crispy breaded tacos with lettuce and cheese" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
          <span class="absolute left-3 top-3 inline-flex rounded-full bg-white/90 backdrop-blur px-2.5 py-1 text-[10px] font-black tracking-wide text-[#17611f] shadow-sm">Party Handhelds</span>
        </div>
        <div class="p-4">
          <h3 class="font-black text-[15px] text-[#1a2e1c]">Crispy Lettuce Tacos</h3>
          <p class="mt-1 text-[12px] leading-relaxed text-[#5a7a5c]">Golden breaded shells stuffed with lettuce, cheese, and fresh veggies — proof that hydroponic lettuce stays crunchy even in fried creations.</p>
          <p class="mt-3 text-[10px] font-bold uppercase tracking-widest text-[#9e9e9e]">Perfect with: <span class="text-[#17611f]">Batavia & Red Lettuce</span></p>
        </div>
      </article>

      <article class="group relative rounded-[20px] overflow-hidden bg-[#f4faf5] border border-[rgba(27,94,32,0.08)] hover:shadow-[0_12px_32px_rgba(23,97,31,0.12)] hover:-translate-y-1 transition-all duration-300">
        <div class="relative aspect-[4/3] overflow-hidden">
          <img src="{{ asset('images/creations/fresh-lettuce-wraps.jpg') }}" alt="Fresh lettuce wraps platter" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
          <span class="absolute left-3 top-3 inline-flex rounded-full bg-white/90 backdrop-blur px-2.5 py-1 text-[10px] font-black tracking-wide text-[#17611f] shadow-sm">Healthy Bites</span>
        </div>
        <div class="p-4">
          <h3 class="font-black text-[15px] text-[#1a2e1c]">Garden Fresh Wraps</h3>
          <p class="mt-1 text-[12px] leading-relaxed text-[#5a7a5c]">Lalique & Estrosa lettuce wrapping seasoned mushrooms and greens — elegant, fresh, and ready in 1-3 hours after you order.</p>
          <p class="mt-3 text-[10px] font-bold uppercase tracking-widest text-[#9e9e9e]">Perfect with: <span class="text-[#17611f]">Lalique Family Pack</span></p>
        </div>
      </article>

      <article class="group relative rounded-[20px] overflow-hidden bg-[#f4faf5] border border-[rgba(27,94,32,0.08)] hover:shadow-[0_12px_32px_rgba(23,97,31,0.12)] hover:-translate-y-1 transition-all duration-300">
        <div class="relative aspect-[4/3] overflow-hidden">
          <img src="{{ asset('images/creations/club-sandwich-lettuce.jpg') }}" alt="Club sandwiches with lettuce and calamansi juice" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
          <span class="absolute left-3 top-3 inline-flex rounded-full bg-white/90 backdrop-blur px-2.5 py-1 text-[10px] font-black tracking-wide text-[#17611f] shadow-sm">Baon & Catering</span>
        </div>
        <div class="p-4">
          <h3 class="font-black text-[15px] text-[#1a2e1c]">Club Sandwiches & Baon</h3>
          <p class="mt-1 text-[12px] leading-relaxed text-[#5a7a5c]">From school baon to catering trays, our lettuce keeps sandwiches crunchy — paired with our signature calamansi dalandan juice.</p>
          <p class="mt-3 text-[10px] font-bold uppercase tracking-widest text-[#9e9e9e]">Perfect with: <span class="text-[#17611f]">Wholesale Trays 50-cups</span></p>
        </div>
      </article>
    </div>

    <div class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4 rounded-2xl bg-[#f4faf5] border border-dashed border-[rgba(27,94,32,0.18)] p-5">
      <div class="flex-1">
        <p class="text-sm font-black text-[#1a2e1c]">Made something amazing with our lettuce?</p>
        <p class="text-xs text-[#5a7a5c] mt-1">Tag us on Facebook or submit via Feedback — we’d love to feature your creation here!</p>
      </div>
      <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 rounded-full bg-[#17611f] px-5 py-2.5 text-xs font-black text-white shadow-sm hover:bg-[#14521a] hover:shadow-md transition-all">
        Shop Lettuce for Your Recipe
        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none"><path d="M7 4.5L13.5 10L7 15.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
    </div>
  </div>

  <!-- Farm Story -->
  <div class="bg-white rounded-3xl border border-[rgba(27,94,32,0.08)] p-10 mb-8 shadow-sm">
    <h2 class="text-2xl font-black mb-4">🌱 Our Story</h2>
    <p class="text-[#5a7a5c] leading-relaxed mb-3">What started as a passion for sustainable farming has grown into Luntiang H.A.P.A.G. — a modern hydroponic farm dedicated to providing fresh, chemical-free lettuce to our community. We believe everyone deserves access to freshly harvested, nutritious produce grown right in their neighborhood.</p>
    <p class="text-[#5a7a5c] leading-relaxed mb-3">Our name reflects our mission: <strong>Luntian</strong> (Filipino for "green" or "verdant") and <strong>Hapag</strong> (Filipino for "dining table") — bringing green, fresh produce to your table every day.</p>
  </div>

  <!-- Hydroponic Process -->
  <div class="bg-white rounded-3xl border border-[rgba(27,94,32,0.08)] p-10 mb-8 shadow-sm">
    <h2 class="text-2xl font-black mb-4">💧 How We Grow – Hydroponic Farming</h2>
    <div class="text-[#5a7a5c] leading-relaxed space-y-3">
      <p>Our lettuce is grown hydroponically — meaning it grows in nutrient-rich water instead of soil. This method:</p>
      <div class="grid gap-3 sm:grid-cols-2 mt-4">
        <div class="p-4 rounded-xl bg-[#e8f5e9]"><p class="font-black text-[#17611f] text-sm">✅ No Soil, No Mess</p><p class="text-xs text-[#5a7a5c] mt-1">Clean growing environment eliminates soil-borne diseases.</p></div>
        <div class="p-4 rounded-xl bg-[#e8f5e9]"><p class="font-black text-[#17611f] text-sm">💧 90% Less Water</p><p class="text-xs text-[#5a7a5c] mt-1">Recirculating water system uses far less water than traditional farming.</p></div>
        <div class="p-4 rounded-xl bg-[#e8f5e9]"><p class="font-black text-[#17611f] text-sm">🚫 Chemical-Free</p><p class="text-xs text-[#5a7a5c] mt-1">No pesticides or herbicides needed in our controlled environment.</p></div>
        <div class="p-4 rounded-xl bg-[#e8f5e9]"><p class="font-black text-[#17611f] text-sm">🌡️ Year-Round Growing</p><p class="text-xs text-[#5a7a5c] mt-1">Protected from weather, we grow consistently all year.</p></div>
      </div>
    </div>
  </div>

  <!-- Harvest-on-Demand -->
  <div class="bg-white rounded-3xl border border-[rgba(27,94,32,0.08)] p-10 mb-8 shadow-sm">
    <h2 class="text-2xl font-black mb-4">⟳ Harvest-on-Demand Model</h2>
    <p class="text-[#5a7a5c] leading-relaxed mb-3">Unlike supermarkets where lettuce may have been sitting on shelves for days, our lettuce stays growing in our hydroponic system until you place your order. We only harvest after your order is confirmed — usually within 1–3 hours before delivery or pick-up.</p>
    <p class="text-[#5a7a5c] leading-relaxed mb-3">This means:</p>
    <ul class="list-disc pl-5 text-[#5a7a5c] space-y-1 text-sm">
      <li>Maximum freshness — your lettuce was still growing hours before you receive it</li>
      <li>Longer shelf life — starts its journey to you at peak freshness</li>
      <li>Zero food waste — nothing is pre-harvested and left unsold</li>
      <li>Better nutrition — nutrients are at their peak when lettuce is freshly harvested</li>
    </ul>
  </div>

  <!-- Sustainability -->
  <div class="bg-white rounded-3xl border border-[rgba(27,94,32,0.08)] p-10 mb-8 shadow-sm">
    <h2 class="text-2xl font-black mb-4">♻️ Sustainability Practices</h2>
    <div class="text-[#5a7a5c] leading-relaxed space-y-3">
      <p>We're committed to sustainable farming:</p>
      <ul class="list-disc pl-5 space-y-1 text-sm text-[#5a7a5c]">
        <li>Water-efficient hydroponic system using 90% less water than traditional farming</li>
        <li>Harvest-on-demand eliminates food waste from unsold inventory</li>
        <li>Locally grown — reducing transportation carbon footprint</li>
        <li>No chemical runoff into soil or waterways</li>
        <li>Reusable growing media where possible</li>
      </ul>
    </div>
  </div>

  <!-- Freshness Guarantee -->
  <div class="bg-white rounded-3xl border border-[rgba(27,94,32,0.08)] p-10 mb-8 shadow-sm">
    <h2 class="text-2xl font-black mb-4">✅ Our Freshness Guarantee</h2>
    <p class="text-[#5a7a5c] leading-relaxed">We guarantee your lettuce arrives fresh, crisp, and of the highest quality. If you're not satisfied with the freshness of your order, let us know within 24 hours and we'll make it right — whether that's a replacement or a refund. Your satisfaction is the foundation of our farm.</p>
  </div>

  <!-- Contact Info -->
  <div class="bg-white rounded-3xl border border-[rgba(27,94,32,0.08)] p-10 text-center shadow-sm">
    <h2 class="text-2xl font-black mb-3">📍 Visit Us or Get in Touch</h2>
    <div class="text-[#5a7a5c] space-y-2">
      <p class="font-black text-[#1a2e1c]">📞 0998-572-1327</p>
      <p>📍 Nostalji Subdivision, Paliparan I, Dasmarinas, Cavite</p>
      <p>🕐 Open Everyday</p>
      <p>🚚 Same-Day Delivery | 🛍️ Same-Day Pick-Up</p>
    </div>
    <div class="flex flex-wrap justify-center gap-3 mt-5">
      <a href="{{ route('products.index') }}" class="px-5 py-2.5 rounded-2xl bg-[#17611f] text-white text-sm font-bold hover:opacity-90 transition-opacity">Shop Our Lettuce</a>
      <a href="{{ route('contact') }}" class="px-5 py-2.5 rounded-2xl border border-[rgba(27,94,32,0.12)] text-[#1a2e1c] text-sm font-bold hover:bg-[#e8f5e9] transition-colors">Contact Us</a>
    </div>
  </div>
</main>

@endsection
