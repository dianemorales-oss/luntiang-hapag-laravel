<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin-auth.php';
$activePage = 'orders';
$message = $_SESSION['admin_message'] ?? ''; unset($_SESSION['admin_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    $allowed = ['preparing','ready','delivered','completed','cancelled'];
    if (in_array($newStatus, $allowed)) {
        $conn->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$newStatus, $orderId]);
        $labels = ['preparing'=>'Preparing Order','ready'=>'Ready','delivered'=>'Delivered/Picked Up','completed'=>'Completed','cancelled'=>'Cancelled'];
        $_SESSION['admin_message'] = "Order updated to: " . $labels[$newStatus];
        try {
            $oStmt = $conn->prepare("SELECT order_number, customer_name FROM orders WHERE id = ?");
            $oStmt->execute([$orderId]);
            $oInfo = $oStmt->fetch(PDO::FETCH_ASSOC);
            if ($oInfo) {
                $conn->prepare("INSERT INTO notifications (type, related_id, title, message, customer_name, is_read, related_link) VALUES ('order_status', ?, ?, ?, ?, 0, ?)")
                     ->execute([$orderId, 'Order Status Updated', 'Order ' . $oInfo['order_number'] . ' → ' . $labels[$newStatus], $oInfo['customer_name'], 'admin-orders.php']);
            }
        } catch (Exception $e) {}
    }
    header("Location: admin-orders.php"); exit();
}

$filter = $_GET['filter'] ?? 'all';
$where = $filter !== 'all' ? "WHERE status = ?" : "";
$params = $filter !== 'all' ? [$filter] : [];
$orders = $conn->prepare("SELECT * FROM orders $where ORDER BY created_at DESC LIMIT 50");
$orders->execute($params);
$allOrders = $orders->fetchAll(PDO::FETCH_ASSOC);

$preparingCount = $conn->query("SELECT COUNT(*) FROM orders WHERE status='preparing'")->fetchColumn();
$readyCount = $conn->query("SELECT COUNT(*) FROM orders WHERE status='ready'")->fetchColumn();
$todayOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$todayRevenue = $conn->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at)=CURDATE() AND status NOT IN ('cancelled')")->fetchColumn();

$flowLabels = ['preparing'=>'🌱 Preparing Order','ready'=>'Ready','delivered'=>'Delivered/Picked Up','completed'=>'🎉 Completed','cancelled'=>'❌ Cancelled'];

