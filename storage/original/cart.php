<?php
session_start();
require 'config.php';
$isLoggedIn = isset($_SESSION['user_id']);

// If logged in and session cart is empty, try loading from DB
if ($isLoggedIn && empty($_SESSION['cart'])) loadCartFromDb($conn);

if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    $_SESSION['cart'] = []; unset($_SESSION['selected_cart']);
    $_SESSION['cart_message'] = 'Cart cleared.';
    header("Location: cart.php"); exit();
}

// Save selection from form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sel'])) {
    $_SESSION['selected_cart'] = array_map('intval', $_POST['sel']);
}

$cartItems = []; $subtotal = 0; $selectedSubtotal = 0;
$selectedIds = $_SESSION['selected_cart'] ?? [];
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $idx => $item) {
        $stmt = $conn->prepare("SELECT id, name, slug, price, image, plants_available, harvest_time FROM products WHERE id = ? AND is_active = 1");
        $stmt->execute([$item['id']]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($prod) {
            $prod['qty'] = $item['qty']; $prod['cart_idx'] = $idx;
            $prod['line_total'] = $prod['price'] * $item['qty'];
            $subtotal += $prod['line_total'];
            $prod['selected'] = in_array((int)$item['id'], $selectedIds);
            if ($prod['selected']) $selectedSubtotal += $prod['line_total'];
            $cartItems[] = $prod;
        }
    }
}

$allSelected = count($cartItems) > 0 && count(array_filter($cartItems, fn($c) => $c['selected'])) === count($cartItems);
$selectedCount = count(array_filter($cartItems, fn($c) => $c['selected']));

$deliveryFee = 50.00; $isFreeDeliveryZone = false;
if ($isLoggedIn) {
    $us = $conn->prepare("SELECT address FROM users WHERE id = ?"); $us->execute([$_SESSION['user_id']]);
    $u = $us->fetch(PDO::FETCH_ASSOC); $ua = $u['address'] ?? '';
    $isFreeDeliveryZone = stripos($ua, 'nostalji') !== false || stripos($ua, 'paliparan') !== false;
}
$promo = $_SESSION['applied_promo'] ?? null; $discount = 0;
if ($promo) { $discount = $promo['discount_type'] === 'percentage' ? $selectedSubtotal * ($promo['discount_value'] / 100) : $promo['discount_value']; if ($promo['is_free_delivery']) $deliveryFee = 0; }
if ($isFreeDeliveryZone) $deliveryFee = 0;
if ($selectedCount === 0) $deliveryFee = 0;
$total = max(0, $selectedSubtotal + $deliveryFee - $discount);
$message = $_SESSION['cart_message'] ?? ''; unset($_SESSION['cart_message']);

