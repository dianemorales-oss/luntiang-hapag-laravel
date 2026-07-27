<?php
session_start();
require 'config.php';
require_once __DIR__ . '/includes/form-helpers.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { session_unset(); session_destroy(); header("Location: login.php?expired=1"); exit(); }

$uid = $_SESSION['user_id'];
$section = $_GET['section'] ?? 'overview';

$orderStats = [];
foreach (['preparing','ready','delivered','completed','cancelled'] as $s) {
    $st = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = ?");
    $st->execute([$uid, $s]); $orderStats[$s] = (int)$st->fetchColumn();
}
$totalOrders = array_sum($orderStats);

$activeOrder = $conn->prepare("SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count FROM orders o WHERE o.user_id = ? AND o.status NOT IN ('completed','cancelled','delivered') ORDER BY o.created_at DESC LIMIT 1");
$activeOrder->execute([$uid]); $activeOrder = $activeOrder->fetch(PDO::FETCH_ASSOC);
$activeItems = [];
if ($activeOrder) { $items = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?"); $items->execute([$activeOrder['id']]); $activeItems = $items->fetchAll(PDO::FETCH_ASSOC); }

$orderTab = $_GET['otab'] ?? 'all';
$orderWhere = "WHERE user_id = ?"; $orderParams = [$uid];
if ($orderTab === 'active') $orderWhere .= " AND status NOT IN ('completed','cancelled','delivered')";
elseif ($orderTab !== 'all') { $orderWhere .= " AND status = ?"; $orderParams[] = $orderTab; }
$allOrders = $conn->prepare("SELECT * FROM orders $orderWhere ORDER BY created_at DESC LIMIT 20");
$allOrders->execute($orderParams); $allOrders = $allOrders->fetchAll(PDO::FETCH_ASSOC);

$otSt = $conn->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ? AND status IN ('open','in_progress')"); $otSt->execute([$uid]); $openTickets = (int)$otSt->fetchColumn();
$prSt = $conn->prepare("SELECT COUNT(*) FROM return_requests WHERE user_id = ? AND status = 'pending'"); $prSt->execute([$uid]); $pendingReturns = (int)$prSt->fetchColumn();

if (isset($_GET['reorder']) && ($oid = (int)$_GET['reorder'])) {
    $items = $conn->prepare("SELECT oi.product_id, oi.quantity FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.id = ? AND o.user_id = ?");
    $items->execute([$oid, $uid]); $_SESSION['cart'] = [];
    foreach ($items->fetchAll() as $it) { $_SESSION['cart'][] = ['id' => (int)$it['product_id'], 'qty' => (int)$it['quantity']]; }
    $_SESSION['cart_message'] = 'Items added to cart.'; header("Location: cart.php"); exit();
}

$addresses = $conn->prepare("SELECT * FROM customer_addresses WHERE user_id = ? ORDER BY is_default DESC");
$addresses->execute([$uid]); $addresses = $addresses->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_address'])) {
    $alabel = trim($_POST['address_label'] ?? 'Home');
    $aaddr = trim($_POST['address'] ?? '');
    $acity = ucwords(strtolower(trim($_POST['city'] ?? '')));
    $aprov = ucwords(strtolower(trim($_POST['province'] ?? '')));
    $azip = trim($_POST['zip'] ?? '');
    if ($aaddr && $acity) {
        $conn->prepare("INSERT INTO customer_addresses (user_id, label, address, city, province, zip) VALUES (?,?,?,?,?,?)")
             ->execute([$uid, $alabel, $aaddr, $acity, $aprov, $azip]);
        header("Location: my-profile.php?section=addresses&saved=1"); exit();
    }
}
if (isset($_GET['deladdr']) && ($daid = (int)$_GET['deladdr'])) {
    $conn->prepare("DELETE FROM customer_addresses WHERE id = ? AND user_id = ?")->execute([$daid, $uid]);
    header("Location: my-profile.php?section=addresses"); exit();
}
if (isset($_GET['setdefault']) && ($sdid = (int)$_GET['setdefault'])) {
    $conn->prepare("UPDATE customer_addresses SET is_default = 0 WHERE user_id = ?")->execute([$uid]);
    $conn->prepare("UPDATE customer_addresses SET is_default = 1 WHERE id = ? AND user_id = ?")->execute([$sdid, $uid]);
    header("Location: my-profile.php?section=addresses"); exit();
}

