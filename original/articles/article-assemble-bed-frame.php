<?php
session_start();
require '../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Harvest-on-Demand Guide | Luntiang H.A.P.A.G.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>body{font-family:'Nunito',sans-serif;background:#f4faf5}</style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c]">
<?php include __DIR__.'/../includes/header.php'; ?>
<main class="max-w-3xl mx-auto px-6 py-10">
  <a href="<?= isset($_SESSION['user_id'])?'../my-profile.php?section=support':'../index.php'?>" class="inline-flex items-center gap-2 text-sm text-[#17611f] font-semibold hover:underline mb-6">← Back</a>
  <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-8">
    <h1 class="text-2xl font-black mb-4">How Harvest-on-Demand Works</h1>
    <div class="text-sm text-[#5a7a5c] leading-relaxed space-y-3">
      <p>At Luntiang H.A.P.A.G., every lettuce stays growing in our hydroponic system until your order is confirmed. Unlike supermarkets where produce sits on shelves for days, ours is harvested only after you order — usually within 1-3 hours before delivery or pick-up.</p>
      <p><strong>Step 1:</strong> You place your order online. Browse our varieties, add to cart, and check out.</p>
      <p><strong>Step 2:</strong> Payment is confirmed. Your order enters our harvest queue.</p>
      <p><strong>Step 3:</strong> We harvest your lettuce — cut fresh, never pre-stored.</p>
      <p><strong>Step 4:</strong> Quality inspection — every leaf is checked before packing.</p>
      <p><strong>Step 5:</strong> Packed and ready — same-day delivery or pick-up.</p>
    </div>
  </div>
</main>
<?php include __DIR__.'/../includes/footer.php'; ?>
</body>
</html>
