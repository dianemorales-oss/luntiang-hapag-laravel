<?php
session_start();
require 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

// Ensure cart is loaded from DB (in case session expired but user is still logged in)
if (empty($_SESSION['cart'])) loadCartFromDb($conn);

// Only load selected cart items
$selectedIds = $_SESSION['selected_cart'] ?? [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sel'])) {
    $selectedIds = array_map('intval', $_POST['sel']);
    $_SESSION['selected_cart'] = $selectedIds;
}
$cartItems = []; $subtotal = 0;
if (!empty($_SESSION['cart']) && !empty($selectedIds)) {
    foreach ($_SESSION['cart'] as $item) {
        if (!in_array((int)$item['id'], $selectedIds)) continue;
        $stmt = $conn->prepare("SELECT id, name, slug, price, image, plants_available, harvest_time FROM products WHERE id = ? AND is_active = 1");
        $stmt->execute([$item['id']]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($prod) { $prod['qty'] = $item['qty']; $prod['line_total'] = $prod['price'] * $item['qty']; $subtotal += $prod['line_total']; $cartItems[] = $prod; }
    }
}
if (empty($cartItems)) { header("Location: cart.php"); exit(); }

$userStmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]); $user = $userStmt->fetch(PDO::FETCH_ASSOC);

$addrStmt = $conn->prepare("SELECT * FROM customer_addresses WHERE user_id = ? ORDER BY is_default DESC");
$addrStmt->execute([$_SESSION['user_id']]); $savedAddresses = $addrStmt->fetchAll(PDO::FETCH_ASSOC);
// Auto-select default saved address if one exists
$defaultAddr = null;
foreach ($savedAddresses as $sa) {
    if ($sa['is_default']) { $defaultAddr = $sa; break; }
}
// Fall back to user profile address — but parse registration address into components
$registrationAddr = $user['address'] ?? '';
// Try to parse "Street, City, Province ZIP" format from registration
$regParts = []; $regCity = 'Dasmariñas'; $regProvince = 'Cavite'; $regZip = '4114'; $regStreet = '';
if ($registrationAddr) {
    $commaParts = array_map('trim', explode(',', $registrationAddr));
    if (count($commaParts) >= 2) {
        $regStreet = $commaParts[0];
        $regCity = $commaParts[1] ?: 'Dasmariñas';
        $last = end($commaParts);
        $provZip = array_map('trim', explode(' ', trim($last)));
        if (count($provZip) >= 2) {
            $regProvince = $provZip[0];
            $regZip = $provZip[1];
        }
    } else {
        $regStreet = $registrationAddr;
    }
}
$defaultAddress = $defaultAddr['address'] ?? $regStreet;
$defaultCity = $defaultAddr['city'] ?? $regCity;
$defaultProvince = $defaultAddr['province'] ?? $regProvince;
$defaultZip = $defaultAddr['zip'] ?? $regZip;
$defaultAddrId = $defaultAddr['id'] ?? null;

// Check free delivery zone based on default address
$isFreeZone = stripos($defaultAddress, 'nostalji') !== false || stripos($defaultAddress, 'paliparan') !== false;

// Promo and delivery fee calculation
$promo = $_SESSION['applied_promo'] ?? null; $discount = 0; $deliveryFee = 50.00;
if ($promo) { $discount = $promo['discount_type'] === 'percentage' ? $subtotal * ($promo['discount_value'] / 100) : $promo['discount_value']; if ($promo['is_free_delivery']) $deliveryFee = 0; }
if ($isFreeZone) $deliveryFee = 0;
$total = max(0, $subtotal + $deliveryFee - $discount);
$message = $_SESSION['cart_message'] ?? ''; unset($_SESSION['cart_message']);

