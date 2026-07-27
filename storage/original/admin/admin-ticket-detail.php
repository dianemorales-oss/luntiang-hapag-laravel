<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin-auth.php';
require_once __DIR__ . '/../includes/form-helpers.php';

$activePage = 'tickets';
$pageTitle = 'Ticket Details';

$ticketId = (int)($_GET['id'] ?? 0);
if ($ticketId <= 0) {
    header("Location: admin-tickets.php");
    exit();
}

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $newStatus = $_POST['status'] ?? '';
    $reply = trim($_POST['admin_reply'] ?? '');
    $allowedStatuses = ['open', 'in_progress', 'resolved', 'closed'];

    if (!in_array($newStatus, $allowedStatuses, true)) {

        $message = "Please choose a valid status.";
        $messageType = "error";

    } elseif (mb_strlen($reply) > 2000) {

        $message = "Reply is too long (2000 characters max).";
        $messageType = "error";

    } else {

        try {

            $conn->beginTransaction();

            $update = $conn->prepare("UPDATE tickets SET status = ? WHERE id = ?");
            $update->execute([$newStatus, $ticketId]);

            if ($reply !== '') {
                $insertReply = $conn->prepare("
                    INSERT INTO ticket_replies (ticket_id, sender_type, message)
                    VALUES (?, 'admin', ?)
                ");
                $insertReply->execute([$ticketId, $reply]);
            }

            $conn->commit();

            $message = "Ticket updated successfully.";
            $messageType = "success";

        } catch (PDOException $e) {

            $conn->rollBack();
            $message = "Something went wrong updating this ticket.";
            $messageType = "error";

        }

    }

}

$stmt = $conn->prepare("
    SELECT t.*, u.first_name, u.last_name, u.email, u.phone, u.address
    FROM tickets t
    JOIN users u ON u.id = t.user_id
    WHERE t.id = ?
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header("Location: admin-tickets.php");
    exit();
}

$repliesStmt = $conn->prepare("SELECT * FROM ticket_replies WHERE ticket_id = ? ORDER BY created_at ASC, id ASC");
$repliesStmt->execute([$ticketId]);
$ticketReplies = $repliesStmt->fetchAll(PDO::FETCH_ASSOC);

