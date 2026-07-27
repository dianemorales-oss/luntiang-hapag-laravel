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
