<?php
session_start();
require_once __DIR__ . '/../config.php';
if (isset($_SESSION['admin_id'])) { header("Location: admin-dashboard.php"); exit(); }

$message = ""; $messageType = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? ''); $password = $_POST['password'] ?? '';
    if (empty($email) || empty($password)) { $message = "Please enter both your email and password."; $messageType = "error"; }
    else {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?"); $stmt->execute([$email]); $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id']; $_SESSION['admin_name'] = $admin['name']; $_SESSION['admin_email'] = $admin['email']; $_SESSION['admin_role'] = $admin['role'];
            header("Location: admin-dashboard.php"); exit();
        } else { $message = "Incorrect email or password."; $messageType = "error"; }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login | Luntiang H.A.P.A.G.</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
body{font-family:'Nunito',sans-serif;background:#f4faf5}
.pw-toggle{cursor:pointer;position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;padding:4px;color:#9e9e9e}
.pw-toggle:hover{color:#5a7a5c}
.pw-toggle .eye-off{display:none}
</style>
</head>
<body class="bg-[#f4faf5] min-h-screen flex items-center justify-center px-6">
<div class="w-full max-w-sm">
  <a href="../index.php" class="flex justify-center mb-8"><img src="../images/lettuce/logo-cropped.png" alt="Luntiang H.A.P.A.G." class="h-[70px] w-auto object-contain"></a>
  <div class="bg-white rounded-2xl border border-[rgba(27,94,32,0.08)] shadow-sm p-8">
    <span class="inline-block text-[11px] font-semibold tracking-wide text-[#17611f] bg-[#e8f5e9] rounded-full px-3 py-1 mb-4">ADMIN</span>
    <h1 class="text-2xl font-black mb-2">Admin Login</h1>
    <p class="text-[#5a7a5c] text-sm mb-6">Sign in to manage products, orders, and customers.</p>
    <?php if($message):?><div class="mb-5 rounded-xl px-4 py-3 text-sm <?=$messageType==='error'?'bg-red-50 text-red-700 border border-red-100':'bg-[#e8f5e9] text-[#17611f] border border-[#c8e6c9]'?>"><?=htmlspecialchars($message)?></div><?php endif;?>
    <form class="space-y-4" method="POST">
      <div><label class="block text-sm font-bold mb-1.5">Admin Email</label><input type="email" name="email" required placeholder="admin@email.com" value="<?=htmlspecialchars($_POST['email']??'')?>" class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40"></div>
      <div><label class="block text-sm font-bold mb-1.5">Password</label>
        <div class="relative">
          <input type="password" id="password" name="password" required placeholder="Enter password" class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
          <button type="button" class="pw-toggle" onclick="var p=document.getElementById('password');var e=this.querySelector('.eye-on');var o=this.querySelector('.eye-off');if(p.type==='password'){p.type='text';e.style.display='none';o.style.display='block'}else{p.type='password';e.style.display='block';o.style.display='none'}" aria-label="Toggle password">
            <svg class="w-4 h-4 eye-on" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            <svg class="w-4 h-4 eye-off" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 012.132-3.532m3.32-2.454A9.958 9.958 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.973 9.973 0 01-4.132 5.411"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18"/></svg>
          </button>
        </div>
      </div>
      <button type="submit" class="w-full rounded-xl bg-[#17611f] text-white text-sm font-bold py-3 hover:bg-[#14521a] transition-colors">Sign In as Admin</button>
    </form>
    <p class="text-xs text-[#9e9e9e] mt-5 text-center">Default: admin@luntianghapag.com / Admin@123</p>
  </div>
</div>
</body>
</html>
