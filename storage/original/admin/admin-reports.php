<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin-auth.php';
$activePage = 'reports';

// Date filter from GET param, default to today
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$ts = strtotime($selectedDate);
$selectedYear = date('Y', $ts);
$selectedMonth = date('m', $ts);

// Day: selected date
$daySales = $conn->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at) = ? AND status NOT IN ('cancelled')");
$daySales->execute([$selectedDate]);
$daySales = (float)$daySales->fetchColumn();
$dayOrders = $conn->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ? AND status NOT IN ('cancelled')");
$dayOrders->execute([$selectedDate]);
$dayOrders = (int)$dayOrders->fetchColumn();

// Week: Monday-Sunday of the week containing selected date
$weekStart = date('Y-m-d', strtotime('monday this week', $ts));
$weekEnd = date('Y-m-d', strtotime('sunday this week', $ts));
$weekSales = $conn->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND status NOT IN ('cancelled')");
$weekSales->execute([$weekStart, $weekEnd]);
$weekSales = (float)$weekSales->fetchColumn();

// Month: selected month/year
$monthStart = "$selectedYear-$selectedMonth-01";
$monthEnd = date('Y-m-t', strtotime($monthStart));
$monthSales = $conn->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND status NOT IN ('cancelled')");
$monthSales->execute([$monthStart, $monthEnd]);
$monthSales = (float)$monthSales->fetchColumn();

// Total orders (all time)
$totalOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE status NOT IN ('cancelled')")->fetchColumn();
$avgOrder = $conn->query("SELECT COALESCE(AVG(total),0) FROM orders WHERE status NOT IN ('cancelled')")->fetchColumn();

// Delivery vs Pickup for selected month
$deliveryCount = $conn->prepare("SELECT COUNT(*) FROM orders WHERE delivery_method='delivery' AND DATE(created_at) BETWEEN ? AND ? AND status NOT IN ('cancelled')");
$deliveryCount->execute([$monthStart, $monthEnd]);
$deliveryCount = (int)$deliveryCount->fetchColumn();

$pickupCount = $conn->prepare("SELECT COUNT(*) FROM orders WHERE delivery_method='pickup' AND DATE(created_at) BETWEEN ? AND ? AND status NOT IN ('cancelled')");
$pickupCount->execute([$monthStart, $monthEnd]);
$pickupCount = (int)$pickupCount->fetchColumn();

