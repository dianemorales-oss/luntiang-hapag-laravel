<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin-auth.php';

$activePage = 'profile';
$pageTitle = 'My Profile';

$adminId = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

$profileMessage = "";
$profileMessageType = "";
$passwordMessage = "";
$passwordMessageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($name) || empty($email)) {
        $profileMessage = "Please fill in all fields.";
        $profileMessageType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profileMessage = "Please enter a valid email address.";
        $profileMessageType = "error";
    } else {
        $check = $conn->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
        $check->execute([$email, $adminId]);
        if ($check->rowCount() > 0) {
            $profileMessage = "That email is already used by another admin account.";
            $profileMessageType = "error";
        } else {
            $update = $conn->prepare("UPDATE admins SET name = ?, email = ? WHERE id = ?");
            if ($update->execute([$name, $email, $adminId])) {
                $_SESSION['admin_name'] = $name;
                $_SESSION['admin_email'] = $email;
                $admin['name'] = $name;
                $admin['email'] = $email;
                $profileMessage = "Profile updated successfully.";
                $profileMessageType = "success";
            } else {
                $profileMessage = "Something went wrong updating your profile.";
                $profileMessageType = "error";
            }
        }
    }

}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {

    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($new) || empty($confirm)) {
        $passwordMessage = "Please fill in all password fields.";
        $passwordMessageType = "error";
    } elseif (!password_verify($current, $admin['password'])) {
        $passwordMessage = "Your current password is incorrect.";
        $passwordMessageType = "error";
    } elseif ($new !== $confirm) {
        $passwordMessage = "New password and confirmation do not match.";
        $passwordMessageType = "error";
    } elseif (strlen($new) < 8) {
        $passwordMessage = "New password must be at least 8 characters long.";
        $passwordMessageType = "error";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
        if ($update->execute([$hashed, $adminId])) {
            $passwordMessage = "Password changed successfully.";
            $passwordMessageType = "success";
        } else {
            $passwordMessage = "Something went wrong updating your password.";
            $passwordMessageType = "error";
        }
    }

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Profile | Luntiang H.A.P.A.G. Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Nunito', sans-serif; }
    .font-black { font-family: 'Nunito', serif; }
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-thumb { background: #d8cfbd; border-radius: 8px; }
  </style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c]">
  <div class="flex min-h-screen">
    <?php require_once __DIR__ . '/includes/admin-sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php require_once __DIR__ . '/includes/admin-topbar.php'; ?>

      <main class="flex-1 overflow-y-auto p-6"><div class="max-w-3xl mx-auto space-y-5">

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
          <h3 class="text-sm font-semibold text-[#1a2e1c] mb-4">Profile Information</h3>

          <?php if ($profileMessage): ?>
            <div class="mb-4 rounded-xl px-4 py-3 text-sm <?= $profileMessageType === 'error' ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-green-50 text-green-700 border border-green-100' ?>"><?= htmlspecialchars($profileMessage) ?></div>
          <?php endif; ?>

          <form method="POST" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Name</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($admin['name']) ?>" class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
              </div>
              <div>
                <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Email</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($admin['email']) ?>" class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
              </div>
            </div>
            <div class="text-[13px] text-[#5a7a5c]">Role: <span class="font-medium text-[#1a2e1c]"><?= htmlspecialchars($admin['role']) ?></span></div>
            <button type="submit" name="update_profile" value="1" class="px-6 py-3 rounded-full bg-[#17611f] text-white text-sm font-medium hover:bg-[#14521a] transition-colors">Save Changes</button>
          </form>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
          <h3 class="text-sm font-semibold text-[#1a2e1c] mb-4">Change Password</h3>

          <?php if ($passwordMessage): ?>
            <div class="mb-4 rounded-xl px-4 py-3 text-sm <?= $passwordMessageType === 'error' ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-green-50 text-green-700 border border-green-100' ?>"><?= htmlspecialchars($passwordMessage) ?></div>
          <?php endif; ?>

          <form method="POST" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Current Password</label>
              <div class="relative">
                <input type="password" id="current_password" name="current_password" required class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
                <button type="button" class="password-toggle-btn absolute right-3.5 top-1/2 -translate-y-1/2 text-[#9e9e9e] hover:text-[#5a7a5c] transition-colors" data-target="current_password" aria-label="Show password">
                  <svg class="w-4 h-4 icon-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  <svg class="w-4 h-4 icon-eye-off hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 012.132-3.532m3.32-2.454A9.958 9.958 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.973 9.973 0 01-4.132 5.411M14.121 14.121A3 3 0 019.88 9.88M9.879 9.879l4.242 4.242M9.879 9.879L3 3m6.879 6.879L21 21"/></svg>
                </button>
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-[#1a2e1c] mb-2">New Password</label>
                <div class="relative">
                  <input type="password" id="new_password" name="new_password" required class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
                  <button type="button" class="password-toggle-btn absolute right-3.5 top-1/2 -translate-y-1/2 text-[#9e9e9e] hover:text-[#5a7a5c] transition-colors" data-target="new_password" aria-label="Show password">
                    <svg class="w-4 h-4 icon-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg class="w-4 h-4 icon-eye-off hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 012.132-3.532m3.32-2.454A9.958 9.958 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.973 9.973 0 01-4.132 5.411M14.121 14.121A3 3 0 019.88 9.88M9.879 9.879l4.242 4.242M9.879 9.879L3 3m6.879 6.879L21 21"/></svg>
                  </button>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-[#1a2e1c] mb-2">Confirm New Password</label>
                <div class="relative">
                  <input type="password" id="confirm_password" name="confirm_password" required class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
                  <button type="button" class="password-toggle-btn absolute right-3.5 top-1/2 -translate-y-1/2 text-[#9e9e9e] hover:text-[#5a7a5c] transition-colors" data-target="confirm_password" aria-label="Show password">
                    <svg class="w-4 h-4 icon-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg class="w-4 h-4 icon-eye-off hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 012.132-3.532m3.32-2.454A9.958 9.958 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.973 9.973 0 01-4.132 5.411M14.121 14.121A3 3 0 019.88 9.88M9.879 9.879l4.242 4.242M9.879 9.879L3 3m6.879 6.879L21 21"/></svg>
                  </button>
                </div>
              </div>
            </div>
            <button type="submit" name="update_password" value="1" class="px-6 py-3 rounded-full bg-[#17611f] text-white text-sm font-medium hover:bg-[#14521a] transition-colors">Update Password</button>
          </form>
        </div>

      </div></main>
    </div>
  </div>

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