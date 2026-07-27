<?php
session_start();
require 'config.php';

$faqs = [];
$categories = [];
try {
    $faqs = $conn->query("SELECT * FROM faqs ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    // Collect unique categories
    foreach ($faqs as $f) {
        $cat = $f['category'] ?? 'General';
        $slug = strtolower($cat);
        if (!isset($categories[$slug])) $categories[$slug] = $cat;
    }
    // Fallback if empty
    if (empty($faqs)) {
        $categories = ['general' => 'General'];
    }
} catch (Exception $e) { $faqs = []; $categories = ['general' => 'General']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FAQ | Luntiang H.A.P.A.G.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    body{font-family:'Nunito',sans-serif}
    details summary::-webkit-details-marker{display:none}
    details summary{list-style:none}
  </style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c] min-h-screen flex flex-col">

<?php include __DIR__ . '/includes/header.php'; ?>

<section class="bg-[#17611f] text-white py-14">
  <div class="max-w-4xl mx-auto px-6">
    <h1 class="text-2xl sm:text-3xl font-black">Frequently Asked Questions</h1>
    <p class="text-[#c8e6c9] mt-2 text-sm">Find answers about our hydroponic lettuce, orders, delivery, and more</p>
  </div>
</section>

<main class="flex-1 max-w-3xl w-full mx-auto px-6 py-10">
  <div class="bg-white rounded-2xl border border-[rgba(27,94,32,0.08)] overflow-hidden">
    <div class="px-6 pt-5 pb-2">
      <div class="relative mb-4">
        <svg class="w-4 h-4 text-[#9e9e9e] absolute left-4 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
        <input id="faqSearch" type="text" placeholder="Search FAQs..." class="w-full rounded-xl bg-[#f4faf5] border border-[rgba(27,94,32,0.12)] pl-11 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" />
      </div>
      <div id="faqPills" class="flex flex-wrap gap-2 pb-4 border-b border-[rgba(27,94,32,0.08)]">
        <button data-cat="all" class="faq-pill text-sm font-bold rounded-full px-4 py-2 bg-[#17611f] text-white transition-colors">All</button>
        <?php foreach ($categories as $slug => $label): ?>
          <button data-cat="<?= htmlspecialchars($slug) ?>" class="faq-pill text-sm font-bold rounded-full px-4 py-2 border border-[rgba(27,94,32,0.12)] text-[#5a7a5c] hover:bg-[#e8f5e9] transition-colors"><?= htmlspecialchars($label) ?></button>
        <?php endforeach; ?>
      </div>
    </div>

    <div id="faqList" class="px-6 pb-6">
      <?php if (empty($faqs)): ?>
        <p class="text-sm text-[#9e9e9e] text-center py-10">No FAQs available.</p>
      <?php else: ?>
        <?php foreach ($faqs as $i => $f): 
            $catSlug = strtolower($f['category'] ?? 'general');
        ?>
          <details data-cat="<?= htmlspecialchars($catSlug) ?>" class="group faq-item <?= $i < count($faqs)-1 ? 'border-b border-[rgba(27,94,32,0.08)]' : '' ?> py-4">
            <summary class="flex items-center justify-between cursor-pointer">
              <span class="font-bold text-sm text-[#1a2e1c]"><?= htmlspecialchars($f['question']) ?></span>
              <svg class="w-4 h-4 text-[#9e9e9e] flex-shrink-0 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </summary>
            <p class="mt-3 text-sm text-[#5a7a5c] leading-relaxed"><?= nl2br(htmlspecialchars($f['answer'])) ?></p>
          </details>
        <?php endforeach; ?>
      <?php endif; ?>
      <p id="faqEmpty" class="hidden text-sm text-[#9e9e9e] text-center py-10">No questions match your search. Try a different keyword or category.</p>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
const pills=document.querySelectorAll('.faq-pill'),items=document.querySelectorAll('.faq-item'),search=document.getElementById('faqSearch'),empty=document.getElementById('faqEmpty');let activeCat='all';
function applyFilters(){const t=search.value.trim().toLowerCase();let v=0;items.forEach(i=>{const m=activeCat==='all'||i.dataset.cat===activeCat,s=t===''||i.textContent.toLowerCase().includes(t),sh=m&&s;i.style.display=sh?'':'none';if(sh)v++});empty.classList.toggle('hidden',v!==0)}
pills.forEach(p=>{p.addEventListener('click',()=>{pills.forEach(x=>{x.classList.remove('bg-[#17611f]','text-white');x.classList.add('border','border-[rgba(27,94,32,0.12)]','text-[#5a7a5c]')});p.classList.add('bg-[#17611f]','text-white');p.classList.remove('border','border-[rgba(27,94,32,0.12)]','text-[#5a7a5c]');activeCat=p.dataset.cat;applyFilters()})});
search.addEventListener('input',applyFilters);
</script>
</body>
</html>