// Customers
$newCust = $conn->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = ?");
$newCust->execute([$selectedDate]);
$newCust = (int)$newCust->fetchColumn();
$totalCust = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Best selling (all time)
$bestSellers = $conn->query("SELECT oi.product_name, SUM(oi.quantity) as total_qty, SUM(oi.quantity * oi.price) as revenue FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.status NOT IN ('cancelled') GROUP BY oi.product_name ORDER BY total_qty DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Daily sales for chart: 7 days starting from selected date
$chartStart = date('Y-m-d', strtotime('-6 days', $ts));
$chartEnd = $selectedDate;
$dailyData = $conn->prepare("SELECT DATE(created_at) as d, COALESCE(SUM(total),0) as rev, COUNT(*) as cnt FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND status NOT IN ('cancelled') GROUP BY DATE(created_at) ORDER BY d");
$dailyData->execute([$chartStart, $chartEnd]);
$dailyData = $dailyData->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sales Analytics | Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>body{font-family:'Nunito',sans-serif;background:#f4faf5}</style>
</head>
<body class="bg-[#f4faf5] flex"><?php include __DIR__.'/includes/admin-sidebar.php'; ?>
<div class="flex-1 flex flex-col min-w-0">
<?php $pageTitle = 'Sales'; include __DIR__.'/includes/admin-topbar.php'; ?>
<main class="flex-1 p-8 overflow-auto">
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-black mb-1">Sales Analytics</h1>
      <p class="text-sm text-[#5a7a5c]">
        <?= date('F j, Y', $ts) ?> &nbsp;·&nbsp; Week: <?= date('M j', strtotime($weekStart)) ?> – <?= date('M j, Y', strtotime($weekEnd)) ?>
      </p>
    </div>
    <form method="GET" class="flex items-center gap-2">
      <input type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>" max="<?= date('Y-m-d') ?>"
             class="border border-[rgba(27,94,32,0.12)] rounded-xl px-4 py-2.5 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-[#52b788]/40"
             onchange="this.form.submit()" />
    </form>
  </div>

  <!-- KPI Cards -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-[#5a7a5c] font-bold">Today's Sales</p>
      <p class="text-2xl font-black text-[#17611f]">₱<?= number_format($daySales,2) ?></p>
      <p class="text-[10px] text-[#9e9e9e] mt-1"><?= $dayOrders ?> order(s)</p>
    </div>
    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-[#5a7a5c] font-bold">This Week</p>
      <p class="text-2xl font-black text-[#17611f]">₱<?= number_format($weekSales,2) ?></p>
      <p class="text-[10px] text-[#9e9e9e] mt-1"><?= date('M j', strtotime($weekStart)) ?> – <?= date('M j', strtotime($weekEnd)) ?></p>
    </div>
    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-[#5a7a5c] font-bold">This Month</p>
      <p class="text-2xl font-black text-[#17611f]">₱<?= number_format($monthSales,2) ?></p>
      <p class="text-[10px] text-[#9e9e9e] mt-1"><?= date('F Y', $ts) ?></p>
    </div>
    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-[#5a7a5c] font-bold">Total Orders</p>
      <p class="text-2xl font-black"><?= $totalOrders ?></p>
      <p class="text-[10px] text-[#9e9e9e] mt-1">All time</p>
    </div>
    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-[#5a7a5c] font-bold">Avg Order Value</p>
      <p class="text-2xl font-black">₱<?= number_format($avgOrder,2) ?></p>
    </div>
    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-[#5a7a5c] font-bold">🚚 Delivery</p>
      <p class="text-2xl font-black"><?= $deliveryCount ?></p>
      <p class="text-[10px] text-[#9e9e9e] mt-1"><?= date('F', $ts) ?></p>
    </div>
    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-[#5a7a5c] font-bold">🛍️ Pick-Up</p>
      <p class="text-2xl font-black"><?= $pickupCount ?></p>
      <p class="text-[10px] text-[#9e9e9e] mt-1"><?= date('F', $ts) ?></p>
    </div>
    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-[#5a7a5c] font-bold">Customers</p>
      <p class="text-2xl font-black"><?= $totalCust ?> <span class="text-sm text-green-600">+<?= $newCust ?> new</span></p>
    </div>
  </div>

  <div class="grid md:grid-cols-2 gap-6">
    <!-- 7-Day Revenue Chart -->
    <div class="bg-white rounded-xl border p-5">
      <h2 class="font-black text-lg mb-4">📈 7-Day Revenue (ending <?= date('M j', strtotime($chartEnd)) ?>)</h2>
      <div class="space-y-2">
        <?php $revs = array_column($dailyData, 'rev'); $maxRev = !empty($revs) ? max($revs) : 1; ?>
        <?php if (empty($dailyData)): ?>
          <p class="text-sm text-[#9e9e9e] py-4 text-center">No sales data for this period.</p>
        <?php else: foreach ($dailyData as $d): 
          $pct = $maxRev > 0 ? ($d['rev'] / $maxRev) * 100 : 0; ?>
          <div class="flex items-center gap-3">
            <span class="text-xs text-[#5a7a5c] w-14"><?= date('D M j', strtotime($d['d'])) ?></span>
            <div class="flex-1 bg-[#e8f5e9] rounded-full h-6 overflow-hidden">
              <div class="bg-[#17611f] h-full rounded-full flex items-center pl-2 text-xs text-white font-bold" style="width:<?= max($pct, 10) ?>%">₱<?= number_format($d['rev'],0) ?></div>
            </div>
            <span class="text-xs text-[#9e9e9e]"><?= $d['cnt'] ?> orders</span>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <!-- Best Sellers -->
    <div class="bg-white rounded-xl border p-5">
      <h2 class="font-black text-lg mb-4">🏆 Best Selling Products</h2>
      <?php foreach ($bestSellers as $i => $bs): ?>
        <div class="flex items-center justify-between py-2 border-b border-[rgba(27,94,32,0.05)]">
          <div class="flex items-center gap-3">
            <span class="w-7 h-7 rounded-full bg-[#e8f5e9] flex items-center justify-center font-black text-sm text-[#17611f]"><?= $i+1 ?></span>
            <span class="text-sm font-bold"><?= htmlspecialchars($bs['product_name']) ?></span>
          </div>
          <div class="text-right">
            <p class="font-bold text-sm"><?= $bs['total_qty'] ?> sold</p>
            <p class="text-xs text-[#5a7a5c]">₱<?= number_format($bs['revenue'],2) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Delivery vs Pick-Up -->
    <div class="bg-white rounded-xl border p-5">
      <h2 class="font-black text-lg mb-4">🚚 Delivery vs 🛍️ Pick-Up (<?= date('F', $ts) ?>)</h2>
      <?php $delPct = ($deliveryCount + $pickupCount) > 0 ? round(($deliveryCount / ($deliveryCount + $pickupCount)) * 100) : 0; ?>
      <div class="flex items-center gap-4 mb-2">
        <div class="flex-1 bg-gray-200 rounded-full h-8 overflow-hidden">
          <div class="bg-[#17611f] h-full rounded-full flex items-center justify-center text-xs text-white font-bold" style="width:<?= $delPct ?>%"><?= $delPct > 15 ? '🚚 Delivery ' . $delPct . '%' : '' ?></div>
        </div>
        <span class="text-sm font-bold"><?= 100-$delPct ?>% 🛍️ Pick-Up</span>
      </div>
      <div class="flex justify-between text-sm text-[#5a7a5c] mt-4">
        <span>🚚 Delivery: <strong><?= $deliveryCount ?></strong></span>
        <span>🛍️ Pick-Up: <strong><?= $pickupCount ?></strong></span>
      </div>
    </div>

    <!-- Customer Stats -->
    <div class="bg-white rounded-xl border p-5">
      <h2 class="font-black text-lg mb-4">👥 Customer Overview</h2>
      <div class="space-y-3">
        <div class="flex justify-between"><span class="text-sm text-[#5a7a5c]">Total Customers</span><span class="font-bold"><?= $totalCust ?></span></div>
        <div class="flex justify-between"><span class="text-sm text-[#5a7a5c]">New on <?= date('M j', $ts) ?></span><span class="font-bold text-green-600">+<?= $newCust ?></span></div>
        <div class="flex justify-between"><span class="text-sm text-[#5a7a5c]">Total Orders (All Time)</span><span class="font-bold"><?= $totalOrders ?></span></div>
        <div class="flex justify-between"><span class="text-sm text-[#5a7a5c]">Avg. Order Value</span><span class="font-bold">₱<?= number_format($avgOrder,2) ?></span></div>
      </div>
    </div>
  </div>
</main>
</div></body></html>