// Get claimed coupons for this customer
$claimedCoupons = [];
if ($isLoggedIn) {
    $cc = $conn->prepare("SELECT p.* FROM promotions p INNER JOIN claimed_coupons cc ON p.id = cc.promotion_id WHERE cc.user_id = ? AND p.is_active = 1 AND (p.expires_at IS NULL OR p.expires_at >= CURDATE())");
    $cc->execute([$_SESSION['user_id']]);
    $claimedCoupons = $cc->fetchAll(PDO::FETCH_ASSOC);
}
?><!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shopping Cart | Luntiang H.A.P.A.G.</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>body{font-family:'Nunito',sans-serif;background:#f4faf5}</style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c]">
<?php include __DIR__.'/includes/header.php'; ?>
<main class="max-w-5xl mx-auto px-6 py-8">
<h1 class="text-2xl font-black mb-6">Shopping Cart</h1>
<?php if ($message): ?><div class="mb-4 rounded-xl px-4 py-3 text-sm bg-[#e8f5e9] text-[#17611f] border border-[#c8e6c9]"><?=htmlspecialchars($message)?></div><?php endif; ?>
<?php if (empty($cartItems)): ?>
<div class="text-center py-16 bg-white rounded-xl border"><p class="text-xl font-bold mb-2">Your cart is empty</p>
<p class="text-[#5a7a5c] mb-4">Browse our fresh hydroponic lettuce.</p>
<a href="products.php" class="inline-flex px-6 py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a]">Browse Products</a></div>
<?php else: ?>
<form id="cartForm" method="POST" action="checkout.php">
<div class="grid lg:grid-cols-3 gap-6">
<div class="lg:col-span-2 space-y-3">
  <!-- Select All -->
  <div class="bg-white rounded-xl border p-4 flex items-center gap-3">
    <input type="checkbox" id="selectAll" onchange="toggleAll(this)" <?=$allSelected?'checked':''?> class="w-4 h-4 accent-[#17611f]">
    <label for="selectAll" class="font-bold text-sm cursor-pointer select-none">Select All (<?=count($cartItems)?> items)</label>
  </div>
  <?php foreach ($cartItems as $ci): ?>
  <div class="bg-white rounded-xl border p-4 flex gap-4 items-start" id="cart-item-<?=$ci['id']?>">
    <input type="checkbox" name="sel[]" value="<?=$ci['id']?>" <?=$ci['selected']?'checked':''?> onchange="recalc()" class="mt-1 w-4 h-4 accent-[#17611f] item-cb">
    <img src="<?=htmlspecialchars($ci['image']?:'images/lettuce/hero-farm.png')?>" class="w-20 h-20 rounded-lg object-cover" alt="">
    <div class="flex-1">
      <a href="product.php?slug=<?=urlencode($ci['slug'])?>" class="font-bold text-sm hover:text-[#17611f]"><?=htmlspecialchars($ci['name'])?></a>
      <p class="text-xs text-[#5a7a5c]">Harvest time: <?=htmlspecialchars($ci['harvest_time']?:'1-3 hours')?></p>
      <p class="font-black text-[#17611f] text-sm mt-1">P<?=number_format($ci['price'],2)?> each</p>
      <div class="flex items-center gap-3 mt-2 flex-wrap">
        <span class="inline-flex items-center border border-[rgba(27,94,32,0.12)] rounded-lg overflow-hidden">
          <button type="button" class="px-3 py-1.5 font-black text-sm hover:bg-[#e8f5e9] text-[#5a7a5c] transition-colors" onclick="updateQty(<?=$ci['id']?>,-1)" <?=$ci['qty']<=1?'disabled':''?>>−</button>
          <span class="px-3 py-1.5 text-sm font-bold" id="qty-<?=$ci['id']?>"><?=$ci['qty']?></span>
          <button type="button" class="px-3 py-1.5 font-black text-sm hover:bg-[#e8f5e9] text-[#5a7a5c] transition-colors" onclick="updateQty(<?=$ci['id']?>,1)">+</button>
        </span>
        <button type="button" onclick="removeItem(<?=$ci['id']?>)" class="px-3 py-1 rounded-lg border border-red-200 text-xs font-bold text-red-500 hover:bg-red-50 transition-colors">Remove</button>
      </div>
    </div>
    <p class="font-black text-[#17611f]" id="line-<?=$ci['id']?>">P<?=number_format($ci['line_total'],2)?></p>
  </div>
  <?php endforeach; ?>
  <a href="?clear=1" onclick="return confirm('Clear all items?')" class="inline-flex items-center gap-1 px-4 py-2 rounded-xl border border-red-200 text-sm font-bold text-red-500 hover:bg-red-50 transition-colors">Clear Cart</a>
</div>
<!-- Summary -->
<div class="bg-white rounded-xl border p-5 h-fit sticky top-24">
  <h2 class="font-black text-lg mb-4">Order Summary</h2>
  <div class="space-y-2 text-sm mb-4">
    <div class="flex justify-between"><span class="text-[#5a7a5c]">Selected Items</span><span class="font-bold" id="selCount"><?=$selectedCount?></span></div>
    <div class="flex justify-between"><span class="text-[#5a7a5c]">Subtotal</span><span class="font-bold" id="subtotalDisplay">P<?=number_format($selectedSubtotal,2)?></span></div>
    <div class="flex justify-between"><span class="text-[#5a7a5c]">Delivery Fee</span><span class="font-bold <?=$deliveryFee==0?'text-green-600':''?>" id="delFeeDisplay"><?=$deliveryFee==0?'FREE':'P'.number_format($deliveryFee,2)?></span></div>
    <?php if($isFreeDeliveryZone && $deliveryFee==0):?><p class="text-xs text-green-600">Free delivery - Nostalji Subdivision</p><?php endif;?>
    <div class="flex justify-between" id="discRow" <?=$promo?'':'style="display:none"'?>><span class="text-[#5a7a5c]">Discount</span><span class="font-bold text-red-500" id="discDisplay"><?=$promo?'-P'.number_format($discount,2):''?></span></div>
  </div>
  <div class="flex justify-between font-black text-lg border-t pt-3 mb-4"><span>Total</span><span class="text-[#17611f]" id="totalDisplay">P<?=number_format($total,2)?></span></div>

  <!-- Coupons: claimed + manual -->
  <details class="mb-4"><summary class="text-sm font-bold text-[#17611f] cursor-pointer hover:underline">Apply Coupon</summary>
    <?php if (!empty($claimedCoupons)): ?>
      <p class="text-xs text-[#9e9e9e] mt-2 mb-1">Your claimed coupons:</p>
      <div class="space-y-1 mb-2">
        <?php foreach ($claimedCoupons as $cc):
          $label = $cc['discount_type']==='percentage' ? $cc['discount_value'].'% Off' : 'P'.$cc['discount_value'].' Off';
          if ($cc['is_free_delivery']) $label .= ' + Free Delivery';
          $active = $promo && $promo['code'] === $cc['code'];
        ?>
          <button type="button" onclick="applyCoupon('<?=htmlspecialchars($cc['code'])?>')" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-xs font-bold border <?=$active?'bg-[#e8f5e9] border-[#17611f] text-[#17611f]':'border-[rgba(27,94,32,0.12)] hover:bg-[#e8f5e9] text-[#5a7a5c]'?>">
            <span><?=htmlspecialchars($cc['code'])?> — <?=$label?></span>
            <?php if ($active): ?><span class="text-[#17611f]">✓</span><?php endif; ?>
          </button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if (empty($claimedCoupons)): ?><p class="text-xs text-[#9e9e9e] mt-2">No claimed coupons available.</p><?php endif; ?>
    <?php if($promo):?><p class="text-xs text-green-600 mt-1" id="promoLabel"><?=htmlspecialchars($promo['code'])?> applied</p><?php endif;?>
  </details>
  <?php if ($isLoggedIn): ?><button type="submit" class="w-full py-3 rounded-xl bg-[#17611f] text-white font-bold hover:bg-[#14521a]">Proceed to Checkout</button>
  <?php else: ?><a href="login.php" class="block text-center w-full py-3 rounded-xl bg-[#17611f] text-white font-bold hover:bg-[#14521a]">Login to Checkout</a><p class="text-xs text-center mt-2 text-[#9e9e9e]">You need an account to complete your order</p><?php endif; ?>
  <a href="products.php" class="block text-center w-full py-2.5 mt-2 rounded-xl border text-sm font-bold hover:bg-[#e8f5e9]">Continue Shopping</a>
</div>
</div>
</form>
<?php endif; ?>
</main>

<script>
const items = <?=json_encode(array_map(function($c){return['id'=>(int)$c['id'],'price'=>(float)$c['price'],'qty'=>(int)$c['qty']];},$cartItems))?>;
const cbs = document.querySelectorAll('.item-cb');
const selectAllCb = document.getElementById('selectAll');
let currentPromo = <?= $promo ? json_encode(['code'=>$promo['code'],'discount_type'=>$promo['discount_type'],'discount_value'=>(float)$promo['discount_value'],'is_free_delivery'=>(bool)$promo['is_free_delivery']]) : 'null' ?>;
let currentDiscount = <?= $discount ?>;

function recalc(){
  let st=0, cnt=0;
  cbs.forEach(cb=>{if(cb.checked){let id=parseInt(cb.value);let it=items.find(i=>i.id===id);if(it){st+=it.price*it.qty;cnt++;}}});
  let df=<?=$isFreeDeliveryZone?1:0?>?0:(cnt===0?0:50);
  if(currentPromo && currentPromo.is_free_delivery) df=0;
  let d=0;
  if(currentPromo){d=currentPromo.discount_type==='percentage'?st*(currentPromo.discount_value/100):currentPromo.discount_value;}
  if(cnt===0) df=0;
  let tot=Math.max(0,st+df-d);
  document.getElementById('selCount').textContent=cnt;
  document.getElementById('subtotalDisplay').textContent='P'+st.toFixed(2);
  document.getElementById('delFeeDisplay').textContent=df===0?'FREE':'P'+df.toFixed(2);
  document.getElementById('delFeeDisplay').className='font-bold '+(df===0?'text-green-600':'');
  document.getElementById('totalDisplay').textContent='P'+tot.toFixed(2);
  let dr=document.getElementById('discRow'), dd=document.getElementById('discDisplay');
  if(currentPromo){dr.style.display='';dd.textContent='-P'+d.toFixed(2);}else{dr.style.display='none';}
  if(selectAllCb) selectAllCb.checked = cnt === cbs.length && cbs.length > 0;
}
function toggleAll(el){cbs.forEach(cb=>cb.checked=el.checked);recalc();}

// AJAX quantity update
async function updateQty(id,delta){
  let it=items.find(i=>i.id===id);if(!it)return;
  let newQty=it.qty+delta;if(newQty<1)return;
  try{
    let r=await fetch('cart-actions-ajax.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'update',id:id,qty:newQty})});
    let d=await r.json();
    if(d.success){it.qty=d.qty;document.getElementById('qty-'+id).textContent=d.qty;document.getElementById('line-'+id).textContent='P'+d.line_total;recalc();}
  }catch(e){}
}

// AJAX remove item
async function removeItem(id){
  if(!confirm('Remove this item?'))return;
  try{
    let r=await fetch('cart-actions-ajax.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'remove',id:id})});
    let d=await r.json();
    if(d.success){document.getElementById('cart-item-'+id).remove();items=items.filter(i=>i.id!==id);recalc();if(items.length===0)location.reload();}
  }catch(e){}
}

// Select claimed coupon — toggle on/off, no page reload
async function applyCoupon(code){
  try{
    // If already applied, remove it
    if(currentPromo && currentPromo.code === code){
      let r=await fetch('cart-actions-ajax.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'remove_promo'})});
      let d=await r.json();
      if(d.success){currentPromo=null;recalc();document.getElementById('promoLabel').style.display='none';
        document.querySelectorAll('details button[onclick^="applyCoupon"]').forEach(b=>{b.classList.remove('bg-[#e8f5e9]','border-[#17611f]','text-[#17611f]');b.classList.add('border-[rgba(27,94,32,0.12)]','text-[#5a7a5c]');});
      }
      return;
    }
    let r=await fetch('cart-actions-ajax.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'select_promo',promo_code:code})});
    let d=await r.json();
    if(d.success){
      currentPromo=d.promo;
      recalc();
      document.getElementById('promoLabel').textContent=d.promo.code+' applied';
      document.getElementById('promoLabel').style.display='';
      document.querySelectorAll('details button[onclick^="applyCoupon"]').forEach(b=>{b.classList.remove('bg-[#e8f5e9]','border-[#17611f]','text-[#17611f]');b.classList.add('border-[rgba(27,94,32,0.12)]','text-[#5a7a5c]');});
    } else { alert(d.message||'Could not apply coupon'); }
  }catch(e){}
}

// Initial
if(selectAllCb) selectAllCb.checked = <?=$allSelected?'true':'false'?>;
</script>

<?php include __DIR__.'/includes/footer.php'; ?>
</body>
</html>