function statusBadgeDetail(string $status): string
{
    $map = [
        'open' => ['blue', 'Open'],
        'in_progress' => ['amber', 'In Progress'],
        'resolved' => ['green', 'Resolved'],
        'closed' => ['gray', 'Closed'],
    ];
    [$color, $label] = $map[$status] ?? ['gray', ucfirst($status)];
    $colors = ['blue' => 'text-blue-600 bg-blue-50', 'amber' => 'text-amber-600 bg-[#fff8e1]', 'green' => 'text-green-600 bg-green-50', 'gray' => 'text-[#5a7a5c] bg-gray-100'];
    return "<span class=\"inline-flex items-center px-3 py-1 rounded-full text-[12px] font-medium {$colors[$color]}\">$label</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ticket #WC-<?= str_pad($ticket['id'], 4, '0', STR_PAD_LEFT) ?> | Luntiang H.A.P.A.G. Admin</title>
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

      <main class="flex-1 overflow-y-auto p-6">
        <a href="admin-tickets.php" class="inline-flex items-center gap-2 text-sm text-[#17611f] hover:text-[#14521a] transition-colors mb-5">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
          Back to Tickets
        </a>

        <?php if ($message): ?>
          <div data-flash-message data-flash-type="<?= htmlspecialchars($messageType) ?>" class="mb-5 rounded-xl px-4 py-3 text-sm <?= $messageType === 'error' ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-green-50 text-green-700 border border-green-100' ?>">
            <?= htmlspecialchars($message) ?>
          </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

          <div class="lg:col-span-2 space-y-5">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
              <div class="flex items-start justify-between mb-4">
                <div>
                  <p class="text-[12px] text-[#9e9e9e] mb-1">Ticket #WC-<?= str_pad($ticket['id'], 4, '0', STR_PAD_LEFT) ?></p>
                  <h1 class="font-black text-2xl font-semibold text-[#1a2e1c]"><?= htmlspecialchars($ticket['subject']) ?></h1>
                </div>
                <?= statusBadgeDetail($ticket['status']) ?>
              </div>
              <div class="flex flex-wrap gap-x-6 gap-y-1 text-[13px] text-[#5a7a5c] mb-5">
                <span>Category: <span class="font-medium text-[#1a2e1c]"><?= htmlspecialchars($ticket['category']) ?></span></span>
                <span>Priority: <span class="font-medium text-[#1a2e1c]"><?= htmlspecialchars($ticket['priority'] ?? 'Medium') ?></span></span>
                <?php if (!empty($ticket['order_number'])): ?>
                  <span>Order #: <span class="font-medium text-[#1a2e1c]"><?= htmlspecialchars($ticket['order_number']) ?></span></span>
                <?php endif; ?>
                <span>Submitted: <span class="font-medium text-[#1a2e1c]"><?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?></span></span>
              </div>
              <div class="bg-gray-50 rounded-xl p-4 text-[14px] text-[#1a2e1c] leading-relaxed whitespace-pre-line"><?= htmlspecialchars($ticket['issue_description']) ?></div>
              <?php $ticketAttachments = decodeAttachmentPaths($ticket['attachment_path'] ?? null); ?>
              <?php if (!empty($ticketAttachments)): ?>
                <div class="flex flex-col gap-1 mt-3">
                  <?php foreach ($ticketAttachments as $i => $attPath): ?>
                    <a href="view-attachment.php?path=<?= urlencode($attPath) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-[13px] font-medium text-[#17611f] hover:underline">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                      View Attachment<?= count($ticketAttachments) > 1 ? ' ' . ($i + 1) : '' ?>
                    </a>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
              <h3 class="text-sm font-semibold text-[#1a2e1c] mb-4">Conversation</h3>
              <div class="space-y-4 max-h-[420px] overflow-y-auto pr-1">

                <!-- Original customer message -->
                <div class="flex justify-start">
                  <div class="max-w-[80%]">
                    <div class="rounded-2xl px-4 py-2.5 text-[14px] leading-relaxed bg-white border border-[rgba(27,94,32,0.12)] text-[#1a2e1c] rounded-bl-sm whitespace-pre-line"><?= htmlspecialchars($ticket['issue_description']) ?></div>
                    <p class="text-[11px] text-[#9e9e9e] mt-1 text-left">
                      <?= htmlspecialchars($ticket['first_name']) ?> · <?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?>
                    </p>
                  </div>
                </div>

                <?php foreach ($ticketReplies as $r):
                    $isAdmin = $r['sender_type'] === 'admin';
                ?>
                  <div class="flex <?= $isAdmin ? 'justify-end' : 'justify-start' ?>">
                    <div class="max-w-[80%]">
                      <div class="rounded-2xl px-4 py-2.5 text-[14px] leading-relaxed whitespace-pre-line <?= $isAdmin ? 'bg-[#17611f] text-white rounded-br-sm' : 'bg-white border border-[rgba(27,94,32,0.12)] text-[#1a2e1c] rounded-bl-sm' ?>">
                        <?= htmlspecialchars($r['message']) ?>
                      </div>
                      <p class="text-[11px] text-[#9e9e9e] mt-1 <?= $isAdmin ? 'text-right' : 'text-left' ?>">
                        <?= $isAdmin ? 'Luntiang H.A.P.A.G. Support' : htmlspecialchars($ticket['first_name']) ?> · <?= date('M j, Y g:i A', strtotime($r['created_at'])) ?>
                      </p>
                    </div>
                  </div>
                <?php endforeach; ?>

              </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
              <h3 class="text-sm font-semibold text-[#1a2e1c] mb-4">Reply &amp; Update Status</h3>
              <form method="POST" class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Reply to customer</label>
                  <textarea name="admin_reply" rows="5" maxlength="2000" placeholder="Type your reply..." class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors"></textarea>
                </div>
                <div>
                  <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Status</label>
                  <select name="status" class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors">
                    <?php foreach (['open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $val => $label): ?>
                      <option value="<?= $val ?>" <?= $ticket['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <button type="submit" class="px-6 py-3 rounded-full bg-[#17611f] text-white text-sm font-medium hover:bg-[#14521a] transition-colors">Save Changes</button>
              </form>
            </div>
          </div>

          <div class="space-y-5">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
              <h3 class="text-sm font-semibold text-[#1a2e1c] mb-4">Customer</h3>
              <div class="flex items-center gap-3 mb-4">
                <div class="w-11 h-11 rounded-full bg-[#17611f] text-white text-sm font-semibold flex items-center justify-center"><?= strtoupper(substr($ticket['first_name'], 0, 1) . substr($ticket['last_name'], 0, 1)) ?></div>
                <div>
                  <p class="text-sm font-semibold text-[#1a2e1c]"><?= htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']) ?></p>
                  <p class="text-[12px] text-[#9e9e9e]"><?= htmlspecialchars($ticket['email']) ?></p>
                </div>
              </div>
              <div class="text-[13px] text-[#5a7a5c] space-y-1">
                <p>Phone: <span class="text-[#1a2e1c]"><?= htmlspecialchars($ticket['phone']) ?></span></p>
                <?php if (!empty($ticket['address'])): ?>
                  <p>Address: <span class="text-[#1a2e1c] whitespace-pre-line"><?= htmlspecialchars($ticket['address']) ?></span></p>
                <?php endif; ?>
              </div>
              <a href="admin-customers.php?email=<?= urlencode($ticket['email']) ?>" class="inline-block mt-4 text-[12px] font-medium text-[#17611f] hover:underline">View customer profile →</a>
            </div>
          </div>

        </div>
      </main>
    </div>
  </div>

  <script>
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