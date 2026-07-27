<?php
session_start();
require 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

// Handle reorder
if (isset($_GET['reorder']) && ($oid = (int)$_GET['reorder'])) {
    $items = $conn->prepare("SELECT oi.product_id, oi.quantity, oi.product_name FROM order_items oi WHERE oi.order_id = ?");
    $items->execute([$oid]);
    $reorderItems = $items->fetchAll(PDO::FETCH_ASSOC);
    $skipped = [];
    foreach ($reorderItems as $ri) {
        $check = $conn->prepare("SELECT id, plants_available FROM products WHERE id = ? AND is_active = 1");
        $check->execute([$ri['product_id']]);
        if ($prod = $check->fetch(PDO::FETCH_ASSOC)) {
            if ($prod['plants_available'] > 0) {
                $_SESSION['cart'][] = ['id' => (int)$ri['product_id'], 'qty' => min((int)$ri['quantity'], (int)$prod['plants_available'])];
            } else { $skipped[] = $ri['product_name']; }
        } else { $skipped[] = $ri['product_name']; }
    }
    $msg = 'Items from your previous order have been added to your cart.';
    if (!empty($skipped)) $msg .= ' Some items (' . implode(', ', $skipped) . ') are no longer available and were skipped.';
    $_SESSION['cart_message'] = $msg;
    header("Location: cart.php"); exit();
}

$orderNumber = $_GET['order'] ?? '';
$filter = $_GET['filter'] ?? 'all';

// Handle cancel order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $orderId = (int)$_POST['order_id'];
    $reason = trim($_POST['cancel_reason'] ?? '');
    $notes = trim($_POST['cancel_notes'] ?? '');
    if ($reason) {
        $conn->prepare("UPDATE orders SET status = 'cancelled', cancellation_reason = ?, cancellation_notes = ?, cancelled_at = NOW() WHERE id = ? AND user_id = ? AND status = 'preparing'")
             ->execute([$reason, $notes ?: null, $orderId, $_SESSION['user_id']]);
        $items = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $items->execute([$orderId]);
        foreach ($items->fetchAll(PDO::FETCH_ASSOC) as $oi) {
            $conn->prepare("UPDATE products SET plants_available = plants_available + ? WHERE id = ?")->execute([$oi['quantity'], $oi['product_id']]);
        }
        $promoCode = $conn->prepare("SELECT promo_code FROM orders WHERE id = ?");
        $promoCode->execute([$orderId]);
        if ($code = $promoCode->fetchColumn()) {
            $conn->prepare("UPDATE promotions SET used_count = GREATEST(0, used_count - 1) WHERE code = ?")->execute([$code]);
        }
        $_SESSION['cart_message'] = 'Order cancelled successfully.';
    }
    header("Location: order-tracking.php?order=" . urlencode($orderNumber));
    exit();
}

