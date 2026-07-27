<?php
session_start();
require '../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Freshness Guarantee | Luntiang H.A.P.A.G.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>body{font-family:'Nunito',sans-serif;background:#f4faf5}</style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c]">
<?php include __DIR__.'/../includes/header.php'; ?>
<main class="max-w-3xl mx-auto px-6 py-10">
  <a href="<?= isset($_SESSION['user_id'])?'../my-profile.php?section=support':'../index.php'?>" class="inline-flex items-center gap-2 text-sm text-[#17611f] font-semibold hover:underline mb-6">← Back</a>
  <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-8">
    <h1 class="text-2xl font-black mb-4">Freshness Guarantee</h1>
    <div class="text-sm text-[#5a7a5c] leading-relaxed space-y-3">
      <p>We guarantee your lettuce arrives fresh, crisp, and of the highest quality. If your order is wilted, damaged, or not meeting our standards, we will replace it at no cost — just let us know within 24 hours.</p>
      <p><strong>What's covered:</strong> Wilted or damaged lettuce upon delivery, wrong variety delivered, missing items, quality below our standards.</p>
      <p><strong>How to claim:</strong> Submit a support ticket or contact us with your order number and photos. We will review and resolve within 1-2 business days.</p>
    </div>
  </div>
</main>
<?php include __DIR__.'/../includes/footer.php'; ?>
</body>
</html>
