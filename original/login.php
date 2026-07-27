<?php
session_start();
require 'config.php';

$message = "";
$messageType = "";

if (isset($_GET['registered'])) {
    $message = "Your account has been created successfully. You can now sign in.";
    $messageType = "success";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $loginInput = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($loginInput) || empty($password)) {
        $message = "Please enter your email or mobile number and password.";
        $messageType = "error";
    } else {
        // Detect if input is email or mobile
        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);
        if ($isEmail) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE phone = ?");
        }
        $stmt->execute([$loginInput]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            // Merge guest cart with persistent DB cart
            $guestCart = $_SESSION['cart'] ?? [];
            mergeGuestCartToDb($conn, $guestCart);
            header("Location: my-profile.php");
            exit();
        } else {
            $message = "Invalid email/mobile number or password.";
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
  <title>Login | Luntiang H.A.P.A.G.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>body{font-family:'Nunito',sans-serif}</style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c] min-h-screen flex flex-col">

  <?php include __DIR__ . '/includes/header.php'; ?>

  <main class="flex-1 flex items-center justify-center px-6 py-16">
    <div class="w-full max-w-md bg-white rounded-2xl border border-[rgba(27,94,32,0.08)] shadow-sm p-9">
      <span class="inline-block text-[11px] font-semibold tracking-wide text-[#17611f] bg-[#e8f5e9] rounded-full px-3 py-1 mb-5">LOGIN</span>
      <h1 class="text-3xl font-black text-[#1a2e1c] mb-2">Welcome back 🌿</h1>
      <p class="text-[#5a7a5c] text-sm mb-8">Sign in to manage your orders and support requests.</p>

      <?php if (!empty($message)): ?>
        <div id="alertMessage" class="mb-6 flex items-start gap-3 rounded-2xl border px-5 py-4 shadow-sm transition-all duration-500
          <?= $messageType == 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-[#e8f5e9] border-[#c8e6c9] text-[#1b5e20]'; ?>">
          <div class="mt-0.5">
            <?php if($messageType == 'error'): ?>
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
            <?php else: ?>
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-[#17611f]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <?php endif; ?>
          </div>
          <div class="flex-1">
            <h3 class="font-semibold"><?= $messageType == 'error' ? 'Login Failed' : 'Success'; ?></h3>
            <p class="text-sm mt-1"><?= htmlspecialchars($message); ?></p>
          </div>
          <button type="button" onclick="closeAlert()" class="text-gray-400 hover:text-gray-700 transition">✕</button>
        </div>
        <script>function closeAlert(){const a=document.getElementById("alertMessage");if(a){a.classList.add("opacity-0","translate-y-2");setTimeout(()=>a.remove(),400);}}setTimeout(closeAlert,5000);</script>
      <?php endif; ?>

      <form class="space-y-5" method="POST">
        <div>
          <label for="login" class="block text-sm font-bold text-[#1a2e1c] mb-2">Email or Mobile Number</label>
          <input type="text" id="login" name="login" placeholder="your@email.com or 09123456789" required value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
                 class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm text-[#1a2e1c] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
        </div>
        <div>
          <label for="password" class="block text-sm font-bold text-[#1a2e1c] mb-2">Password</label>
          <div class="relative">
            <input type="password" id="password" name="password" placeholder="••••••••••••" required
                   class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 pr-11 text-sm text-[#1a2e1c] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
            <button type="button" class="password-toggle-btn absolute right-3.5 top-1/2 -translate-y-1/2 text-[#9e9e9e] hover:text-[#5a7a5c] transition-colors" data-target="password" aria-label="Show password">
              <svg class="w-4 h-4 icon-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              <svg class="w-4 h-4 icon-eye-off hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 012.132-3.532m3.32-2.454A9.958 9.958 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.973 9.973 0 01-4.132 5.411M14.121 14.121A3 3 0 019.88 9.88M9.879 9.879l4.242 4.242M9.879 9.879L3 3m6.879 6.879L21 21"/></svg>
            </button>
          </div>
        </div>
        <button type="submit" class="w-full rounded-xl bg-[#17611f] text-white text-sm font-black py-3.5 hover:bg-[#14521a] transition-colors">Sign In</button>
        <div class="flex items-center justify-between pt-1">
          <a href="forgot-password.php" class="text-sm text-[#17611f] hover:text-[#14521a] font-semibold transition-colors">Forgot password?</a>
          <a href="register.php" class="text-sm text-[#17611f] hover:text-[#14521a] font-semibold transition-colors">Create account →</a>
        </div>
      </form>
    </div>
  </main>

  <script>
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