$orders = [];
if ($orderNumber) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
    $stmt->execute([$orderNumber, $_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $where = "WHERE user_id = ?"; $params = [$_SESSION['user_id']];
    if ($filter === 'active') $where .= " AND status NOT IN ('completed','cancelled','delivered')";
    elseif ($filter === 'completed') $where .= " AND status IN ('completed','delivered')";
    elseif ($filter !== 'all' && $filter !== 'active' && $filter !== 'completed') { $where .= " AND status = ?"; $params[] = $filter; }
    $stmt = $conn->prepare("SELECT * FROM orders $where ORDER BY created_at DESC LIMIT 20");
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$statusOrder = ['preparing','ready','delivered','completed'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Order Tracking | Luntiang H.A.P.A.G.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>body{font-family:'Nunito',sans-serif;background:#f4faf5}</style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c]">
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="max-w-4xl mx-auto px-6 py-8">
  <h1 class="text-3xl font-black mb-6">📦 Order Tracking</h1>

  <?php if (empty($orders)): ?>
    <div class="text-center py-16 bg-white rounded-xl border">
      <p class="text-5xl mb-4">📦</p><h2 class="text-xl font-black mb-2">No orders found</h2>
      <p class="text-[#5a7a5c] mb-4">Start shopping to see your orders here!</p>
      <a href="products.php" class="inline-flex px-6 py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a]">Browse Products</a>
    </div>

  <?php elseif ($orderNumber && count($orders) === 1):
    $order = $orders[0];
    $items = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $items->execute([$order['id']]);
    $orderItems = $items->fetchAll(PDO::FETCH_ASSOC);
    $isPickup = $order['delivery_method'] === 'pickup';
    $isCancelled = $order['status'] === 'cancelled';
    $currentIdx = array_search($order['status'], $statusOrder);

    $timeline = [
        ['key'=>'preparing','label'=>'🌱 Preparing Order','icon'=>'1','desc'=>'Payment confirmed. We are harvesting fresh lettuce, performing quality inspection, and carefully packing your order.'],
    ];
    if ($isPickup) {
        $timeline[] = ['key'=>'ready','label'=>'🛍️ Ready for Pick-Up','icon'=>'2','desc'=>'Your order has been packed and is ready for pickup at the farm.'];
        $timeline[] = ['key'=>'delivered','label'=>'✅ Picked Up','icon'=>'3','desc'=>'Your order has been successfully picked up.'];
    } else {
        $timeline[] = ['key'=>'ready','label'=>'🚚 Out for Delivery','icon'=>'2','desc'=>'Your order has been packed and is on its way to your delivery address.'];
        $timeline[] = ['key'=>'delivered','label'=>'✅ Delivered','icon'=>'3','desc'=>'Your order has been successfully delivered.'];
    }
    $timeline[] = ['key'=>'completed','label'=>'🎉 Completed','icon'=>'✓','desc'=>'Thank you for choosing Luntiang Hapag! Your order has been completed successfully.'];
  ?>
    <a href="order-tracking.php" class="inline-flex items-center gap-1 text-sm text-[#17611f] font-semibold hover:underline mb-6">← Back to All Orders</a>

    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-6 mb-4">
      <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
        <div>
          <p class="text-xs text-[#5a7a5c] font-bold">Order Number</p>
          <p class="font-black text-lg"><?= htmlspecialchars($order['order_number']) ?></p>
          <p class="text-xs text-[#9e9e9e]"><?= date('F j, Y · g:i A', strtotime($order['created_at'])) ?></p>
        </div>
        <?php if ($isCancelled): ?>
          <span class="px-3 py-1.5 rounded-full text-xs font-bold bg-red-100 text-red-700">❌ Cancelled</span>
        <?php elseif (in_array($order['status'],['completed','delivered'])): ?>
          <span class="px-3 py-1.5 rounded-full text-xs font-bold bg-green-100 text-green-700"><?= $order['status']==='completed'?'🎉 Completed':'✅ '.($isPickup?'Picked Up':'Delivered') ?></span>
        <?php else: ?>
          <span class="px-3 py-1.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">🌱 Preparing</span>
        <?php endif; ?>
      </div>

      <!-- 4-step timeline -->
      <div class="relative">
        <?php foreach ($timeline as $i => $step):
          $stepIdx = array_search($step['key'], $statusOrder);
          $isDone = !$isCancelled && $stepIdx !== false && $currentIdx !== false && $stepIdx <= $currentIdx;
          $isCurrent = $step['key'] === $order['status'];
        ?>
          <div class="flex gap-4">
            <div class="flex flex-col items-center">
              <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg <?= $isCurrent ? 'bg-[#17611f] text-white shadow-lg shadow-[#17611f]/20' : ($isDone ? 'bg-[#e8f5e9] text-[#17611f]' : ($isCancelled ? 'bg-red-50 text-red-400' : 'bg-gray-100 text-[#9e9e9e]')) ?>">
                <?= $isCurrent ? ($step['key']==='completed'?'✓':'●') : ($isDone ? '✓' : $step['icon']) ?>
              </div>
              <?php if ($i < count($timeline) - 1): ?>
                <div class="w-0.5 h-8 <?= $isDone && !$isCancelled ? 'bg-[#17611f]' : ($isCancelled ? 'bg-red-200' : 'bg-gray-200') ?>"></div>
              <?php endif; ?>
            </div>
            <div class="pb-6 flex-1">
              <p class="font-bold text-sm <?= $isCurrent ? 'text-[#17611f]' : ($isDone ? 'text-[#1a2e1c]' : ($isCancelled ? 'text-red-400' : 'text-[#9e9e9e]')) ?>">
                <?= $step['label'] ?>
                <?php if ($isCurrent && !$isCancelled): ?><span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#e8f5e9] text-[#17611f] animate-pulse">Current</span><?php endif; ?>
              </p>
              <p class="text-xs text-[#5a7a5c] mt-0.5"><?= $step['desc'] ?></p>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if ($isCancelled): ?>
          <div class="ml-14 p-4 rounded-xl bg-red-50 border border-red-100 text-sm">
            <p class="font-bold text-red-600 mb-1">❌ This order has been cancelled.</p>
            <?php if (!empty($order['cancellation_reason'])): ?>
              <p class="text-red-500 text-xs"><b>Reason:</b> <?= htmlspecialchars($order['cancellation_reason']) ?></p>
            <?php endif; ?>
            <?php if (!empty($order['cancellation_notes'])): ?>
              <p class="text-red-400 text-xs mt-1"><?= htmlspecialchars($order['cancellation_notes']) ?></p>
            <?php endif; ?>
            <?php if (!empty($order['cancelled_at'])): ?>
              <p class="text-red-300 text-[10px] mt-1">Cancelled: <?= date('M j, Y g:i A', strtotime($order['cancelled_at'])) ?></p>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Items -->
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-6 mb-4">
      <h3 class="font-black text-lg mb-4">🛒 Items</h3>
      <?php foreach ($orderItems as $oi): ?>
        <div class="flex justify-between text-sm py-2 border-b border-[rgba(27,94,32,0.05)] last:border-0">
          <span class="font-medium"><?= htmlspecialchars($oi['product_name']) ?> × <?= $oi['quantity'] ?></span>
          <span class="font-bold">₱<?= number_format($oi['price'] * $oi['quantity'], 2) ?></span>
        </div>
      <?php endforeach; ?>
      <div class="flex justify-between font-black text-lg mt-3 pt-3 border-t border-[rgba(27,94,32,0.12)]">
        <span>Total</span><span class="text-[#17611f]">₱<?= number_format($order['total'], 2) ?></span>
      </div>
    </div>

    <!-- Details -->
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-6 mb-4">
      <h3 class="font-black text-lg mb-4">📋 Order Details</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
        <div class="bg-[#f4faf5] rounded-xl p-3"><p class="text-xs text-[#5a7a5c] font-bold">Method</p><p class="font-bold mt-0.5"><?= $isPickup ? '🛍️ Farm Pick-Up' : '🚚 Delivery' ?></p></div>
        <div class="bg-[#f4faf5] rounded-xl p-3"><p class="text-xs text-[#5a7a5c] font-bold">Payment</p><p class="font-bold mt-0.5"><?= strtoupper(str_replace('_',' ',$order['payment_method'])) ?></p></div>
        <div class="bg-[#f4faf5] rounded-xl p-3"><p class="text-xs text-[#5a7a5c] font-bold">Delivery Fee</p><p class="font-bold mt-0.5 <?=$order['delivery_fee']==0?'text-green-600':''?>"><?=$order['delivery_fee']==0?'FREE 🎉':'₱'.number_format($order['delivery_fee'],2)?></p></div>
        <div class="bg-[#f4faf5] rounded-xl p-3"><p class="text-xs text-[#5a7a5c] font-bold">Date</p><p class="font-bold mt-0.5"><?=date('M j, Y',strtotime($order['created_at']))?></p></div>
        <?php if (!$isPickup): ?>
          <div class="sm:col-span-2 bg-[#f4faf5] rounded-xl p-3"><p class="text-xs text-[#5a7a5c] font-bold">Address</p><p class="font-bold mt-0.5"><?=htmlspecialchars($order['delivery_address'].', '.$order['delivery_city'].', '.$order['delivery_province'].' '.($order['delivery_zip']??''))?></p></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="flex flex-wrap gap-3">
      <a href="my-profile.php?section=orders" class="px-6 py-3 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a] transition-colors">My Orders</a>
      <a href="products.php" class="px-6 py-3 rounded-xl border border-[rgba(27,94,32,0.12)] text-sm font-bold hover:bg-[#e8f5e9] transition-colors">Continue Shopping</a>
      <?php if ($order['status'] === 'preparing'): ?>
        <button onclick="showCancelModal()" class="px-6 py-3 rounded-xl border border-red-200 text-sm font-bold text-red-500 hover:bg-red-50 transition-colors">❌ Cancel Order</button>
      <?php endif; ?>
      <?php if (in_array($order['status'], ['completed','delivered','cancelled'])): ?>
        <a href="?order=<?=urlencode($order['order_number'])?>&reorder=<?=$order['id']?>" class="px-6 py-3 rounded-xl bg-amber-500 text-white text-sm font-bold hover:bg-amber-600 transition-colors">🔄 Order Again</a>
      <?php endif; ?>
      <?php if (!empty($order['gift_note'])): ?>
        <div class="w-full mt-2 p-4 rounded-xl bg-[#fff8e1] border border-[#ffe082]"><p class="text-xs font-bold text-[#e65100] mb-1">🎁 Gift Note</p><p class="text-sm text-[#e65100]"><?=htmlspecialchars($order['gift_note'])?></p></div>
      <?php endif; ?>
    </div>

    <!-- Cancel Modal -->
    <div id="cancelModal" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
        <h3 class="font-black text-lg mb-2">Cancel Order</h3>
        <p class="text-sm text-[#5a7a5c] mb-4">Are you sure you want to cancel this order? Please select a reason.</p>
        <form method="POST" id="cancelForm">
          <input type="hidden" name="cancel_order" value="1">
          <input type="hidden" name="order_id" value="<?=$order['id']?>">
          <div class="space-y-2 mb-4">
            <?php foreach (['Changed my mind','Found better price','Ordered by mistake','Shipping takes too long','Want to change order','Other'] as $r): ?>
              <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-[#f4faf5] cursor-pointer text-sm">
                <input type="radio" name="cancel_reason" value="<?=$r?>" onchange="toggleOtherReason(this)" class="accent-[#17611f]" required> <?=$r?>
              </label>
            <?php endforeach; ?>
          </div>
          <div id="otherReasonBox" class="hidden mb-4">
            <textarea name="cancel_notes" rows="2" class="w-full border rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" placeholder="Please tell us more..."></textarea>
          </div>
          <div class="flex gap-3">
            <button type="button" onclick="hideCancelModal()" class="flex-1 py-2.5 rounded-xl border text-sm font-bold hover:bg-[#e8f5e9]">Keep Order</button>
            <button type="submit" class="flex-1 py-2.5 rounded-xl bg-red-500 text-white text-sm font-bold hover:bg-red-600">Confirm Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <script>
    function showCancelModal(){document.getElementById('cancelModal').classList.remove('hidden');}
    function hideCancelModal(){document.getElementById('cancelModal').classList.add('hidden');}
    function toggleOtherReason(r){document.getElementById('otherReasonBox').classList.toggle('hidden', r.value !== 'Other');}
    document.getElementById('cancelModal').addEventListener('click',function(e){if(e.target===this)hideCancelModal();});
    </script>

  <?php else: ?>
    <div class="flex flex-wrap gap-2 mb-6">
      <a href="?filter=all" class="px-4 py-2 rounded-full text-xs font-bold <?=$filter==='all'?'bg-[#17611f] text-white':'bg-white border text-[#5a7a5c]'?>">All</a>
      <a href="?filter=active" class="px-4 py-2 rounded-full text-xs font-bold <?=$filter==='active'?'bg-[#17611f] text-white':'bg-white border text-[#5a7a5c]'?>">Active</a>
      <a href="?filter=completed" class="px-4 py-2 rounded-full text-xs font-bold <?=$filter==='completed'?'bg-[#17611f] text-white':'bg-white border text-[#5a7a5c]'?>">Completed</a>
    </div>
    <div class="space-y-3">
      <?php foreach ($orders as $o): $isPickup = $o['delivery_method']==='pickup'; $isCanc = $o['status']==='cancelled'; ?>
        <a href="?order=<?=urlencode($o['order_number'])?>" class="block bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-5 hover:shadow-md transition-all group">
          <div class="flex items-center justify-between flex-wrap gap-2 mb-2">
            <div><p class="font-black text-lg group-hover:text-[#17611f]"><?=htmlspecialchars($o['order_number'])?></p><p class="text-xs text-[#9e9e9e]"><?=date('M j, Y · g:i A',strtotime($o['created_at']))?></p></div>
            <span class="px-3 py-1 rounded-full text-xs font-bold <?=in_array($o['status'],['completed','delivered'])?'bg-green-100 text-green-700':($isCanc?'bg-red-100 text-red-700':'bg-amber-100 text-amber-700')?>">
              <?= $isCanc?'❌ Cancelled':(in_array($o['status'],['completed','delivered'])?'🎉 '.($o['status']==='completed'?'Completed':($isPickup?'Picked Up':'Delivered')):'🌱 Preparing') ?>
            </span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-[#5a7a5c]"><?=$isPickup?'🛍️ Pick-Up':'🚚 Delivery'?> · <?=strtoupper($o['payment_method'])?></span>
            <span class="font-bold text-[#17611f]">₱<?=number_format($o['total'],2)?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
