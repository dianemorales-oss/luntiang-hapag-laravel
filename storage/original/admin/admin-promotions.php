<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin-auth.php';
$activePage = 'promotions';
$message = $_SESSION['admin_message'] ?? ''; unset($_SESSION['admin_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $conn->prepare("INSERT INTO promotions (code,description,discount_type,discount_value,min_order,is_active,is_free_delivery) VALUES (?,?,?,?,?,?,?)")
             ->execute([strtoupper(trim($_POST['code'])),$_POST['description'],$_POST['discount_type'],(float)$_POST['discount_value'],(float)$_POST['min_order'],isset($_POST['is_active'])?1:0,isset($_POST['is_free_delivery'])?1:0]);
        $_SESSION['admin_message'] = 'Promo created.';
    } elseif ($action === 'toggle' && ($id=(int)($_POST['id']??0))) {
        $conn->prepare("UPDATE promotions SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
    } elseif ($action === 'delete' && ($id=(int)($_POST['id']??0))) {
        $conn->prepare("DELETE FROM promotions WHERE id = ?")->execute([$id]);
    }
    header("Location: admin-promotions.php"); exit();
}
$promos = $conn->query("SELECT * FROM promotions ORDER BY is_active DESC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Promotions | Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>body{font-family:'Nunito',sans-serif;background:#f4faf5}</style>
</head>
<body class="bg-[#f4faf5] flex"><?php include __DIR__.'/includes/admin-sidebar.php'; ?>
<div class="flex-1 flex flex-col min-w-0">
<?php $pageTitle = 'Promotions'; include __DIR__.'/includes/admin-topbar.php'; ?>
<main class="flex-1 p-8 overflow-auto">
  <h1 class="text-2xl font-black mb-1">Promo Codes</h1>
  <p class="text-sm text-[#5a7a5c] mb-6">Manage discount codes visible to customers</p>
  <?php if($message):?><div class="mb-4 rounded-xl px-4 py-3 text-sm bg-[#e8f5e9] text-[#17611f] border border-[#c8e6c9]"><?=htmlspecialchars($message)?></div><?php endif;?>

  <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-5 mb-6">
    <h2 class="font-black text-sm mb-3">Create Promo Code</h2>
    <form method="POST" class="grid grid-cols-2 md:grid-cols-4 gap-3">
      <input type="hidden" name="action" value="create">
      <input name="code" placeholder="CODE" class="border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm" required>
      <input name="description" placeholder="Description" class="border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm">
      <select name="discount_type" class="border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm"><option value="percentage">Percentage</option><option value="fixed">Fixed Amount</option></select>
      <input name="discount_value" type="number" step="0.01" placeholder="Value" class="border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm" required>
      <input name="min_order" type="number" step="0.01" placeholder="Min order (0=none)" value="0" class="border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm">
      <div class="flex items-center gap-4 col-span-2">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" checked> Active</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_free_delivery"> Free Delivery</label>
      </div>
      <button type="submit" class="col-span-full px-5 py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a]">Create Promo</button>
    </form>
  </div>

  <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead><tr class="bg-[#f4faf5] text-[#5a7a5c] text-xs uppercase"><th class="p-3 text-left">Code</th><th class="p-3 text-left">Description</th><th class="p-3 text-left">Type/Value</th><th class="p-3 text-left">Used</th><th class="p-3 text-left">Status</th><th class="p-3 text-left">Actions</th></tr></thead>
        <tbody>
          <?php foreach($promos as $p):?>
          <tr class="border-t border-[rgba(27,94,32,0.05)]">
            <td class="p-3 font-bold"><?=htmlspecialchars($p['code'])?></td>
            <td class="p-3 text-[#5a7a5c]"><?=htmlspecialchars($p['description'])?></td>
            <td class="p-3"><?=$p['discount_type']==='percentage'?$p['discount_value'].'%':'P'.number_format($p['discount_value'],2)?><?=$p['is_free_delivery']?' + Free Delivery':''?></td>
            <td class="p-3"><?=$p['used_count']?></td>
            <td class="p-3"><span class="px-2.5 py-0.5 rounded-full text-xs font-bold <?=$p['is_active']?'bg-[#e8f5e9] text-[#17611f]':'bg-gray-100 text-[#9e9e9e]'?>"><?=$p['is_active']?'Active':'Inactive'?></span></td>
            <td class="p-3"><div class="flex gap-1">
              <form method="POST"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=$p['id']?>"><button class="text-xs text-[#17611f] font-bold hover:underline"><?=$p['is_active']?'Deactivate':'Activate'?></button></form>
              <form method="POST" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$p['id']?>"><button class="text-xs text-red-500 font-bold hover:underline">Delete</button></form>
            </div></td>
          </tr>
          <?php endforeach;?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</div></body></html>
