<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | Luntiang H.A.P.A.G.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Nunito', sans-serif; background: #f4faf5; }
  </style>
</head>
<body class="min-h-screen bg-[#f4faf5] text-[#1a2e1c]">
  <a href="{{ route('home') }}"
     class="fixed left-6 top-6 z-20 inline-flex items-center gap-2 rounded-full border border-[rgba(27,94,32,0.12)] bg-white px-5 py-2.5 text-sm font-bold text-[#17611f] shadow-sm transition hover:bg-[#e8f5e9] hover:border-[#17611f]/30">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
    </svg>
    Back to Home
  </a>

  <main class="min-h-screen flex flex-col items-center justify-center px-6 py-10">
    <div class="mb-10 flex justify-center">
      <div class="bg-white px-3 py-1 shadow-sm">
        <img src="{{ asset('images/lettuce/logo-cropped.png') }}" alt="Luntiang H.A.P.A.G." class="h-[86px] w-auto object-contain">
      </div>
    </div>

    <section class="w-full max-w-[480px] rounded-[22px] border border-[rgba(27,94,32,0.10)] bg-white px-10 py-10 shadow-[0_2px_10px_rgba(9,26,11,0.06)]">
      <div class="mb-7">
        <span class="inline-flex rounded-full bg-[#e8f5e9] px-5 py-2 text-sm font-bold uppercase tracking-wide text-[#17611f]">
          Admin
        </span>
        <h1 class="mt-6 text-[30px] font-black leading-tight text-black">Admin Login</h1>
        <p class="mt-3 max-w-[320px] text-[17px] leading-relaxed text-[#4f734f]">
          Sign in to manage products, orders, and customers.
        </p>
      </div>

      @if (session('error'))
        <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
          {{ session('error') }}
        </div>
      @endif

      <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-5">
        @csrf

        <div>
          <label for="email" class="mb-2 block text-[16px] font-black text-black">Admin Email</label>
          <input id="email"
                 type="email"
                 name="email"
                 value="{{ old('email') }}"
                 required
                 autofocus
                 autocomplete="username"
                 placeholder="admin@email.com"
                 class="w-full rounded-2xl border border-[rgba(27,94,32,0.14)] bg-white px-5 py-4 text-[16px] text-[#1a2e1c] placeholder-[#9aa1ad] outline-none transition focus:border-[#52b788] focus:ring-4 focus:ring-[#52b788]/20">
        </div>

        <div>
          <label for="password" class="mb-2 block text-[16px] font-black text-black">Password</label>
          <div class="relative">
            <input id="password"
                   type="password"
                   name="password"
                   required
                   autocomplete="current-password"
                   placeholder="Enter password"
                   class="w-full rounded-2xl border border-[rgba(27,94,32,0.14)] bg-white py-4 pl-5 pr-14 text-[16px] text-[#1a2e1c] placeholder-[#9aa1ad] outline-none transition focus:border-[#52b788] focus:ring-4 focus:ring-[#52b788]/20">
            <button type="button"
                    id="togglePassword"
                    class="absolute inset-y-0 right-0 flex w-14 items-center justify-center text-[#9e9e9e] transition hover:text-[#17611f]"
                    aria-label="Show password">
              <svg id="eyeOpen" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <svg id="eyeClosed" class="hidden h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.584 10.587A2 2 0 0012 14a2 2 0 001.414-.586M9.88 4.24A9.77 9.77 0 0112 4c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-2.132 3.592M6.61 6.61A10.05 10.05 0 002.458 12C3.732 16.057 7.523 19 12 19a9.77 9.77 0 004.39-1.03" />
              </svg>
            </button>
          </div>
        </div>

        <button type="submit" class="w-full rounded-2xl bg-[#17611f] px-5 py-4 text-[17px] font-black text-white shadow-sm transition hover:bg-[#14521a] focus:outline-none focus:ring-4 focus:ring-[#52b788]/30">
          Sign In as Admin
        </button>
      </form>

      <p class="mt-7 text-center text-[14px] font-semibold text-[#9aa1ad]">
        Default: admin@luntianghapag.com / Admin@123
      </p>
    </section>
  </main>

  <script>
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const eyeOpen = document.getElementById('eyeOpen');
    const eyeClosed = document.getElementById('eyeClosed');

    togglePassword.addEventListener('click', function () {
      const isHidden = passwordInput.type === 'password';
      passwordInput.type = isHidden ? 'text' : 'password';
      eyeOpen.classList.toggle('hidden', isHidden);
      eyeClosed.classList.toggle('hidden', !isHidden);
      togglePassword.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });
  </script>
</body>
</html>
