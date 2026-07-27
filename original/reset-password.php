<?php
session_start();
require 'config.php';

$message = "";
$messageType = "";
$tokenValid = false;
$resetSuccess = false;
$userId = null;

$rawToken = trim($_GET['token'] ?? $_POST['token'] ?? '');

if ($rawToken !== '') {
    $tokenHash = hash('sha256', $rawToken);

    $stmt = $conn->prepare("SELECT id, reset_token_expires FROM users WHERE reset_token = ?");
    $stmt->execute([$tokenHash]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && strtotime($user['reset_token_expires']) >= time()) {
        $tokenValid = true;
        $userId = (int)$user['id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {

    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($newPassword) || empty($confirmPassword)) {

        $message = "Please fill in both password fields.";
        $messageType = "error";

    } elseif ($newPassword !== $confirmPassword) {

        $message = "New password and confirmation do not match.";
        $messageType = "error";

    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $newPassword)) {

        $message = "Password must be at least 8 characters long and include at least 1 uppercase letter, 1 lowercase letter, 1 number, and 1 special character.";
        $messageType = "error";

    } else {

        try {

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update the password and clear the token in the same
            // statement so the link can never be reused.
            $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
            $success = $update->execute([$hashedPassword, $userId]);

            if ($success) {
                $resetSuccess = true;
                $tokenValid = false; // token is now consumed
            } else {
                $message = "Something went wrong updating your password. Please try again.";
                $messageType = "error";
            }

        } catch (PDOException $e) {

            $message = "Something went wrong updating your password. Please try again.";
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
  <title>Reset Password | Luntiang H.A.P.A.G.</title>
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
  <main class="flex-1 flex items-center justify-center px-6 py-16">
    <div class="w-full max-w-md">

      <?php if ($resetSuccess): ?>

        <!-- ============================================= -->
        <!-- Success                                        -->
        <!-- ============================================= -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-9 text-center">
          <div class="mx-auto w-14 h-14 rounded-full bg-green-50 flex items-center justify-center mb-5">
            <svg class="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <h1 class="font-black text-2xl font-semibold text-[#1a2e1c] mb-2">✓ Password Reset Successful</h1>
          <p class="text-[#5a7a5c] text-sm mb-6">Your password has been updated successfully. You can now log in using your new password.</p>
          <p class="text-[13px] text-[#9e9e9e] mb-4">Redirecting you to login in <span id="countdown">5</span> seconds…</p>
          <a href="login.php" class="inline-block w-full rounded-full bg-[#17611f] text-white text-sm font-medium py-3.5 hover:bg-[#14521a] transition-colors">Go to Login Now</a>
        </div>
        <script>
          let secondsLeft = 5;
          const countdownEl = document.getElementById('countdown');
          const timer = setNunitoval(() => {
            secondsLeft -= 1;
            if (countdownEl) countdownEl.textContent = Math.max(secondsLeft, 0);
            if (secondsLeft <= 0) {
              clearNunitoval(timer);
              window.location.href = 'login.php';
            }
          }, 1000);
        </script>

      <?php elseif (!$tokenValid): ?>

        <!-- ============================================= -->
        <!-- Invalid / expired token                        -->
        <!-- ============================================= -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-9 text-center">
          <div class="mx-auto w-14 h-14 rounded-full bg-red-50 flex items-center justify-center mb-5">
            <svg class="w-7 h-7 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
            </svg>
          </div>
          <h1 class="font-black text-2xl font-semibold text-[#1a2e1c] mb-2">Link Invalid or Expired</h1>
          <p class="text-[#5a7a5c] text-sm mb-6">This password reset link is invalid or has expired.</p>
          <a href="forgot-password.php" class="inline-block w-full rounded-full bg-[#17611f] text-white text-sm font-medium py-3.5 hover:bg-[#14521a] transition-colors">Request New Reset Link</a>
        </div>

      <?php else: ?>

        <!-- ============================================= -->
        <!-- Valid token: reset form                        -->
        <!-- ============================================= -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-9">
          <span class="inline-block text-[11px] font-semibold tracking-wide text-[#5a7a5c] bg-gray-100 rounded-full px-3 py-1 mb-5">RESET PASSWORD</span>
          <h1 class="font-black text-3xl font-semibold text-[#1a2e1c] mb-2">Reset Password</h1>
          <p class="text-[#5a7a5c] text-sm mb-8">Choose a strong new password for your account.</p>

          <?php if (!empty($message)): ?>
            <div id="alertMessage"
                 class="mb-6 flex items-start gap-3 rounded-2xl border px-5 py-4 shadow-sm transition-all duration-500 bg-red-50 border-red-200 text-red-700">
              <div class="mt-0.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
                </svg>
              </div>
              <div class="flex-1">
                <h3 class="font-semibold">Password Reset Failed</h3>
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

          <form method="POST" class="space-y-5" id="resetPasswordForm">
            <input type="hidden" name="token" value="<?= htmlspecialchars($rawToken) ?>" />
            <div>
              <label for="new_password" class="block text-sm font-medium text-[#1a2e1c] mb-2">New Password</label>
              <div class="relative">
                <input type="password" id="new_password" name="new_password" placeholder="••••••••••••" required minlength="8"
                       class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 pr-11 text-sm text-[#1a2e1c] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
                <button type="button" class="password-toggle-btn absolute right-3.5 top-1/2 -translate-y-1/2 text-[#9e9e9e] hover:text-[#5a7a5c] transition-colors" data-target="new_password" aria-label="Show password">
                  <svg class="w-4 h-4 icon-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  <svg class="w-4 h-4 icon-eye-off hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 012.132-3.532m3.32-2.454A9.958 9.958 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.973 9.973 0 01-4.132 5.411M14.121 14.121A3 3 0 019.88 9.88M9.879 9.879l4.242 4.242M9.879 9.879L3 3m6.879 6.879L21 21"/></svg>
                </button>
              </div>
              <ul id="passwordRequirements" class="mt-3 hidden space-y-1 text-xs text-[#5a7a5c]">
                <li id="rule-length" class="flex items-center gap-2"><span class="text-[#9e9e9e]">•</span> At least 8 characters</li>
                <li id="rule-uppercase" class="flex items-center gap-2"><span class="text-[#9e9e9e]">•</span> At least 1 uppercase letter</li>
                <li id="rule-lowercase" class="flex items-center gap-2"><span class="text-[#9e9e9e]">•</span> At least 1 lowercase letter</li>
                <li id="rule-number" class="flex items-center gap-2"><span class="text-[#9e9e9e]">•</span> At least 1 number</li>
                <li id="rule-special" class="flex items-center gap-2"><span class="text-[#9e9e9e]">•</span> At least 1 special character</li>
              </ul>
            </div>
            <div>
              <label for="confirm_password" class="block text-sm font-medium text-[#1a2e1c] mb-2">Confirm Password</label>
              <div class="relative">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••••••" required minlength="8"
                       class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 pr-11 text-sm text-[#1a2e1c] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
                <button type="button" class="password-toggle-btn absolute right-3.5 top-1/2 -translate-y-1/2 text-[#9e9e9e] hover:text-[#5a7a5c] transition-colors" data-target="confirm_password" aria-label="Show password">
                  <svg class="w-4 h-4 icon-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  <svg class="w-4 h-4 icon-eye-off hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 012.132-3.532m3.32-2.454A9.958 9.958 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.973 9.973 0 01-4.132 5.411M14.121 14.121A3 3 0 019.88 9.88M9.879 9.879l4.242 4.242M9.879 9.879L3 3m6.879 6.879L21 21"/></svg>
                </button>
              </div>
            </div>
            <button type="submit" id="resetPasswordSubmit" class="w-full rounded-full bg-[#17611f] text-white text-sm font-medium py-3.5 hover:bg-[#14521a] transition-colors flex items-center justify-center gap-2">
              <svg id="resetPasswordSpinner" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
              </svg>
              <span>Reset Password</span>
            </button>
          </form>
        </div>

        <script>
          // Show/hide toggle for each password field
          document.querySelectorAll('.password-toggle-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
              const input = document.getElementById(btn.dataset.target);
              const eyeIcon = btn.querySelector('.icon-eye');
              const eyeOffIcon = btn.querySelector('.icon-eye-off');
              const isHidden = input.type === 'password';
              input.type = isHidden ? 'text' : 'password';
              eyeIcon.classList.toggle('hidden', isHidden);
              eyeOffIcon.classList.toggle('hidden', !isHidden);
              btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            });
          });

          // Live password requirements checklist
          const newPasswordInput = document.getElementById('new_password');
          const passwordRequirements = document.getElementById('passwordRequirements');

          const passwordRules = {
            length: {
              element: document.getElementById('rule-length'),
              test: (value) => value.length >= 8
            },
            uppercase: {
              element: document.getElementById('rule-uppercase'),
              test: (value) => /[A-Z]/.test(value)
            },
            lowercase: {
              element: document.getElementById('rule-lowercase'),
              test: (value) => /[a-z]/.test(value)
            },
            number: {
              element: document.getElementById('rule-number'),
              test: (value) => /\d/.test(value)
            },
            special: {
              element: document.getElementById('rule-special'),
              test: (value) => /[^A-Za-z\d]/.test(value)
            }
          };

          function updatePasswordChecklist() {
            const value = newPasswordInput.value;

            Object.values(passwordRules).forEach(({ element, test }) => {
              const isMet = test(value);
              const bullet = element.querySelector('span');

              element.classList.toggle('text-green-600', isMet);
              element.classList.toggle('text-[#5a7a5c]', !isMet);
              bullet.classList.toggle('text-green-600', isMet);
              bullet.classList.toggle('text-[#9e9e9e]', !isMet);
              bullet.textContent = isMet ? '✓' : '•';
            });
          }

          if (newPasswordInput && passwordRequirements) {
            newPasswordInput.addEventListener('focus', () => {
              passwordRequirements.classList.remove('hidden');
              updatePasswordChecklist();
            });

            newPasswordInput.addEventListener('blur', () => {
              passwordRequirements.classList.add('hidden');
            });

            newPasswordInput.addEventListener('input', updatePasswordChecklist);
            updatePasswordChecklist();
          }
        </script>

        <script>
          const resetForm = document.getElementById('resetPasswordForm');
          if (resetForm) {
            resetForm.addEventListener('submit', () => {
              const btn = document.getElementById('resetPasswordSubmit');
              const spinner = document.getElementById('resetPasswordSpinner');
              btn.disabled = true;
              spinner.classList.remove('hidden');
            });
          }
        </script>

      <?php endif; ?>

    </div>
  </main>

  <!-- Footer -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>