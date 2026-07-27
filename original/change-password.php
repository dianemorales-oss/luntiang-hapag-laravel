<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Empty fields
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {

        $message = "Please fill in all password fields.";
        $messageType = "error";

    }

    // Wrong current password
    elseif (!password_verify($current_password, $user['password'])) {

        $message = "Your current password is incorrect.";
        $messageType = "error";

    }

    // Mismatch
    elseif ($new_password !== $confirm_password) {

        $message = "New password and confirmation do not match.";
        $messageType = "error";

    }

    // Length
    elseif (strlen($new_password) < 8) {

        $message = "New password must be at least 8 characters long.";
        $messageType = "error";

    }

    else {

        try {

            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

            $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $success = $update->execute([$hashedPassword, $uid]);

            if ($success) {

                header("Location: my-profile.php?password_updated=1");
                exit();

            } else {

                $message = "Something went wrong updating your password. Please try again.";
                $messageType = "error";

            }

        } catch (PDOException $e) {

            $message = "Database Error: " . $e->getMessage();
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
  <title>Change Password | Luntiang H.A.P.A.G.</title>
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

  <!-- Change Password Form -->
  <main class="flex-1 flex items-center justify-center px-6 py-16">
    <div class="w-full max-w-md">
        <a href="my-profile.php"
           class="inline-flex items-center gap-2 text-sm text-[#17611f] hover:text-[#14521a] transition-colors mb-6">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to My Profile
        </a>
    <div class="w-full max-w-md bg-white rounded-3xl shadow-sm border border-gray-100 p-9">
      <span class="inline-block text-[11px] font-semibold tracking-wide text-[#5a7a5c] bg-gray-100 rounded-full px-3 py-1 mb-5">CHANGE PASSWORD</span>
      <h1 class="font-black text-3xl font-semibold text-[#1a2e1c] mb-2">Update your password</h1>
      <p class="text-[#5a7a5c] text-sm mb-8">Choose a strong password you don't use anywhere else.</p>

      <?php if (!empty($message)): ?>

<div id="alertMessage"
     class="mb-6 flex items-start gap-3 rounded-2xl border px-5 py-4 shadow-sm transition-all duration-500
     <?= $messageType == 'error'
        ? 'bg-red-50 border-red-200 text-red-700'
        : 'bg-green-50 border-green-200 text-green-700'; ?>">

    <!-- Icon -->
    <div class="mt-0.5">

        <?php if($messageType == 'error'): ?>

        <svg xmlns="http://www.w3.org/2000/svg"
             class="w-6 h-6 text-red-500"
             fill="none"
             viewBox="0 0 24 24"
             stroke="currentColor"
             stroke-width="2">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
        </svg>

        <?php else: ?>

        <svg xmlns="http://www.w3.org/2000/svg"
             class="w-6 h-6 text-green-500"
             fill="none"
             viewBox="0 0 24 24"
             stroke="currentColor"
             stroke-width="2">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M9 12l2 2 4-4m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>

        <?php endif; ?>

    </div>

    <!-- Message -->
    <div class="flex-1">

        <h3 class="font-semibold">

            <?= $messageType == 'error'
                ? 'Password Change Failed'
                : 'Success'; ?>

        </h3>

        <p class="text-sm mt-1">

            <?= htmlspecialchars($message); ?>

        </p>

    </div>

    <!-- Close Button -->
    <button type="button"
            onclick="closeAlert()"
            class="text-[#9e9e9e] hover:text-[#1a2e1c] transition">

        ✕

    </button>

</div>

<script>

function closeAlert(){

    const alert = document.getElementById("alertMessage");

    if(alert){

        alert.classList.add("opacity-0","translate-y-2");

        setTimeout(()=>{

            alert.remove();

        },400);

    }

}

setTimeout(closeAlert,5000);

</script>

<?php endif; ?>

      <form class="space-y-5" method="POST">
        <div>
          <label for="current_password" class="block text-sm font-medium text-[#1a2e1c] mb-2">Current Password</label>
          <div class="relative">
            <input type="password" id="current_password" name="current_password" placeholder="••••••••••••" required
                   class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 pr-11 text-sm text-[#1a2e1c] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
            <button type="button" class="password-toggle-btn absolute right-3.5 top-1/2 -translate-y-1/2 text-[#9e9e9e] hover:text-[#5a7a5c] transition-colors" data-target="current_password" aria-label="Show password">
              <svg class="w-4 h-4 icon-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              <svg class="w-4 h-4 icon-eye-off hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 012.132-3.532m3.32-2.454A9.958 9.958 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.973 9.973 0 01-4.132 5.411M14.121 14.121A3 3 0 019.88 9.88M9.879 9.879l4.242 4.242M9.879 9.879L3 3m6.879 6.879L21 21"/></svg>
            </button>
          </div>
        </div>

        <div>
          <label for="new_password" class="block text-sm font-medium text-[#1a2e1c] mb-2">New Password</label>
          <div class="relative">
            <input type="password" id="new_password" name="new_password" placeholder="••••••••••••" required
                   class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 pr-11 text-sm text-[#1a2e1c] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
            <button type="button" class="password-toggle-btn absolute right-3.5 top-1/2 -translate-y-1/2 text-[#9e9e9e] hover:text-[#5a7a5c] transition-colors" data-target="new_password" aria-label="Show password">
              <svg class="w-4 h-4 icon-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              <svg class="w-4 h-4 icon-eye-off hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 012.132-3.532m3.32-2.454A9.958 9.958 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.973 9.973 0 01-4.132 5.411M14.121 14.121A3 3 0 019.88 9.88M9.879 9.879l4.242 4.242M9.879 9.879L3 3m6.879 6.879L21 21"/></svg>
            </button>
          </div>
        </div>

        <div>
          <label for="confirm_password" class="block text-sm font-medium text-[#1a2e1c] mb-2">Confirm New Password</label>
          <div class="relative">
            <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••••••" required
                   class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 pr-11 text-sm text-[#1a2e1c] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
            <button type="button" class="password-toggle-btn absolute right-3.5 top-1/2 -translate-y-1/2 text-[#9e9e9e] hover:text-[#5a7a5c] transition-colors" data-target="confirm_password" aria-label="Show password">
              <svg class="w-4 h-4 icon-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              <svg class="w-4 h-4 icon-eye-off hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 012.132-3.532m3.32-2.454A9.958 9.958 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.973 9.973 0 01-4.132 5.411M14.121 14.121A3 3 0 019.88 9.88M9.879 9.879l4.242 4.242M9.879 9.879L3 3m6.879 6.879L21 21"/></svg>
            </button>
          </div>
        </div>

        <div class="flex flex-wrap gap-4 pt-1">
          <button type="submit" class="px-6 py-3 rounded-full bg-[#17611f] text-white text-sm font-medium hover:bg-[#14521a] transition-colors">Update Password</button>
          <a href="my-profile.php" class="px-6 py-3 rounded-full border border-gray-300 text-[#1a2e1c] text-sm font-medium hover:bg-gray-100 transition-colors">Cancel</a>
        </div>
      </form>
    </div>
    </div>
  </main>

  <!-- Footer -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script>
    // Show/hide toggle for password fields
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
  </script>

</body>
</html>