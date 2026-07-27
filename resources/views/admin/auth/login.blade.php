<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Admin Login</title>
<script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet"><style>body{font-family:'Nunito',sans-serif;background:#f4faf5}</style></head>
<body class="min-h-screen flex items-center justify-center px-6">
  <div class="w-full max-w-md bg-white rounded-2xl border p-8">
    <h1 class="text-2xl font-black mb-2">Admin Portal</h1><p class="text-sm text-[#5a7a5c] mb-6">Luntiang H.A.P.A.G.</p>
    @if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>@endif
    <form method="POST" action="{{ route('admin.login.submit') }}">
      @csrf
      <div class="mb-3"><label class="text-sm font-bold">Email</label><input type="email" name="email" required class="w-full border rounded-xl px-4 py-3 text-sm mt-1"></div>
      <div class="mb-4"><label class="text-sm font-bold">Password</label><input type="password" name="password" required class="w-full border rounded-xl px-4 py-3 text-sm mt-1"></div>
      <button type="submit" class="w-full py-3 rounded-xl bg-[#17611f] text-white font-bold">Sign In</button>
    </form>
    <p class="text-xs text-center mt-4 text-[#9e9e9e]">Default: admin@luntianghapag.com / Admin@123</p>
  </div>
</body>
</html>
