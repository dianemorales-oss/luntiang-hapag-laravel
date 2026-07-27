<?php
session_start();
require 'config.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) { header("Location: products.php"); exit(); }

$stmt = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.slug = ? AND p.is_active = 1");
$stmt->execute([$slug]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) { header("Location: products.php"); exit(); }

// Related products
$related = $conn->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? AND is_active = 1 ORDER BY is_best_seller DESC LIMIT 4");
$related->execute([$product['category_id'], $product['id']]);
$relatedProducts = $related->fetchAll(PDO::FETCH_ASSOC);

// Reviews
$reviews = $conn->prepare("SELECT r.*, u.first_name, u.last_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? AND r.is_approved = 1 ORDER BY r.created_at DESC LIMIT 10");
$reviews->execute([$product['id']]);
$productReviews = $reviews->fetchAll(PDO::FETCH_ASSOC);

$reviewCount = $conn->prepare("SELECT COUNT(*) FROM reviews WHERE product_id = ? AND is_approved = 1");
$reviewCount->execute([$product['id']]);
$totalReviews = $reviewCount->fetchColumn();

$avgRating = $conn->prepare("SELECT AVG(rating) FROM reviews WHERE product_id = ? AND is_approved = 1");
$avgRating->execute([$product['id']]);
$avg = $avgRating->fetchColumn();

if (isset($_SESSION['user_id'])) {
}

$available = (int)$product['plants_available'];
$availLabel = $available > 50 ? 'In Stock' : ($available > 20 ? 'In Stock' : ($available > 0 ? 'Low Stock' : 'Unavailable'));
$availColor = $available > 20 ? 'text-green-600' : ($available > 0 ? 'text-amber-600' : 'text-red-600');

$isLoggedIn = isset($_SESSION['user_id']);