// Handle order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $deliveryMethod = $_POST['delivery_method'] ?? 'delivery';
    $paymentMethod = $_POST['payment_method'] ?? 'cod';
    $addr = trim($_POST['address'] ?? ''); $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? ''); $zip = trim($_POST['zip'] ?? '');
    $notes = trim($_POST['delivery_notes'] ?? ''); $giftNote = trim($_POST['gift_note'] ?? '');
    $preferredTime = trim($_POST['preferred_time'] ?? '');

    $isFreeZone = stripos($addr, 'nostalji') !== false || stripos($addr, 'paliparan') !== false;
    $deliveryFee = $deliveryMethod === 'pickup' ? 0 : ($isFreeZone ? 0 : 50.00);
    if ($promo && $promo['is_free_delivery']) $deliveryFee = 0;
    $total = max(0, $subtotal + $deliveryFee - $discount);
    // Generate sequential LH-0000 order number
    $maxOrder = $conn->query("SELECT order_number FROM orders ORDER BY id DESC LIMIT 1")->fetchColumn();
    $nextNum = 1;
    if ($maxOrder && preg_match('/^LH-(\d{4})$/', $maxOrder, $m)) {
        $nextNum = (int)$m[1] + 1;
    }
    $orderNumber = 'LH-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

    try {
        $conn->beginTransaction();
        $orderStmt = $conn->prepare("INSERT INTO orders (user_id, order_number, status, subtotal, delivery_fee, discount, total, delivery_method, payment_method, promo_code, delivery_address, delivery_city, delivery_province, delivery_zip, delivery_notes, gift_note, preferred_delivery_time, is_free_delivery, estimated_harvest_time, customer_name, customer_email, customer_phone) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $orderStmt->execute([$_SESSION['user_id'], $orderNumber, 'preparing', $subtotal, $deliveryFee, $discount, $total, $deliveryMethod, $paymentMethod, $promo['code'] ?? null, $deliveryMethod === 'pickup' ? 'Farm Pick-Up' : $addr, $city, $province, $zip, $notes, $giftNote ?: null, $preferredTime ?: null, $deliveryFee == 0 ? 1 : 0, '1-3 hours', $user['first_name'] . ' ' . $user['last_name'], $user['email'], $user['phone']]);
        $orderId = $conn->lastInsertId();

        foreach ($cartItems as $ci) {
            $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?,?,?,?,?)")->execute([$orderId, $ci['id'], $ci['name'], $ci['price'], $ci['qty']]);
            $conn->prepare("UPDATE products SET plants_available = GREATEST(0, plants_available - ?) WHERE id = ?")->execute([$ci['qty'], $ci['id']]);
        }
        if ($promo) { 
            $conn->prepare("UPDATE promotions SET used_count = used_count + 1 WHERE code = ?")->execute([$promo['code']]);
            // Remove claimed coupon after use (single-use)
            $conn->prepare("DELETE FROM claimed_coupons WHERE user_id = ? AND promotion_id = ?")->execute([$_SESSION['user_id'], $promo['id']]);
        }

        // Create admin notification for new order
        try {
            $notifStmt = $conn->prepare("INSERT INTO notifications (type, related_id, title, message, customer_name, is_read, related_link) VALUES ('order_new', ?, ?, ?, ?, 0, ?)");
            $notifStmt->execute([$orderId, 'New Order: ' . $orderNumber, 'New order from ' . $user['first_name'] . ' ' . $user['last_name'] . ' — Total: P' . number_format($total, 2) . ' — ' . ($deliveryMethod === 'pickup' ? 'Pick-Up' : 'Delivery'), $user['first_name'] . ' ' . $user['last_name'], 'admin-orders.php']);
        } catch (Exception $e) { /* non-critical */ }

        $conn->commit();

        // Remove only purchased items from cart, keep the rest
        $purchasedIds = array_column($cartItems, 'id');
        $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], fn($i) => !in_array((int)$i['id'], $purchasedIds)));
        syncCartToDb($conn);  // ← persist remaining items
        unset($_SESSION['selected_cart'], $_SESSION['applied_promo']);

        header("Location: order-confirmation.php?order=" . urlencode($orderNumber)); exit();
    } catch (Exception $e) { $conn->rollBack(); $message = "Order failed: " . $e->getMessage(); }
}
?><!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout | Luntiang H.A.P.A.G.</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>body{font-family:'Nunito',sans-serif;background:#f4faf5}</style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c]">
<?php include __DIR__.'/includes/header.php'; ?>
<main class="max-w-5xl mx-auto px-6 py-8">
<h1 class="text-2xl font-black mb-6">Checkout</h1>
<?php if($message):?><div class="mb-4 rounded-xl px-4 py-3 text-sm bg-red-50 text-red-700 border border-red-100"><?=htmlspecialchars($message)?></div><?php endif;?>

<form method="POST" class="grid lg:grid-cols-3 gap-6">
<div class="lg:col-span-2 space-y-5">
  <div class="bg-white rounded-xl border p-5">
    <h2 class="font-black text-lg mb-3">Delivery Method</h2>
    <div class="grid grid-cols-2 gap-3">
      <label class="flex items-center gap-3 p-4 rounded-xl border-2 border-[#17611f] bg-[#e8f5e9] cursor-pointer"><input type="radio" name="delivery_method" value="delivery" checked onchange="toggleAddr()" class="accent-[#17611f]"><div><p class="font-bold text-sm">Delivery</p><p class="text-xs text-[#5a7a5c]">Same-day delivery</p></div></label>
      <label class="flex items-center gap-3 p-4 rounded-xl border-2 border-[rgba(27,94,32,0.12)] cursor-pointer hover:bg-[#e8f5e9]"><input type="radio" name="delivery_method" value="pickup" onchange="toggleAddr()" class="accent-[#17611f]"><div><p class="font-bold text-sm">Pick-Up</p><p class="text-xs text-[#5a7a5c]">Free, ready in 1-3 hours</p><p class="text-[10px] text-[#17611f] font-bold mt-1">📍 Nostalji Subd., Paliparan I, Dasmarinas, Cavite</p></div></label>
    </div>
  </div>
  <div class="bg-white rounded-xl border p-5" id="addressSection">
    <h2 class="font-black text-lg mb-3">Delivery Address</h2>
    <?php if(!empty($savedAddresses)):?><div class="mb-3 space-y-2"><?php foreach($savedAddresses as $sa):?>
      <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer hover:bg-[#e8f5e9] text-sm"><input type="radio" name="saved_address_id" value="<?=$sa['id']?>" class="accent-[#17611f]" onchange="fillAddr(this)" data-address="<?=htmlspecialchars($sa['address'])?>" data-city="<?=htmlspecialchars($sa['city'])?>" data-province="<?=htmlspecialchars($sa['province'])?>" data-zip="<?=htmlspecialchars($sa['zip'])?>"><div><span class="font-bold"><?=htmlspecialchars($sa['label'])?>:</span> <?=htmlspecialchars($sa['address'])?>, <?=htmlspecialchars($sa['city'])?></div></label>
    <?php endforeach;?></div><?php endif;?>
    <div class="space-y-3">
      <textarea name="address" rows="2" class="w-full border rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" placeholder="Street address, barangay" required><?=htmlspecialchars($defaultAddress)?></textarea>
      <div class="grid grid-cols-2 gap-3"><input name="city" class="w-full border rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" placeholder="City" required value="<?=htmlspecialchars($defaultCity)?>"><input name="province" class="w-full border rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" placeholder="Province" required value="<?=htmlspecialchars($defaultProvince)?>"></div>
      <input name="zip" class="w-full border rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" placeholder="ZIP Code" value="<?=htmlspecialchars($defaultZip)?>">
    </div>
  </div>
  <div class="bg-white rounded-xl border p-5">
    <h2 class="font-black text-lg mb-3">Additional Information</h2>
    <div class="space-y-3">
      <div><label class="text-xs font-bold text-[#5a7a5c]">Preferred Delivery Time</label><select name="preferred_time" class="w-full border rounded-xl px-3 py-2 text-sm mt-1"><option value="">As soon as possible</option><option>Morning (8 AM - 12 PM)</option><option>Afternoon (12 PM - 4 PM)</option><option>Late Afternoon (4 PM - 6 PM)</option></select></div>
      <div><label class="text-xs font-bold text-[#5a7a5c]">Delivery Notes</label><textarea name="delivery_notes" rows="2" class="w-full border rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" placeholder="Special instructions..."></textarea></div>
      <div><label class="text-xs font-bold text-[#5a7a5c]">Gift Note (optional)</label><textarea name="gift_note" rows="2" class="w-full border rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" placeholder="Add a gift message..."></textarea></div>
    </div>
  </div>
  <div class="bg-white rounded-xl border p-5">
    <h2 class="font-black text-lg mb-3">Payment Method</h2>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
      <label class="flex items-center gap-2 p-3 rounded-xl border-2 border-[#17611f] bg-[#e8f5e9] cursor-pointer text-sm"><input type="radio" name="payment_method" value="cod" checked class="accent-[#17611f]"> 💵 COD</label>
      <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer hover:bg-[#e8f5e9] text-sm"><input type="radio" name="payment_method" value="gcash" class="accent-[#17611f]"> <img src="images/payment/gcash.png" class="h-5 inline" alt="GCash" onerror="this.outerHTML='💙'" style="display:inline"> GCash</label>
      <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer hover:bg-[#e8f5e9] text-sm"><input type="radio" name="payment_method" value="maya" class="accent-[#17611f]"> <img src="images/payment/maya.png" class="h-5 inline" alt="Maya" onerror="this.outerHTML='💜'" style="display:inline"> Maya</label>
      <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer hover:bg-[#e8f5e9] text-sm"><input type="radio" name="payment_method" value="bank_transfer" class="accent-[#17611f]"> 🏦 Bank</label>
    </div>
  </div>
</div>
<div class="bg-white rounded-xl border p-5 h-fit sticky top-24">
  <h2 class="font-black text-lg mb-4">Order Summary</h2>
  <?php foreach($cartItems as $ci):?>
    <div class="flex justify-between text-sm mb-2 items-center">
      <span class="flex-1"><?=htmlspecialchars($ci['name'])?> <span class="text-xs text-[#5a7a5c]">× <?=$ci['qty']?></span></span>
        <span class="font-bold w-20 text-right">P<?=number_format($ci['line_total'],2)?></span>
      </div>
    </div>
  <?php endforeach;?>
  <hr class="my-3 border-[rgba(27,94,32,0.08)]">
  <div class="space-y-1 text-sm mb-3">
    <div class="flex justify-between"><span class="text-[#5a7a5c]">Subtotal</span><span class="font-bold">P<?=number_format($subtotal,2)?></span></div>
    <div class="flex justify-between"><span class="text-[#5a7a5c]">Delivery Fee</span><span class="font-bold <?=$deliveryFee==0?'text-green-600':''?>" id="delFeeDisp"><?=$deliveryFee==0?'FREE':'P'.number_format($deliveryFee,2)?></span></div>
    <?php if($discount>0):?><div class="flex justify-between"><span class="text-[#5a7a5c]">Discount</span><span class="font-bold text-red-500">-P<?=number_format($discount,2)?></span></div><?php endif;?>
  </div>
  <div class="flex justify-between font-black text-lg border-t pt-3 mb-4"><span>Total</span><span class="text-[#17611f]" id="totalDisp">P<?=number_format($total,2)?></span></div>
  <div class="p-3 rounded-xl bg-[#e8f5e9] mb-4 text-center text-xs"><p class="font-black">Harvest-on-Demand</p><p class="text-[#5a7a5c] mt-1">Estimated harvest: 1-3 hours after payment confirmation</p></div>
  <button type="submit" name="place_order" value="1" class="w-full py-3 rounded-xl bg-[#17611f] text-white font-bold hover:bg-[#14521a]">Place Order</button>
</div>
</form>
</main>
<script>
function toggleAddr(){
  var m=document.querySelector('input[name=delivery_method]:checked').value;
  var s=document.getElementById('addressSection');s.style.opacity=m==='pickup'?'0.5':'1';
  s.querySelectorAll('input,textarea').forEach(e=>{if(e.type!=='radio')e.required=m!=='pickup';});
  var isPickup = m==='pickup';
  var isFree = <?=$isFreeZone?'true':'false'?>;
  var d = isPickup ? 0 : (isFree ? 0 : 50);
  document.getElementById('delFeeDisp').textContent = d===0 ? 'FREE' : 'P'+d.toFixed(2);
  document.getElementById('delFeeDisp').className='font-bold '+(d===0?'text-green-600':'');
  document.getElementById('totalDisp').textContent='P'+Math.max(0,<?=$subtotal?>+d-<?=$discount?>).toFixed(2);
}
function fillAddr(r){document.querySelector('textarea[name=address]').value=r.dataset.address;document.querySelector('input[name=city]').value=r.dataset.city;document.querySelector('input[name=province]').value=r.dataset.province;document.querySelector('input[name=zip]').value=r.dataset.zip||'';}
document.querySelectorAll('input[name=payment_method],input[name=delivery_method]').forEach(r=>{r.addEventListener('change',()=>{document.querySelectorAll('input[name='+r.name+']').forEach(r2=>{r2.closest('label').classList.toggle('border-[#17611f]',r2.checked);r2.closest('label').classList.toggle('bg-[#e8f5e9]',r2.checked);r2.closest('label').classList.toggle('border-[rgba(27,94,32,0.12)]',!r2.checked);});});});
</script>
<?php include __DIR__.'/includes/footer.php'; ?>
</body>
</html>
