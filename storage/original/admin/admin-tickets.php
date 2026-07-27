<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin-auth.php';

$activePage = 'tickets';
$pageTitle = 'Support Tickets';

$statusFilter = $_GET['status'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$allowedStatuses = ['open', 'in_progress', 'resolved', 'closed'];

$sql = "
    SELECT t.id, t.subject, t.category, t.priority, t.status, t.created_at,
           u.first_name, u.last_name, u.email
    FROM tickets t
    JOIN users u ON u.id = t.user_id
    WHERE 1=1
";
$params = [];

if (in_array($statusFilter, $allowedStatuses, true)) {
    $sql .= " AND t.status = ?";
    $params[] = $statusFilter;
}

if ($search !== '') {
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR t.subject LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like);
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusCounts = [];
foreach ($allowedStatuses as $s) {
    $c = $conn->prepare("SELECT COUNT(*) FROM tickets WHERE status = ?");
    $c->execute([$s]);
    $statusCounts[$s] = (int)$c->fetchColumn();
}
$totalCount = (int)$conn->query("SELECT COUNT(*) FROM tickets")->fetchColumn();

function statusBadgeTickets(string $status): string
{
    $map = [
        'open' => ['blue', 'Open'],
        'in_progress' => ['amber', 'In Progress'],
        'resolved' => ['green', 'Resolved'],
        'closed' => ['gray', 'Closed'],
    ];
    [$color, $label] = $map[$status] ?? ['gray', ucfirst($status)];
    $colors = ['blue' => 'text-blue-600 bg-blue-500', 'amber' => 'text-amber-600 bg-[#fff8e1]0', 'green' => 'text-green-600 bg-green-500', 'gray' => 'text-[#9e9e9e] bg-gray-400'];
    [$textColor, $dotColor] = explode(' ', $colors[$color]);
    return "<span class=\"inline-flex items-center gap-1.5 text-[13px] font-medium $textColor\"><span class=\"w-1.5 h-1.5 rounded-full $dotColor\"></span>$label</span>";
}

function priorityBadgeTickets(string $priority): string
{
    $colors = ['Low' => 'text-[#5a7a5c] bg-gray-100', 'Medium' => 'text-amber-600 bg-[#fff8e1]', 'High' => 'text-red-600 bg-red-50'];
    $classes = $colors[$priority] ?? 'text-[#5a7a5c] bg-gray-100';
    return "<span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-[12px] font-medium $classes\">" . htmlspecialchars($priority) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Support Tickets | Luntiang H.A.P.A.G. Admin</title>
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

        <!-- Status filter tabs -->
        <div class="flex flex-wrap items-center gap-2">
          <a href="admin-tickets.php" class="px-4 py-2 rounded-full text-[13px] font-medium <?= $statusFilter === 'all' ? 'bg-[#17611f] text-white' : 'bg-white border border-[rgba(27,94,32,0.12)] text-[#5a7a5c] hover:bg-gray-50' ?>">All <span class="opacity-70">(<?= $totalCount ?>)</span></a>
          <?php foreach ($allowedStatuses as $s):
            $label = ucwords(str_replace('_', ' ', $s));
          ?>
            <a href="admin-tickets.php?status=<?= $s ?>" class="px-4 py-2 rounded-full text-[13px] font-medium <?= $statusFilter === $s ? 'bg-[#17611f] text-white' : 'bg-white border border-[rgba(27,94,32,0.12)] text-[#5a7a5c] hover:bg-gray-50' ?>"><?= $label ?> <span class="opacity-70">(<?= $statusCounts[$s] ?>)</span></a>
          <?php endforeach; ?>

          <form method="GET" class="ml-auto flex items-center gap-2">
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>" />
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search customer, email, subject..." class="w-64 rounded-full border border-[rgba(27,94,32,0.12)] px-4 py-2 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#6B4226]/30 focus:border-[#6B4226] transition-colors" />
            <button type="submit" class="px-4 py-2 rounded-full bg-[#17611f] text-white text-sm font-medium hover:bg-[#14521a] transition-colors">Search</button>
          </form>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left">
              <thead>
                <tr class="text-[11px] uppercase tracking-wide text-[#9e9e9e] border-b border-gray-100">
                  <th class="py-3 px-4 font-medium">Ticket</th>
                  <th class="py-3 px-4 font-medium">Customer</th>
                  <th class="py-3 px-4 font-medium">Email</th>
                  <th class="py-3 px-4 font-medium">Subject</th>
                  <th class="py-3 px-4 font-medium">Category</th>
                  <th class="py-3 px-4 font-medium">Priority</th>
                  <th class="py-3 px-4 font-medium">Status</th>
                  <th class="py-3 px-4 font-medium">Date</th>
                  <th class="py-3 px-4 font-medium">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($tickets)): ?>
                  <tr><td colspan="9" class="py-10 px-4 text-center text-sm text-[#9e9e9e]">No tickets found.</td></tr>
                <?php else: foreach ($tickets as $t): ?>
                  <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50/60">
                    <td class="py-3 px-4 text-[13px] font-medium text-[#1a2e1c]">#WC-<?= str_pad($t['id'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td class="py-3 px-4 text-[13px] text-[#1a2e1c]"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></td>
                    <td class="py-3 px-4 text-[13px] text-[#5a7a5c]"><?= htmlspecialchars($t['email']) ?></td>
                    <td class="py-3 px-4 text-[13px] text-[#5a7a5c] max-w-[200px] truncate"><?= htmlspecialchars($t['subject']) ?></td>
                    <td class="py-3 px-4 text-[13px] text-[#5a7a5c]"><?= htmlspecialchars($t['category']) ?></td>
                    <td class="py-3 px-4"><?= priorityBadgeTickets($t['priority'] ?? 'Medium') ?></td>
                    <td class="py-3 px-4"><?= statusBadgeTickets($t['status']) ?></td>
                    <td class="py-3 px-4 text-[13px] text-[#9e9e9e]"><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
                    <td class="py-3 px-4"><a href="admin-ticket-detail.php?id=<?= $t['id'] ?>" class="text-[12px] font-medium border border-[#6B4226] rounded-full px-3 py-1 text-[#17611f] hover:bg-[#17611f] hover:text-white transition-colors">View</a></td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </main>
</div>
    </div>
  </div>
</body>
</html>