// Check if user has at least one completed/delivered order to be eligible to review
$canReview = false;
if ($isLoggedIn) {
    $orderCheck = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status IN ('completed','delivered','preparing','ready')");
    $orderCheck->execute([$_SESSION['user_id']]);
    $canReview = $orderCheck->fetchColumn() > 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($product['name']) ?> | Luntiang H.A.P.A.G.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>body{font-family:'Nunito',sans-serif;background:#f4faf5}.btn-green{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;padding:.7rem 1.5rem;border-radius:.75rem;font-weight:800;font-size:.875rem;cursor:pointer;transition:all .18s;border:none;font-family:inherit}.btn-green:active{transform:scale(.96)}.btn-primary{background:#17611f;color:#fff}.btn-primary:hover{background:#14521a}.btn-outline{border:1.5px solid rgba(27,94,32,.2);background:transparent;color:#1a2e1c}.btn-outline:hover{background:#e8f5e9}.btn-buy{background:#f9a825;color:#fff}.btn-buy:hover{background:#f57f17}</style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c]">

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="max-w-7xl mx-auto px-6 py-6">
  <!-- Breadcrumb -->
  <nav class="flex items-center gap-2 text-sm text-[#5a7a5c] mb-5">
    <a href="index.php" class="hover:text-[#17611f] font-semibold">Home</a><span>/</span>
    <a href="products.php" class="hover:text-[#17611f] font-semibold">Products</a><span>/</span>
    <span class="font-bold text-[#1a2e1c] truncate"><?= htmlspecialchars($product['name']) ?></span>
  </nav>

  <!-- Product Main Section -->
  <div class="grid md:grid-cols-2 gap-8 mb-10">
    <!-- LEFT: Image -->
    <div class="sticky top-24 self-start">
      <div class="rounded-2xl overflow-hidden bg-white border border-[rgba(27,94,32,0.08)]">
        <img src="<?= htmlspecialchars($product['image'] ?: 'images/lettuce/hero-farm.png') ?>" class="w-full aspect-square object-cover" alt="<?= htmlspecialchars($product['name']) ?>">
      </div>
    </div>

    <!-- RIGHT: Info -->
    <div>
      <!-- Badges -->
      <div class="flex flex-wrap items-center gap-1.5 mb-3">
        <?php if ($product['is_best_seller']): ?><span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#fff8e1] text-[#e65100]">🏆 Best Seller</span><?php endif; ?>
        <?php if ($product['is_new']): ?><span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#e8f5e9] text-[#17611f]">✨ New</span><?php endif; ?>
        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#e8f5e9] text-[#17611f]">🌱 Hydroponic</span>
      </div>

      <!-- Title -->
      <h1 class="text-2xl sm:text-3xl font-black mb-1"><?= htmlspecialchars($product['name']) ?></h1>
      <?php if ($product['variety']): ?>
        <p class="text-sm text-[#5a7a5c] mb-2">Variety: <?= htmlspecialchars($product['variety']) ?></p>
      <?php endif; ?>

      <!-- Rating -->
      <?php if ($avg): ?>
        <div class="flex items-center gap-2 mb-3">
          <span class="text-amber-400 text-base"><?= str_repeat('★', round($avg)) . str_repeat('☆', 5 - round($avg)) ?></span>
          <span class="text-sm font-bold text-[#5a7a5c]"><?= number_format($avg, 1) ?></span>
          <span class="text-xs text-[#9e9e9e]">|</span>
          <a href="#reviews" class="text-sm font-semibold text-[#17611f] hover:underline"><?= $totalReviews ?> review<?= $totalReviews!=1?'s':'' ?></a>
          <span class="text-xs text-[#9e9e9e]">|</span>
          <span class="text-xs text-[#5a7a5c]"><?= $available > 0 ? $available . ' plants available' : 'Unavailable' ?></span>
        </div>
      <?php endif; ?>

      <!-- Price -->
      <div class="bg-[#e8f5e9] rounded-xl p-4 mb-4">
        <p class="text-[10px] uppercase tracking-wide text-[#5a7a5c] font-bold mb-1">Price</p>
        <p class="text-3xl font-black text-[#17611f]">₱<?= number_format((float)$product['price'], 2) ?></p>
        <p class="text-xs text-[#5a7a5c] mt-0.5"><?= htmlspecialchars($product['unit']) ?> · <?= htmlspecialchars($product['harvest_time'] ?: '1-3 hours after order') ?></p>
      </div>

      <!-- Description -->
      <?php if ($product['description']): ?>
        <p class="text-sm text-[#5a7a5c] leading-relaxed mb-4"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
      <?php endif; ?>

      <!-- Action Buttons -->
      <div class="flex items-center gap-3 mb-5">
        <?php if ($isLoggedIn && $available > 0): ?>
          <div class="flex items-center gap-3 flex-1">
            <div class="flex items-center border border-[rgba(27,94,32,0.12)] rounded-xl overflow-hidden">
              <button type="button" onclick="let n=this.nextElementSibling;n.value=Math.max(1,parseInt(n.value)-1)" class="px-3 py-2.5 font-black hover:bg-[#e8f5e9]">−</button>
              <input type="number" id="productQty" value="1" min="1" max="<?= $available ?>" class="w-14 text-center font-bold text-sm border-x border-[rgba(27,94,32,0.12)] py-2.5 outline-none" readonly onkeydown="return false" />
              <button type="button" onclick="let n=this.previousElementSibling;n.value=Math.min(<?=$available?>,parseInt(n.value)+1)" class="px-3 py-2.5 font-black hover:bg-[#e8f5e9]">+</button>
            </div>
            <button type="button" onclick="addToCart(<?= $product['id'] ?>, parseInt(document.getElementById('productQty').value))" class="btn-green btn-primary flex-1">🛒 Add to Cart</button>
          </div>
          <a href="cart-actions.php?action=buy_now&id=<?= $product['id'] ?>" class="btn-green btn-buy px-6">⚡ Buy Now</a>
        <?php elseif ($available < 1): ?>
          <button disabled class="btn-green w-full opacity-50 cursor-not-allowed">Temporarily Unavailable</button>
        <?php else: ?>
          <a href="javascript:void(0)" onclick="addToCart(<?= $product['id'] ?>,parseInt(document.getElementById('productQty').value||1))" class="btn-green btn-primary flex-1">🛒 Add to Cart</a>
          <a href="login.php" class="btn-green btn-buy">⚡ Buy Now</a>
        <?php endif; ?>
      </div>

      <!-- Harvest & Delivery Cards -->
      <div class="grid grid-cols-2 gap-3 mb-5">
        <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-3 text-center">
          <p class="text-xl mb-1">✂️</p>
          <p class="font-black text-xs">Harvest Time</p>
          <p class="text-[11px] text-[#5a7a5c]"><?= htmlspecialchars($product['harvest_time'] ?: '1-3 hours') ?></p>
        </div>
        <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-3 text-center">
          <p class="text-xl mb-1">❄️</p>
          <p class="font-black text-xs">Shelf Life</p>
          <p class="text-[11px] text-[#5a7a5c]"><?= htmlspecialchars($product['shelf_life'] ?: '5-7 days') ?></p>
        </div>
        <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-3 text-center">
          <p class="text-xl mb-1">🚚</p>
          <p class="font-black text-xs">Same-Day Delivery</p>
          <p class="text-[11px] text-[#5a7a5c]">Before 2 PM orders</p>
        </div>
        <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-3 text-center">
          <p class="text-xl mb-1">🛍️</p>
          <p class="font-black text-xs">Pick-Up Available</p>
          <p class="text-[11px] text-[#5a7a5c]">Ready in 1-3 hours</p>
        </div>
      </div>

      <!-- Accordion: Details, Nutrition, Storage -->
      <?php if ($product['calories'] || $product['best_for'] || $product['storage_instructions']): ?>
      <div class="border border-[rgba(27,94,32,0.08)] rounded-xl overflow-hidden divide-y divide-[rgba(27,94,32,0.08)]">
        <?php if ($product['calories']): ?>
        <details class="group">
          <summary class="flex items-center justify-between px-4 py-3 cursor-pointer font-bold text-sm hover:bg-[#f4faf5]">📊 Nutritional Information <svg class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 9l-7 7-7-7"/></svg></summary>
          <div class="px-4 pb-3 text-sm text-[#5a7a5c] space-y-1">
            <?php if ($product['calories']): ?><p>🔥 Calories: <?= $product['calories'] ?> kcal</p><?php endif; ?>
            <?php if (!empty($product['protein'])): ?><p>💪 Protein: <?= $product['protein'] ?>g</p><?php endif; ?>
            <?php if (!empty($product['fiber'])): ?><p>🌾 Fiber: <?= $product['fiber'] ?>g</p><?php endif; ?>
            <?php if (!empty($product['vitamin_a'])): ?><p>🥕 Vitamin A: <?= htmlspecialchars($product['vitamin_a']) ?></p><?php endif; ?>
            <?php if (!empty($product['vitamin_c'])): ?><p>🍋 Vitamin C: <?= htmlspecialchars($product['vitamin_c']) ?></p><?php endif; ?>
          </div>
        </details>
        <?php endif; ?>
        <?php if ($product['best_for']): ?>
        <details class="group">
          <summary class="flex items-center justify-between px-4 py-3 cursor-pointer font-bold text-sm hover:bg-[#f4faf5]">🍽️ Best For <svg class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 9l-7 7-7-7"/></svg></summary>
          <div class="px-4 pb-3 text-sm text-[#5a7a5c]"><?= nl2br(htmlspecialchars($product['best_for'])) ?></div>
        </details>
        <?php endif; ?>
        <?php if ($product['storage_instructions']): ?>
        <details class="group">
          <summary class="flex items-center justify-between px-4 py-3 cursor-pointer font-bold text-sm hover:bg-[#f4faf5]">❄️ Storage Instructions <svg class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 9l-7 7-7-7"/></svg></summary>
          <div class="px-4 pb-3 text-sm text-[#5a7a5c]"><?= nl2br(htmlspecialchars($product['storage_instructions'])) ?></div>
        </details>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Reviews Section -->
  <section id="reviews" class="mb-10">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-xl font-black">Customer Reviews</h2>
        <?php if ($avg): ?>
          <div class="flex items-center gap-2 mt-1">
            <span class="text-amber-400"><?= str_repeat('★', round($avg)) . str_repeat('☆', 5 - round($avg)) ?></span>
            <span class="font-bold text-sm"><?= number_format($avg, 1) ?> out of 5</span>
            <span class="text-xs text-[#9e9e9e]">· <?= $totalReviews ?> review<?= $totalReviews!=1?'s':'' ?></span>
          </div>
        <?php endif; ?>
      </div>
      <?php if ($canReview): ?>
        <button onclick="document.getElementById('reviewForm').classList.toggle('hidden')" class="btn-green btn-outline text-sm">✍️ Write a Review</button>
      <?php endif; ?>
    </div>

    <!-- Review Form -->
    <?php if ($canReview): ?>
    <div id="reviewForm" class="hidden bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-5 mb-4">
      <form method="POST" action="review-actions.php" enctype="multipart/form-data" class="space-y-3">
        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
        <input type="hidden" name="action" value="submit">
        <div>
          <label class="text-sm font-bold">Rating</label>
          <div class="flex gap-1 mt-1" id="starRating">
            <?php for ($i=1;$i<=5;$i++): ?>
              <button type="button" data-star="<?=$i?>" class="star-btn text-2xl text-gray-300 hover:text-amber-400 transition-colors">★</button>
            <?php endfor; ?>
          </div>
          <input type="hidden" name="rating" id="ratingInput" value="5">
        </div>
        <div>
          <label class="text-sm font-bold">Your Review</label>
          <textarea name="comment" rows="3" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl p-3 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" placeholder="Share your experience with this lettuce..."></textarea>
        </div>
        <div>
          <label class="text-sm font-bold">Add Photos <span class="text-xs text-[#9e9e9e] font-normal">(optional)</span></label>
          <input type="file" name="review_photos[]" accept=".jpg,.jpeg,.png" multiple class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl p-2 text-sm mt-1 focus:outline-none">
          <p class="text-[11px] text-[#9e9e9e] mt-1">JPG or PNG. Up to 5 MB total.</p>
        </div>
        <button type="submit" class="btn-green btn-primary">Submit Review</button>
      </form>
    </div>
    <?php endif; ?>

    <?php if (empty($productReviews)): ?>
      <div class="text-center py-10 bg-white rounded-xl border border-[rgba(27,94,32,0.08)]">
        <p class="text-4xl mb-3">💬</p>
        <p class="font-bold text-[#5a7a5c]">No reviews yet</p>
        <p class="text-sm text-[#9e9e9e] mt-1">Be the first to review this product!</p>
      </div>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($productReviews as $rev):
            $revPhotos = !empty($rev['photos']) ? json_decode($rev['photos'], true) : [];
        ?>
          <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-5">
            <div class="flex items-center gap-3 mb-2">
              <div class="w-9 h-9 rounded-full bg-[#e8f5e9] flex items-center justify-center font-bold text-sm text-[#17611f]"><?= strtoupper(substr($rev['first_name'],0,1)) ?></div>
              <div class="flex-1">
                <p class="font-bold text-sm"><?= htmlspecialchars($rev['first_name']) ?></p>
                <div class="flex items-center gap-2">
                  <span class="text-amber-400 text-xs"><?= str_repeat('★',$rev['rating']).str_repeat('☆',5-$rev['rating']) ?></span>
                  <?php if ($rev['is_verified']): ?><span class="text-[10px] bg-[#e8f5e9] text-[#17611f] px-1.5 py-0.5 rounded font-bold">✓ Verified</span><?php endif; ?>
                </div>
              </div>
              <span class="text-xs text-[#9e9e9e]"><?= date('M j, Y', strtotime($rev['created_at'])) ?></span>
            </div>
            <?php if ($rev['comment']): ?><p class="text-sm text-[#5a7a5c] leading-relaxed"><?= nl2br(htmlspecialchars($rev['comment'])) ?></p><?php endif; ?>
            <?php if (!empty($revPhotos)): ?>
            <div class="flex gap-2 mt-3 flex-wrap">
              <?php foreach ($revPhotos as $photo): ?>
                <a href="<?= htmlspecialchars($photo) ?>" target="_blank" rel="noopener"><img src="<?= htmlspecialchars($photo) ?>" class="w-20 h-20 object-cover rounded-lg border border-[rgba(27,94,32,0.08)]" alt="Review photo"></a>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($rev['admin_reply'])): ?>
            <div class="mt-3 pl-4 border-l-2 border-[#17611f]">
              <p class="text-xs font-bold text-[#17611f] mb-1">🌱 Luntiang H.A.P.A.G. replied:</p>
              <p class="text-sm text-[#5a7a5c]"><?= nl2br(htmlspecialchars($rev['admin_reply'])) ?></p>
              <?php if (!empty($rev['admin_replied_at'])): ?>
                <p class="text-[11px] text-[#9e9e9e] mt-1"><?= date('M j, Y g:i A', strtotime($rev['admin_replied_at'])) ?></p>
              <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['admin_id']) && empty($rev['admin_reply'])): ?>
            <form method="POST" action="review-actions.php" class="mt-3 border-t border-[rgba(27,94,32,0.08)] pt-3">
              <input type="hidden" name="action" value="reply">
              <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
              <textarea name="admin_reply" rows="2" class="w-full border rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" placeholder="Reply to this review..."></textarea>
              <button type="submit" class="mt-2 px-4 py-1.5 rounded-lg bg-[#17611f] text-white text-xs font-bold hover:bg-[#14521a]">Reply</button>
            </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- Related Products -->
  <?php if (!empty($relatedProducts)): ?>
    <section>
      <h2 class="text-xl font-black mb-4">You Might Also Like</h2>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <?php foreach ($relatedProducts as $rp): ?>
          <a href="product.php?slug=<?= urlencode($rp['slug']) ?>" class="product-card bg-white rounded-xl overflow-hidden border border-[rgba(27,94,32,0.08)] hover:shadow-lg hover:-translate-y-1 transition-all">
            <img src="<?= htmlspecialchars($rp['image'] ?: 'images/lettuce/hero-farm.png') ?>" class="w-full aspect-square object-cover" alt="">
            <div class="p-3">
              <p class="text-sm font-bold truncate"><?= htmlspecialchars($rp['name']) ?></p>
              <p class="font-black text-[#17611f] text-sm mt-1">₱<?= number_format((float)$rp['price'], 2) ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</main>

