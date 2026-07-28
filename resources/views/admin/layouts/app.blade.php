<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Admin | Luntiang H.A.P.A.G.')</title>
  @include('partials.offline-assets')
  <style>body{font-family:'Nunito',sans-serif;background:#f4faf5}</style>
  @stack('styles')
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c] min-h-screen flex">

  @php
    $adminName = session('admin_name') ?? 'Admin';
    $adminRole = session('admin_role') ?? 'Super Admin';
    $nameParts = preg_split('/\s+/', trim($adminName));
    $initials = strtoupper(substr($nameParts[0] ?? 'A', 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));

    $openTicketsCount = 0;
    $pendingWarrantyCount = 0;
    $pendingReturnsCount = 0;
    $unreadNotifCount = 0;
    $recentNotifications = collect([]);

    try {
        $openTicketsCount = \App\Models\Ticket::where('status', 'open')->count();
        $pendingWarrantyCount = \App\Models\WarrantyRequest::where('status', 'pending')->count();
        $pendingReturnsCount = \App\Models\ReturnRequest::where('status', 'pending')->count();
        $unreadNotifCount = \App\Models\Notification::where('is_read', 0)->count();
        $recentNotifications = \App\Models\Notification::orderByDesc('created_at')->limit(8)->get();
    } catch (\Exception $e) {
        // Database not migrated yet, safe fallback
    }

    $routeUri = request()->route() ? request()->route()->getName() : '';
  @endphp

  <!-- Sidebar -->
  <aside class="w-64 flex-shrink-0 bg-gradient-to-b from-[#0d3311] to-[#091a0b] flex flex-col h-screen sticky top-0">
    <div class="px-5 pt-6 pb-5 flex items-center gap-3 border-b border-white/10">
      <div class="w-11 h-11 rounded-xl border border-[#52b788]/50 bg-[#14521a] flex items-center justify-center">
        <img src="{{ asset('images/lettuce/logo-cropped.png') }}" class="h-9 w-auto object-contain rounded-lg bg-white p-0.5" alt="LH">
      </div>
      <div>
        <p class="font-black text-white text-lg leading-none">Luntiang H.A.P.A.G.</p>
        <p class="text-[10px] tracking-[0.2em] text-[#5a7a5c] mt-1.5">ADMIN PANEL</p>
      </div>
    </div>

    <nav class="flex-1 overflow-y-auto py-5">
      <p class="px-6 text-[11px] tracking-[0.15em] text-[#5a7a5c] font-semibold mb-2">SALES & MARKETING</p>
      <div class="space-y-1 mb-6">
        @php
          $dashboardActive = ($routeUri === 'admin.dashboard');
          $productsActive = ($routeUri === 'admin.products.index');
          $ordersActive = ($routeUri === 'admin.orders.index');
          $reportsActive = ($routeUri === 'admin.reports.index');
          $customersActive = ($routeUri === 'admin.customers.index');
          $promotionsActive = ($routeUri === 'admin.promotions.index');
          $reviewsActive = ($routeUri === 'admin.reviews.index');
        @endphp

        <!-- Dashboard -->
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-xl text-sm {{ $dashboardActive ? 'bg-[#17611f] text-white font-semibold' : 'text-gray-300 hover:bg-[#14521a] hover:text-white transition-colors font-medium' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
          <span class="flex-1">Dashboard</span>
        </a>
        
        <!-- Products -->
        <a href="{{ route('admin.products.index') }}" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-xl text-sm {{ $productsActive ? 'bg-[#17611f] text-white font-semibold' : 'text-gray-300 hover:bg-[#14521a] hover:text-white transition-colors font-medium' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
          <span class="flex-1">Products</span>
        </a>

        <!-- Orders -->
        <a href="{{ route('admin.orders.index') }}" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-xl text-sm {{ $ordersActive ? 'bg-[#17611f] text-white font-semibold' : 'text-gray-300 hover:bg-[#14521a] hover:text-white transition-colors font-medium' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
          <span class="flex-1">Orders</span>
        </a>

        <!-- Sales -->
        <a href="{{ route('admin.reports.index') }}" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-xl text-sm {{ $reportsActive ? 'bg-[#17611f] text-white font-semibold' : 'text-gray-300 hover:bg-[#14521a] hover:text-white transition-colors font-medium' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          <span class="flex-1">Sales</span>
        </a>

        <!-- Customers -->
        <a href="{{ route('admin.customers.index') }}" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-xl text-sm {{ $customersActive ? 'bg-[#17611f] text-white font-semibold' : 'text-gray-300 hover:bg-[#14521a] hover:text-white transition-colors font-medium' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 3a4 4 0 10-8 0"/></svg>
          <span class="flex-1">Customers</span>
        </a>

        <!-- Promotions -->
        <a href="{{ route('admin.promotions.index') }}" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-xl text-sm {{ $promotionsActive ? 'bg-[#17611f] text-white font-semibold' : 'text-gray-300 hover:bg-[#14521a] hover:text-white transition-colors font-medium' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
          <span class="flex-1">Promotions</span>
        </a>

        <!-- Product Reviews -->
        <a href="{{ route('admin.reviews.index') }}" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-xl text-sm {{ $reviewsActive ? 'bg-[#17611f] text-white font-semibold' : 'text-gray-300 hover:bg-[#14521a] hover:text-white transition-colors font-medium' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
          <span class="flex-1">Product Reviews</span>
        </a>
      </div>

      <p class="px-6 text-[11px] tracking-[0.15em] text-[#5a7a5c] font-semibold mb-2">CUSTOMER SERVICE</p>
      <div class="space-y-1">
        @php
          $notificationsActive = ($routeUri === 'admin.notifications.index');
          $ticketsActive = ($routeUri === 'admin.tickets.index' || $routeUri === 'admin.tickets.show');
          $returnsActive = ($routeUri === 'admin.returns.index');
          $liveChatActive = ($routeUri === 'admin.live-chat.index' || $routeUri === 'admin.live-chat.show');
          $feedbackActive = ($routeUri === 'admin.feedback.index');
          $faqActive = ($routeUri === 'admin.faqs.index');
        @endphp

        <!-- Notifications -->
        <a href="{{ route('admin.notifications.index') }}" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-xl text-sm {{ $notificationsActive ? 'bg-[#17611f] text-white font-semibold' : 'text-gray-300 hover:bg-[#14521a] hover:text-white transition-colors font-medium' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
          <span class="flex-1">Notifications</span>
          @if ($unreadNotifCount > 0)
            <span class="min-w-[20px] h-5 px-1 rounded-full bg-[#17611f] text-white text-[11px] font-semibold flex items-center justify-center">{{ $unreadNotifCount }}</span>
          @endif
        </a>

        <!-- Tickets -->
        <a href="{{ route('admin.tickets.index') }}" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-xl text-sm {{ $ticketsActive ? 'bg-[#17611f] text-white font-semibold' : 'text-gray-300 hover:bg-[#14521a] hover:text-white transition-colors font-medium' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          <span class="flex-1">Tickets</span>
          @if ($openTicketsCount > 0)
            <span class="min-w-[20px] h-5 px-1 rounded-full bg-[#17611f] text-white text-[11px] font-semibold flex items-center justify-center">{{ $openTicketsCount }}</span>
          @endif
        </a>

        <!-- Returns -->
        <a href="{{ route('admin.returns.index') }}" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-xl text-sm {{ $returnsActive ? 'bg-[#17611f] text-white font-semibold' : 'text-gray-300 hover:bg-[#14521a] hover:text-white transition-colors font-medium' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8h-1"/></svg>
          <span class="flex-1">Returns & Refunds</span>
          @if ($pendingReturnsCount > 0)
            <span class="min-w-[20px] h-5 px-1 rounded-full bg-[#17611f] text-white text-[11px] font-semibold flex items-center justify-center">{{ $pendingReturnsCount }}</span>
          @endif
        </a>

        <!-- Live Chat -->
        <a href="{{ route('admin.live-chat.index') }}" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-xl text-sm {{ $liveChatActive ? 'bg-[#17611f] text-white font-semibold' : 'text-gray-300 hover:bg-[#14521a] hover:text-white transition-colors font-medium' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
          <span class="flex-1">Live Chat</span>
        </a>

        <!-- Feedback -->
        <a href="{{ route('admin.feedback.index') }}" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-xl text-sm {{ $feedbackActive ? 'bg-[#17611f] text-white font-semibold' : 'text-gray-300 hover:bg-[#14521a] hover:text-white transition-colors font-medium' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/></svg>
          <span class="flex-1">Feedback</span>
        </a>

        <!-- FAQ -->
        <a href="{{ route('admin.faqs.index') }}" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-xl text-sm {{ $faqActive ? 'bg-[#17611f] text-white font-semibold' : 'text-gray-300 hover:bg-[#14521a] hover:text-white transition-colors font-medium' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span class="flex-1">FAQ</span>
        </a>
      </div>

      <p class="px-6 text-[11px] tracking-[0.15em] text-[#5a7a5c] font-semibold mb-2 mt-4">ACCOUNT</p>
      <div class="space-y-1 mb-2">
        @php $adminProfileActive = ($routeUri === 'admin.profile'); @endphp
        <a href="{{ route('admin.profile') }}" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-xl text-sm {{ $adminProfileActive ? 'bg-[#17611f] text-white font-semibold' : 'text-gray-300 hover:bg-[#14521a] hover:text-white transition-colors font-medium' }}">
          <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          <span class="flex-1">My Profile</span>
        </a>
      </div>
    </nav>

    <!-- Sidebar footer logout / profile links -->
    <div class="p-4 border-t border-white/10 space-y-1">
      <button type="button"
              id="adminLogoutOpen"
              class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-gray-300 hover:bg-red-600 hover:text-white transition-all duration-200 text-left">
        <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        <span>Logout</span>
      </button>
    </div>
  </aside>

  <!-- Main Container -->
  <div class="flex-1 flex flex-col min-w-0">
    <!-- Topbar Header -->
    <header class="h-16 bg-white border-b border-[rgba(27,94,32,0.12)] flex items-center justify-between px-6 flex-shrink-0">
      <h1 class="text-lg font-semibold text-[#1a2e1c]">@yield('header', 'Dashboard')</h1>
      <div class="flex items-center gap-4">

        <!-- Notification Bell with Dropdown -->
        <div class="relative">
          <button type="button" id="notif-bell-btn" onclick="document.getElementById('notif-dropdown').classList.toggle('hidden')" class="relative w-9 h-9 rounded-full hover:bg-gray-100 flex items-center justify-center transition-colors">
            <svg class="w-5 h-5 text-[#5a7a5c]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            @if ($unreadNotifCount > 0)
              <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-semibold flex items-center justify-center">{{ $unreadNotifCount > 99 ? '99+' : $unreadNotifCount }}</span>
            @endif
          </button>

          <!-- Notifications Dropdown -->
          <div id="notif-dropdown" class="hidden absolute right-0 mt-2 w-96 max-w-[90vw] bg-white rounded-2xl border border-gray-100 shadow-lg z-50 overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
              <p class="text-sm font-semibold text-[#1a2e1c]">Notifications</p>
              @if ($unreadNotifCount > 0)
                <form method="POST" action="{{ route('admin.notifications.markAll') }}">
                  @csrf
                  <input type="hidden" name="redirect" value="{{ request()->getRequestUri() }}" />
                  <button type="submit" class="text-[12px] font-medium text-[#17611f] hover:underline">Mark all as read</button>
                </form>
              @endif
            </div>
            <div class="max-h-96 overflow-y-auto divide-y divide-gray-50">
              @if ($recentNotifications->isEmpty())
                <p class="text-center text-sm text-[#9e9e9e] py-8">No notifications yet.</p>
              @else
                @foreach ($recentNotifications as $n)
                  <a href="{{ route('admin.notifications.open', $n->id) }}" class="block px-4 py-3 hover:bg-gray-50 transition-colors {{ !$n->is_read ? 'bg-[#e8f5e9]/60' : '' }}">
                    <div class="flex items-start gap-2">
                      @if (!$n->is_read)
                        <span class="w-2 h-2 rounded-full bg-[#17611f] mt-1.5 flex-shrink-0"></span>
                      @else
                        <span class="w-2 h-2 flex-shrink-0"></span>
                      @endif
                      <div class="min-w-0 flex-1">
                        <p class="text-[13px] font-semibold text-[#1a2e1c] truncate">{{ $n->title }}</p>
                        <p class="text-[12px] text-[#5a7a5c] truncate">{{ strtok($n->message, "\n") }}</p>
                        <p class="text-[11px] text-[#9e9e9e] mt-0.5">
                          @if (!empty($n->customer_name)){{ $n->customer_name }} · @endif
                          {{ $n->created_at->format('M j, g:i A') }}
                        </p>
                      </div>
                    </div>
                  </a>
                @endforeach
              @endif
            </div>
            <a href="{{ route('admin.notifications.index') }}" class="block text-center text-[12px] font-medium text-[#17611f] hover:underline py-2.5 border-t border-gray-100 font-semibold">View all notifications</a>
          </div>
        </div>

        <!-- Admin Profile Info with Picture -->
        @php $adminPicture = session('admin_picture'); @endphp
        <a href="{{ route('admin.profile') }}" class="flex items-center gap-2.5 pl-3 border-l border-[rgba(27,94,32,0.12)] hover:bg-[#f4faf5] rounded-xl px-2 py-1 transition-colors">
          @if($adminPicture)
            <img src="{{ asset($adminPicture) }}" alt="Admin" class="w-9 h-9 rounded-full object-cover border border-[rgba(27,94,32,0.12)]">
          @else
            <div class="w-9 h-9 rounded-full bg-[#17611f] text-white text-xs font-semibold flex items-center justify-center">{{ $initials }}</div>
          @endif
          <div class="leading-tight text-left">
            <p class="text-sm font-semibold text-[#1a2e1c]">{{ $adminName }}</p>
            <p class="text-[11px] text-[#9e9e9e]">{{ $adminRole }}</p>
          </div>
        </a>
      </div>
    </header>

    <main class="flex-1 p-6 overflow-auto">
      @if (session('success'))
        <div data-flash-message class="mb-4 rounded-xl bg-[#e8f5e9] border border-[#c8e6c9] px-4 py-3 text-sm text-[#1b5e20] shadow-sm">
          {{ session('success') }}
        </div>
      @endif
      @if (session('error'))
        <div data-flash-message class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 shadow-sm">
          {{ session('error') }}
        </div>
      @endif

      @yield('content')
    </main>
  </div>



  <!-- Admin Logout Confirmation Modal -->
  <div id="adminLogoutModal"
       class="hidden fixed inset-0 z-[9999] items-center justify-center bg-[#091a0b]/65 backdrop-blur-sm px-4"
       role="dialog"
       aria-modal="true"
       aria-labelledby="adminLogoutModalTitle">
    <div class="w-full max-w-md overflow-hidden rounded-3xl border border-[#c8e6c9] bg-white shadow-2xl">
      <div class="px-7 pt-7 pb-5 text-center">
        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-[#e8f5e9] text-[#17611f] ring-8 ring-[#f4faf5]">
          <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H9m4 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
        </div>
        <h2 id="adminLogoutModalTitle" class="text-xl font-black text-[#1a2e1c]">Log out of admin?</h2>
        <p class="mt-2 text-sm leading-relaxed text-[#5a7a5c]">
          You are about to leave the Luntiang H.A.P.A.G. admin panel. You can sign back in anytime.
        </p>
      </div>
      <div class="flex flex-col-reverse gap-3 border-t border-[#e8f5e9] bg-[#f4faf5] px-7 py-5 sm:flex-row sm:justify-end">
        <button type="button"
                id="adminLogoutCancel"
                class="rounded-full border border-[rgba(27,94,32,0.14)] bg-white px-6 py-2.5 text-sm font-bold text-[#1a2e1c] hover:bg-[#e8f5e9] transition-colors">
          Stay signed in
        </button>
        <a id="adminLogoutConfirm"
           href="{{ route('admin.logout') }}"
           class="rounded-full bg-[#17611f] px-6 py-2.5 text-center text-sm font-bold text-white shadow-sm hover:bg-[#14521a] transition-colors">
          Yes, log out
        </a>
      </div>
    </div>
  </div>

  <script>
    // Close the notification dropdown when clicking anywhere outside it.
    document.addEventListener('click', function (e) {
      var dropdown = document.getElementById('notif-dropdown');
      var btn = document.getElementById('notif-bell-btn');
      if (dropdown && !dropdown.classList.contains('hidden') && !dropdown.contains(e.target) && !btn.contains(e.target)) {
        dropdown.classList.add('hidden');
      }
    });

    // Custom centered admin logout confirmation modal.
    (function () {
      var openBtn = document.getElementById('adminLogoutOpen');
      var modal = document.getElementById('adminLogoutModal');
      var cancelBtn = document.getElementById('adminLogoutCancel');

      if (!openBtn || !modal || !cancelBtn) return;

      function openLogoutModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        setTimeout(function () { cancelBtn.focus(); }, 0);
      }

      function closeLogoutModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        openBtn.focus();
      }

      openBtn.addEventListener('click', openLogoutModal);
      cancelBtn.addEventListener('click', closeLogoutModal);
      modal.addEventListener('click', function (e) {
        if (e.target === modal) closeLogoutModal();
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
          closeLogoutModal();
        }
      });
    })();

    // Auto-fade flash messages
    document.querySelectorAll('[data-flash-message]').forEach(function (el) {
      setTimeout(function () {
        el.style.transition = 'opacity 0.6s ease, max-height 0.6s ease, margin 0.6s ease, padding 0.6s ease';
        el.style.opacity = '0';
        el.style.maxHeight = el.offsetHeight + 'px';
        requestAnimationFrame(function () {
          el.style.maxHeight = '0px';
          el.style.marginTop = '0px';
          el.style.marginBottom = '0px';
          el.style.paddingTop = '0px';
          el.style.paddingBottom = '0px';
          el.style.overflow = 'hidden';
        });
      }, 4000);
    });
  </script>
  @stack('scripts')
</body>
</html>
