<?php
/**
 * includes/header.php
 * ------------------------------------------------------------------
 * Single, shared site header for Luntiang H.A.P.A.G.
 * Hydroponic Harvest-on-Demand Lettuce Farm
 *
 * Uses the lettuce-php inspired green design system consistently.
 *
 * USAGE: Set $baseUrl before including ('' for root pages, '../' for subdirectories)
 * ------------------------------------------------------------------
 */

if (!isset($baseUrl)) {
    $baseUrl = '';
}

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$isLoggedIn = isset($_SESSION['user_id']);
?>
  <!-- Top Announcement Bar -->
  <div class="bg-[#17611f] px-4 py-[7px] text-center text-xs font-bold text-white">
    Free delivery within Nostalji Subdivision &nbsp;|&nbsp; Harvest-on-Demand - Same-Day Delivery &nbsp;|&nbsp; Luntiang H.A.P.A.G.
  </div>

  <!-- Header -->
  <header class="bg-white border-b border-[rgba(27,94,32,0.12)] sticky top-0 z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 h-[86px] flex items-center gap-4">
      <a href="<?= $baseUrl ?>index.php" class="flex shrink-0 items-center gap-3">
        <img src="<?= $baseUrl ?>images/lettuce/logo-cropped.png" alt="Luntiang H.A.P.A.G." class="h-[60px] w-auto object-contain">
      </a>
      <span class="hidden h-5 border-l border-[rgba(27,94,32,0.12)] lg:block"></span>
      <span class="hidden shrink-0 text-sm font-semibold text-[#5a7a5c] lg:block">100% Hydroponic Lettuce Farm</span>

      <!-- Nav: visible to everyone -->
      <nav class="hidden md:flex items-center gap-6 text-[15px] font-semibold text-[#1a2e1c] ml-auto mr-2">
        <a href="<?= $baseUrl ?>index.php" class="hover:text-[#17611f] transition-colors">Home</a>
        <a href="<?= $baseUrl ?>products.php" class="hover:text-[#17611f] transition-colors">Shop</a>
        <a href="<?= $baseUrl ?>faq.php" class="hover:text-[#17611f] transition-colors">FAQ</a>
        <a href="<?= $baseUrl ?>contact-support.php" class="hover:text-[#17611f] transition-colors">Contact</a>
        <a href="<?= $baseUrl ?>about.php" class="hover:text-[#17611f] transition-colors">About</a>
      </nav>

      <div class="flex items-center gap-3">

        <!-- Cart icon: always visible -->
        <a href="<?= $baseUrl ?>cart.php" class="relative p-2 rounded-xl hover:bg-[#e8f5e9] transition-colors" title="Shopping Cart">
          <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3h2l2.4 11.4a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 7H6"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
          <?php if (!empty($_SESSION['cart'])): ?><span class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-[#17611f] text-white text-[10px] font-bold flex items-center justify-center"><?= count(array_unique(array_column($_SESSION["cart"],"id"))) ?></span><?php endif; ?>
        </a>

        <?php if ($isLoggedIn): ?>

          <a href="<?= $baseUrl ?>my-profile.php"
             class="hidden sm:inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl border border-[rgba(27,94,32,0.12)] text-sm font-bold text-[#1a2e1c] hover:bg-[#e8f5e9] transition-colors">
             <?= htmlspecialchars($_SESSION['first_name']) ?>
          </a>
          <a href="<?= $baseUrl ?>logout.php"
             class="px-5 py-2.5 rounded-2xl bg-[#17611f] text-white text-sm font-bold hover:opacity-90 transition-opacity">
            Logout
          </a>

        <?php else: ?>

          <a href="<?= $baseUrl ?>login.php"
             class="hidden sm:inline-block px-5 py-2.5 rounded-2xl border border-[rgba(27,94,32,0.12)] text-sm font-bold text-[#1a2e1c] hover:bg-[#e8f5e9] transition-colors">
            Login
          </a>
          <a href="<?= $baseUrl ?>register.php"
             class="px-5 py-2.5 rounded-2xl bg-[#17611f] text-white text-sm font-bold hover:opacity-90 transition-opacity">
            Register
          </a>

        <?php endif; ?>

      </div>
    </div>
  </header>
