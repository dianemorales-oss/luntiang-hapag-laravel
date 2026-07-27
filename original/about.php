<?php
session_start();
require 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>About Our Farm | Luntiang H.A.P.A.G.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Nunito', sans-serif; }
  </style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c] min-h-screen flex flex-col">

  <!-- Header -->
  <?php include __DIR__ . '/includes/header.php'; ?>

  <main class="flex-1 max-w-4xl w-full mx-auto px-6 py-16">
    <a href="index.php" class="inline-flex items-center gap-2 text-sm text-[#17611f] hover:text-[#14521a] font-semibold transition-colors mb-8">
    </a>

    <div class="card p-10 mb-8">
      <span class="badge badge-green mb-4 text-sm px-4 py-1">🌿 OUR FARM</span>
      <h1 class="text-3xl font-black mb-4">About Luntiang H.A.P.A.G.</h1>
      <div class="text-[#5a7a5c] leading-relaxed space-y-4">
        <p><strong>Luntiang H.A.P.A.G.</strong> (Health Awareness and Professional Advisory Group) is a hydroponic lettuce farm located at Nostalji Subdivision, Paliparan I, Dasmariñas, Cavite — operating every day to bring you the freshest lettuce possible.</p>
        <p>We grow <strong>8 premium hydroponic lettuce varieties</strong> using clean, soil-free systems. Our farm uses no chemicals or pesticides — just pure water, nutrients, and sunlight to produce consistently crisp, clean, and nutritious lettuce.</p>
      </div>
    </div>

    <!-- Farm Story -->
    <div class="card p-10 mb-8">
      <h2 class="text-2xl font-black mb-4">🌱 Our Story</h2>
      <p class="text-[#5a7a5c] leading-relaxed mb-3">What started as a passion for sustainable farming has grown into Luntiang H.A.P.A.G. — a modern hydroponic farm dedicated to providing fresh, chemical-free lettuce to our community. We believe everyone deserves access to freshly harvested, nutritious produce grown right in their neighborhood.</p>
      <p class="text-[#5a7a5c] leading-relaxed mb-3">Our name reflects our mission: <strong>Luntian</strong> (Filipino for "green" or "verdant") and <strong>Hapag</strong> (Filipino for "dining table") — bringing green, fresh produce to your table every day.</p>
    </div>

    <!-- Hydroponic Process -->
    <div class="card p-10 mb-8">
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
    <div class="card p-10 mb-8">
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
    <div class="card p-10 mb-8">
      <h2 class="text-2xl font-black mb-4">♻️ Sustainability Practices</h2>
      <div class="text-[#5a7a5c] leading-relaxed space-y-3">
        <p>We're committed to sustainable farming:</p>
        <ul class="list-disc pl-5 space-y-1 text-sm">
          <li>Water-efficient hydroponic system using 90% less water than traditional farming</li>
          <li>Harvest-on-demand eliminates food waste from unsold inventory</li>
          <li>Locally grown — reducing transportation carbon footprint</li>
          <li>No chemical runoff into soil or waterways</li>
          <li>Reusable growing media where possible</li>
        </ul>
      </div>
    </div>

    <!-- Freshness Guarantee -->
    <div class="card p-10 mb-8">
      <h2 class="text-2xl font-black mb-4">✅ Our Freshness Guarantee</h2>
      <p class="text-[#5a7a5c] leading-relaxed">We guarantee your lettuce arrives fresh, crisp, and of the highest quality. If you're not satisfied with the freshness of your order, let us know within 24 hours and we'll make it right — whether that's a replacement or a refund. Your satisfaction is the foundation of our farm.</p>
    </div>

    <!-- Contact Info -->
    <div class="card p-10 text-center">
      <h2 class="text-2xl font-black mb-3">📍 Visit Us or Get in Touch</h2>
      <div class="text-[#5a7a5c] space-y-2">
        <p class="font-black text-[#1a2e1c]">📞 0998-572-1327</p>
        <p>📍 Nostalji Subdivision, Paliparan I, Dasmariñas, Cavite</p>
        <p>🕐 Open Everyday</p>
        <p>🚚 Same-Day Delivery | 🛍️ Same-Day Pick-Up</p>
      </div>
      <div class="flex flex-wrap justify-center gap-3 mt-5">
        <a href="index.php" class="btn btn-primary">Shop Our Lettuce</a>
        <a href="contact-support.php" class="btn btn-outline">Contact Us</a>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
