<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin-auth.php';

$activePage = 'faq';
$pageTitle = 'FAQ Management';

$message = "";
$messageType = "";

// ---------------------------------------------------------------
// Handle add / update / delete actions
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['save_faq'])) {

        $faqId = (int)($_POST['faq_id'] ?? 0);
        $question = trim($_POST['question'] ?? '');
        $answer = trim($_POST['answer'] ?? '');
        $category = trim($_POST['category'] ?? '') ?: 'General';

        if (empty($question) || empty($answer)) {

            $message = "Please fill in both the question and the answer.";
            $messageType = "error";

        } elseif ($faqId > 0) {

            // Editing an existing FAQ
            $update = $conn->prepare("UPDATE faqs SET question = ?, answer = ?, category = ? WHERE id = ?");
            if ($update->execute([$question, $answer, $category, $faqId])) {
                $message = "FAQ updated successfully.";
                $messageType = "success";
            } else {
                $message = "Something went wrong updating that FAQ.";
                $messageType = "error";
            }

        } else {

            // Adding a new FAQ
            $insert = $conn->prepare("INSERT INTO faqs (question, answer, category) VALUES (?, ?, ?)");
            if ($insert->execute([$question, $answer, $category])) {
                $message = "New FAQ added successfully.";
                $messageType = "success";
            } else {
                $message = "Something went wrong adding that FAQ.";
                $messageType = "error";
            }

        }

    } elseif (isset($_POST['delete_faq_id'])) {

        $deleteId = (int)$_POST['delete_faq_id'];
        $delete = $conn->prepare("DELETE FROM faqs WHERE id = ?");
        if ($delete->execute([$deleteId])) {
            $message = "FAQ deleted.";
            $messageType = "success";
        } else {
            $message = "Something went wrong deleting that FAQ.";
            $messageType = "error";
        }

    }

}

// If we're editing, load that FAQ's current values into the form
$editingFaq = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM faqs WHERE id = ?");
    $stmt->execute([$editId]);
    $editingFaq = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$faqs = $conn->query("SELECT * FROM faqs ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$totalFaqs = count($faqs);

$categoryColors = [
    'Warranty' => 'bg-[#F1E4D1] text-[#52b788]',
    'Returns' => 'bg-blue-50 text-blue-600',
    'Care' => 'bg-green-50 text-green-600',
    'Shipping' => 'bg-[#fff8e1] text-amber-600',
    'Damaged' => 'bg-red-50 text-red-500',
];

function faqCategoryClass(string $category, array $categoryColors): string
{
    return $categoryColors[$category] ?? 'bg-gray-100 text-[#5a7a5c]';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FAQ Management | Luntiang H.A.P.A.G. Admin</title>
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

      <!-- Main Content -->
      <main class="flex-1 overflow-y-auto p-6 space-y-6">

        <?php if ($message): ?>
          <div data-flash-message data-flash-type="<?= htmlspecialchars($messageType) ?>" class="rounded-xl px-4 py-3 text-sm <?= $messageType === 'error' ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-green-50 text-green-700 border border-green-100' ?>">
            <?= htmlspecialchars($message) ?>
          </div>
        <?php endif; ?>

        <!-- Add / Edit form -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
          <h3 class="text-sm font-semibold text-[#1a2e1c] mb-4"><?= $editingFaq ? 'Edit FAQ' : 'Add a New FAQ' ?></h3>
          <form method="POST" class="space-y-4">
            <?php if ($editingFaq): ?>
              <input type="hidden" name="faq_id" value="<?= $editingFaq['id'] ?>" />
            <?php endif; ?>
            <div>
              <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Question</label>
              <input type="text" name="question" required placeholder="e.g. What is the return policy?"
                     value="<?= htmlspecialchars($editingFaq['question'] ?? '') ?>"
                     class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Answer</label>
              <textarea name="answer" rows="3" required placeholder="Write the answer customers will see..."
                        class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors"><?= htmlspecialchars($editingFaq['answer'] ?? '') ?></textarea>
            </div>
            <div class="max-w-xs">
              <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Category</label>
              <select name="category" class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors">
                <?php foreach (['General', 'Products', 'Orders', 'Delivery', 'Returns', 'Care', 'Freshness', 'Account'] as $cat): ?>
                  <option value="<?= $cat ?>" <?= (($editingFaq['category'] ?? 'General') === $cat) ? 'selected' : '' ?>><?= $cat ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="flex items-center gap-3">
              <button type="submit" name="save_faq" value="1" class="px-6 py-3 rounded-full bg-[#17611f] text-white text-sm font-medium hover:bg-[#14521a] transition-colors"><?= $editingFaq ? 'Save Changes' : 'Add FAQ' ?></button>
              <?php if ($editingFaq): ?>
                <a href="admin-faq.php" class="px-6 py-3 rounded-full border border-gray-300 text-[#1a2e1c] text-sm font-medium hover:bg-gray-100 transition-colors">Cancel</a>
              <?php endif; ?>
            </div>
          </form>
        </div>

        <!-- FAQ list -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-[#1a2e1c]">All FAQs</h3>
            <span class="text-[12px] text-[#9e9e9e]"><?= $totalFaqs ?> total</span>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="bg-gray-50 text-[11px] uppercase tracking-wide text-[#9e9e9e]">
                  <th class="text-left font-medium py-2.5 px-4">Question</th>
                  <th class="text-left font-medium py-2.5 px-4">Category</th>
                  <th class="text-left font-medium py-2.5 px-4">Last Updated</th>
                  <th class="text-left font-medium py-2.5 px-4">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($faqs)): ?>
                  <tr><td colspan="4" class="py-10 px-4 text-center text-sm text-[#9e9e9e]">No FAQs yet — add your first one above.</td></tr>
                <?php else: foreach ($faqs as $f): ?>
                  <tr class="border-b border-gray-100 last:border-0">
                    <td class="py-4 px-4 text-[13px] font-medium text-[#1a2e1c] max-w-xs"><?= htmlspecialchars($f['question']) ?></td>
                    <td class="py-4 px-4"><span class="inline-block text-[11px] font-medium <?= faqCategoryClass($f['category'], $categoryColors) ?> rounded-full px-3 py-1"><?= htmlspecialchars($f['category']) ?></span></td>
                    <td class="py-4 px-4 text-[13px] text-[#5a7a5c]"><?= date('M j, Y', strtotime($f['updated_at'])) ?></td>
                    <td class="py-4 px-4">
                      <div class="flex gap-2">
                        <a href="admin-faq.php?edit=<?= $f['id'] ?>" class="text-[11px] font-medium border border-[rgba(27,94,32,0.12)] rounded-full px-3 py-1 text-[#5a7a5c] hover:bg-gray-50 transition-colors">Edit</a>
                        <form method="POST" onsubmit="return confirm('Delete this FAQ? This cannot be undone.');" class="inline">
                          <input type="hidden" name="delete_faq_id" value="<?= $f['id'] ?>" />
                          <button type="submit" class="text-[11px] font-medium border border-red-200 rounded-full px-3 py-1 text-red-500 hover:bg-red-50 transition-colors">Delete</button>
                        </form>
                      </div>
                    </td>
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