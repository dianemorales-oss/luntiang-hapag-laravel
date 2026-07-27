<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title','Admin | Luntiang H.A.P.A.G.')</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>body{font-family:'Nunito',sans-serif;background:#f4faf5}</style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c] min-h-screen flex">
  <!-- Sidebar -->
  <aside class="w-64 bg-[#17611f] text-white min-h-screen p-6 flex flex-col">
    <div class="mb-8">
      <img src="{{ asset('images/lettuce/logo-cropped.png') }}" class="h-12 bg-white rounded-lg p-1" alt="">
      <p class="mt-2 text-xs text-white/60">Admin Panel</p>
    </div>
    <nav class="space-y-1 flex-1">
      <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">📊 Dashboard</a>
      <a href="{{ route('admin.orders.index') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">🛒 Orders</a>
      <a href="{{ route('admin.products.index') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">🥬 Products</a>
      <a href="{{ route('admin.customers.index') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">👥 Customers</a>
      <a href="{{ route('admin.tickets.index') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">🎫 Tickets</a>
      <a href="{{ route('admin.warranty.index') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">🛡️ Freshness</a>
      <a href="{{ route('admin.returns.index') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">↩️ Returns</a>
      <a href="{{ route('admin.reviews.index') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">⭐ Reviews</a>
      <a href="{{ route('admin.feedback.index') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">💬 Feedback</a>
      <a href="{{ route('admin.faqs.index') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">❓ FAQ</a>
      <a href="{{ route('admin.promotions.index') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">🎟️ Promotions</a>
      <a href="{{ route('admin.live-chat.index') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">💭 Live Chat</a>
      <a href="{{ route('admin.reports.index') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">📈 Reports</a>
      <a href="{{ route('admin.notifications.index') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">🔔 Notifications</a>
    </nav>
    <div class="mt-auto pt-6 border-t border-white/10">
      <p class="text-sm font-bold">{{ session('admin_name') }}</p>
      <p class="text-xs text-white/60">{{ session('admin_email') }}</p>
      <a href="{{ route('admin.logout') }}" class="block mt-3 px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-sm">Logout</a>
    </div>
  </aside>

  <div class="flex-1 flex flex-col">
    <header class="bg-white border-b border-[rgba(27,94,32,0.12)] px-6 py-4 flex items-center justify-between">
      <h1 class="font-black text-lg">@yield('header','Admin Dashboard')</h1>
      <div class="flex items-center gap-3">
        <a href="{{ route('home') }}" class="text-sm text-[#17611f] hover:underline">View Store</a>
      </div>
    </header>
    <main class="flex-1 p-6">
      @if(session('success'))<div class="mb-4 rounded-xl bg-[#e8f5e9] border border-[#c8e6c9] px-4 py-3 text-sm text-[#1b5e20]">{{ session('success') }}</div>@endif
      @if(session('error'))<div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>@endif
      @yield('content')
    </main>
  </div>
</body>
</html>
