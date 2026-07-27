<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $rating = $_POST['rating'] ?? '';
    $comments = trim($_POST['comments'] ?? '');

    if (empty($rating) || !ctype_digit((string)$rating) || $rating < 1 || $rating > 5) {

        $message = "Please choose a rating before submitting.";
        $messageType = "error";

    } else {

        $stmt = $conn->prepare("
            INSERT INTO feedback (user_id, rating, comments)
            VALUES (?, ?, ?)
        ");

        $success = $stmt->execute([
            $_SESSION['user_id'],
            (int)$rating,
            $comments !== '' ? $comments : null
        ]);

        if ($success) {
            header("Location: my-profile.php?feedback=1");
            exit();
        } else {
            $message = "Something went wrong submitting your feedback. Please try again.";
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Send Feedback | Luntiang H.A.P.A.G.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Nunito', sans-serif; }
    .font-black { font-family: 'Nunito', serif; }
  </style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c] min-h-screen flex flex-col">

  <!-- Header -->
  <?php include __DIR__ . '/includes/header.php'; ?>

  <!-- Main Content -->
  <main class="flex-1 max-w-3xl w-full mx-auto px-6 py-16">
    <a href="my-profile.php" class="inline-flex items-center gap-2 text-sm text-[#17611f] hover:text-[#14521a] transition-colors mb-8">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
      Back to Dashboard
    </a>
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-10">
      <span class="inline-block text-[11px] font-semibold tracking-wide text-[#17611f] bg-[#e8f5e9] rounded-full px-3 py-1 mb-5">QUICK SUPPORT</span>
      <h1 class="font-black text-3xl font-semibold text-[#1a2e1c] mb-4">Send Feedback</h1>
      <div class="text-[#5a7a5c] text-[15px] leading-relaxed space-y-4">
        <p>Share your experience and help us improve our products and customer service.</p>
      </div>

      <?php if ($message): ?>
        <div class="mt-6 rounded-xl px-4 py-3 text-sm <?= $messageType === 'error' ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-green-50 text-green-700 border border-green-100' ?>">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>

      <form class="space-y-5 mt-6" method="POST">
          <div>
            <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Overall Rating</label>
            <div class="flex flex-row-reverse justify-end gap-1">
              <?php
                // Star 5 sits first in the DOM (so ~ siblings after it are stars 4,3,2,1),
                // and flex-row-reverse displays them left-to-right as 1,2,3,4,5.
                // Each label lists peer-checked/s{j} for every j >= its own number, so
                // choosing rating N lights up stars 1..N.
                for ($i = 5; $i >= 1; $i--):
                    $peerClasses = [];
                    for ($j = $i; $j <= 5; $j++) {
                        $peerClasses[] = "peer-checked/s{$j}:text-amber-400";
                    }
                    $peerClassStr = implode(' ', $peerClasses);
              ?>
                <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>"
                       class="peer/s<?= $i ?> sr-only" <?= (isset($_POST['rating']) && (int)$_POST['rating'] === $i) ? 'checked' : '' ?> />
                <label for="star<?= $i ?>"
                       class="cursor-pointer select-none text-4xl leading-none text-gray-300 transition-colors hover:text-amber-300 <?= $peerClassStr ?>">&#9733;</label>
              <?php endfor; ?>
            </div>
            <p class="text-xs text-[#9e9e9e] mt-2">Click a star to rate your experience, 1 (very poor) to 5 (excellent).</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Comments (optional)</label>
            <textarea rows="4" name="comments" placeholder="Tell us about your experience..." class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors"><?= htmlspecialchars($_POST['comments'] ?? '') ?></textarea>
          </div>
          <button type="submit" class="px-6 py-3 rounded-full bg-[#17611f] text-white text-sm font-medium hover:bg-[#14521a] transition-colors">Send Feedback</button>
        </form>
    </div>
  </main>

  <!-- Footer -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
