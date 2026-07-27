<?php
session_start();
require 'config.php';
require_once __DIR__ . '/includes/form-helpers.php';
require_once __DIR__ . '/includes/notifications.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$messageType = "";
$showConfirmation = false;
$submittedData = null;

$reasons = [
    'Wrong item received',
    'Defective product',
    'Damaged during delivery',
    'Missing parts',
    'Other',
];
$conditions = ['Unopened', 'Opened', 'Used', 'Damaged'];

// Preserve form values
$formData = [
    'order_number' => '',
    'product_name' => '',
    'purchase_date' => '',
    'reason_category' => '',
    'reason' => '',
    'product_condition' => '',
    'proof_of_purchase' => null,
    'damage_photo' => null
];

// If the user is returning to the form (e.g. clicked "Edit Details" or the
// ✕ button on the confirmation modal) before confirming, repopulate the
// fields from what they already entered instead of showing a blank form.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_SESSION['pending_return'])) {
    $pending = $_SESSION['pending_return'];
    $formData['order_number'] = $pending['order_number'] ?? '';
    $formData['product_name'] = $pending['product_name'] ?? '';
    $formData['purchase_date'] = $pending['purchase_date'] ?? '';
    $formData['reason_category'] = $pending['reason_category'] ?? '';
    $formData['reason'] = $pending['reason'] ?? '';
    $formData['product_condition'] = $pending['product_condition'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check if this is a confirmation submission
    if (isset($_POST['confirm_submit']) && $_POST['confirm_submit'] === '1') {
        $submittedData = $_SESSION['pending_return'] ?? null;
        
        if ($submittedData) {
            $order_number = $submittedData['order_number'];
            $product_name = $submittedData['product_name'];
            $purchase_date = $submittedData['purchase_date'];
            $reason_category = $submittedData['reason_category'];
            $reason = $submittedData['reason'];
            $product_condition = $submittedData['product_condition'];
            $proof_path = $submittedData['proof_path'] ?? null;
            $damage_path = $submittedData['damage_path'] ?? null;

            try {
                $stmt = $conn->prepare("
                    INSERT INTO return_requests (user_id, order_number, product_name, purchase_date, reason_category, reason, product_condition, proof_of_purchase_path, damage_photo_path, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                ");

                $success = $stmt->execute([
                    $_SESSION['user_id'],
                    $order_number,
                    $product_name,
                    $purchase_date,
                    $reason_category,
                    $reason,
                    $product_condition,
                    $proof_path,
                    $damage_path
                ]);

                if ($success) {
                    $newRequestId = (int)$conn->lastInsertId();

                    $nameStmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
                    $nameStmt->execute([$_SESSION['user_id']]);
                    $nameRow = $nameStmt->fetch(PDO::FETCH_ASSOC);
                    $customerName = $nameRow ? trim($nameRow['first_name'] . ' ' . $nameRow['last_name']) : 'A customer';

                    createNotification(
                        $conn,
                        'return_new',
                        $newRequestId,
                        'New Return & Refund Request',
                        "Reason: " . ($reason_category !== '' ? $reason_category : $reason),
                        $customerName
                    );

                    unset($_SESSION['pending_return']);
                    header("Location: my-profile.php?return=1");
                    exit();
                } else {
                    $message = "Something went wrong submitting your request. Please try again.";
                    $messageType = "error";
                }
            } catch (PDOException $e) {
                $message = "Something went wrong submitting your request. Please try again.";
                $messageType = "error";
            }
        } else {
            $message = "Session expired. Please try submitting again.";
            $messageType = "error";
        }
        
        $showConfirmation = false;
        
    } else {
        // Initial form submission
        $order_number = trim($_POST['order_number'] ?? '');
        $product_name = trim($_POST['product_name'] ?? '');
        $purchase_date = trim($_POST['purchase_date'] ?? '');
        $reason_category = trim($_POST['reason_category'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $product_condition = trim($_POST['product_condition'] ?? '');

        // Store values for repopulation
        $formData['order_number'] = $order_number;
        $formData['product_name'] = $product_name;
        $formData['purchase_date'] = $purchase_date;
        $formData['reason_category'] = $reason_category;
        $formData['reason'] = $reason;
        $formData['product_condition'] = $product_condition;

        $isValidDate = false;
        if ($purchase_date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $purchase_date)) {
            [$py, $pm, $pd] = explode('-', $purchase_date);
            $isValidDate = checkdate((int)$pm, (int)$pd, (int)$py);
        }

        if (empty($order_number) || empty($product_name) || $purchase_date === '' || empty($reason_category) || empty($reason) || empty($product_condition)) {
            $message = "Please fill in the order number, product name, purchase date, reason, explanation, and product condition.";
            $messageType = "error";
        } elseif (!isValidOrderNumber($order_number)) {
            $message = "Format: LH-0000 (e.g. LH-0001)";
            $messageType = "error";
        } elseif (!in_array($reason_category, $reasons, true)) {
            $message = "Please choose a valid reason for return.";
            $messageType = "error";
        } elseif (!$isValidDate) {
            $message = "Please enter a valid purchase date.";
            $messageType = "error";
        } elseif ($purchase_date > date('Y-m-d')) {
            $message = "Purchase date cannot be in the future.";
            $messageType = "error";
        } elseif (!in_array($product_condition, $conditions, true)) {
            $message = "Please choose a valid product condition.";
            $messageType = "error";
        } else {
            // Handle file uploads
            $proofUpload = handleMultiFileUpload(
                'proof_of_purchase',
                ['jpg', 'jpeg', 'png', 'pdf'],
                __DIR__ . '/uploads/returns',
                'uploads/returns',
                true
            );

            if (!$proofUpload['ok']) {
                $message = $proofUpload['error'];
                $messageType = "error";
            } else {
                $damagePhotoUpload = handleMultiFileUpload(
                    'damage_photo',
                    ['jpg', 'jpeg', 'png'],
                    __DIR__ . '/uploads/returns',
                    'uploads/returns',
                    false
                );

                if (!$damagePhotoUpload['ok']) {
                    $message = $damagePhotoUpload['error'];
                    $messageType = "error";
                } else {
                    $_SESSION['pending_return'] = [
                        'order_number' => $order_number,
                        'product_name' => $product_name,
                        'purchase_date' => $purchase_date,
                        'reason_category' => $reason_category,
                        'reason' => $reason,
                        'product_condition' => $product_condition,
                        'proof_path' => encodeAttachmentPaths($proofUpload['paths']),
                        'damage_path' => encodeAttachmentPaths($damagePhotoUpload['paths']),
                        'proof_names' => $proofUpload['names'],
                        'damage_names' => $damagePhotoUpload['names']
                    ];
                    
                    $showConfirmation = true;
                    $submittedData = $_SESSION['pending_return'];
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Return &amp; Refund | Luntiang H.A.P.A.G.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Nunito', sans-serif; }
    .font-black { font-family: 'Nunito', serif; }
    
    .upload-progress {
      transition: width 0.3s ease;
    }
    
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(4px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1000;
      padding: 1rem;
      animation: fadeIn 0.3s ease;
    }
    
    .modal-content {
      max-width: 560px;
      width: 100%;
      max-height: 90vh;
      overflow-y: auto;
      animation: slideUp 0.3s ease;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    @keyframes slideUp {
      from { transform: translateY(20px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }
    
    .confirm-field {
      background: #f8f6f2;
      border-radius: 0.75rem;
      padding: 0.75rem 1rem;
    }
    .confirm-label {
      font-size: 0.7rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #9ca3af;
      display: block;
      margin-bottom: 0.25rem;
    }
    .confirm-value {
      color: #1f2937;
      font-size: 0.95rem;
      word-wrap: break-word;
    }
  </style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c] min-h-screen flex flex-col">

  <!-- Header -->
  <?php include __DIR__ . '/includes/header.php'; ?>

  <!-- Main Content -->
  <main class="flex-1 max-w-3xl w-full mx-auto px-6 py-16">
    <a href="<?= isset($_SESSION["user_id"]) ? "my-profile.php?section=support" : "index.php" ?>" class="inline-flex items-center gap-2 text-sm text-[#17611f] hover:text-[#14521a] transition-colors mb-8">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
      Back to Dashboard
    </a>
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-10">
      <span class="inline-block text-[11px] font-semibold tracking-wide text-[#17611f] bg-[#e8f5e9] rounded-full px-3 py-1 mb-5">QUICK SUPPORT</span>
      <h1 class="font-black text-3xl font-semibold text-[#1a2e1c] mb-4">Return &amp; Refund</h1>
      <div class="text-[#5a7a5c] text-[15px] leading-relaxed space-y-4">
        <p>Initiate a return or request a refund for items purchased within the last 30 days.</p>
      </div>

      <?php if ($message && !$showConfirmation): ?>
        <div class="mt-6 rounded-xl px-4 py-3 text-sm <?= $messageType === 'error' ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-green-50 text-green-700 border border-green-100' ?>">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>

      <!-- Confirmation Modal -->
      <?php if ($showConfirmation && $submittedData): ?>
        <div class="modal-overlay" id="confirmationModal">
          <div class="modal-content bg-white rounded-3xl shadow-2xl p-8">
            <div class="flex items-start justify-between mb-6">
              <div>
                <span class="inline-block text-[11px] font-semibold tracking-wide text-[#17611f] bg-[#e8f5e9] rounded-full px-3 py-1 mb-3">REVIEW &amp; CONFIRM</span>
                <h2 class="font-black text-2xl font-semibold text-[#1a2e1c]">Review Your Return Request</h2>
                <p class="text-[#5a7a5c] text-sm mt-1">Please verify all details before submitting.</p>
              </div>
              <a href="returns-refund.php" class="text-[#9e9e9e] hover:text-[#5a7a5c] transition-colors text-2xl leading-none">✕</a>
            </div>

            <div class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div class="confirm-field">
                  <span class="confirm-label">Order Number</span>
                  <p class="confirm-value"><?= htmlspecialchars($submittedData['order_number']) ?></p>
                </div>
                <div class="confirm-field">
                  <span class="confirm-label">Product Name</span>
                  <p class="confirm-value"><?= htmlspecialchars($submittedData['product_name']) ?></p>
                </div>
              </div>
              
              <div class="grid grid-cols-2 gap-4">
                <div class="confirm-field">
                  <span class="confirm-label">Purchase Date</span>
                  <p class="confirm-value"><?= date('M j, Y', strtotime($submittedData['purchase_date'])) ?></p>
                </div>
                <div class="confirm-field">
                  <span class="confirm-label">Condition</span>
                  <p class="confirm-value"><?= htmlspecialchars($submittedData['product_condition']) ?></p>
                </div>
              </div>

              <div class="confirm-field">
                <span class="confirm-label">Reason for Return</span>
                <p class="confirm-value"><?= htmlspecialchars($submittedData['reason_category']) ?></p>
              </div>

              <div class="confirm-field">
                <span class="confirm-label">Detailed Explanation</span>
                <p class="confirm-value whitespace-pre-line"><?= nl2br(htmlspecialchars($submittedData['reason'])) ?></p>
              </div>

              <?php if (!empty($submittedData['proof_names'])): ?>
                <div class="confirm-field">
                  <span class="confirm-label">Proof of Purchase</span>
                  <?php foreach ($submittedData['proof_names'] as $n): ?>
                    <p class="confirm-value text-[#17611f]">📎 <?= htmlspecialchars($n) ?></p>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($submittedData['damage_names'])): ?>
                <div class="confirm-field">
                  <span class="confirm-label">Damage Photo</span>
                  <?php foreach ($submittedData['damage_names'] as $n): ?>
                    <p class="confirm-value text-[#17611f]">📎 <?= htmlspecialchars($n) ?></p>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <div class="flex flex-wrap gap-3 mt-6 pt-6 border-t border-gray-100">
              <form method="POST" class="inline">
                <input type="hidden" name="confirm_submit" value="1">
                <button type="submit" class="px-6 py-3 rounded-full bg-[#17611f] text-white text-sm font-medium hover:bg-[#14521a] transition-colors">
                  ✅ Confirm &amp; Submit
                </button>
              </form>
              <a href="returns-refund.php" class="px-6 py-3 rounded-full border border-gray-300 text-[#1a2e1c] text-sm font-medium hover:bg-gray-50 transition-colors">
                ← Edit Details
              </a>
            </div>
            
            <p class="text-[12px] text-[#9e9e9e] mt-4">
              By submitting, you agree to our return policy. Our team will review your request within 1-2 business days.
            </p>
          </div>
        </div>
      <?php endif; ?>

      <form class="space-y-5 mt-6" method="POST" enctype="multipart/form-data" novalidate id="returnForm">
          <div>
            <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Order Number</label>
            <input type="text" name="order_number" required maxlength="7" pattern="LH-[A-Z0-9-]+" value="<?= htmlspecialchars($formData['order_number']) ?>" placeholder="LH-0000" class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
            <p class="mt-1.5 text-[12px] text-[#9e9e9e]">Format: LH-0000 (e.g. LH-0001)</p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Product Name</label>
            <input type="text" name="product_name" required value="<?= htmlspecialchars($formData['product_name']) ?>" placeholder="e.g. Romaine Lettuce" class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Purchase Date</label>
            <input type="date" name="purchase_date" required value="<?= htmlspecialchars($formData['purchase_date']) ?>" max="<?= date('Y-m-d') ?>" class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Reason for Return</label>
            <select name="reason_category" required class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors">
              <option value="" disabled <?= empty($formData['reason_category']) ? 'selected' : '' ?>>Select a reason</option>
              <?php foreach ($reasons as $r): ?>
                <option value="<?= htmlspecialchars($r) ?>" <?= ($formData['reason_category'] === $r) ? 'selected' : '' ?>><?= htmlspecialchars($r) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Detailed Explanation</label>
            <textarea rows="4" name="reason" required placeholder="Tell us why you'd like to return this item..." class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors"><?= htmlspecialchars($formData['reason']) ?></textarea>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Product Condition</label>
            <select name="product_condition" required class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors">
              <option value="" disabled <?= empty($formData['product_condition']) ? 'selected' : '' ?>>Select a condition</option>
              <?php foreach ($conditions as $c): ?>
                <option value="<?= $c ?>" <?= ($formData['product_condition'] === $c) ? 'selected' : '' ?>><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Proof of Purchase / Receipt</label>
            <div class="rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3">
              <div class="flex items-center gap-3 flex-wrap">
                <label for="proofInput" id="proofInputBtn" class="cursor-pointer inline-flex items-center px-4 py-1.5 rounded-full bg-[#f4faf5] text-[#17611f] text-sm font-medium hover:bg-[#e9e3d2] transition-colors whitespace-nowrap focus-within:ring-2 focus-within:ring-[#6B4226]/40">Upload Files</label>
                <input type="file" name="proof_of_purchase[]" required multiple accept=".jpg,.jpeg,.png,.pdf" id="proofInput" class="sr-only" />
                <span id="proofInputPlaceholder" class="text-sm text-[#9e9e9e]">No file chosen</span>
              </div>
              <div id="proofProgressContainer" class="hidden mt-3">
                <div class="flex items-center justify-between mb-1">
                  <span class="text-xs text-[#5a7a5c]" id="proofStatus">Uploading...</span>
                  <span class="text-xs text-[#5a7a5c]" id="proofPercentage">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                  <div id="proofProgressBar" class="upload-progress bg-[#17611f] h-2 rounded-full" style="width: 0%"></div>
                </div>
              </div>
              <div id="proofFileName" class="hidden mt-3 pt-3 border-t border-gray-100 text-xs text-[#5a7a5c] flex items-start gap-2">
                <span>📎</span>
                <span id="proofFileNameText" class="flex-1"></span>
                <button type="button" id="removeProofBtn" class="text-red-500 hover:text-red-700 text-sm shrink-0">✕</button>
              </div>
            </div>
            <p class="mt-1.5 text-[12px] text-[#9e9e9e]">JPG, JPEG, PNG, or PDF. You can attach multiple files — 5 MB total combined.</p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Damage Photo <span class="text-[#9e9e9e] font-normal">(optional)</span></label>
            <div class="rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3">
              <div class="flex items-center gap-3 flex-wrap">
                <label for="damageInput" id="damageInputBtn" class="cursor-pointer inline-flex items-center px-4 py-1.5 rounded-full bg-[#f4faf5] text-[#17611f] text-sm font-medium hover:bg-[#e9e3d2] transition-colors whitespace-nowrap focus-within:ring-2 focus-within:ring-[#6B4226]/40">Upload Files</label>
                <input type="file" name="damage_photo[]" multiple accept=".jpg,.jpeg,.png" id="damageInput" class="sr-only" />
                <span id="damageInputPlaceholder" class="text-sm text-[#9e9e9e]">No file chosen</span>
              </div>
              <div id="damageProgressContainer" class="hidden mt-3">
                <div class="flex items-center justify-between mb-1">
                  <span class="text-xs text-[#5a7a5c]" id="damageStatus">Uploading...</span>
                  <span class="text-xs text-[#5a7a5c]" id="damagePercentage">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                  <div id="damageProgressBar" class="upload-progress bg-[#17611f] h-2 rounded-full" style="width: 0%"></div>
                </div>
              </div>
              <div id="damageFileName" class="hidden mt-3 pt-3 border-t border-gray-100 text-xs text-[#5a7a5c] flex items-start gap-2">
                <span>📎</span>
                <span id="damageFileNameText" class="flex-1"></span>
                <button type="button" id="removeDamageBtn" class="text-red-500 hover:text-red-700 text-sm shrink-0">✕</button>
              </div>
            </div>
            <p class="mt-1.5 text-[12px] text-[#9e9e9e]">JPG, JPEG, or PNG. You can attach multiple files — 5 MB total combined.</p>
          </div>
          
          <button type="submit" id="submitBtn" class="px-6 py-3 rounded-full bg-[#17611f] text-white text-sm font-medium hover:bg-[#14521a] transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
            <svg id="submitSpinner" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span>Submit Return &amp; Refund Request</span>
          </button>
        </form>
    </div>
  </main>

  <!-- Footer -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script>
    // File upload handling with progress simulation and append mode
    function setupFileUpload(inputId, progressContainerId, progressBarId, statusId, percentageId, fileNameId, fileNameTextId, removeBtnId, isRequired = false, btnId, placeholderId) {
      const input = document.getElementById(inputId);
      const progressContainer = document.getElementById(progressContainerId);
      const progressBar = document.getElementById(progressBarId);
      const status = document.getElementById(statusId);
      const percentage = document.getElementById(percentageId);
      const fileName = document.getElementById(fileNameId);
      const fileNameText = document.getElementById(fileNameTextId);
      const removeBtn = document.getElementById(removeBtnId);
      const submitBtn = document.getElementById('submitBtn');
      const uploadBtn = btnId ? document.getElementById(btnId) : null;
      const placeholder = placeholderId ? document.getElementById(placeholderId) : null;

      const MAX_TOTAL_BYTES = 5 * 1024 * 1024;
      let isUploading = false;
      // Store previously selected files
      let selectedFiles = [];

      function totalSize(files) {
        return Array.from(files).reduce((sum, f) => sum + f.size, 0);
      }

      function renderSelection(files) {
        if (!files.length) {
          fileName.classList.add('hidden');
          fileNameText.textContent = '';
          if (uploadBtn) uploadBtn.textContent = 'Upload Files';
          if (placeholder) placeholder.classList.remove('hidden');
          return;
        }
        const names = Array.from(files).map((f) => f.name).join(', ');
        const totalMb = (totalSize(files) / 1024 / 1024).toFixed(2);
        fileNameText.textContent = files.length > 1
          ? `${files.length} files (${totalMb} MB total): ${names}`
          : `${names} (${totalMb} MB)`;
        fileName.classList.remove('hidden');
        if (uploadBtn) uploadBtn.textContent = 'Upload Again';
        if (placeholder) placeholder.classList.add('hidden');
      }

      function updateFileInput() {
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => dataTransfer.items.add(file));
        input.files = dataTransfer.files;
        renderSelection(selectedFiles);
        
        if (selectedFiles.length && totalSize(selectedFiles) > MAX_TOTAL_BYTES) {
          alert('Your attached files total more than 5MB combined. Please remove some files.');
          selectedFiles.pop();
          updateFileInput();
        }
      }

      if (input) {
        input.addEventListener('change', function(e) {
          const newFiles = this.files;

          if (newFiles && newFiles.length) {
            // Append new files to existing selection
            for (let i = 0; i < newFiles.length; i++) {
              selectedFiles.push(newFiles[i]);
            }

            // Reset the native input now (before rebuilding it below) so
            // selecting the same file again later still fires 'change'.
            // Doing this AFTER updateFileInput() would wipe out the
            // file list we just assigned.
            this.value = '';

            if (totalSize(selectedFiles) > MAX_TOTAL_BYTES) {
              alert('Your attached files total more than 5MB combined. Please choose fewer or smaller files.');
              for (let i = 0; i < newFiles.length; i++) {
                selectedFiles.pop();
              }
              updateFileInput();
              return;
            }

            updateFileInput();

            isUploading = true;
            progressContainer.classList.remove('hidden');
            let progress = 0;
            const interval = setNunitoval(() => {
              progress += Math.random() * 15 + 5;
              if (progress >= 100) {
                progress = 100;
                clearNunitoval(interval);
                isUploading = false;
                status.textContent = '✓ Ready';
                status.className = 'text-xs text-green-600';
                if (submitBtn) submitBtn.disabled = false;
              }
              progressBar.style.width = progress + '%';
              percentage.textContent = Math.round(progress) + '%';
            }, 150);
            
            if (submitBtn) {
              submitBtn.disabled = true;
              status.textContent = 'Uploading...';
              status.className = 'text-xs text-[#5a7a5c]';
            }

          } else {
            renderSelection(selectedFiles);
          }
        });

        if (removeBtn) {
          removeBtn.addEventListener('click', function() {
            if (selectedFiles.length > 0) {
              selectedFiles.pop();
              updateFileInput();
              if (selectedFiles.length === 0) {
                progressContainer.classList.add('hidden');
                progressBar.style.width = '0%';
                percentage.textContent = '0%';
                if (submitBtn && !isRequired) submitBtn.disabled = false;
              }
            }
          });
        }
      }

      return { isUploading: () => isUploading };
    }

    // Setup file uploads
    const proofUpload = setupFileUpload('proofInput', 'proofProgressContainer', 'proofProgressBar', 'proofStatus', 'proofPercentage', 'proofFileName', 'proofFileNameText', 'removeProofBtn', true, 'proofInputBtn', 'proofInputPlaceholder');
    const damageUpload = setupFileUpload('damageInput', 'damageProgressContainer', 'damageProgressBar', 'damageStatus', 'damagePercentage', 'damageFileName', 'damageFileNameText', 'removeDamageBtn', false, 'damageInputBtn', 'damageInputPlaceholder');

    // Form submission
    const form = document.getElementById('returnForm');
    const submitBtn = document.getElementById('submitBtn');
    const submitSpinner = document.getElementById('submitSpinner');

    if (form) {
      form.addEventListener('submit', function(e) {
        if (proofUpload.isUploading() || damageUpload.isUploading()) {
          e.preventDefault();
          alert('Please wait for all file uploads to complete.');
          return;
        }
        submitBtn.disabled = true;
        submitSpinner.classList.remove('hidden');
      });
    }
  </script>

</body>
</html>