// 4-step flow: preparing → ready → delivered → completed
$flowNext = ['preparing'=>'ready','ready'=>'delivered','delivered'=>'completed'];
$flowPrev = ['ready'=>'preparing','delivered'=>'ready','completed'=>'delivered'];
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Management | Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>body{font-family:'Nunito',sans-serif;background:#f4faf5}</style>
</head>
<body class="bg-[#f4faf5] flex"><?php include __DIR__.'/includes/admin-sidebar.php'; ?>
<div class="flex-1 flex flex-col min-w-0">
<?php $pageTitle = 'Orders'; include __DIR__.'/includes/admin-topbar.php'; ?>

<main class="flex-1 p-8 overflow-auto">
  <h1 class="text-2xl font-black mb-1">Order Management</h1>
  <p class="text-sm text-[#5a7a5c] mb-6">4-Step Order Flow: Preparing → Ready → Delivered → Completed</p>
  <?php if ($message): ?><div class="mb-4 rounded-xl px-4 py-3 text-sm bg-[#e8f5e9] text-[#17611f] border border-[#c8e6c9]"><?=htmlspecialchars($message)?></div><?php endif; ?>

  <div class="grid grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border p-4"><p class="text-xs text-[#5a7a5c] font-bold">Today</p><p class="text-2xl font-black"><?=$todayOrders?></p></div>
    <div class="bg-white rounded-xl border p-4"><p class="text-xs text-[#5a7a5c] font-bold">Revenue</p><p class="text-2xl font-black text-[#17611f]">P<?=number_format($todayRevenue,2)?></p></div>
    <div class="bg-white rounded-xl border p-4"><p class="text-xs text-[#5a7a5c] font-bold">Preparing</p><p class="text-2xl font-black text-amber-600"><?=$preparingCount?></p></div>
    <div class="bg-white rounded-xl border p-4"><p class="text-xs text-[#5a7a5c] font-bold">Ready</p><p class="text-2xl font-black text-[#17611f]"><?=$readyCount?></p></div>
  </div>

  <div class="flex flex-wrap gap-1.5 mb-4">
    <?php foreach (['all'=>'All','preparing'=>'Preparing','ready'=>'Ready','delivered'=>'Delivered','completed'=>'Completed','cancelled'=>'Cancelled'] as $k=>$l): ?>
      <a href="?filter=<?=$k?>" class="px-3 py-1 rounded-full text-xs font-bold <?=$filter===$k?'bg-[#17611f] text-white':'bg-white border text-[#5a7a5c]'?>"><?=$l?></a>
    <?php endforeach; ?>
  </div>

  <div class="bg-white rounded-xl border overflow-hidden"><div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead><tr class="bg-[#f4faf5] text-[#5a7a5c] text-xs uppercase"><th class="p-3 text-left">Order #</th><th class="p-3 text-left">Customer</th><th class="p-3 text-left">Items</th><th class="p-3 text-left">Total</th><th class="p-3 text-left">Method</th><th class="p-3 text-left">Status</th><th class="p-3 text-left">Process</th></tr></thead>
      <tbody>
        <?php foreach ($allOrders as $o):
          $items = $conn->prepare("SELECT product_name, quantity FROM order_items WHERE order_id = ?");
          $items->execute([$o['id']]); $orderItems = $items->fetchAll(PDO::FETCH_ASSOC);
          $prev = $flowPrev[$o['status']] ?? null;
          $next = $flowNext[$o['status']] ?? null;
          $isEnd = in_array($o['status'], ['completed','cancelled']);
        ?>
          <tr class="border-t border-[rgba(27,94,32,0.05)]">
            <td class="p-3 font-bold text-sm"><?=htmlspecialchars($o['order_number'])?><br><span class="text-xs text-[#9e9e9e]"><?=date('M j, g:i A',strtotime($o['created_at']))?></span></td>
            <td class="p-3 text-sm"><?=htmlspecialchars($o['customer_name'])?></td>
            <td class="p-3 text-xs"><?=implode(', ',array_map(fn($i)=>$i['product_name'].' x'.$i['quantity'],$orderItems))?></td>
            <td class="p-3 font-bold">P<?=number_format($o['total'],2)?></td>
            <td class="p-3 text-xs"><?=$o['delivery_method']==='pickup'?'Pick-Up':'Delivery'?></td>
            <td class="p-3"><span class="px-2 py-0.5 rounded-full text-xs font-bold <?=in_array($o['status'],['completed','delivered'])?'bg-green-100 text-green-700':($o['status']==='cancelled'?'bg-red-100 text-red-700':'bg-amber-100 text-amber-700')?>"><?=$flowLabels[$o['status']]?></span></td>
            <td class="p-3">
              <?php if (!$isEnd): ?>
              <div class="flex items-center gap-1">
                <?php if ($prev): ?>
                <form method="POST" class="inline">
                  <input type="hidden" name="order_id" value="<?=$o['id']?>"><input type="hidden" name="update_status" value="1"><input type="hidden" name="status" value="<?=$prev?>">
                  <button type="submit" class="px-2.5 py-1 rounded-l-lg border border-[rgba(27,94,32,0.12)] text-xs font-bold text-[#5a7a5c] hover:bg-[#e8f5e9] transition-colors">← Prev</button>
                </form>
                <?php endif; ?>
                <span class="px-2 py-1 text-[10px] font-bold text-[#5a7a5c] bg-gray-50 rounded"><?=$flowLabels[$o['status']]?></span>
                <?php if ($next): ?>
                <form method="POST" class="inline">
                  <input type="hidden" name="order_id" value="<?=$o['id']?>"><input type="hidden" name="update_status" value="1"><input type="hidden" name="status" value="<?=$next?>">
                  <button type="submit" class="px-2.5 py-1 rounded-r-lg border border-[rgba(27,94,32,0.12)] text-xs font-bold text-[#17611f] hover:bg-[#e8f5e9] transition-colors">Next →</button>
                </form>
                <?php endif; ?>
              </div>
              <?php else: ?><span class="text-xs text-[#9e9e9e]">—</span><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</main>
</div></body></html>
