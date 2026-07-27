<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin-auth.php';
$activePage = 'reviews';
$pageTitle = 'Product Reviews';

// Handle reply/edit/delete
$msg = ''; $msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reviewId = (int)($_POST['review_id'] ?? 0);
    if (isset($_POST['save_reply']) && $reviewId) {
        $reply = trim($_POST['admin_reply'] ?? '');
        $conn->prepare("UPDATE reviews SET admin_reply = ?, admin_replied_at = NOW() WHERE id = ?")
             ->execute([$reply, $reviewId]);
        $msg = 'Reply saved.'; $msgType = 'success';
    } elseif (isset($_POST['delete_reply']) && $reviewId) {
        $conn->prepare("UPDATE reviews SET admin_reply = NULL, admin_replied_at = NULL WHERE id = ?")->execute([$reviewId]);
        $msg = 'Reply deleted.'; $msgType = 'success';
    } elseif (isset($_POST['delete_review']) && $reviewId) {
        $conn->prepare("DELETE FROM reviews WHERE id = ?")->execute([$reviewId]);
        $msg = 'Review deleted.'; $msgType = 'success';
    }
    header("Location: admin-reviews.php" . ($_GET['product'] ?? isset($_POST['product_id']) ? '?product='.(int)$_POST['product_id'] : '')); exit();
}

$productId = isset($_GET['product']) ? (int)$_GET['product'] : 0;
$search = $_GET['search'] ?? '';
$ratingFilter = $_GET['rating'] ?? '';
$filterReplied = $_GET['replied'] ?? '';

