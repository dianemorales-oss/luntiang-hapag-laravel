<?php
/**
 * admin/includes/admin-topbar.php
 * ------------------------------------------------------------------
 * Shared topbar for every admin page.
 *
 * Expects:
 *   - $conn        PDO connection
 *   - $pageTitle    string shown as the H1 in the topbar
 *   - $_SESSION['admin_name'], $_SESSION['admin_role'] (set at login)
 * ------------------------------------------------------------------
 */

if (!isset($pageTitle)) {
    $pageTitle = 'Dashboard';
}

$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminRole = $_SESSION['admin_role'] ?? 'Admin';

// Initials for the avatar circle (e.g. "Luntiang H.A.P.A.G. Admin" -> "WA")
$nameParts = preg_split('/\s+/', trim($adminName));
$initials = strtoupper(substr($nameParts[0] ?? 'A', 0, 1) . substr($nameParts[count($nameParts) - 1] ?? '', 0, 1));

// --- Admin Notification System ---
require_once __DIR__ . '/../../includes/notifications.php';
$unreadNotificationCount = (int)$conn->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn();
$recentNotifications = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

?>
    <!-- Topbar -->
    <header class="h-16 bg-white border-b border-[rgba(27,94,32,0.12)] flex items-center justify-between px-6 flex-shrink-0">
      <h1 class="text-lg font-semibold text-[#1a2e1c]"><?= htmlspecialchars($pageTitle) ?></h1>
      <div class="flex items-center gap-4">

        <!-- Notification Bell -->
        <div class="relative">
          <button type="button" id="notif-bell-btn" onclick="document.getElementById('notif-dropdown').classList.toggle('hidden')" class="relative w-9 h-9 rounded-full hover:bg-gray-100 flex items-center justify-center transition-colors">
            <svg class="w-5 h-5 text-[#5a7a5c]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            <?php if ($unreadNotificationCount > 0): ?>
              <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-semibold flex items-center justify-center"><?= $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount ?></span>
            <?php endif; ?>
          </button>

          <div id="notif-dropdown" class="hidden absolute right-0 mt-2 w-96 max-w-[90vw] bg-white rounded-2xl border border-gray-100 shadow-lg z-50 overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
              <p class="text-sm font-semibold text-[#1a2e1c]">Notifications</p>
              <?php if ($unreadNotificationCount > 0): ?>
                <form method="POST" action="notifications-mark-all.php">
                  <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" />
                  <button type="submit" class="text-[12px] font-medium text-[#17611f] hover:underline">Mark all as read</button>
                </form>
              <?php endif; ?>
            </div>
            <div class="max-h-96 overflow-y-auto divide-y divide-gray-50">
              <?php if (empty($recentNotifications)): ?>
                <p class="text-center text-sm text-[#9e9e9e] py-8">No notifications yet.</p>
              <?php else: foreach ($recentNotifications as $n): ?>
                <a href="notification-open.php?id=<?= (int)$n['id'] ?>" class="block px-4 py-3 hover:bg-gray-50 transition-colors <?= !$n['is_read'] ? 'bg-[#e8f5e9]/60' : '' ?>">
                  <div class="flex items-start gap-2">
                    <?php if (!$n['is_read']): ?>
                      <span class="w-2 h-2 rounded-full bg-[#17611f] mt-1.5 flex-shrink-0"></span>
                    <?php else: ?>
                      <span class="w-2 h-2 flex-shrink-0"></span>
                    <?php endif; ?>
                    <div class="min-w-0 flex-1">
                      <p class="text-[13px] font-semibold text-[#1a2e1c] truncate"><?= htmlspecialchars($n['title']) ?></p>
                      <p class="text-[12px] text-[#5a7a5c] truncate"><?= htmlspecialchars(strtok($n['message'], "\n")) ?></p>
                      <p class="text-[11px] text-[#9e9e9e] mt-0.5">
                        <?php if (!empty($n['customer_name'])): ?><?= htmlspecialchars($n['customer_name']) ?> · <?php endif; ?>
                        <?= date('M j, g:i A', strtotime($n['created_at'])) ?>
                      </p>
                    </div>
                  </div>
                </a>
              <?php endforeach; endif; ?>
            </div>
            <a href="notifications.php" class="block text-center text-[12px] font-medium text-[#17611f] hover:underline py-2.5 border-t border-gray-100">View all notifications</a>
          </div>
        </div>

        <a href="admin-profile.php" class="flex items-center gap-2.5 pl-3 border-l border-[rgba(27,94,32,0.12)] hover:opacity-80 transition-opacity">
          <div class="w-9 h-9 rounded-full bg-[#17611f] text-white text-xs font-semibold flex items-center justify-center"><?= htmlspecialchars($initials) ?></div>
          <div class="leading-tight text-left">
            <p class="text-sm font-semibold text-[#1a2e1c]"><?= htmlspecialchars($adminName) ?></p>
            <p class="text-[11px] text-[#9e9e9e]"><?= htmlspecialchars($adminRole) ?></p>
          </div>
        </a>
      </div>
    </header>

    <script>
      // Close the notification dropdown when clicking anywhere outside it.
      document.addEventListener('click', function (e) {
        var dropdown = document.getElementById('notif-dropdown');
        var btn = document.getElementById('notif-bell-btn');
        if (dropdown && !dropdown.classList.contains('hidden') && !dropdown.contains(e.target) && !btn.contains(e.target)) {
          dropdown.classList.add('hidden');
        }
      });

      // Auto-fade any success/error flash message (e.g. "Warranty request
      // #1 updated to 'Approved'.") a few seconds after it appears, so it
      // doesn't linger on screen indefinitely.
      document.querySelectorAll('[data-flash-message]').forEach(function (el) {
        setTimeout(function () {
          el.style.transition = 'opacity 0.6s ease, max-height 0.6s ease, margin 0.6s ease, padding 0.6s ease';
          el.style.opacity = '0';
          el.style.maxHeight = el.offsetHeight + 'px';
          requestAnimationFrame(function () {
            el.style.maxHeight = '0px';
            el.style.marginTop = '0px';
            el.style.marginBottom = '0px';
            el.style.paddingTop = '0px';
            el.style.paddingBottom = '0px';
            el.style.overflow = 'hidden';
          });
        }, 4000);
      });
    </script>

