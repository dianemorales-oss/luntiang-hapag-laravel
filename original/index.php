<?php
session_start();
require 'config.php';
require __DIR__ . '/includes/navigation.php';

// Fetch featured & best-selling products from DB
try {
    $featured = $conn->query("SELECT * FROM products WHERE is_active = 1 ORDER BY is_best_seller DESC, plants_available DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback to static catalog if DB tables don't exist yet
    $featured = require __DIR__ . '/includes/lettuce-catalog.php';
    $featured = array_slice($featured, 0, 8);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Luntiang H.A.P.A.G. | Fresh Hydroponic Harvest-on-Demand Lettuce</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Nunito', sans-serif; background: #f4faf5; }
    html { scroll-behavior: smooth; }
    .product-card { transition: all .25s ease; }
    .product-card:hover { box-shadow: 0 8px 28px rgba(27,94,32,.1); transform: translateY(-3px); }
    .product-image { transition: transform .35s ease; }
    .product-card:hover .product-image { transform: scale(1.06); }
  </style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c]">

<?php include __DIR__ . '/includes/header.php'; ?>

<!-- ============================================================ -->
<!-- HERO                                                          -->
<!-- ============================================================ -->
<section class="max-w-7xl mx-auto px-6 py-6">
  <div class="relative h-[340px] sm:h-[380px] overflow-hidden rounded-2xl">
    <img src="images/lettuce/hero-farm.png" class="absolute inset-0 h-full w-full object-cover object-center" alt="Hydroponic Lettuce Farm">
    <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/50 to-black/10"></div>
    <div class="relative flex h-full flex-col justify-center px-6 sm:px-10 text-white">
      <span class="mb-3 inline-flex w-fit items-center gap-1.5 rounded-full bg-[#17611f]/85 px-3 py-1 text-xs font-black">100% Hydroponic · Harvest-on-Demand</span>
      <h1 class="max-w-[520px] text-[26px] sm:text-[32px] font-black leading-[1.2] tracking-[-.5px]">
        Harvested Only After You Order
      </h1>
      <p class="mt-3 max-w-[460px] text-sm sm:text-base text-white/90">
        Farm-to-table freshness — lettuce stays growing until your order is confirmed. Same-day harvest, pack, and delivery.
      </p>
      <div class="mt-5 flex flex-wrap gap-3">
        <a href="products.php" class="inline-flex items-center gap-2 rounded-xl bg-white text-[#17611f] px-5 py-2.5 text-sm font-black hover:bg-[#e8f5e9] transition-colors">🛍️ Shop Now</a>
        <a href="about.php" class="inline-flex items-center gap-2 rounded-xl bg-white/15 text-white px-5 py-2.5 text-sm font-bold hover:bg-white/25 transition-colors">Learn More</a>
      </div>
    </div>
  </div>

  <!-- Trust Strip -->
  <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-1.5 py-4 text-xs sm:text-sm font-bold text-[#17611f]">
    <span></span><span class="text-[#c8e6c9]">|</span>
    <span>🌱 Hydroponic</span><span class="text-[#c8e6c9]">|</span>
    <span>🚚 Same-Day Delivery</span><span class="text-[#c8e6c9]">|</span>
    <span>🛍️ Pick-Up</span><span class="text-[#c8e6c9]">|</span>
    
  </div>

  <!-- Claimable Coupons Section -->
  <?php
  $isLoggedIn = isset($_SESSION['user_id']);
  $activeCoupons = [];
  $claimedIds = [];
  try {
      $activeCoupons = $conn->query("SELECT * FROM promotions WHERE is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE()) ORDER BY created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
      if ($isLoggedIn) {
          $claimed = $conn->prepare("SELECT promotion_id FROM claimed_coupons WHERE user_id = ?");
          $claimed->execute([$_SESSION['user_id']]);
          $claimedIds = $claimed->fetchAll(PDO::FETCH_COLUMN);
      }
  } catch (Exception $e) { $activeCoupons = []; }
  ?>
  <?php if (!empty($activeCoupons)): ?>
  <div class="mb-10">
    <div class="flex items-center justify-between mb-4"><h2 class="text-xl font-black">🎟️ Claimable Coupons</h2></div>
    <div class="grid gap-4 sm:grid-cols-3" id="couponSection">
      <?php foreach ($activeCoupons as $c):
        $alreadyClaimed = in_array($c['id'], $claimedIds);
        $discountLabel = $c['discount_type'] === 'percentage' ? $c['discount_value'].'% Off' : '₱'.number_format($c['discount_value'],2).' Off';
        $expiry = $c['expires_at'] ? date('M j, Y', strtotime($c['expires_at'])) : 'No expiry';
      ?>
        <div class="rounded-2xl border p-5 bg-white hover:shadow-md transition-all">
          <div class="flex items-start justify-between mb-3">
            <div><p class="text-xs font-black uppercase text-[#17611f]"><?=htmlspecialchars($c['code'])?></p><h3 class="mt-1 text-lg font-black text-[#1a2e1c]"><?=$discountLabel?></h3></div>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#e8f5e9] text-[#17611f]"><?=$expiry?></span>
          </div>
          <p class="text-sm text-[#5a7a5c] mb-1"><?=htmlspecialchars($c['description'])?></p>
          <?php if ($c['min_order'] > 0): ?><p class="text-xs text-[#9e9e9e] mb-3">Min. purchase: ₱<?=number_format($c['min_order'],2)?></p><?php endif; ?>
          <?php if ($isLoggedIn): ?>
            <button onclick="claimCoupon(this,<?=$c['id']?>)" class="w-full mt-2 py-2 rounded-xl text-sm font-bold transition-colors <?=$alreadyClaimed?'bg-gray-100 text-[#9e9e9e] cursor-not-allowed':'bg-[#17611f] text-white hover:bg-[#14521a]'?>" <?=$alreadyClaimed?'disabled':''?>>
              <?=$alreadyClaimed?'✓ Claimed':'🎟️ Claim Coupon'?>
            </button>
          <?php else: ?>
            <a href="login.php" class="block w-full mt-2 py-2 rounded-xl bg-[#17611f] text-white text-sm font-bold text-center hover:bg-[#14521a]">🎟️ Claim Coupon</a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Featured Products -->
  <section>
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-2xl font-black">Fresh Lettuce & Bundles</h2>
        <p class="text-sm text-[#5a7a5c] mt-1">All hydroponically grown — harvested only after you order</p>
      </div>
      <a href="products.php" class="text-sm font-bold text-[#17611f] hover:underline">View All →</a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
      <?php foreach (array_slice($featured, 0, 8) as $p): 
        $pid = $p['id'] ?? 0;
        $pslug = $p['slug'] ?? '';
        $pimg = $p['image'] ?? 'images/lettuce/hero-farm.png';
        $pname = $p['name'] ?? '';
        $pvariety = $p['variety'] ?? $p['unit'] ?? '';
        $pprice = (float)($p['price'] ?? 0);
        $pbest = $p['is_best_seller'] ?? $p['bestSeller'] ?? false;
        $pavail = $p['plants_available'] ?? 999;
      ?>
        <article class="product-card bg-white rounded-xl overflow-hidden border border-[rgba(27,94,32,0.08)]">
          <a href="product.php?slug=<?= urlencode($pslug) ?>" class="block relative overflow-hidden">
            <img src="<?= htmlspecialchars($pimg) ?>" class="product-image aspect-square w-full object-cover" alt="<?= htmlspecialchars($pname) ?>">
            <?php if ($pbest): ?><b class="absolute left-2 top-2 rounded bg-[#f9a825] px-2 py-1 text-[10px] font-black text-white">🏆 Best</b><?php endif; ?>
            
          </a>
          <div class="p-3">
            <a href="product.php?slug=<?= urlencode($pslug) ?>" class="block">
              <p class="text-sm font-bold hover:text-[#17611f] transition-colors line-clamp-1"><?= htmlspecialchars($pname) ?></p>
            </a>
            <p class="text-xs text-[#5a7a5c] truncate"><?= htmlspecialchars($pvariety) ?></p>
            <div class="flex items-center justify-between mt-2">
              <p class="font-black text-[#17611f]">₱<?= number_format($pprice, 2) ?></p>
            </div>
            <a href="javascript:void(0)" onclick="addToCart(<?= $pid ?>)" class="block mt-2 text-center text-xs font-bold py-1.5 rounded-lg bg-[#17611f] text-white hover:bg-[#14521a] transition-colors cursor-pointer">🛒 Add to Cart</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</section>

<!-- How It Works -->
<section class="bg-white border-y border-[rgba(27,94,32,0.06)] py-10 mt-10">
  <div class="max-w-7xl mx-auto px-6 text-center">
    <h2 class="text-2xl font-black mb-8">How Harvest-on-Demand Works</h2>
    <div class="flex flex-wrap items-start justify-center gap-2 lg:gap-3">
      <?php foreach ([['🛒','You Order','Browse & place order'],['✅','Confirmed','Payment verified'],['✂️','Harvest','Cut within 1–3 hrs'],['📦','Pack','Freshly packed'],['🏠','Deliver','Same-day']] as $step): ?>
        <div class="bg-[#f4faf5] rounded-xl p-4 text-center w-[100px] sm:w-[120px]">
          <div class="w-10 h-10 rounded-full bg-[#e8f5e9] flex items-center justify-center mx-auto mb-2 text-xl"><?= $step[0] ?></div>
          <p class="font-black text-xs"><?= $step[1] ?></p>
          <p class="text-[10px] text-[#5a7a5c] mt-0.5"><?= $step[2] ?></p>
        </div>
        <?php if ($step[0] !== '🏠'): ?><span class="self-center text-[#c8e6c9] text-xl font-black">→</span><?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- About Snippet -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-6">
      <h3 class="font-black text-lg mb-2">🌿 About Our Farm</h3>
      <p class="text-sm text-[#5a7a5c] leading-relaxed mb-3">Luntiang H.A.P.A.G. grows 8 hydroponic lettuce varieties in Nostalji Subdivision, Dasmariñas, Cavite. Chemical-free, soil-free, harvested on demand.</p>
      <a href="about.php" class="text-sm font-bold text-[#17611f] hover:underline">Learn more →</a>
    </div>
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-6">
      <h3 class="font-black text-lg mb-2">✅ Freshness Guarantee</h3>
      <p class="text-sm text-[#5a7a5c] leading-relaxed mb-3">Every order is harvested fresh. If your lettuce arrives wilted, damaged, or not right — we replace it free. Just let us know within 24 hours.</p>
      <a href="contact-support.php" class="text-sm font-bold text-[#17611f] hover:underline">Contact Support →</a>
    </div>
  </div>
</section>

<?php renderBackToTop(); ?>
<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
var toast=document.createElement('div');toast.id='cartToast';toast.className='fixed top-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-lg text-sm font-bold transition-all duration-300 translate-x-[120%] opacity-0 pointer-events-none';document.body.appendChild(toast);
function showToast(msg,ok){toast.textContent=msg;toast.className='fixed top-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-lg text-sm font-bold transition-all duration-300 '+(ok?'bg-[#e8f5e9] text-[#17611f] border border-[#c8e6c9]':'bg-red-50 text-red-700 border border-red-100');toast.classList.remove('translate-x-[120%]','opacity-0');toast.classList.add('translate-x-0','opacity-100');clearTimeout(toast._t);toast._t=setTimeout(function(){toast.classList.add('translate-x-[120%]','opacity-0');},3000);}
function updateCartCount(count){
  var b=document.querySelector('a[href$="cart.php"] span');
  if(count>0){if(b){b.textContent=count}else{var a=document.querySelector('a[href$="cart.php"]');if(a){var s=document.createElement('span');s.className='absolute -top-1 -right-1 w-5 h-5 rounded-full bg-[#17611f] text-white text-[10px] font-bold flex items-center justify-center';s.textContent=count;a.appendChild(s)}}}else{if(b)b.remove()}
}
async function addToCart(id,qty){qty=qty||1;try{var r=await fetch('cart-actions-ajax.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'add',id:id,qty:qty})});var d=await r.json();showToast(d.message,d.success);if(d.success)updateCartCount(d.count)}catch(e){showToast('Network error',false)}}
async function claimCoupon(btn,promoId){btn.disabled=true;btn.textContent='Claiming...';try{var r=await fetch('cart-actions-ajax.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'claim_coupon',promo_id:promoId})});var d=await r.json();if(d.success){btn.textContent='✓ Claimed';btn.className='w-full mt-2 py-2 rounded-xl text-sm font-bold bg-gray-100 text-[#9e9e9e] cursor-not-allowed';showToast(d.message,true)}else if(d.redirect){window.location=d.redirect}else{btn.disabled=false;btn.textContent='🎟️ Claim Coupon';showToast(d.message,false)}}catch(e){btn.disabled=false;btn.textContent='🎟️ Claim Coupon';showToast('Network error',false)}}
</script>
</body>
</html>
