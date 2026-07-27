<?php
if (!isset($baseUrl)) { $baseUrl = ''; }
?>
<footer class="mt-16 bg-[#17611f] px-6 pb-8 pt-14 text-white">
  <div class="mx-auto grid max-w-7xl gap-10 sm:grid-cols-3">
    <div>
      <img src="<?= $baseUrl ?>images/lettuce/logo-cropped.png" class="h-[68px] w-[190px] rounded-xl bg-white p-1 object-contain" alt="Luntiang Hapag">
      <p class="mt-3 text-sm text-white/60">Health Awareness and Professional Advisory Group</p>
      <p class="mt-1 text-sm text-white/60">Hydroponic Harvest-on-Demand Lettuce Farm</p>
      <div class="flex items-center gap-3 mt-4">
        <a href="https://www.facebook.com/elijaspride.kennel" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition-colors" title="Facebook">
          <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
        </a>
        <a href="https://maps.app.goo.gl/mZ2NZzbCeGwh2M27A" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition-colors" title="Google Maps">
          <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
        </a>
      </div>
    </div>
    <div>
      <p class="mb-3 font-black text-sm">QUICK LINKS</p>
      <div class="space-y-1.5">
        <a href="<?= $baseUrl ?>index.php" class="block text-sm text-white/70 hover:text-white">Home</a>
        <a href="<?= $baseUrl ?>products.php" class="block text-sm text-white/70 hover:text-white">Shop Products</a>
        <a href="<?= $baseUrl ?>about.php" class="block text-sm text-white/70 hover:text-white">About Our Farm</a>
        <a href="<?= $baseUrl ?>faq.php" class="block text-sm text-white/70 hover:text-white">FAQ</a>
        <a href="<?= $baseUrl ?>contact-support.php" class="block text-sm text-white/70 hover:text-white">Contact Support</a>
        <a href="<?= $baseUrl ?>privacy.php" class="block text-sm text-white/70 hover:text-white">Privacy Policy</a>
        <a href="<?= $baseUrl ?>terms.php" class="block text-sm text-white/70 hover:text-white">Terms of Service</a>
      </div>
    </div>
    <div>
      <p class="mb-3 font-black text-sm">GET IN TOUCH</p>
      <div class="space-y-1.5 text-sm text-white/70">
        <p>📞 0998-572-1327</p>
        <a href="https://maps.app.goo.gl/mZ2NZzbCeGwh2M27A" target="_blank" rel="noopener noreferrer" class="block hover:text-white">📍 Nostalji Subd., Paliparan I, Dasmarinas, Cavite</a>
        <p>🕐 Open Everyday</p>
      </div>
    </div>
  </div>
  <div class="mx-auto mt-8 flex max-w-7xl justify-between border-t border-white/10 pt-5 text-xs text-white/40">
    <span>2026 Luntiang H.A.P.A.G. - Fresh Harvested Daily</span>
    <a href="<?= $baseUrl ?>admin/admin-login.php" class="hover:text-white/70 transition-colors">Admin Portal</a>
  </div>
</footer>

<?php $cp = basename($_SERVER['PHP_SELF'] ?? '');
if ($cp === 'index.php'): ?>
<a href="live-chat.php" id="chatWidget" class="fixed bottom-8 right-8 z-[9998] flex items-center gap-3 bg-[#17611f] text-white px-5 py-3 rounded-full shadow-lg hover:opacity-90 hover:scale-105 transition-all duration-300" aria-label="Chat with us">
  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
  <span class="hidden md:inline text-sm font-semibold">Chat with us</span>
  <span class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-green-400 border-2 border-white rounded-full animate-pulse"></span>
</a>
<script>document.addEventListener('DOMContentLoaded',function(){const w=document.getElementById('chatWidget');if(!w)return;let l=window.scrollY,v=1;window.addEventListener('scroll',function(){const s=window.scrollY;s>500&&s>l?(v&&(w.style.opacity='0',w.style.transform='translateY(20px) scale(.9)',w.style.pointerEvents='none',v=0)):(s<l||s<500)&&(!v&&(w.style.opacity='1',w.style.transform='translateY(0) scale(1)',w.style.pointerEvents='auto',v=1));l=s},{passive:1})});</script>
<?php endif; ?>
