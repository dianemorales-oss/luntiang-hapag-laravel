<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin-auth.php';
$activePage = 'products';
$message = $_SESSION['admin_message'] ?? ''; unset($_SESSION['admin_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    if ($action === 'update' && $id) {
        $stmt = $conn->prepare("UPDATE products SET name=?, variety=?, description=?, price=?, unit=?, plants_available=?, is_best_seller=?, is_new=?, is_active=?, calories=?, best_for=?, shelf_life=?, harvest_time=?, storage_instructions=? WHERE id=?");
        $stmt->execute([$_POST['name'],$_POST['variety']??null,$_POST['description']??null,(float)$_POST['price'],$_POST['unit'],(int)$_POST['plants_available'],isset($_POST['is_best_seller'])?1:0,isset($_POST['is_new'])?1:0,isset($_POST['is_active'])?1:0,$_POST['calories']?(int)$_POST['calories']:null,$_POST['best_for']??null,$_POST['shelf_life']??null,$_POST['harvest_time']??null,$_POST['storage_instructions']??null,$id]);
        $_SESSION['admin_message'] = 'Product updated.';
    } elseif ($action === 'toggle' && $id) {
        $conn->prepare("UPDATE products SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
        $_SESSION['admin_message'] = 'Product toggled.';
    }
    header("Location: admin-products.php"); exit();
}
$editId = (int)($_GET['edit'] ?? 0); $editProduct = null;
if ($editId) { $s = $conn->prepare("SELECT * FROM products WHERE id = ?"); $s->execute([$editId]); $editProduct = $s->fetch(PDO::FETCH_ASSOC); }
$products = $conn->query("SELECT p.*, c.name AS cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.is_active DESC, p.is_best_seller DESC, p.name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Products | Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>body{font-family:'Nunito',sans-serif;background:#f4faf5}</style>
</head>
<body class="bg-[#f4faf5] flex"><?php include __DIR__.'/includes/admin-sidebar.php'; ?>
<div class="flex-1 flex flex-col min-w-0">
<?php $pageTitle = 'Products'; include __DIR__.'/includes/admin-topbar.php'; ?>

<main class="flex-1 p-8 overflow-auto">
  <h1 class="text-2xl font-black mb-1">Product Management</h1>
  <p class="text-sm text-[#5a7a5c] mb-6"><?= count($products) ?> products total</p>
  <?php if($message): ?><div class="mb-4 rounded-xl px-4 py-3 text-sm bg-[#e8f5e9] text-[#17611f] border border-[#c8e6c9]"><?= htmlspecialchars($message) ?></div><?php endif; ?>

  <?php if($editProduct): ?>
  <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-6 mb-6">
    <h2 class="font-black text-lg mb-4">Edit: <?= htmlspecialchars($editProduct['name']) ?></h2>
    <form method="POST" class="grid grid-cols-2 gap-4">
      <input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?=$editProduct['id']?>">
      <div><label class="text-xs font-bold text-[#5a7a5c]">Name</label><input name="name" value="<?=htmlspecialchars($editProduct['name'])?>" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" required></div>
      <div><label class="text-xs font-bold text-[#5a7a5c]">Variety</label><input name="variety" value="<?=htmlspecialchars($editProduct['variety']??'')?>" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40"></div>
      <div class="col-span-2"><label class="text-xs font-bold text-[#5a7a5c]">Description</label><textarea name="description" rows="2" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40"><?=htmlspecialchars($editProduct['description']??'')?></textarea></div>
      <div><label class="text-xs font-bold text-[#5a7a5c]">Price</label><input name="price" type="number" step="0.01" value="<?=$editProduct['price']?>" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" required></div>
      <div><label class="text-xs font-bold text-[#5a7a5c]">Unit</label><input name="unit" value="<?=htmlspecialchars($editProduct['unit'])?>" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40"></div>
      <div><label class="text-xs font-bold text-[#5a7a5c]">Plants Available</label><input name="plants_available" type="number" value="<?=$editProduct['plants_available']?>" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40"></div>
      <div><label class="text-xs font-bold text-[#5a7a5c]">Shelf Life</label><input name="shelf_life" value="<?=htmlspecialchars($editProduct['shelf_life']??'')?>" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40"></div>
      <div class="col-span-2"><label class="text-xs font-bold text-[#5a7a5c]">Best For</label><input name="best_for" value="<?=htmlspecialchars($editProduct['best_for']??'')?>" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40"></div>
      <div class="col-span-2"><label class="text-xs font-bold text-[#5a7a5c]">Storage Instructions</label><textarea name="storage_instructions" rows="2" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40"><?=htmlspecialchars($editProduct['storage_instructions']??'')?></textarea></div>
      <div class="flex gap-4 col-span-2">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_best_seller" <?=$editProduct['is_best_seller']?'checked':''?>> Best Seller</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_new" <?=$editProduct['is_new']?'checked':''?>> New</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" <?=$editProduct['is_active']?'checked':''?>> Active</label>
      </div>
      <div class="col-span-2 flex gap-3">
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a]">Save Changes</button>
        <a href="admin-products.php" class="px-5 py-2.5 rounded-xl border border-[rgba(27,94,32,0.12)] text-sm font-bold hover:bg-[#e8f5e9]">Cancel</a>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <div class="grid grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-4 text-center"><p class="text-2xl font-black text-[#17611f]"><?=$conn->query("SELECT SUM(plants_available) FROM products WHERE is_active=1")->fetchColumn()?></p><p class="text-xs text-[#5a7a5c] font-bold">Plants Available</p></div>
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-4 text-center"><p class="text-2xl font-black text-amber-600"><?=$conn->query("SELECT COUNT(*) FROM products WHERE is_active=1 AND plants_available<=20 AND plants_available>0")->fetchColumn()?></p><p class="text-xs text-[#5a7a5c] font-bold">Low Availability</p></div>
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-4 text-center"><p class="text-2xl font-black text-red-500"><?=$conn->query("SELECT COUNT(*) FROM products WHERE is_active=1 AND plants_available=0")->fetchColumn()?></p><p class="text-xs text-[#5a7a5c] font-bold">Out of Stock</p></div>
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-4 text-center"><p class="text-2xl font-black text-[#17611f]"><?=$conn->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn()?></p><p class="text-xs text-[#5a7a5c] font-bold">Active</p></div>
  </div>

  <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead><tr class="bg-[#f4faf5] text-[#5a7a5c] text-xs uppercase"><th class="p-3 text-left">Product</th><th class="p-3 text-left">Price</th><th class="p-3 text-left">Available</th><th class="p-3 text-left">Status</th><th class="p-3 text-left">Actions</th></tr></thead>
        <tbody>
          <?php foreach($products as $p): ?>
          <tr class="border-t border-[rgba(27,94,32,0.05)] <?=!$p['is_active']?'opacity-50':''?>">
            <td class="p-3"><div class="flex items-center gap-3"><img src="<?=htmlspecialchars($p['image']?:'../images/lettuce/hero-farm.png')?>" class="w-10 h-10 rounded-lg object-cover" alt=""><div><p class="font-bold"><?=htmlspecialchars($p['name'])?></p><p class="text-xs text-[#5a7a5c]"><?=htmlspecialchars($p['variety']??'')?></p></div></div></td>
            <td class="p-3 font-bold">P<?=number_format($p['price'],2)?></td>
            <td class="p-3"><span class="<?=$p['plants_available']>50?'text-green-600':($p['plants_available']>0?'text-amber-600':'text-red-600')?> font-bold"><?=$p['plants_available']?></span></td>
            <td class="p-3">
              <?php if(!$p['is_active']):?><span class="px-2 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-[#9e9e9e]">Inactive</span>
              <?php elseif($p['plants_available']==0):?><span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">Unavailable</span>
              <?php elseif($p['plants_available']<=20):?><span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">Limited</span>
              <?php else:?><span class="px-2 py-0.5 rounded-full text-xs font-bold bg-[#e8f5e9] text-[#17611f]">Available</span><?php endif;?>
              <?php if($p['is_best_seller']):?><span class="ml-1 px-2 py-0.5 rounded-full text-xs font-bold bg-[#fff8e1] text-[#e65100]">Best Seller</span><?php endif;?>
            </td>
            <td class="p-3"><div class="flex gap-1">
              <a href="?edit=<?=$p['id']?>" class="px-3 py-1.5 rounded-lg border border-[rgba(27,94,32,0.12)] text-xs font-bold hover:bg-[#e8f5e9]">Edit</a>
              <form method="POST" class="inline"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=$p['id']?>"><button class="px-3 py-1.5 rounded-lg text-xs font-bold text-[#5a7a5c] hover:bg-[#e8f5e9]"><?=$p['is_active']?'Deactivate':'Activate'?></button></form>
            </div></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</div></body></html>
