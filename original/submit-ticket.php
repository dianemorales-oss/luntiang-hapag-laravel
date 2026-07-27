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

$categories = [
    'Order Issue',
    'Product Defect',
    'Delivery Issue',
    'Payment Issue',
    'Account Issue',
    'Website / Technical Issue',
    'Other',
];
$priorities = ['Low', 'Medium', 'High'];

// Preserve form values
$formData = [
    'subject' => '',
    'category' => '',
    'priority' => 'Medium',
    'order_number' => '',
    'issue_description' => '',
    'attachment' => null
];

// If the user is returning to the form (e.g. clicked "Edit Details" or the
// ✕ button on the confirmation modal) before confirming, repopulate the
// fields from what they already entered instead of showing a blank form.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_SESSION['pending_ticket'])) {
    $pending = $_SESSION['pending_ticket'];
    $formData['subject'] = $pending['subject'] ?? '';
    $formData['category'] = $pending['category'] ?? '';
    $formData['priority'] = $pending['priority'] ?? 'Medium';
    $formData['order_number'] = $pending['order_number'] ?? '';
    $formData['issue_description'] = $pending['issue_description'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check if this is a confirmation submission
    if (isset($_POST['confirm_submit']) && $_POST['confirm_submit'] === '1') {
        // Retrieve stored data from session
        $submittedData = $_SESSION['pending_ticket'] ?? null;
        
        if ($submittedData) {
            // Process the actual submission
            $subject = $submittedData['subject'];
            $category = $submittedData['category'];
            $priority = $submittedData['priority'];
            $order_number = $submittedData['order_number'];
            $issue_description = $submittedData['issue_description'];
            $attachment_path = $submittedData['attachment_path'] ?? null;

            try {
                $stmt = $conn->prepare("
                    INSERT INTO tickets (user_id, subject, category, priority, order_number, issue_description, attachment_path, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'open')
                ");

                $success = $stmt->execute([
                    $_SESSION['user_id'],
                    $subject,
                    $category,
                    $priority,
                    $order_number !== '' ? $order_number : null,
                    $issue_description,
                    $attachment_path
                ]);

                if ($success) {
                    $newTicketId = (int)$conn->lastInsertId();

                    $nameStmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
                    $nameStmt->execute([$_SESSION['user_id']]);
                    $nameRow = $nameStmt->fetch(PDO::FETCH_ASSOC);
                    $customerName = $nameRow ? trim($nameRow['first_name'] . ' ' . $nameRow['last_name']) : 'A customer';

                    createNotification(
                        $conn,
                        'ticket_new',
                        $newTicketId,
                        'New Support Ticket',
                        $subject . "\n\nSubmitted by: " . $customerName,
                        $customerName
                    );

                    // Clear the session data
                    unset($_SESSION['pending_ticket']);
                    
                    header("Location: my-profile.php?ticket=1&ticket_id=" . $newTicketId);
                    exit();
                } else {
                    $message = "Something went wrong submitting your ticket. Please try again.";
                    $messageType = "error";
                }
            } catch (PDOException $e) {
                $message = "Something went wrong submitting your ticket. Please try again.";
                $messageType = "error";
            }
        } else {
            $message = "Session expired. Please try submitting again.";
            $messageType = "error";
        }
        
        // Clear the confirmation flag
        unset($_SESSION['pending_ticket']);
        $showConfirmation = false;
        
    } else {
        // Initial form submission - validate and show confirmation
        $subject = trim($_POST['subject'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $priority = trim($_POST['priority'] ?? '');
        $order_number = trim($_POST['order_number'] ?? '');
        $issue_description = trim($_POST['issue_description'] ?? '');

        // Store values for repopulation
        $formData['subject'] = $subject;
        $formData['category'] = $category;
        $formData['priority'] = $priority;
        $formData['order_number'] = $order_number;
        $formData['issue_description'] = $issue_description;

        if (empty($subject) || empty($issue_description) || empty($category) || empty($priority)) {
            $message = "Please fill in the subject, category, priority, and description before submitting.";
            $messageType = "error";
        } elseif (!in_array($category, $categories, true)) {
            $message = "Please choose a valid category.";
            $messageType = "error";
        } elseif (!in_array($priority, $priorities, true)) {
            $message = "Please choose a valid priority.";
            $messageType = "error";
        } elseif (mb_strlen($issue_description) > 1000) {
            $message = "Your issue description must be 1000 characters or fewer.";
            $messageType = "error";
        } elseif ($order_number !== '' && !isValidOrderNumber($order_number)) {
            $message = ORDER_NUMBER_HELP_TEXT;
            $messageType = "error";
        } else {
            // Handle file upload(s)
            $upload = handleMultiFileUpload(
                'attachment',
                ['jpg', 'jpeg', 'png', 'pdf'],
                __DIR__ . '/uploads/tickets',
                'uploads/tickets',
                false
            );

            if (!$upload['ok']) {
                $message = $upload['error'];
                $messageType = "error";
            } else {
                // Store data in session for confirmation
                $_SESSION['pending_ticket'] = [
                    'subject' => $subject,
                    'category' => $category,
                    'priority' => $priority,
                    'order_number' => $order_number,
                    'issue_description' => $issue_description,
                    'attachment_path' => encodeAttachmentPaths($upload['paths']),
                    'attachment_names' => $upload['names']
                ];
                
                $showConfirmation = true;
                $submittedData = $_SESSION['pending_ticket'];
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
  <title>Submit a Ticket | Luntiang H.A.P.A.G.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Nunito', sans-serif; }
    .font-black { font-family: 'Nunito', serif; }
    
    /* Progress bar animation */
    .upload-progress {
      transition: width 0.3s ease;
    }
    
    /* Confirmation modal overlay */
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
    
    /* Field highlighting for confirmation */
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
      <h1 class="font-black text-3xl font-semibold text-[#1a2e1c] mb-4">Submit a Ticket</h1>
      <div class="text-[#5a7a5c] text-[15px] leading-relaxed space-y-4">
        <p>Report an issue with your order or product and our team will follow up with you shortly.</p>
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
                <h2 class="font-black text-2xl font-semibold text-[#1a2e1c]">Review Your Ticket</h2>
                <p class="text-[#5a7a5c] text-sm mt-1">Please verify all details before submitting.</p>
              </div>
              <a href="submit-ticket.php" class="text-[#9e9e9e] hover:text-[#5a7a5c] transition-colors text-2xl leading-none">
                ✕
              </a>
            </div>

            <div class="space-y-4">
              <div class="confirm-field">
                <span class="confirm-label">Subject</span>
                <p class="confirm-value"><?= htmlspecialchars($submittedData['subject']) ?></p>
              </div>
              
              <div class="grid grid-cols-2 gap-4">
                <div class="confirm-field">
                  <span class="confirm-label">Category</span>
                  <p class="confirm-value"><?= htmlspecialchars($submittedData['category']) ?></p>
                </div>
                <div class="confirm-field">
                  <span class="confirm-label">Priority</span>
                  <p class="confirm-value">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                      <?= $submittedData['priority'] === 'High' ? 'bg-red-100 text-red-700' : 
                          ($submittedData['priority'] === 'Medium' ? 'bg-amber-100 text-[#e65100]' : 
                          'bg-green-100 text-green-700') ?>">
                      <?= htmlspecialchars($submittedData['priority']) ?>
                    </span>
                  </p>
                </div>
              </div>

              <?php if (!empty($submittedData['order_number'])): ?>
                <div class="confirm-field">
                  <span class="confirm-label">Order Number</span>
                  <p class="confirm-value"><?= htmlspecialchars($submittedData['order_number']) ?></p>
                </div>
              <?php endif; ?>

              <div class="confirm-field">
                <span class="confirm-label">Issue Description</span>
                <p class="confirm-value whitespace-pre-line"><?= nl2br(htmlspecialchars($submittedData['issue_description'])) ?></p>
              </div>

              <?php if (!empty($submittedData['attachment_names'])): ?>
                <div class="confirm-field">
                  <span class="confirm-label">Attachment<?= count($submittedData['attachment_names']) > 1 ? 's' : '' ?></span>
                  <?php foreach ($submittedData['attachment_names'] as $attName): ?>
                    <p class="confirm-value text-[#17611f]">📎 <?= htmlspecialchars($attName) ?></p>
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
              <a href="submit-ticket.php" class="px-6 py-3 rounded-full border border-gray-300 text-[#1a2e1c] text-sm font-medium hover:bg-gray-50 transition-colors">
                ← Edit Details
              </a>
            </div>
            
            <p class="text-[12px] text-[#9e9e9e] mt-4">
              By submitting, you agree to our terms of service. Our team will respond within 24-48 hours.
            </p>
          </div>
        </div>
      <?php endif; ?>

      <form class="space-y-5 mt-6" method="POST" enctype="multipart/form-data" novalidate id="ticketForm">
          <div>
            <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Subject</label>
            <input type="text" name="subject" required placeholder="Brief summary of your issue" 
                   value="<?= htmlspecialchars($formData['subject']) ?>"
                   class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Category</label>
            <select name="category" required class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors">
              <option value="" disabled <?= empty($formData['category']) ? 'selected' : '' ?>>Select a category</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" <?= ($formData['category'] === $cat) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Priority</label>
            <select name="priority" required class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors">
              <?php foreach ($priorities as $p): ?>
                <option value="<?= $p ?>" <?= ($formData['priority'] === $p) ? 'selected' : '' ?>>
                  <?= $p ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Order Number <span class="text-[#9e9e9e] font-normal">(optional)</span></label>
            <input type="text" name="order_number" maxlength="7" pattern="LH-\d{4}" placeholder="<?= ORDER_NUMBER_PLACEHOLDER ?>" 
                   value="<?= htmlspecialchars($formData['order_number']) ?>"
                   class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
            <p class="mt-1.5 text-[12px] text-[#9e9e9e]">Format: <?= ORDER_NUMBER_PLACEHOLDER ?></p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Describe the Issue</label>
            <textarea id="issue_description" rows="4" name="issue_description" required maxlength="1000" 
                      placeholder="Describe your issue with your order or product..." 
                      class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors"><?= htmlspecialchars($formData['issue_description']) ?></textarea>
            <p class="mt-1.5 text-[12px] text-[#9e9e9e] text-right">
              <span id="issue_description_count"><?= mb_strlen($formData['issue_description']) ?></span> / 1000 characters
            </p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Attachment <span class="text-[#9e9e9e] font-normal">(optional)</span></label>
            <div class="rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3">
              <div class="flex items-center gap-3 flex-wrap">
                <label for="fileInput" id="fileInputBtn" class="cursor-pointer inline-flex items-center px-4 py-1.5 rounded-full bg-[#f4faf5] text-[#17611f] text-sm font-medium hover:bg-[#e9e3d2] transition-colors whitespace-nowrap focus-within:ring-2 focus-within:ring-[#6B4226]/40">Upload Files</label>
                <input type="file" name="attachment[]" accept=".jpg,.jpeg,.png,.pdf" multiple id="fileInput" class="sr-only" />
                <span id="fileInputPlaceholder" class="text-sm text-[#9e9e9e]">No file chosen</span>
              </div>
              <!-- Progress Bar -->
              <div id="uploadProgressContainer" class="hidden mt-3">
                <div class="flex items-center justify-between mb-1">
                  <span class="text-xs text-[#5a7a5c]" id="uploadStatus">Uploading...</span>
                  <span class="text-xs text-[#5a7a5c]" id="uploadPercentage">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                  <div id="uploadProgressBar" class="upload-progress bg-[#17611f] h-2 rounded-full" style="width: 0%"></div>
                </div>
              </div>
              <!-- File name display -->
              <div id="fileNameDisplay" class="hidden mt-3 pt-3 border-t border-gray-100 text-xs text-[#5a7a5c] flex items-start gap-2">
                <span>📎</span>
                <span id="fileNameText" class="flex-1"></span>
                <button type="button" id="removeFileBtn" class="text-red-500 hover:text-red-700 text-sm shrink-0">✕</button>
              </div>
            </div>
            <p class="mt-1.5 text-[12px] text-[#9e9e9e]">JPG, JPEG, PNG, or PDF. You can attach multiple files — 5 MB total combined.</p>
          </div>

          <p class="text-[13px] text-[#5a7a5c] bg-[#f4faf5] rounded-xl px-4 py-3">
            Our support team typically responds within 24–48 business hours. You can track updates through My Support Tickets.
          </p>

          <button type="submit" id="submitBtn" class="px-6 py-3 rounded-full bg-[#17611f] text-white text-sm font-medium hover:bg-[#14521a] transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
            <svg id="submitSpinner" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span>Submit Support Ticket</span>
          </button>
        </form>
    </div>
  </main>

  <!-- Footer -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script>
    // Live character counter for the Describe the Issue field.
    (function () {
      const textarea = document.getElementById('issue_description');
      const counter = document.getElementById('issue_description_count');
      if (!textarea || !counter) return;
      textarea.addEventListener('input', function () {
        counter.textContent = textarea.value.length;
      });
    })();

    // File upload handling with progress simulation and append mode
    (function () {
      const fileInput = document.getElementById('fileInput');
      const progressContainer = document.getElementById('uploadProgressContainer');
      const progressBar = document.getElementById('uploadProgressBar');
      const uploadStatus = document.getElementById('uploadStatus');
      const uploadPercentage = document.getElementById('uploadPercentage');
      const fileNameDisplay = document.getElementById('fileNameDisplay');
      const fileNameText = document.getElementById('fileNameText');
      const removeFileBtn = document.getElementById('removeFileBtn');
      const fileInputBtn = document.getElementById('fileInputBtn');
      const fileInputPlaceholder = document.getElementById('fileInputPlaceholder');
      const submitBtn = document.getElementById('submitBtn');
      const submitSpinner = document.getElementById('submitSpinner');

      const MAX_TOTAL_BYTES = 5 * 1024 * 1024;
      let isUploading = false;
      // Store previously selected files
      let selectedFiles = [];

      function totalSize(files) {
        return Array.from(files).reduce((sum, f) => sum + f.size, 0);
      }

      function renderSelection(files) {
        if (!files.length) {
          fileNameDisplay.classList.add('hidden');
          fileNameText.textContent = '';
          if (fileInputBtn) fileInputBtn.textContent = 'Upload Files';
          if (fileInputPlaceholder) fileInputPlaceholder.classList.remove('hidden');
          return;
        }
        const names = Array.from(files).map((f) => f.name).join(', ');
        const totalMb = (totalSize(files) / 1024 / 1024).toFixed(2);
        fileNameText.textContent = files.length > 1
          ? `${files.length} files (${totalMb} MB total): ${names}`
          : `${names} (${totalMb} MB)`;
        fileNameDisplay.classList.remove('hidden');
        if (fileInputBtn) fileInputBtn.textContent = 'Upload Again';
        if (fileInputPlaceholder) fileInputPlaceholder.classList.add('hidden');
      }

      // Helper to update the file input with current selection
      function updateFileInput() {
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;
        renderSelection(selectedFiles);
        
        // Re-check total size
        if (selectedFiles.length && totalSize(selectedFiles) > MAX_TOTAL_BYTES) {
          alert('Your attached files total more than 5MB combined. Please remove some files.');
          // Remove the last added file if over limit
          selectedFiles.pop();
          updateFileInput();
        }
      }

      if (fileInput) {
        fileInput.addEventListener('change', function () {
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

            // Check total size
            if (totalSize(selectedFiles) > MAX_TOTAL_BYTES) {
              alert('Your attached files total more than 5MB combined. Please choose fewer or smaller files.');
              // Remove the newly added files
              for (let i = 0; i < newFiles.length; i++) {
                selectedFiles.pop();
              }
              updateFileInput();
              return;
            }

            updateFileInput();

            // Simulate upload progress for the new files
            isUploading = true;
            progressContainer.classList.remove('hidden');
            let progress = 0;
            const interval = setNunitoval(() => {
              progress += Math.random() * 15 + 5;
              if (progress >= 100) {
                progress = 100;
                clearNunitoval(interval);
                isUploading = false;
                uploadStatus.textContent = '✓ Ready';
                uploadStatus.className = 'text-xs text-green-600';
                submitBtn.disabled = false;
              }
              progressBar.style.width = progress + '%';
              uploadPercentage.textContent = Math.round(progress) + '%';
            }, 150);

            submitBtn.disabled = true;
            uploadStatus.textContent = 'Uploading...';
            uploadStatus.className = 'text-xs text-[#5a7a5c]';

          } else {
            // If no files selected (user cancelled), don't change anything
            renderSelection(selectedFiles);
          }
        });

        // Remove a specific file (the last one added)
        if (removeFileBtn) {
          removeFileBtn.addEventListener('click', function() {
            if (selectedFiles.length > 0) {
              selectedFiles.pop(); // Remove the last added file
              updateFileInput();
              if (selectedFiles.length === 0) {
                progressContainer.classList.add('hidden');
                progressBar.style.width = '0%';
                uploadPercentage.textContent = '0%';
                submitBtn.disabled = false;
              }
            }
          });
        }
      }

      // Form submission spinner
      const form = document.getElementById('ticketForm');
      if (form) {
        form.addEventListener('submit', function(e) {
          if (isUploading) {
            e.preventDefault();
            alert('Please wait for the file upload to complete.');
            return;
          }
          submitBtn.disabled = true;
          submitSpinner.classList.remove('hidden');
        });
      }
    })();
  </script>

</body>
</html>