$coupons = $conn->prepare("SELECT p.*, cc.claimed_at FROM promotions p INNER JOIN claimed_coupons cc ON p.id = cc.promotion_id WHERE cc.user_id = ? AND p.is_active = 1 ORDER BY cc.claimed_at DESC");
$coupons->execute([$uid]); 
$coupons = $coupons->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = [
    'preparing'=>'🌱 Preparing Order','ready'=>'Ready',
    'delivered'=>'Delivered/Picked Up','completed'=>'🎉 Completed','cancelled'=>'❌ Cancelled'
];
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | Luntiang H.A.P.A.G.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>body{font-family:'Nunito',sans-serif;background:#f4faf5}</style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c]">
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="max-w-7xl mx-auto px-6 py-8">
  <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
    <div><h1 class="text-2xl font-black">Welcome, <?=htmlspecialchars($user['first_name'])?></h1><p class="text-[#5a7a5c] text-sm">Manage your orders, deliveries, and account</p></div>
    <div class="flex gap-2"><a href="products.php" class="px-4 py-2 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a]">Shop Now</a><a href="cart.php" class="px-4 py-2 rounded-xl border text-sm font-bold hover:bg-[#e8f5e9]">Cart</a></div>
  </div>
  <div class="flex flex-wrap gap-1.5 mb-6 pb-4 border-b border-[rgba(27,94,32,0.08)]">
    <?php foreach (['overview'=>'Overview','orders'=>'Orders','addresses'=>'Addresses','coupons'=>'Coupons','support'=>'Support','profile'=>'Profile'] as $k=>$l): ?>
      <a href="?section=<?=$k?>" class="px-4 py-2 rounded-full text-sm font-bold <?=$section===$k?'bg-[#17611f] text-white':'bg-white border text-[#5a7a5c] hover:bg-[#e8f5e9]'?>"><?=$l?></a>
    <?php endforeach; ?>
  </div>
  <?php if(isset($_GET['saved'])):?><div class="mb-4 rounded-xl px-4 py-3 text-sm bg-[#e8f5e9] text-[#17611f] border border-[#c8e6c9]">Address saved.</div><?php endif;?>

  <?php if ($section === 'overview'): ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-8">
      <?php foreach ([['Total Orders',$totalOrders,'text-[#17611f]'],['Preparing',$orderStats['preparing'],'text-amber-600'],['Ready',$orderStats['ready'],'text-[#17611f]'],['Delivered',$orderStats['delivered'],'text-green-600'],['Completed',$orderStats['completed'],'text-[#17611f]'],['Cancelled',$orderStats['cancelled'],'text-red-600']] as $c):?>
        <div class="bg-white rounded-xl border p-4"><p class="text-2xl font-black <?=$c[2]?>"><?=$c[1]?></p><p class="text-xs text-[#5a7a5c] font-bold mt-1"><?=$c[0]?></p></div>
      <?php endforeach;?>
    </div>
    <div class="grid lg:grid-cols-2 gap-6">
      <div class="bg-white rounded-xl border p-6">
        <h2 class="font-black text-lg mb-4">Current Order</h2>
        <?php if($activeOrder):?>
          <div class="space-y-3"><div class="flex justify-between"><span class="text-sm text-[#5a7a5c]">#<?=htmlspecialchars($activeOrder['order_number'])?></span><span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700"><?=$statusLabels[$activeOrder['status']]?></span></div>
          <?php foreach($activeItems as $ai):?><p class="text-sm"><?=$ai['quantity']?> x <?=htmlspecialchars($ai['product_name'])?></p><?php endforeach;?>
          <div class="flex justify-between text-sm pt-2 border-t"><span class="text-[#5a7a5c]">Total</span><span class="font-black text-[#17611f]">P<?=number_format($activeOrder['total'],2)?></span></div>
          <a href="order-tracking.php?order=<?=urlencode($activeOrder['order_number'])?>" class="block text-center py-2 rounded-xl bg-[#e8f5e9] text-[#17611f] text-sm font-bold hover:bg-[#c8e6c9]">Track</a></div>
        <?php else:?><p class="text-[#5a7a5c] text-sm py-4">No active orders. <a href="products.php" class="text-[#17611f] font-bold hover:underline">Shop now</a></p><?php endif;?>
      </div>
      <div class="bg-white rounded-xl border p-6">
        <h2 class="font-black text-lg mb-4">Support</h2>
        <div class="flex gap-4 mb-4"><div class="flex-1 bg-[#f4faf5] rounded-xl p-4 text-center"><p class="text-2xl font-black"><?=$openTickets?></p><p class="text-xs text-[#5a7a5c]">Open Tickets</p></div><div class="flex-1 bg-[#f4faf5] rounded-xl p-4 text-center"><p class="text-2xl font-black"><?=$pendingReturns?></p><p class="text-xs text-[#5a7a5c]">Pending Returns</p></div></div>
        <div class="space-y-2"><a href="submit-ticket.php" class="block text-center py-2 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a]">Submit Ticket</a><a href="live-chat.php" class="block text-center py-2 rounded-xl border text-sm font-bold hover:bg-[#e8f5e9]">Live Chat</a></div>
      </div>
    </div>

  <?php elseif ($section === 'orders'): ?>
    <div class="flex flex-wrap gap-1.5 mb-4">
      <?php foreach (['active'=>'Active','preparing'=>'Preparing','ready'=>'Ready','completed'=>'Completed'] as $k=>$l):?>
        <a href="?section=orders&otab=<?=$k?>" class="px-3 py-1.5 rounded-full text-xs font-bold <?=$orderTab===$k?'bg-[#17611f] text-white':'bg-white border text-[#5a7a5c]'?>"><?=$l?></a>
      <?php endforeach;?>
    </div>
    <?php if(empty($allOrders)):?><div class="text-center py-16 bg-white rounded-xl border"><p class="text-[#5a7a5c]">No orders</p></div>
    <?php else:?><div class="space-y-3"><?php foreach($allOrders as $o):?>
      <div class="bg-white rounded-xl border p-5">
        <div class="flex justify-between flex-wrap gap-2 mb-2"><div><span class="font-black">#<?=htmlspecialchars($o['order_number'])?></span><span class="text-xs text-[#5a7a5c] ml-2"><?=date('M j, Y',strtotime($o['created_at']))?></span></div><span class="px-2.5 py-0.5 rounded-full text-xs font-bold <?=in_array($o['status'],['completed','delivered'])?'bg-green-100 text-green-700':(in_array($o['status'],['cancelled'])?'bg-red-100 text-red-700':'bg-amber-100 text-amber-700')?>"><?=$statusLabels[$o['status']]?></span></div>
        <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-[#5a7a5c] mb-2"><span>Method: <b><?=$o['delivery_method']==='pickup'?'Pick-Up':'Delivery'?></b></span><span>Payment: <b><?=strtoupper($o['payment_method'])?></b></span><span>Total: <b class="text-[#17611f]">P<?=number_format($o['total'],2)?></b></span></div>
        <div class="flex flex-wrap gap-2"><a href="order-tracking.php?order=<?=urlencode($o['order_number'])?>" class="px-3 py-1.5 rounded-lg bg-[#e8f5e9] text-[#17611f] text-xs font-bold hover:bg-[#c8e6c9]">Track</a><?php if(in_array($o['status'],['completed','delivered'])):?><a href="?section=orders&reorder=<?=$o['id']?>" class="px-3 py-1.5 rounded-lg border text-xs font-bold hover:bg-[#e8f5e9]">Reorder</a><?php endif;?></div>
      </div>
    <?php endforeach;?></div><?php endif;?>

  <?php elseif ($section === 'addresses'): ?>
    <div class="grid md:grid-cols-2 gap-4">
      <div><h2 class="font-black text-lg mb-4">Saved Addresses</h2>
        <?php if(empty($addresses)):?><p class="text-[#5a7a5c] text-sm">No saved addresses.</p><?php endif;?>
        <?php foreach($addresses as $a):?>
          <div class="bg-white rounded-xl border p-4 mb-3">
            <div class="flex justify-between mb-1"><span class="font-bold text-sm"><?=htmlspecialchars($a['label'])?> <?=$a['is_default']?'(Default)':''?></span><div class="flex gap-2 text-xs"><?php if(!$a['is_default']):?><a href="?section=addresses&setdefault=<?=$a['id']?>" class="text-[#17611f] font-bold">Set Default</a><?php endif;?><a href="?section=addresses&deladdr=<?=$a['id']?>" class="text-red-500 font-bold" onclick="return confirm('Delete?')">Delete</a></div></div>
            <p class="text-sm text-[#5a7a5c]"><?=htmlspecialchars($a['address'])?>, <?=htmlspecialchars($a['city'])?>, <?=htmlspecialchars($a['province'])?> <?=htmlspecialchars($a['zip'])?></p>
          </div>
        <?php endforeach;?>
      </div>
      <div class="bg-white rounded-xl border p-5"><h2 class="font-black text-lg mb-4">Add Address</h2>
        <form method="POST" class="space-y-3"><input type="hidden" name="save_address" value="1">
          <div><label class="text-xs font-bold text-[#5a7a5c]">Label</label><select name="address_label" class="w-full border rounded-xl px-3 py-2 text-sm mt-1"><option>Home</option><option>Office</option><option>Restaurant</option></select></div>
          <div><label class="text-xs font-bold text-[#5a7a5c]">Address</label><textarea name="address" rows="2" class="w-full border rounded-xl px-3 py-2 text-sm mt-1" required></textarea></div>
          <div class="grid grid-cols-2 gap-3"><input name="city" placeholder="City" class="border rounded-xl px-3 py-2 text-sm" required><input name="province" placeholder="Province" class="border rounded-xl px-3 py-2 text-sm" required></div>
          <input name="zip" placeholder="ZIP Code" class="w-full border rounded-xl px-3 py-2 text-sm">
          <button type="submit" class="w-full py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a]">Save Address</button>
        </form>
      </div>
    </div>

  <?php elseif ($section === 'coupons'): ?>
    <h2 class="font-black text-lg mb-4">My Claimed Coupons</h2>
    <?php if(empty($coupons)):?><p class="text-[#5a7a5c] text-sm">No coupons claimed yet. Visit the <a href="index.php" class="text-[#17611f] font-bold hover:underline">homepage</a> to claim available coupons.</p><?php else:?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4"><?php foreach($coupons as $c):?>
      <div class="bg-white rounded-xl border p-5"><p class="font-black text-lg text-[#17611f]"><?=htmlspecialchars($c['code'])?></p><p class="text-sm text-[#5a7a5c] mt-1"><?=htmlspecialchars($c['description'])?></p><p class="text-xs text-[#9e9e9e] mt-2"><?=$c['discount_type']==='percentage'?$c['discount_value'].'% off':'P'.number_format($c['discount_value'],2).' off'?><?=$c['is_free_delivery']?' + Free Delivery':''?><?=$c['min_order']>0?' (min P'.number_format($c['min_order'],2).')':''?></p><p class="text-[10px] text-[#9e9e9e] mt-2">Claimed: <?=date('M j, Y',strtotime($c['claimed_at']))?></p></div>
    <?php endforeach;?></div><?php endif;?>

  <?php elseif ($section === 'support'): ?>
    <h2 class="font-black text-lg mb-4">Customer Support</h2>
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
      <a href="submit-ticket.php" class="group bg-white rounded-xl border p-5 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
        <div class="w-12 h-12 rounded-full bg-[#fff8e1] flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
          <svg class="w-5 h-5 text-[#f9a825]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <p class="font-bold text-sm">Submit Ticket</p><p class="text-xs text-[#5a7a5c] mt-1">Report an issue</p>
      </a>
      <a href="returns-refund.php" class="group bg-white rounded-xl border p-5 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
        <div class="w-12 h-12 rounded-full bg-[#e8f5e9] flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
          <svg class="w-5 h-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8h-1"/></svg>
        </div>
        <p class="font-bold text-sm">Return & Refund</p><p class="text-xs text-[#5a7a5c] mt-1">Request return</p>
      </a>
      <a href="live-chat.php" class="group bg-white rounded-xl border p-5 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
        <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
          <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        </div>
        <p class="font-bold text-sm">Live Chat</p><p class="text-xs text-[#5a7a5c] mt-1">Talk to us now</p>
      </a>
      <a href="feedback.php" class="group bg-white rounded-xl border p-5 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
        <div class="w-12 h-12 rounded-full bg-purple-50 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
          <svg class="w-5 h-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/></svg>
        </div>
        <p class="font-bold text-sm">Send Feedback</p><p class="text-xs text-[#5a7a5c] mt-1">Share thoughts</p>
      </a>
      <a href="faq.php" class="group bg-white rounded-xl border p-5 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
        <div class="w-12 h-12 rounded-full bg-pink-50 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
          <svg class="w-5 h-5 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <p class="font-bold text-sm">FAQs</p><p class="text-xs text-[#5a7a5c] mt-1">Common questions</p>
      </a>
    </div>

  <?php elseif ($section === 'profile'): ?>
    <div class="bg-white rounded-xl border p-6"><h2 class="font-black text-lg mb-4">Profile Information</h2>
      <div class="grid md:grid-cols-2 gap-4 mb-4">
        <div><p class="text-xs text-[#5a7a5c] font-bold">First Name</p><p class="text-sm font-bold"><?=htmlspecialchars($user['first_name'])?></p></div>
        <div><p class="text-xs text-[#5a7a5c] font-bold">Last Name</p><p class="text-sm font-bold"><?=htmlspecialchars($user['last_name'])?></p></div>
        <div><p class="text-xs text-[#5a7a5c] font-bold">Email</p><p class="text-sm font-bold"><?=htmlspecialchars($user['email'])?></p></div>
        <div><p class="text-xs text-[#5a7a5c] font-bold">Phone</p><p class="text-sm font-bold"><?=htmlspecialchars($user['phone']??'-')?></p></div>
        <div class="md:col-span-2"><p class="text-xs text-[#5a7a5c] font-bold">Address</p><p class="text-sm font-bold"><?=htmlspecialchars($user['address']??'-')?></p></div>
      </div>
      <div class="flex gap-3"><a href="edit-profile.php" class="px-4 py-2 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a]">Edit Profile</a><a href="change-password.php" class="px-4 py-2 rounded-xl border text-sm font-bold hover:bg-[#e8f5e9]">Change Password</a></div>
    </div>
  <?php endif; ?>
</main>
</body>
</html>
