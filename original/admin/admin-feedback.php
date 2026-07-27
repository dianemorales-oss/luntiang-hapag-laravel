<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin-auth.php';

$activePage = 'feedback';
$pageTitle = 'Feedback Management';

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    $del = $conn->prepare("DELETE FROM feedback WHERE id = ?");
    if ($del->execute([$deleteId])) {
        $message = "Feedback entry deleted.";
        $messageType = "success";
    } else {
        $message = "Something went wrong deleting that entry.";
        $messageType = "error";
    }
}

$ratingFilter = $_GET['rating'] ?? 'all';

$sql = "
    SELECT f.*, u.first_name, u.last_name, u.email AS user_email
    FROM feedback f
    LEFT JOIN users u ON u.id = f.user_id
    WHERE 1=1
";
$params = [];
if (in_array($ratingFilter, ['1', '2', '3', '4', '5'], true)) {
    $sql .= " AND f.rating = ?";
    $params[] = (int)$ratingFilter;
}
$sql .= " ORDER BY f.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$feedbackEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCount = (int)$conn->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
$avgRating = (float)($conn->query("SELECT COALESCE(AVG(rating), 0) FROM feedback")->fetchColumn());
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Feedback Management | Luntiang H.A.P.A.G. Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Nunito', sans-serif; }
    .font-black { font-family: 'Nunito', serif; }
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-thumb { background: #d8cfbd; border-radius: 8px; }
  </style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c]">
  <div class="flex min-h-screen">
    <?php require_once __DIR__ . '/includes/admin-sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php require_once __DIR__ . '/includes/admin-topbar.php'; ?>

      <main class="flex-1 overflow-y-auto p-6 space-y-5">

        <?php if ($message): ?>
          <div data-flash-message class="rounded-xl px-4 py-3 text-sm <?= $messageType === 'error' ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-green-50 text-green-700 border border-green-100' ?>">
            <?= htmlspecialchars($message) ?>
          </div>
        <?php endif; ?>

        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-2">
            <a href="admin-feedback.php" class="px-4 py-2 rounded-full text-[13px] font-medium <?= $ratingFilter === 'all' ? 'bg-[#17611f] text-white' : 'bg-white border border-[rgba(27,94,32,0.12)] text-[#5a7a5c] hover:bg-gray-50' ?>">All</a>
            <?php for ($i = 5; $i >= 1; $i--): ?>
              <a href="admin-feedback.php?rating=<?= $i ?>" class="px-4 py-2 rounded-full text-[13px] font-medium <?= $ratingFilter == $i ? 'bg-[#17611f] text-white' : 'bg-white border border-[rgba(27,94,32,0.12)] text-[#5a7a5c] hover:bg-gray-50' ?>"><?= $i ?>★</a>
            <?php endfor; ?>
          </div>
          <div class="text-[13px] text-[#5a7a5c]"><?= $totalCount ?> total · avg <span class="font-semibold text-[#1a2e1c]"><?= $totalCount > 0 ? number_format($avgRating, 1) : '—' ?></span> ★</div>
        </div>

        <div class="grid grid-cols-1 gap-4">
          <?php if (empty($feedbackEntries)): ?>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center text-sm text-[#9e9e9e]">No feedback submitted yet.</div>
          <?php else: foreach ($feedbackEntries as $f):
              $isGuest = empty($f['user_id']);
              $displayName = $isGuest ? ($f['guest_name'] ?: 'Guest') : trim($f['first_name'] . ' ' . $f['last_name']);
              $displayEmail = $isGuest ? ($f['guest_email'] ?: '—') : $f['user_email'];
          ?>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
              <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-3 mb-2 flex-wrap">
                    <p class="text-[12px] text-[#9e9e9e]"><?= date('M j, Y', strtotime($f['created_at'])) ?></p>
                    <span class="text-amber-400 text-sm"><?= str_repeat('★', (int)$f['rating']) . str_repeat('☆', 5 - (int)$f['rating']) ?></span>
                    <?php if ($isGuest): ?>
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 text-[#5a7a5c]">Guest</span>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($f['subject'])): ?>
                    <h3 class="font-semibold text-[#1a2e1c] mb-1"><?= htmlspecialchars($f['subject']) ?></h3>
                  <?php endif; ?>
                  <p class="text-[13px] text-[#5a7a5c] mb-3"><?= $f['comments'] ? htmlspecialchars($f['comments']) : '<span class="text-[#9e9e9e] italic">No comment left.</span>' ?></p>
                  <p class="text-[12px] text-[#5a7a5c]">From: <span class="font-medium text-[#1a2e1c]"><?= htmlspecialchars($displayName) ?></span> · <?= htmlspecialchars($displayEmail) ?></p>
                </div>
                <form method="POST" onsubmit="return confirm('Delete this feedback entry? This cannot be undone.');">
                  <input type="hidden" name="delete_id" value="<?= $f['id'] ?>" />
                  <button type="submit" class="px-4 py-2 rounded-full border border-red-200 text-red-600 text-[13px] font-medium hover:bg-red-50 transition-colors">Delete</button>
                </form>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>

      </main>
</div>
    </div>
  </div>
</body>
</html>
