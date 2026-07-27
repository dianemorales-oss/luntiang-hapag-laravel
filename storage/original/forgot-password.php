<?php
session_start();
require 'config.php';

$message = "";
$messageType = "";
$devPreview = null; // holds email preview data when a reset link is generated

// Shown when a reset link was actually generated for a matching account.
$foundSuccessMessage = "If an account exists for the email address you entered, a password reset link has been generated.";
// Shown when no account matches the entered email.
$notFoundMessage = "We couldn't find an account registered with that email address.";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {

        $message = "Please enter your email address.";
        $messageType = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "error";

    } else {

        try {

            $stmt = $conn->prepare("SELECT id, first_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {

                // Cryptographically secure token; only its SHA-256 hash
                // is stored, the same way the raw password is never
                // stored — the plain token only ever lives in the
                // reset link/email itself.
                $rawToken = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $rawToken);
                $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

                $update = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
                $update->execute([$tokenHash, $expiresAt, $user['id']]);

                // ---------------------------------------------------
                // Development Email Preview
                // ---------------------------------------------------
                // No SMTP/email service is configured in this
                // environment yet. In production, this is the exact
                // point where the reset email would be sent (e.g. via
                // PHPMailer) using this same $rawToken — nothing else
                // in the workflow above or below would need to change.
                // ---------------------------------------------------
                $resetUrl = "reset-password.php?token=" . urlencode($rawToken);
                $devPreview = [
                    'to' => $email,
                    'subject' => 'Reset Your Luntiang H.A.P.A.G. Password',
                    'reset_url' => $resetUrl,
                ];

                $message = $foundSuccessMessage;
                $messageType = "success";

            } else {

                $message = $notFoundMessage;
                $messageType = "error";

            }

        } catch (PDOException $e) {

            $message = "Something went wrong processing your request. Please try again.";
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
  <title>Forgot Password | Luntiang H.A.P.A.G.</title>
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
    <a href="login.php" class="inline-flex items-center gap-2 text-sm text-[#17611f] hover:text-[#14521a] transition-colors mb-8">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
      Back to Login
    </a>
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-10">
      <span class="inline-block text-[11px] font-semibold tracking-wide text-[#17611f] bg-[#e8f5e9] rounded-full px-3 py-1 mb-5">ACCOUNT</span>
      <h1 class="font-black text-3xl font-semibold text-[#1a2e1c] mb-4">Forgot Your Password?</h1>
      <div class="text-[#5a7a5c] text-[15px] leading-relaxed space-y-4">
        <p>Enter the email address associated with your account and we'll generate a password reset link.</p>
      </div>

      <?php if (!empty($message)): ?>
        <div id="alertMessage"
             class="mt-6 flex items-start gap-3 rounded-2xl border px-5 py-4 shadow-sm transition-all duration-500
             <?= $messageType === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'; ?>">
          <div class="mt-0.5">
            <?php if ($messageType === 'error'): ?>
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
              </svg>
            <?php else: ?>
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            <?php endif; ?>
          </div>
          <div class="flex-1">
            <h3 class="font-semibold"><?= $messageType === 'error' ? ($message === $notFoundMessage ? 'No Account Found' : 'Something Went Wrong') : 'Request Received' ?></h3>
            <p class="text-sm mt-1"><?= htmlspecialchars($message); ?></p>
          </div>
          <button type="button" onclick="closeAlert()" class="text-[#9e9e9e] hover:text-[#1a2e1c] transition">✕</button>
        </div>
        <script>
          function closeAlert(){
            const alert = document.getElementById("alertMessage");
            if(alert){
              alert.classList.add("opacity-0","translate-y-2");
              setTimeout(()=>{ alert.remove(); },400);
            }
          }
        </script>
      <?php endif; ?>

      <?php if ($devPreview): ?>
        <!-- ================================================= -->
        <!-- Development Email Preview                          -->
        <!-- SMTP/email delivery is not configured in this dev  -->
        <!-- environment, so the reset email is shown here      -->
        <!-- instead of actually being sent. Swapping in a real -->
        <!-- mailer later means replacing this block only.      -->
        <!-- ================================================= -->
        <div class="mt-8 rounded-2xl border border-[rgba(27,94,32,0.12)] overflow-hidden">
          <div class="bg-gray-50 border-b border-[rgba(27,94,32,0.12)] px-5 py-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-[#9e9e9e]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <p class="text-[12px] font-semibold tracking-wide text-[#5a7a5c] uppercase">Development Email Preview</p>
          </div>

          <div class="px-6 py-5 bg-white">
            <div class="grid grid-cols-[70px_1fr] gap-y-1 text-[13px] mb-4 pb-4 border-b border-gray-100">
              <span class="text-[#9e9e9e]">To:</span>
              <span class="text-[#1a2e1c] font-medium"><?= htmlspecialchars($devPreview['to']) ?></span>
              <span class="text-[#9e9e9e]">Subject:</span>
              <span class="text-[#1a2e1c] font-medium"><?= htmlspecialchars($devPreview['subject']) ?></span>
            </div>

            <div class="text-[14px] text-[#1a2e1c] leading-relaxed space-y-4">
              <p>Hello,</p>
              <p>We received a request to reset the password for your Luntiang H.A.P.A.G. account.</p>
              <p>Click the button below to reset your password.</p>
              <p class="py-2">
                <a href="<?= htmlspecialchars($devPreview['reset_url']) ?>" class="inline-block rounded-full bg-[#17611f] text-white text-sm font-medium px-6 py-3 hover:bg-[#14521a] transition-colors">Reset Password</a>
              </p>
              <p>This password reset link will expire in 30 minutes.</p>
              <p>If you did not request a password reset, you can safely ignore this message.</p>
              <p>Luntiang H.A.P.A.G. Customer Support</p>
            </div>
          </div>

          <div class="bg-[#e8f5e9] border-t border-[#c8e6c9] px-6 py-3">
            <p class="text-[12px] text-[#17611f] leading-relaxed">This email is being displayed because SMTP/email delivery is not yet configured for this development environment.</p>
          </div>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-5 mt-6" id="forgotPasswordForm">
          <div>
            <label for="email" class="block text-sm font-medium text-[#1a2e1c] mb-2">Email Address</label>
            <input type="email" id="email" name="email" placeholder="you@email.com" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
          </div>
          <button type="submit" id="forgotPasswordSubmit" class="w-full rounded-full bg-[#17611f] text-white text-sm font-medium py-3.5 hover:bg-[#14521a] transition-colors flex items-center justify-center gap-2">
            <svg id="forgotPasswordSpinner" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span>Send Reset Link</span>
          </button>
        </form>
        <p class="text-sm text-[#5a7a5c] mt-5 text-center">Remembered your password? <a href="login.php" class="text-[#17611f] hover:text-[#14521a] font-medium transition-colors">Back to Login</a></p>
    </div>
  </main>

  <!-- Footer -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script>
    const forgotForm = document.getElementById('forgotPasswordForm');
    if (forgotForm) {
      forgotForm.addEventListener('submit', () => {
        const btn = document.getElementById('forgotPasswordSubmit');
        const spinner = document.getElementById('forgotPasswordSpinner');
        btn.disabled = true;
        spinner.classList.remove('hidden');
      });
    }
  </script>

</body>
</html>