<script>
document.querySelectorAll('.star-btn').forEach(b=>{b.addEventListener('click',()=>{const v=parseInt(b.dataset.star);document.getElementById('ratingInput').value=v;document.querySelectorAll('.star-btn').forEach((s,i)=>{s.classList.toggle('text-amber-400',i<v);s.classList.toggle('text-gray-300',i>=v);});});});

// AJAX Add to Cart
var _cartToast=document.createElement('div');_cartToast.id='cartToast';_cartToast.className='fixed top-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-lg text-sm font-bold transition-all duration-300 translate-x-[120%] opacity-0 pointer-events-none';document.body.appendChild(_cartToast);
function showToast(msg,ok){_cartToast.textContent=msg;_cartToast.className='fixed top-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-lg text-sm font-bold transition-all duration-300 '+(ok?'bg-[#e8f5e9] text-[#17611f] border border-[#c8e6c9]':'bg-red-50 text-red-700 border border-red-100');_cartToast.classList.remove('translate-x-[120%]','opacity-0');_cartToast.classList.add('translate-x-0','opacity-100');clearTimeout(_cartToast._t);_cartToast._t=setTimeout(function(){_cartToast.classList.add('translate-x-[120%]','opacity-0');},3000);}
function updateCartCount(count){var b=document.querySelector('a[href$="cart.php"] span');if(count>0){if(b){b.textContent=count}else{var a=document.querySelector('a[href$="cart.php"]');if(a){var s=document.createElement('span');s.className='absolute -top-1 -right-1 w-5 h-5 rounded-full bg-[#17611f] text-white text-[10px] font-bold flex items-center justify-center';s.textContent=count;a.appendChild(s)}}}else{if(b)b.remove()}}
async function addToCart(id,qty){qty=qty||1;try{var r=await fetch('cart-actions-ajax.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'add',id:id,qty:qty})});var d=await r.json();showToast(d.message,d.success);if(d.success)updateCartCount(d.count)}catch(e){showToast('Network error',false)}}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
