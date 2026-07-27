<?php
session_start();
require 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$orderNumber = $_GET['order'] ?? '';
if (!$orderNumber) { header("Location: products.php"); exit(); }

$stmt = $conn->prepare("SELECT o.*, COUNT(oi.id) AS item_count FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE o.order_number = ? AND o.user_id = ? GROUP BY o.id");
$stmt->execute([$orderNumber, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) { header("Location: products.php"); exit(); }

$items = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->execute([$order['id']]);
$orderItems = $items->fetchAll(PDO::FETCH_ASSOC);

$isPickup = $order['delivery_method'] === 'pickup';
$flowSteps = [
    ['label'=>'🌱 Preparing Order','icon'=>'1'],
];
if ($isPickup) {
    $flowSteps[] = ['label'=>'🛍️ Ready for Pick-Up','icon'=>'2'];
    $flowSteps[] = ['label'=>'✅ Picked Up','icon'=>'3'];
} else {
    $flowSteps[] = ['label'=>'🚚 Out for Delivery','icon'=>'2'];
    $flowSteps[] = ['label'=>'✅ Delivered','icon'=>'3'];
}
$flowSteps[] = ['label'=>'🎉 Completed','icon'=>'✓'];
$currentStep = 0; // New orders start at step 0 (Preparing)
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Confirmed | Luntiang H.A.P.A.G.</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>body{font-family:'Nunito',sans-serif;background:#f4faf5}</style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c]">
<?php include __DIR__.'/includes/header.php'; ?>
<main class="max-w-2xl mx-auto px-6 py-12">
  <div class="text-center mb-8">
    <div class="w-16 h-16 rounded-full bg-[#e8f5e9] flex items-center justify-center mx-auto mb-4">
      <svg class="w-8 h-8 text-[#17611f]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
    </div>
    <h1 class="text-2xl font-black mb-2">Order Confirmed!</h1>
    <p class="text-[#5a7a5c] text-sm">Your order has been placed. We'll start preparing your lettuce right away.</p>
  </div>

  <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
      <div><p class="text-xs text-[#5a7a5c]">Order Number</p><p class="font-black text-lg">#<?=htmlspecialchars($order['order_number'])?></p></div>
      <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700">🌱 Preparing Order</span>
    </div>

    <div class="mb-6">
      <h3 class="font-bold text-sm mb-3">Order Progress</h3>
      <div class="flex items-start gap-1">
        <?php foreach ($flowSteps as $i => $step): $done = $i <= $currentStep; ?>
          <div class="flex flex-col items-center flex-1">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-[11px] font-black <?=$done?'bg-[#17611f] text-white':'bg-gray-100 text-[#9e9e9e]'?>"><?=$done?'✓':$step['icon']?></div>
            <p class="text-[9px] text-center mt-1 font-bold <?=$done?'text-[#17611f]':'text-[#9e9e9e]'?>"><?=$step['label']?></p>
          </div>
          <?php if($i < count($flowSteps)-1): ?><div class="w-6 h-0.5 mt-4 <?=$done && $i < $currentStep?'bg-[#17611f]':'bg-gray-200'?>"></div><?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="p-4 rounded-xl bg-[#e8f5e9] mb-4">
      <p class="font-black text-sm">🌱 Harvest-on-Demand</p>
      <p class="text-xs text-[#5a7a5c] mt-1">Your lettuce will be harvested, quality-checked, and packed — all after order confirmation.</p>
    </div>

    <h3 class="font-bold text-sm mb-2">Items Ordered</h3>
    <?php foreach($orderItems as $oi):?>
      <div class="flex justify-between text-sm py-1 border-b border-[rgba(27,94,32,0.05)]"><span><?=htmlspecialchars($oi['product_name'])?> x <?=$oi['quantity']?></span><span class="font-bold">P<?=number_format($oi['price']*$oi['quantity'],2)?></span></div>
    <?php endforeach;?>
    <div class="flex justify-between font-black mt-2 pt-2 border-t border-[rgba(27,94,32,0.12)]"><span>Total</span><span class="text-[#17611f]">P<?=number_format($order['total'],2)?></span></div>

    <div class="grid grid-cols-2 gap-3 mt-4 text-sm">
      <div><span class="text-[#5a7a5c]">Method:</span> <span class="font-bold"><?=$isPickup?'Pick-Up':'Delivery'?></span></div>
      <div><span class="text-[#5a7a5c]">Payment:</span> <span class="font-bold"><?=strtoupper(str_replace('_',' ',$order['payment_method']))?></span></div>
      <?php if(!$isPickup):?>
        <div class="col-span-2"><span class="text-[#5a7a5c]">Address:</span> <span class="font-bold text-xs"><?=htmlspecialchars($order['delivery_address'].', '.$order['delivery_city'].', '.$order['delivery_province'])?></span></div>
      <?php endif;?>
      <?php if($order['delivery_fee']==0):?><div class="col-span-2"><span class="px-2 py-0.5 rounded-full text-xs font-bold bg-[#e8f5e9] text-[#17611f]">Free Delivery</span></div><?php endif;?>
    </div>
  </div>

  <div class="flex flex-col sm:flex-row gap-3 justify-center">
    <a href="order-tracking.php?order=<?=urlencode($order['order_number'])?>" class="px-6 py-3 rounded-xl bg-[#17611f] text-white text-sm font-bold text-center hover:bg-[#14521a] transition-colors">Track Order</a>
    <a href="my-profile.php?section=orders" class="px-6 py-3 rounded-xl border border-[rgba(27,94,32,0.12)] text-sm font-bold text-center hover:bg-[#e8f5e9] transition-colors">My Orders</a>
    <a href="products.php" class="px-6 py-3 rounded-xl border border-[rgba(27,94,32,0.12)] text-sm font-bold text-center hover:bg-[#e8f5e9] transition-colors">Continue Shopping</a>
  </div>
</main>
<?php include __DIR__.'/includes/footer.php'; ?>
</body>
</html>
