<?php
session_start();
require 'config.php';
require __DIR__ . '/includes/navigation.php';

$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'featured';
$category = $_GET['category'] ?? '';
$filter = $_GET['filter'] ?? '';

$where = ["p.is_active = 1"];
$params = [];

if ($search) {
    $where[] = "(p.name LIKE ? OR p.variety LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($category) { $where[] = "c.slug = ?"; $params[] = $category; }
if ($filter === 'best_seller') $where[] = "p.is_best_seller = 1";
if ($filter === 'available') $where[] = "p.plants_available > 0";

$orderBy = "p.is_best_seller DESC, p.created_at DESC";
if ($sort === 'price_asc') $orderBy = "p.price ASC";
if ($sort === 'price_desc') $orderBy = "p.price DESC";
if ($sort === 'newest') $orderBy = "p.created_at DESC";
if ($sort === 'name') $orderBy = "p.name ASC";

$wh = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT p.*, c.name AS category_name, 
         COALESCE((SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1), 0) AS avg_rating,
         (SELECT COUNT(*) FROM reviews WHERE product_id = p.id AND is_approved = 1) AS review_count
         FROM products p LEFT JOIN categories c ON p.category_id = c.id $wh ORDER BY $orderBy";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($search) {
    if (!isset($_SESSION['recent_searches'])) $_SESSION['recent_searches'] = [];
    array_unshift($_SESSION['recent_searches'], $search);
    $_SESSION['recent_searches'] = array_unique(array_slice($_SESSION['recent_searches'], 0, 5));
}
$recentSearches = $_SESSION['recent_searches'] ?? [];

