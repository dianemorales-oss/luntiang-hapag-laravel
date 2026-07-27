<?php
/**
 * admin/includes/admin-sidebar.php
 * ------------------------------------------------------------------
 * Shared sidebar navigation for every admin page.
 *
 * Expects (set by the including page before requiring this file):
 *   - $conn         PDO connection (already available via config.php)
 *   - $activePage   string key identifying which nav item is active,
 *                   e.g. 'dashboard', 'tickets', 'warranty', 'returns',
 *                   'feedback', 'contact', 'live-chat', 'faq',
 *                   'customers', 'profile'
 * ------------------------------------------------------------------
 */

if (!isset($activePage)) {
    $activePage = '';
}

// Real badge counts instead of hardcoded numbers.
$openTicketsCount = (int)$conn->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'")->fetchColumn();
$pendingWarrantyCount = (int)$conn->query("SELECT COUNT(*) FROM warranty_requests WHERE status = 'pending'")->fetchColumn();
$pendingReturnsCount = (int)$conn->query("SELECT COUNT(*) FROM return_requests WHERE status = 'pending'")->fetchColumn();
$unreadNotifCount = (int)$conn->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn();

if (!function_exists('navItem')) {
function navItem(string $key, string $active, string $href, string $label, string $iconPath, ?int $badge = null): void
{
    $isActive = ($key === $active);
    $classes = $isActive
        ? "flex items-center gap-3 px-4 py-2.5 mx-2 rounded-xl text-sm font-semibold bg-[#17611f] text-white transition-colors"
        : "flex items-center gap-3 px-4 py-2.5 mx-2 rounded-xl text-sm font-medium text-gray-300 hover:bg-[#14521a] hover:text-white transition-colors";
    echo "<a href=\"$href\" class=\"$classes\">";
    echo "<svg class=\"w-[18px] h-[18px] flex-shrink-0\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.8\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"$iconPath\"/></svg>";
    echo "<span class=\"flex-1\">$label</span>";
    if ($badge !== null && $badge > 0) {
        echo "<span class=\"min-w-[20px] h-5 px-1 rounded-full bg-[#17611f] text-white text-[11px] font-semibold flex items-center justify-center\">$badge</span>";
    }
    echo "</a>";
}
}
?>
  <!-- Sidebar -->
  <aside class="w-64 flex-shrink-0 bg-gradient-to-b from-[#0d3311] to-[#091a0b] flex flex-col h-screen sticky top-0">
    <div class="px-5 pt-6 pb-5 flex items-center gap-3 border-b border-white/10">
      <div class="w-11 h-11 rounded-xl border border-[#52b788]/50 bg-[#14521a] flex items-center justify-center">
        <img src="../images/lettuce/logo-cropped.png" class="h-9 w-auto object-contain rounded-lg bg-white p-0.5" alt="LH">
      </div>
      <div>
        <p class="font-black text-white text-lg leading-none">Luntiang H.A.P.A.G.</p>
        <p class="text-[10px] tracking-[0.2em] text-[#5a7a5c] mt-1.5">ADMIN PANEL</p>
      </div>
    </div>

    <nav class="flex-1 overflow-y-auto py-5">
      <p class="px-6 text-[11px] tracking-[0.15em] text-[#5a7a5c] font-semibold mb-2">SALES & MARKETING</p>
      <div class="space-y-1 mb-6">
        <?php
        navItem('dashboard', $activePage, 'admin-dashboard.php', 'Dashboard', 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z');
        navItem('products', $activePage, 'admin-products.php', 'Products', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4');
        navItem('orders', $activePage, 'admin-orders.php', 'Orders', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2');
        navItem('reports', $activePage, 'admin-reports.php', 'Sales', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z');
        navItem('customers', $activePage, 'admin-customers.php', 'Customers', 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 3a4 4 0 10-8 0');
        navItem('promotions', $activePage, 'admin-promotions.php', 'Promotions', 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z');
        navItem('reviews', $activePage, 'admin-reviews.php', 'Product Reviews', 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z');
        ?>
      </div>

      <p class="px-6 text-[11px] tracking-[0.15em] text-[#5a7a5c] font-semibold mb-2">CUSTOMER SERVICE</p>
      <div class="space-y-1">
        <?php
        navItem('notifications', $activePage, 'notifications.php', 'Notifications', 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', $unreadNotifCount);
        navItem('tickets', $activePage, 'admin-tickets.php', 'Tickets', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', $openTicketsCount);
        navItem('returns', $activePage, 'admin-returns.php', 'Returns & Refunds', 'M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8h-1', $pendingReturnsCount);
        navItem('live-chat', $activePage, 'admin-live-chat.php', 'Live Chat', 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z');
        navItem('feedback', $activePage, 'admin-feedback.php', 'Feedback', 'M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z');
        navItem('faq', $activePage, 'admin-faq.php', 'FAQ', 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z');
        ?>
      </div>
    </nav>

    <div class="p-4 border-t border-white/10 space-y-1">
      <a href="admin-profile.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-gray-300 hover:bg-[#14521a] hover:text-white transition-colors">
        <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        <span>Profile</span>
      </a>
      <a href="admin-logout.php"
         onclick="return confirm('Are you sure you want to log out?');"
         class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-gray-300 hover:bg-red-600 hover:text-white transition-all duration-200">
        <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        <span>Logout</span>
      </a>
    </div>
  </aside>