// Fetch all products for dropdown
$allProducts = $conn->query("SELECT id, name, image FROM products WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Build review query
$where = []; $params = [];
if ($productId) { $where[] = "r.product_id = ?"; $params[] = $productId; }
if ($search) { $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR p.name LIKE ? OR o.order_number LIKE ?)"; $srch = "%$search%"; $params[] = $srch; $params[] = $srch; $params[] = $srch; $params[] = $srch; }
if ($ratingFilter !== '') { $where[] = "r.rating = ?"; $params[] = (int)$ratingFilter; }
if ($filterReplied === 'yes') { $where[] = "r.admin_reply IS NOT NULL"; }
if ($filterReplied === 'no') { $where[] = "r.admin_reply IS NULL"; }
$wh = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$reviews = $conn->prepare("
    SELECT r.*, u.first_name, u.last_name, p.name AS product_name, p.image AS product_image, o.order_number
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    LEFT JOIN orders o ON r.order_id = o.id
    $wh
    ORDER BY r.created_at DESC LIMIT 100
");
$reviews->execute($params);
$reviews = $reviews->fetchAll(PDO::FETCH_ASSOC);

// Stats
$totalReviews = $conn->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$avgRating = $conn->query("SELECT ROUND(AVG(rating),1) FROM reviews")->fetchColumn();
$pendingReplies = $conn->query("SELECT COUNT(*) FROM reviews WHERE admin_reply IS NULL")->fetchColumn();

// Top products by rating
$topProducts = $conn->query("
    SELECT p.id, p.name, ROUND(AVG(r.rating),1) AS avg_rating, COUNT(*) AS cnt
    FROM reviews r JOIN products p ON r.product_id = p.id
    GROUP BY p.id ORDER BY cnt DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product Reviews | Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>body{font-family:'Nunito',sans-serif;background:#f4faf5}</style>
</head>
<body class="bg-[#f4faf5] flex"><?php include __DIR__.'/includes/admin-sidebar.php'; ?>
<div class="flex-1 flex flex-col min-w-0">
<?php $pageTitle = 'Product Reviews'; include __DIR__.'/includes/admin-topbar.php'; ?>

<main class="flex-1 p-6 overflow-auto">
  <?php if ($msg): ?><div class="mb-4 rounded-xl px-4 py-3 text-sm <?=$msgType==='error'?'bg-red-50 text-red-700 border-red-100':'bg-[#e8f5e9] text-[#17611f] border-[#c8e6c9]'?> border"><?=htmlspecialchars($msg)?></div><?php endif; ?>

  <div class="grid grid-cols-4 gap-3 mb-6">
    <div class="bg-white rounded-xl border p-4"><p class="text-xs text-[#5a7a5c] font-bold">Total Reviews</p><p class="text-2xl font-black"><?=$totalReviews?></p></div>
    <div class="bg-white rounded-xl border p-4"><p class="text-xs text-[#5a7a5c] font-bold">Average Rating</p><p class="text-2xl font-black text-amber-500"><?=$avgRating?> ★</p></div>
    <div class="bg-white rounded-xl border p-4"><p class="text-xs text-[#5a7a5c] font-bold">Needs Reply</p><p class="text-2xl font-black text-blue-600"><?=$pendingReplies?></p></div>
  </div>

  <!-- Top products sidebar info -->
  <?php if (!empty($topProducts)): ?>
  <div class="mb-6 bg-white rounded-xl border p-5">
    <h3 class="font-black text-sm mb-3">Top Rated Products</h3>
    <div class="grid grid-cols-5 gap-3 text-xs">
      <?php foreach ($topProducts as $tp): ?>
        <div class="bg-[#f4faf5] rounded-lg p-3 text-center">
          <p class="font-bold truncate"><?=htmlspecialchars($tp['name'])?></p>
          <p class="text-amber-500 font-black"><?=$tp['avg_rating']?> ★</p>
          <p class="text-[#9e9e9e]"><?=$tp['cnt']?> reviews</p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Filters -->
  <div class="bg-white rounded-xl border p-4 mb-4 flex flex-wrap items-center gap-3">
    <form method="GET" class="flex flex-wrap items-center gap-3 flex-1">
      <select name="product" class="border rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
        <option value="">All Products</option>
        <?php foreach ($allProducts as $ap): ?>
          <option value="<?=$ap['id']?>" <?=$productId==$ap['id']?'selected':''?>><?=htmlspecialchars($ap['name'])?></option>
        <?php endforeach; ?>
      </select>
      <input name="search" value="<?=htmlspecialchars($search)?>" placeholder="Search..." class="border rounded-lg px-3 py-2 text-sm w-40">
      <select name="rating" class="border rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
        <option value="">All Ratings</option>
        <?php for ($i=5;$i>=1;$i--): ?><option value="<?=$i?>" <?=$ratingFilter==(string)$i?'selected':''?>><?=$i?> ★</option><?php endfor; ?>
      </select>
      <select name="replied" class="border rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
        <option value="">All</option>
        <option value="no" <?=$filterReplied==='no'?'selected':''?>>Not Replied</option>
        <option value="yes" <?=$filterReplied==='yes'?'selected':''?>>Replied</option>
      </select>
      <button type="submit" class="px-4 py-2 rounded-lg bg-[#17611f] text-white text-xs font-bold">Filter</button>
      <a href="admin-reviews.php" class="px-4 py-2 rounded-lg border text-xs font-bold">Clear</a>
    </form>
  </div>

  <!-- Reviews list -->
  <?php if (empty($reviews)): ?>
    <div class="text-center py-16 bg-white rounded-xl border"><p class="text-[#5a7a5c]">No reviews found.</p></div>
  <?php else: ?>
    <div class="space-y-4">
      <?php foreach ($reviews as $r):
        $revPhotos = !empty($r['photos']) ? json_decode($r['photos'], true) : [];
      ?>
        <div class="bg-white rounded-xl border p-5">
          <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-[#e8f5e9] flex items-center justify-center font-bold text-sm text-[#17611f]"><?=strtoupper(substr($r['first_name'],0,1))?></div>
              <div>
                <p class="font-bold text-sm"><?=htmlspecialchars($r['first_name'].' '.$r['last_name'])?></p>
                <div class="flex items-center gap-2">
                  <span class="text-amber-400 text-xs"><?=str_repeat('★',$r['rating']).str_repeat('☆',5-$r['rating'])?></span>
                  <?php if ($r['is_verified']): ?><span class="text-[10px] bg-[#e8f5e9] text-[#17611f] px-1.5 py-0.5 rounded font-bold">✓ Verified</span><?php endif; ?>
                </div>
              </div>
            </div>
            <div class="text-right text-xs text-[#9e9e9e]">
              <p><b><?=htmlspecialchars($r['product_name'])?></b></p>
              <?php if ($r['order_number']): ?><p>Order: <?=htmlspecialchars($r['order_number'])?></p><?php endif; ?>
              <p><?=date('M j, Y g:i A',strtotime($r['created_at']))?></p>
            </div>
          </div>
          <?php if ($r['comment']): ?><p class="text-sm text-[#5a7a5c] mb-2"><?=nl2br(htmlspecialchars($r['comment']))?></p><?php endif; ?>
          <?php if (!empty($revPhotos)): ?>
            <div class="flex gap-2 mb-3 flex-wrap">
              <?php foreach ($revPhotos as $photo): ?><img src="<?=htmlspecialchars($photo)?>" class="w-16 h-16 object-cover rounded-lg border"><?php endforeach; ?>
            </div>
          <?php endif; ?>

          <!-- Admin reply -->
          <?php if (!empty($r['admin_reply'])): ?>
            <div class="mt-3 pl-4 border-l-2 border-[#17611f] bg-[#f4faf5] rounded-r-lg p-3">
              <p class="text-xs font-bold text-[#17611f] mb-1">🌱 Your Reply:</p>
              <p class="text-sm text-[#5a7a5c]"><?=nl2br(htmlspecialchars($r['admin_reply']))?></p>
              <p class="text-[11px] text-[#9e9e9e] mt-1"><?=date('M j, Y g:i A', strtotime($r['admin_replied_at']))?></p>
              <div class="flex gap-2 mt-2">
                <button onclick="toggleReplyForm(<?=$r['id']?>,'edit')" class="text-xs text-[#17611f] font-bold hover:underline">Edit</button>
                <form method="POST" class="inline" onsubmit="return confirm('Delete this reply?')"><input type="hidden" name="review_id" value="<?=$r['id']?>"><button type="submit" name="delete_reply" value="1" class="text-xs text-red-500 font-bold hover:underline">Delete</button></form>
              </div>
            </div>
          <?php else: ?>
            <div class="mt-3">
              <button onclick="toggleReplyForm(<?=$r['id']?>,'new')" class="text-sm font-bold text-[#17611f] hover:underline">💬 Reply to this review</button>
            </div>
          <?php endif; ?>

          <!-- Reply form -->
          <div id="replyForm-<?=$r['id']?>" class="hidden mt-3 border-t border-[rgba(27,94,32,0.08)] pt-3">
            <form method="POST">
              <input type="hidden" name="review_id" value="<?=$r['id']?>">
              <input type="hidden" name="product_id" value="<?=$r['product_id']?>">
              <textarea name="admin_reply" rows="2" class="w-full border rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" placeholder="Write your reply..."><?=htmlspecialchars($r['admin_reply']??'')?></textarea>
              <div class="flex gap-2 mt-2">
                <button type="submit" name="save_reply" value="1" class="px-4 py-1.5 rounded-lg bg-[#17611f] text-white text-xs font-bold hover:bg-[#14521a]">Save Reply</button>
                <button type="button" onclick="toggleReplyForm(<?=$r['id']?>,'hide')" class="px-4 py-1.5 rounded-lg border text-xs font-bold hover:bg-[#e8f5e9]">Cancel</button>
              </div>
            </form>
          </div>

          <div class="mt-3 text-right">
            <form method="POST" class="inline" onsubmit="return confirm('Delete this review?')">
              <input type="hidden" name="review_id" value="<?=$r['id']?>">
              <input type="hidden" name="product_id" value="<?=$r['product_id']?>">
              <button type="submit" name="delete_review" value="1" class="text-xs text-red-400 hover:text-red-600">🗑 Delete Review</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
</div>

<script>
function toggleReplyForm(id,action){
  var f=document.getElementById('replyForm-'+id);
  if(action==='hide'){f.classList.add('hidden')}else{f.classList.toggle('hidden')}
}
</script>
</body></html>
