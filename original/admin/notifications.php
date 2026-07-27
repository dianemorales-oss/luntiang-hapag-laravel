<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin-auth.php';
require_once __DIR__ . '/../includes/notifications.php';

$activePage = 'notifications';
$pageTitle = 'Notifications';

// Mark a single notification as read, then stay on this page (rather
// than jumping to the related request), since the admin is browsing
// the full list here.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id'])) {
    $id = (int)$_POST['mark_read_id'];
    if ($id > 0) {
        $update = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $update->execute([$id]);
    }
    header("Location: notifications.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $conn->exec("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
    header("Location: notifications.php");
    exit();
}

$filter = $_GET['filter'] ?? 'all';

$sql = "SELECT * FROM notifications WHERE 1=1";
if ($filter === 'unread') {
    $sql .= " AND is_read = 0";
}
$sql .= " ORDER BY created_at DESC";

$notifications = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$unreadCount = (int)$conn->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn();
$totalCount = (int)$conn->query("SELECT COUNT(*) FROM notifications")->fetchColumn();

function notificationIcon(string $type): string
{
    if (str_starts_with($type, 'ticket')) return 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';
    if (str_starts_with($type, 'warranty')) return 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z';
    if (str_starts_with($type, 'return')) return 'M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8h-1';
    return 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Notifications | Luntiang H.A.P.A.G. Admin</title>
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

        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-2">
            <a href="notifications.php?filter=all" class="px-4 py-2 rounded-full text-[13px] font-medium <?= $filter === 'all' ? 'bg-[#17611f] text-white' : 'bg-white border border-[rgba(27,94,32,0.12)] text-[#5a7a5c] hover:bg-gray-50' ?>">All <span class="opacity-70">(<?= $totalCount ?>)</span></a>
            <a href="notifications.php?filter=unread" class="px-4 py-2 rounded-full text-[13px] font-medium <?= $filter === 'unread' ? 'bg-[#17611f] text-white' : 'bg-white border border-[rgba(27,94,32,0.12)] text-[#5a7a5c] hover:bg-gray-50' ?>">Unread <span class="opacity-70">(<?= $unreadCount ?>)</span></a>
          </div>
          <?php if ($unreadCount > 0): ?>
            <form method="POST">
              <input type="hidden" name="mark_all_read" value="1" />
              <button type="submit" class="px-4 py-2 rounded-full bg-white border border-[rgba(27,94,32,0.12)] text-[13px] font-medium text-[#1a2e1c] hover:bg-gray-50 transition-colors">Mark all as read</button>
            </form>
          <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 gap-3">
          <?php if (empty($notifications)): ?>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center text-sm text-[#9e9e9e]">No notifications found.</div>
          <?php else: foreach ($notifications as $n): ?>
            <div class="bg-white rounded-2xl border <?= !$n['is_read'] ? 'border-[#c8e6c9] bg-[#e8f5e9]/30' : 'border-gray-100' ?> shadow-sm p-5 flex items-start gap-4">
              <div class="w-10 h-10 rounded-xl bg-[#f4faf5] flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-[#17611f]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="<?= notificationIcon($n['type']) ?>"/></svg>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="font-semibold text-[#1a2e1c] text-[14px]"><?= htmlspecialchars($n['title']) ?><?php if (!$n['is_read']): ?> <span class="inline-block w-2 h-2 rounded-full bg-[#17611f] align-middle ml-1"></span><?php endif; ?></p>
                    <p class="text-[13px] text-[#5a7a5c] mt-1 whitespace-pre-line"><?= htmlspecialchars($n['message']) ?></p>
                  </div>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-[12px] text-[#9e9e9e]">
                  <span>Related #<?= (int)$n['related_id'] ?></span>
                  <?php if (!empty($n['customer_name'])): ?><span><?= htmlspecialchars($n['customer_name']) ?></span><?php endif; ?>
                  <span><?= date('M j, Y g:i A', strtotime($n['created_at'])) ?></span>
                </div>
                <div class="flex items-center gap-4 mt-3">
                  <a href="notification-open.php?id=<?= (int)$n['id'] ?>" class="text-[12px] font-medium text-[#17611f] hover:underline">View Related Request →</a>
                  <?php if (!$n['is_read']): ?>
                    <form method="POST">
                      <input type="hidden" name="mark_read_id" value="<?= (int)$n['id'] ?>" />
                      <button type="submit" class="text-[12px] font-medium text-[#5a7a5c] hover:underline">Mark as read</button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>

      </main>
    </div>
  </div>
</body>
</html>
