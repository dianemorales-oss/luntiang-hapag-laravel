<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin-auth.php';
require_once __DIR__ . '/../includes/admin-notes.php';
require_once __DIR__ . '/../includes/form-helpers.php';

$activePage = 'warranty';
$pageTitle = 'Warranty Requests';

$message = "";
$messageType = "";

// Handle approve/deny/pending status updates + Admin Note
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = (int)($_POST['request_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';
    $adminNote = trim($_POST['admin_note'] ?? '');
    $allowed = ['pending', 'approved', 'denied'];

    if ($requestId > 0 && in_array($newStatus, $allowed, true)) {
        // If the admin leaves the note blank, fall back to a status-appropriate
        // default so the customer always sees a relevant update — the note
        // textarea is still fully editable for a custom message.
        $noteToSave = $adminNote !== '' ? $adminNote : defaultAdminNote('warranty', $newStatus);

        $update = $conn->prepare("UPDATE warranty_requests SET status = ?, admin_note = ? WHERE id = ?");
        if ($update->execute([$newStatus, $noteToSave !== '' ? $noteToSave : null, $requestId])) {
            $message = "Warranty request #$requestId updated to \"" . ucfirst($newStatus) . "\".";
            $messageType = "success";
        } else {
            $message = "Something went wrong updating that request.";
            $messageType = "error";
        }
    }
}

$statusFilter = $_GET['status'] ?? 'all';
$allowedStatuses = ['pending', 'approved', 'denied'];

$sql = "
    SELECT w.*, u.first_name, u.last_name, u.email, u.address
    FROM warranty_requests w
    JOIN users u ON u.id = w.user_id
    WHERE 1=1
";
$params = [];
if (in_array($statusFilter, $allowedStatuses, true)) {
    $sql .= " AND w.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY w.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusCounts = [];
foreach ($allowedStatuses as $s) {
    $c = $conn->prepare("SELECT COUNT(*) FROM warranty_requests WHERE status = ?");
    $c->execute([$s]);
    $statusCounts[$s] = (int)$c->fetchColumn();
}
$totalCount = (int)$conn->query("SELECT COUNT(*) FROM warranty_requests")->fetchColumn();

function warrantyBadge(string $status): string
{
    $map = ['pending' => ['amber', 'Pending'], 'approved' => ['green', 'Approved'], 'denied' => ['red', 'Denied']];
    [$color, $label] = $map[$status] ?? ['gray', ucfirst($status)];
    $colors = ['amber' => 'text-amber-600 bg-[#fff8e1]0', 'green' => 'text-green-600 bg-green-500', 'red' => 'text-red-600 bg-red-400', 'gray' => 'text-[#9e9e9e] bg-gray-400'];
    [$textColor, $dotColor] = explode(' ', $colors[$color]);
    return "<span class=\"inline-flex items-center gap-1.5 text-[13px] font-medium $textColor\"><span class=\"w-1.5 h-1.5 rounded-full $dotColor\"></span>$label</span>";
}

// Luntiang H.A.P.A.G.'s manufacturer warranty covers 5 years from the purchase
// // stored as part of the freshness guarantee workflow. Older claims that predate the
// purchase_date column simply have no date on file, so eligibility
// can't be determined for them yet.
const WARRANTY_POLICY_YEARS = 5;

function warrantyEligibilityBadge(?string $purchaseDate): string
{
    if (empty($purchaseDate)) {
        return '<span class="inline-flex items-center gap-1.5 text-[12px] font-medium text-[#9e9e9e]"><span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>No purchase date on file</span>';
    }

    $purchase = new DateTime($purchaseDate);
    $expiry = (clone $purchase)->modify('+' . WARRANTY_POLICY_YEARS . ' years');
    $today = new DateTime('today');

    if ($today <= $expiry) {
        return '<span class="inline-flex items-center gap-1.5 text-[12px] font-medium text-green-600"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>Eligible · expires ' . $expiry->format('M j, Y') . '</span>';
    }

    return '<span class="inline-flex items-center gap-1.5 text-[12px] font-medium text-red-600"><span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>Expired ' . $expiry->format('M j, Y') . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Warranty Requests | Luntiang H.A.P.A.G. Admin</title>
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
          <div data-flash-message data-flash-type="<?= htmlspecialchars($messageType) ?>" class="rounded-xl px-4 py-3 text-sm <?= $messageType === 'error' ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-green-50 text-green-700 border border-green-100' ?>">
            <?= htmlspecialchars($message) ?>
          </div>
        <?php endif; ?>

        <div class="flex flex-wrap items-center gap-2">
          <a href="admin-warranty.php" class="px-4 py-2 rounded-full text-[13px] font-medium <?= $statusFilter === 'all' ? 'bg-[#17611f] text-white' : 'bg-white border border-[rgba(27,94,32,0.12)] text-[#5a7a5c] hover:bg-gray-50' ?>">All <span class="opacity-70">(<?= $totalCount ?>)</span></a>
          <?php foreach ($allowedStatuses as $s): ?>
            <a href="admin-warranty.php?status=<?= $s ?>" class="px-4 py-2 rounded-full text-[13px] font-medium <?= $statusFilter === $s ? 'bg-[#17611f] text-white' : 'bg-white border border-[rgba(27,94,32,0.12)] text-[#5a7a5c] hover:bg-gray-50' ?>"><?= ucfirst($s) ?> <span class="opacity-70">(<?= $statusCounts[$s] ?>)</span></a>
          <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-1 gap-4">
          <?php if (empty($requests)): ?>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center text-sm text-[#9e9e9e]">No warranty requests found.</div>
          <?php else: foreach ($requests as $r): ?>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
              <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-3 mb-2">
                    <p class="text-[12px] text-[#9e9e9e]">Request #<?= $r['id'] ?> · <?= date('M j, Y', strtotime($r['created_at'])) ?></p>
                    <?= warrantyBadge($r['status']) ?>
                  </div>
                  <h3 class="font-semibold text-[#1a2e1c] mb-1"><?= htmlspecialchars($r['product_name']) ?></h3>
                  <p class="text-[13px] text-[#5a7a5c] mb-1">
                    <?php if (!empty($r['order_number'])): ?>
                      Order #: <span class="font-medium text-[#1a2e1c]"><?= htmlspecialchars($r['order_number']) ?></span> ·
                    <?php endif; ?>
                    Purchased: <span class="font-medium text-[#1a2e1c]"><?= !empty($r['purchase_date']) ? date('M j, Y', strtotime($r['purchase_date'])) : '—' ?></span>
                  </p>
                  <?php if (!empty($r['warranty_issue'])): ?>
                    <p class="text-[13px] text-[#5a7a5c] mb-1">Issue type: <span class="font-medium text-[#1a2e1c]"><?= htmlspecialchars($r['warranty_issue']) ?></span></p>
                  <?php endif; ?>
                  <p class="text-[13px] text-[#5a7a5c] mb-3"><?= htmlspecialchars($r['defect_description']) ?></p>
                  <p class="mb-3"><?= warrantyEligibilityBadge($r['purchase_date']) ?></p>
                  <?php
                    $proofAttachments = decodeAttachmentPaths($r['proof_of_purchase_path'] ?? null);
                    $damageAttachments = decodeAttachmentPaths($r['damage_photo_path'] ?? null);
                  ?>
                  <?php if (!empty($proofAttachments) || !empty($damageAttachments)): ?>
                    <div class="flex flex-wrap gap-4 mb-3">
                      <?php foreach ($proofAttachments as $i => $path): ?>
                        <a href="view-attachment.php?path=<?= urlencode($path) ?>" target="_blank" rel="noopener" class="text-[12px] font-medium text-[#17611f] hover:underline">View Proof of Purchase<?= count($proofAttachments) > 1 ? ' ' . ($i + 1) : '' ?></a>
                      <?php endforeach; ?>
                      <?php foreach ($damageAttachments as $i => $path): ?>
                        <a href="view-attachment.php?path=<?= urlencode($path) ?>" target="_blank" rel="noopener" class="text-[12px] font-medium text-[#17611f] hover:underline">View Damage Photo<?= count($damageAttachments) > 1 ? ' ' . ($i + 1) : '' ?></a>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                  <p class="text-[12px] text-[#5a7a5c]">
                    Customer: <span class="font-medium text-[#1a2e1c]"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></span> · 
                    <?= htmlspecialchars($r['email']) ?>
                    <?php if (!empty($r['address'])): ?>
                      <br><span class="text-[#5a7a5c]">Address: </span><span class="font-medium text-[#1a2e1c] whitespace-pre-line"><?= htmlspecialchars($r['address']) ?></span>
                    <?php endif; ?>
                  </p>
                </div>
                <form method="POST" class="flex-shrink-0 w-full md:w-80 space-y-2">
                  <input type="hidden" name="request_id" value="<?= $r['id'] ?>" />
                  <label class="block text-[12px] font-medium text-[#5a7a5c]">Admin Note <span class="text-[#9e9e9e] font-normal">(visible to customer)</span></label>
                  <textarea name="admin_note" rows="3" data-warranty-note placeholder="e.g. Your warranty request has been approved. Please bring the product to our service center together with your proof of purchase." class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-3 py-2 text-[13px] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#6B4226]/30 focus:border-[#6B4226] transition-colors resize-none"><?= htmlspecialchars($r['admin_note'] ?? '') ?></textarea>
                  <p class="text-[11px] text-[#9e9e9e]">Changing the status below fills this in automatically — feel free to edit it before saving.</p>
                  <div class="flex items-center gap-2">
                    <select name="new_status" data-warranty-status-select class="flex-1 rounded-full border border-[rgba(27,94,32,0.12)] px-3 py-2 text-[13px] focus:outline-none focus:ring-2 focus:ring-[#6B4226]/30 focus:border-[#6B4226] transition-colors">
                      <option value="pending" data-note="<?= htmlspecialchars(defaultAdminNote('warranty', 'pending')) ?>" <?= $r['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                      <option value="approved" data-note="<?= htmlspecialchars(defaultAdminNote('warranty', 'approved')) ?>" <?= $r['status'] === 'approved' ? 'selected' : '' ?>>Approve</option>
                      <option value="denied" data-note="<?= htmlspecialchars(defaultAdminNote('warranty', 'denied')) ?>" <?= $r['status'] === 'denied' ? 'selected' : '' ?>>Deny</option>
                    </select>
                    <button type="submit" class="px-4 py-2 rounded-full bg-[#17611f] text-white text-[13px] font-medium hover:bg-[#14521a] transition-colors flex-shrink-0">Save</button>
                  </div>
                  <?php if (!empty($r['updated_at'])): ?>
                    <p class="text-[11px] text-[#9e9e9e]">Last updated <?= date('M j, Y g:i A', strtotime($r['updated_at'])) ?></p>
                  <?php endif; ?>
                </form>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>

      </main>
    </div>
  </div>

  <script>
    // Auto-fill each card's Admin Note with a status-appropriate default
    // whenever the admin changes that card's status dropdown. The note
    // stays fully editable afterwards in case the admin wants to
    // customize the wording before saving.
    document.querySelectorAll('[data-warranty-status-select]').forEach(function (select) {
      select.addEventListener('change', function () {
        var form = select.closest('form');
        var textarea = form ? form.querySelector('[data-warranty-note]') : null;
        var option = select.options[select.selectedIndex];
        if (textarea && option && option.dataset.note) {
          textarea.value = option.dataset.note;
        }
      });
    });

    // Auto fade-out success/warning/info flash messages after a few seconds.
    document.querySelectorAll('[data-flash-message]').forEach(function (el) {
      if (el.dataset.flashType === 'error') return;
      setTimeout(function () {
        el.style.transition = 'opacity 0.6s ease';
        el.style.opacity = '0';
        setTimeout(function () {
          el.remove();
        }, 600);
      }, 4000);
    });
  </script>
</body>
</html>