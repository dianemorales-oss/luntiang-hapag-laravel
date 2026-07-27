<?php
session_start();
require '../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hydroponic Growing Process | Luntiang H.A.P.A.G.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>body{font-family:'Nunito',sans-serif;background:#f4faf5}</style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c]">
<?php include __DIR__.'/../includes/header.php'; ?>
<main class="max-w-3xl mx-auto px-6 py-10">
  <a href="<?= isset($_SESSION['user_id'])?'../my-profile.php?section=support':'../index.php'?>" class="inline-flex items-center gap-2 text-sm text-[#17611f] font-semibold hover:underline mb-6">← Back</a>
  <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-8">
    <h1 class="text-2xl font-black mb-4">Hydroponic Growing Process</h1>
    <div class="text-sm text-[#5a7a5c] leading-relaxed space-y-3">
      <p>Hydroponics is a method of growing plants without soil, using nutrient-rich water instead. Our lettuce is grown in controlled systems with no pesticides or chemicals.</p>
      <p><strong>Why Hydroponics?</strong> It uses 90% less water than traditional farming, produces cleaner crops, and allows year-round growing regardless of weather.</p>
      <p><strong>Our Process:</strong> Premium seeds → Nursery seedlings → Nutrient-rich water systems → Daily harvest at peak freshness → Same-day delivery to your doorstep.</p>
      <p>This means you get consistently crisp, clean, and nutritious lettuce — chemical-free and harvested on demand.</p>
    </div>
  </div>
</main>
<?php include __DIR__.'/../includes/footer.php'; ?>
</body>
</html>