// Build query string helper
function qs($keep = []) {
    $p = $_GET; unset($p['sort']);
    foreach ($keep as $k) if (isset($p[$k]) && $p[$k] === '') unset($p[$k]);
    return http_build_query($p);
}
$baseQS = qs(['sort']);
?><!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products | Luntiang H.A.P.A.G.</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>body{font-family:'Nunito',sans-serif;background:#f4faf5}.product-card{transition:all .25s ease}.product-card:hover{box-shadow:0 8px 28px rgba(27,94,32,.1);transform:translateY(-3px)}.product-image{transition:transform .35s ease}.product-card:hover .product-image{transform:scale(1.06)}</style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c]">
<?php include __DIR__.'/includes/header.php'; ?>
<main class="max-w-7xl mx-auto px-6 py-6">
<div class="mb-5"><h1 class="text-2xl font-black">Fresh Hydroponic Lettuce</h1><p class="text-sm text-[#5a7a5c] mt-1">All harvested-on-demand -- your lettuce stays growing until you order</p></div>

<div class="flex items-center gap-3 mb-6">
  <form method="GET" action="products.php" class="flex-1 max-w-md">
    <div class="relative">
      <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[#9e9e9e]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
      <input name="search" value="<?=htmlspecialchars($search)?>" placeholder="Search lettuce..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-[rgba(27,94,32,0.12)] text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
    </div>
  </form>
  <select onchange="window.location.href='products.php?<?=$baseQS?>&sort='+this.value" class="px-4 py-2.5 rounded-xl text-sm font-bold border border-[rgba(27,94,32,0.12)] bg-white text-[#5a7a5c] cursor-pointer">
    <option value="featured" <?=$sort==='featured'?'selected':''?>>Featured</option>
    <option value="price_asc" <?=$sort==='price_asc'?'selected':''?>>Price: Low - High</option>
    <option value="price_desc" <?=$sort==='price_desc'?'selected':''?>>Price: High - Low</option>
    <option value="newest" <?=$sort==='newest'?'selected':''?>>Newest</option>
    <option value="name" <?=$sort==='name'?'selected':''?>>Name A-Z</option>
  </select>
  <?php if($search):?><a href="products.php" class="text-sm font-bold text-[#17611f] hover:underline whitespace-nowrap">Clear</a><?php endif;?>
</div>

<?php if(!empty($recentSearches)):?>
<div class="mb-4 flex flex-wrap items-center gap-2 text-xs"><span class="text-[#9e9e9e] font-semibold">Recent:</span>
<?php foreach($recentSearches as $rs):?><a href="?search=<?=urlencode($rs)?>" class="px-2.5 py-1 rounded-full bg-[#e8f5e9] text-[#17611f] hover:bg-[#c8e6c9] font-semibold"><?=htmlspecialchars($rs)?></a><?php endforeach;?></div>
<?php endif;?>

<?php if(empty($products)):?>
<div class="text-center py-16"><p class="text-xl font-bold mb-2">No products found</p><p class="text-[#5a7a5c] mb-4">Try a different search term.</p><a href="products.php" class="inline-flex px-6 py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a]">View All Products</a></div>
<?php else:?>
<p class="text-sm text-[#5a7a5c] mb-4"><?=count($products)?> product<?=count($products)!==1?'s':''?> found</p>
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
<?php foreach($products as $p):?>
<article class="product-card bg-white rounded-xl overflow-hidden border border-[rgba(27,94,32,0.08)]">
  <a href="product.php?slug=<?=urlencode($p['slug'])?>" class="block relative overflow-hidden">
    <img src="<?=htmlspecialchars($p['image']?:'images/lettuce/hero-farm.png')?>" class="product-image aspect-square w-full object-cover" alt="<?=htmlspecialchars($p['name'])?>">
    <?php if($p['is_best_seller']):?><b class="absolute left-2 top-2 rounded bg-[#f9a825] px-2 py-1 text-[10px] font-black text-white">Best Seller</b><?php endif;?>
    <?php if($p['plants_available']>0&&$p['plants_available']<=20):?><span class="absolute right-2 bottom-2 rounded bg-red-500/85 px-2 py-1 text-[10px] font-black text-white">Limited</span><?php endif;?>
  </a>
  <div class="p-3.5">
    <a href="product.php?slug=<?=urlencode($p['slug'])?>" class="block"><p class="text-sm font-bold text-[#1a2e1c] hover:text-[#17611f]"><?=htmlspecialchars($p['name'])?></p></a>
    <p class="text-xs text-[#5a7a5c]"><?=htmlspecialchars($p['variety']?:$p['unit'])?></p>
    <?php if ($p['avg_rating'] > 0): ?>
    <div class="flex items-center gap-1 mt-1">
      <span class="text-amber-400 text-[10px]"><?= str_repeat('★', round($p['avg_rating'])) . str_repeat('☆', 5 - round($p['avg_rating'])) ?></span>
      <span class="text-[10px] text-[#9e9e9e]">(<?= $p['review_count'] ?>)</span>
    </div>
    <?php endif; ?>
    <div class="flex items-center justify-between mt-2 mb-1"><p class="font-black text-[#17611f]">P<?=number_format((float)$p['price'],2)?></p></div>
    <a href="javascript:void(0)" onclick="addToCart(<?=$p['id']?>)" class="block text-center text-xs font-bold py-1.5 rounded-lg bg-[#17611f] text-white hover:bg-[#14521a] cursor-pointer">Add to Cart</a>
  </div>
</article>
<?php endforeach;?>
</div>
<?php endif;?>
</main>
<script>
(function(){
  var key = 'lh_scroll_' + location.pathname;
  window.addEventListener('beforeunload', function(){ sessionStorage.setItem(key, window.scrollY); });
  var sy = sessionStorage.getItem(key);
  if (sy) { window.scrollTo(0, parseInt(sy)); sessionStorage.removeItem(key); }
})();

// AJAX Add to Cart
var toast=document.createElement('div');toast.id='cartToast';toast.className='fixed top-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-lg text-sm font-bold transition-all duration-300 translate-x-[120%] opacity-0 pointer-events-none';document.body.appendChild(toast);
function showToast(msg,ok){toast.textContent=msg;toast.className='fixed top-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-lg text-sm font-bold transition-all duration-300 '+(ok?'bg-[#e8f5e9] text-[#17611f] border border-[#c8e6c9]':'bg-red-50 text-red-700 border border-red-100');toast.classList.remove('translate-x-[120%]','opacity-0');toast.classList.add('translate-x-0','opacity-100');clearTimeout(toast._t);toast._t=setTimeout(function(){toast.classList.add('translate-x-[120%]','opacity-0');},3000);}

function updateCartCount(count){
  var badge=document.querySelector('a[href$="cart.php"] span');
  if(count>0){if(badge){badge.textContent=count}else{var a=document.querySelector('a[href$="cart.php"]');if(a){var s=document.createElement('span');s.className='absolute -top-1 -right-1 w-5 h-5 rounded-full bg-[#17611f] text-white text-[10px] font-bold flex items-center justify-center';s.textContent=count;a.appendChild(s)}}}
  else{if(badge)badge.remove()}
}

async function addToCart(id,qty){
  qty=qty||1;
  try{
    var r=await fetch('cart-actions-ajax.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'add',id:id,qty:qty})});
    var d=await r.json();
    showToast(d.message,d.success);
    if(d.success)updateCartCount(d.count);
  }catch(e){showToast('Network error',false);}
}
</script>
<?php include __DIR__.'/includes/footer.php'; ?>
</body>
</html>
