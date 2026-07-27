<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin-auth.php';
$activePage = 'customers';
$emailParam = trim($_GET['email'] ?? '');
$search = trim($_GET['q'] ?? '');
$customer = null; $tickets = []; $orders = []; $returns = []; $message = '';

if ($emailParam !== '') {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$emailParam]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($customer) {
        $uid = $customer['id'];
        $tickets = $conn->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC");
        $tickets->execute([$uid]); $tickets = $tickets->fetchAll(PDO::FETCH_ASSOC);
        $orders = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
        $orders->execute([$uid]); $orders = $orders->fetchAll(PDO::FETCH_ASSOC);
        $returns = $conn->prepare("SELECT * FROM return_requests WHERE user_id = ? ORDER BY created_at DESC");
        $returns->execute([$uid]); $returns = $returns->fetchAll(PDO::FETCH_ASSOC);
    }
    // Handle customer edit
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_customer'])) {
        $fn = trim($_POST['first_name'] ?? ''); $ln = trim($_POST['last_name'] ?? '');
        $em = trim($_POST['email'] ?? ''); $ph = trim($_POST['phone'] ?? ''); $ad = trim($_POST['address'] ?? '');
        if ($fn && $ln && $em) {
            $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, address=? WHERE id=?")
                 ->execute([$fn, $ln, $em, $ph, $ad, $customer['id']]);
            $_SESSION['admin_message'] = 'Customer updated.';
            header("Location: admin-customers.php?email=".urlencode($em)); exit();
        } else { $message = 'Please fill all required fields.'; }
    }
} else {
    $sql = "SELECT u.*, (SELECT COUNT(*) FROM tickets t WHERE t.user_id=u.id) AS ticket_count, (SELECT COUNT(*) FROM orders o WHERE o.user_id=u.id) AS order_count, (SELECT COUNT(*) FROM return_requests r WHERE r.user_id=u.id) AS return_count FROM users u";
    $params = [];
    if ($search !== '') { $sql .= " WHERE u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?"; $like = "%$search%"; $params = [$like,$like,$like]; }
    $sql .= " ORDER BY u.created_at DESC";
    $stmt = $conn->prepare($sql); $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customers | Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>body{font-family:'Nunito',sans-serif;background:#f4faf5}</style>
</head>
<body class="bg-[#f4faf5] flex">
<?php include __DIR__.'/includes/admin-sidebar.php'; ?>
<div class="flex-1 flex flex-col min-w-0">
<?php $pageTitle = 'Customers'; include __DIR__.'/includes/admin-topbar.php'; ?>

<main class="flex-1 p-8 overflow-auto">
<?php if ($emailParam && $customer): ?>
  <a href="admin-customers.php" class="inline-flex items-center gap-2 text-sm text-[#17611f] font-bold hover:underline mb-6">Back to Customers</a>
  <?php if($message):?><div class="mb-4 rounded-xl px-4 py-3 text-sm bg-red-50 text-red-700 border border-red-100"><?=htmlspecialchars($message)?></div><?php endif;?>
  <div class="bg-white rounded-xl border p-6 mb-6 flex items-center gap-4">
    <div class="w-14 h-14 rounded-full bg-[#17611f] text-white font-black flex items-center justify-center"><?=strtoupper(substr($customer['first_name'],0,1).substr($customer['last_name'],0,1))?></div>
    <div><h1 class="font-black text-xl"><?=htmlspecialchars($customer['first_name'].' '.$customer['last_name'])?></h1><p class="text-sm text-[#5a7a5c]"><?=htmlspecialchars($customer['email'])?> | <?=htmlspecialchars($customer['phone'])?> | Joined <?=date('M j, Y',strtotime($customer['created_at']))?></p></div>
    <button onclick="document.getElementById('editForm').classList.toggle('hidden')" class="ml-auto px-4 py-2 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a]">Edit Profile</button>
  </div>

  <!-- Edit Form -->
  <div id="editForm" class="hidden bg-white rounded-xl border p-6 mb-6">
    <h2 class="font-black text-sm mb-4">Edit Customer Information</h2>
    <form method="POST" class="grid grid-cols-2 gap-4">
      <input type="hidden" name="save_customer" value="1">
      <div><label class="text-xs font-bold text-[#5a7a5c]">First Name</label><input name="first_name" value="<?=htmlspecialchars($customer['first_name'])?>" class="w-full border rounded-xl px-3 py-2 text-sm mt-1" required></div>
      <div><label class="text-xs font-bold text-[#5a7a5c]">Last Name</label><input name="last_name" value="<?=htmlspecialchars($customer['last_name'])?>" class="w-full border rounded-xl px-3 py-2 text-sm mt-1" required></div>
      <div><label class="text-xs font-bold text-[#5a7a5c]">Email</label><input type="email" name="email" value="<?=htmlspecialchars($customer['email'])?>" class="w-full border rounded-xl px-3 py-2 text-sm mt-1" required></div>
      <div><label class="text-xs font-bold text-[#5a7a5c]">Phone</label><input name="phone" value="<?=htmlspecialchars($customer['phone'])?>" class="w-full border rounded-xl px-3 py-2 text-sm mt-1"></div>
      <div class="col-span-2"><label class="text-xs font-bold text-[#5a7a5c]">Address</label><textarea name="address" rows="2" class="w-full border rounded-xl px-3 py-2 text-sm mt-1"><?=htmlspecialchars($customer['address']??'')?></textarea></div>
      <div class="col-span-2 flex gap-3"><button type="submit" class="px-5 py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold">Save Changes</button><button type="button" onclick="document.getElementById('editForm').classList.add('hidden')" class="px-5 py-2.5 rounded-xl border text-sm font-bold">Cancel</button></div>
    </form>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
    <div class="bg-white rounded-xl border p-5"><h3 class="font-black text-sm mb-3">Orders (<?=count($orders)?>)</h3><?php if(empty($orders)):?><p class="text-sm text-[#9e9e9e]">None yet.</p><?php else:?><div class="space-y-2"><?php foreach($orders as $o):?><div class="text-sm">#<?=htmlspecialchars($o['order_number'])?> - P<?=number_format($o['total'],2)?> <span class="text-xs text-[#9e9e9e]"><?=ucwords(str_replace('_',' ',$o['status']))?></span></div><?php endforeach;?></div><?php endif;?></div>
    <div class="bg-white rounded-xl border p-5"><h3 class="font-black text-sm mb-3">Tickets (<?=count($tickets)?>)</h3><?php if(empty($tickets)):?><p class="text-sm text-[#9e9e9e]">None yet.</p><?php else:?><div class="space-y-2"><?php foreach($tickets as $t):?><a href="admin-ticket-detail.php?id=<?=$t['id']?>" class="block text-sm hover:text-[#17611f]"><?=htmlspecialchars($t['subject'])?> <span class="text-xs text-[#9e9e9e]"><?=ucfirst($t['status'])?></span></a><?php endforeach;?></div><?php endif;?></div>
    <div class="bg-white rounded-xl border p-5"><h3 class="font-black text-sm mb-3">Returns (<?=count($returns)?>)</h3><?php if(empty($returns)):?><p class="text-sm text-[#9e9e9e]">None yet.</p><?php else:?><div class="space-y-2"><?php foreach($returns as $r):?><div class="text-sm">#<?=htmlspecialchars($r['order_number'])?> <span class="text-xs text-[#9e9e9e]"><?=ucfirst($r['status'])?></span></div><?php endforeach;?></div><?php endif;?></div>
  </div>

<?php else: ?>
  <h1 class="text-2xl font-black mb-4">Customers</h1>
  <form method="GET" class="flex gap-2 mb-6"><input type="text" name="q" value="<?=htmlspecialchars($search)?>" placeholder="Search by name or email..." class="w-full max-w-md rounded-xl border px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40"><button type="submit" class="px-4 py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold">Search</button></form>
  <div class="bg-white rounded-xl border overflow-hidden"><div class="overflow-x-auto"><table class="w-full text-sm">
    <thead><tr class="bg-[#f4faf5] text-[#5a7a5c] text-xs uppercase"><th class="p-3 text-left">Customer</th><th class="p-3 text-left">Email</th><th class="p-3 text-left">Orders</th><th class="p-3 text-left">Tickets</th><th class="p-3 text-left">Returns</th><th class="p-3 text-left">Joined</th><th class="p-3 text-left">Action</th></tr></thead>
    <tbody><?php if(empty($customers)):?><tr><td colspan="7" class="p-6 text-center text-[#9e9e9e]">No customers found.</td></tr>
    <?php else: foreach($customers as $c):?><tr class="border-t border-[rgba(27,94,32,0.05)]"><td class="p-3 font-bold"><?=htmlspecialchars($c['first_name'].' '.$c['last_name'])?></td><td class="p-3 text-[#5a7a5c]"><?=htmlspecialchars($c['email'])?></td><td class="p-3"><?=$c['order_count']?></td><td class="p-3"><?=$c['ticket_count']?></td><td class="p-3"><?=$c['return_count']?></td><td class="p-3 text-xs text-[#9e9e9e]"><?=date('M j, Y',strtotime($c['created_at']))?></td><td class="p-3"><a href="?email=<?=urlencode($c['email'])?>" class="text-[#17611f] font-bold text-xs hover:underline">View</a></td></tr><?php endforeach; endif;?></tbody>
  </table></div></div>
<?php endif; ?>
</main>
</div></body></html>
