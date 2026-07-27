@extends('layouts.app')
@section('title', 'Register | Luntiang H.A.P.A.G.')
@section('content')

<main class="flex-1 flex items-center justify-center px-6 py-16">
  <div class="w-full max-w-2xl bg-white rounded-3xl shadow-sm border border-gray-100 p-9">
    <span class="inline-block text-[11px] font-semibold tracking-wide text-[#17611f] bg-[#e8f5e9] rounded-xl px-3 py-1 mb-5">REGISTER</span>
    <h1 class="text-3xl font-black text-[#1a2e1c] mb-2">Create your account 🌱</h1>
    <p class="text-[#5a7a5c] text-sm mb-8">Join Luntiang H.A.P.A.G. to order fresh hydroponic lettuce and track your deliveries.</p>

    @if (session('success') || session('error'))
      @php
        $messageType = session('success') ? 'success' : 'error';
        $message = session('success') ?: session('error');
      @endphp
      <div id="alertMessage" class="mb-6 flex items-start gap-3 rounded-2xl border px-5 py-4 shadow-sm transition-all duration-500 {{ $messageType == 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'; }}">
        <div class="mt-0.5">
          @if ($messageType == 'error')
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
            </svg>
          @else
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          @endif
        </div>
        <div class="flex-1">
          <h3 class="font-semibold">{{ $messageType == 'error' ? 'Registration Failed' : 'Success' }}</h3>
          <p class="text-sm mt-1">{{ $message }}</p>
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
        setTimeout(closeAlert,5000);
      </script>
    @endif

    <form class="space-y-5" method="POST" action="{{ route('register.submit') }}">
      @csrf
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label for="first_name" class="block text-sm font-medium text-[#1a2e1c] mb-2">First Name</label>
          <input type="text" id="first_name" name="first_name" placeholder="First Name" required value="{{ old('first_name') }}"
                 oninput="this.value = this.value.replace(/[^A-Za-z\s\-\.]/g, '')"
                 class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm text-[#1a2e1c] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
        </div>
        <div>
          <label for="last_name" class="block text-sm font-medium text-[#1a2e1c] mb-2">Last Name</label>
          <input type="text" id="last_name" name="last_name" placeholder="Last Name" required value="{{ old('last_name') }}"
                 oninput="this.value = this.value.replace(/[^A-Za-z\s\-\.]/g, '')"
                 class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm text-[#1a2e1c] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
        </div>
      </div>

      <div>
        <label for="email" class="block text-sm font-medium text-[#1a2e1c] mb-2">Email Address</label>
        <input type="email" id="email" name="email" placeholder="your@email.com" required value="{{ old('email') }}"
               class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm text-[#1a2e1c] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
      </div>

      <div>
        <label for="phone" class="block text-sm font-medium text-[#1a2e1c] mb-2">Phone Number</label>
        <input type="text" id="phone" name="phone" placeholder="09123456789" required minlength="11" maxlength="11" inputmode="numeric" pattern="[0-9]*" value="{{ old('phone') }}"
               oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)"
               class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm text-[#1a2e1c] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
      </div>

      <!-- Address Fields -->
      <fieldset class="space-y-4">
        <legend class="text-sm font-medium text-[#1a2e1c] mb-1">Address</legend>
        <div>
          <label for="street" class="block text-xs font-bold text-[#5a7a5c] mb-1">Street Address</label>
          <input type="text" id="street" name="street" placeholder="House/Unit No., Street, Barangay" required value="{{ old('street') }}"
                 class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm text-[#1a2e1c] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
        </div>
        @php
          $caviteCitiesMunicipalities = [
              'Alfonso',
              'Amadeo',
              'Bacoor City',
              'Carmona City',
              'Cavite City',
              'Dasmarinas City',
              'General Emilio Aguinaldo',
              'General Mariano Alvarez',
              'General Trias City',
              'Imus City',
              'Indang',
              'Kawit',
              'Magallanes',
              'Maragondon',
              'Mendez',
              'Naic',
              'Noveleta',
              'Rosario',
              'Silang',
              'Tagaytay City',
              'Tanza',
              'Ternate',
              'Trece Martires City',
          ];
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="city" class="block text-xs font-bold text-[#5a7a5c] mb-1">City / Municipality</label>
            <select id="city" name="city" required
                    class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] bg-white px-4 py-3 text-sm text-[#1a2e1c] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors">
              <option value="" disabled {{ old('city') ? '' : 'selected' }}>Select city or municipality</option>
              @foreach ($caviteCitiesMunicipalities as $cityOption)
                <option value="{{ $cityOption }}" {{ old('city') === $cityOption ? 'selected' : '' }}>{{ $cityOption }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label for="province" class="block text-xs font-bold text-[#5a7a5c] mb-1">Province</label>
            <select id="province" name="province" required
                    class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] bg-white px-4 py-3 text-sm text-[#1a2e1c] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors">
              <option value="Cavite" {{ old('province', 'Cavite') === 'Cavite' ? 'selected' : '' }}>Cavite</option>
            </select>
          </div>
        </div>
        <div class="max-w-[200px]">
          <label for="zip" class="block text-xs font-bold text-[#5a7a5c] mb-1">ZIP Code</label>
          <input type="text" id="zip" name="zip" placeholder="4114" required minlength="4" maxlength="4" inputmode="numeric" pattern="\d{4}" value="{{ old('zip') }}"
                 oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4)"
                 class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm text-[#1a2e1c] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
        </div>
      </fieldset>
      
      <div>
        <label for="password" class="block text-sm font-medium text-[#1a2e1c] mb-2">Password</label>
        <div class="relative">
          <input type="password" id="password" name="password" placeholder="••••••••••••" required minlength="8"
                 pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$"
                 title="Password must be at least 8 characters long and include at least 1 uppercase letter, 1 lowercase letter, 1 number, and 1 special character."
                 class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 pr-11 text-sm text-[#1a2e1c] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
          <button type="button" class="password-toggle-btn absolute right-3.5 top-1/2 -translate-y-1/2 text-[#9e9e9e] hover:text-[#5a7a5c] transition-colors" data-target="password" aria-label="Show password">
            <svg class="w-4 h-4 icon-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <svg class="w-4 h-4 icon-eye-off hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 012.132-3.532m3.32-2.454A9.958 9.958 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.973 9.973 0 01-4.132 5.411M14.121 14.121A3 3 0 019.88 9.88M9.879 9.879l4.242 4.242M9.879 9.879L3 3m6.879 6.879L21 21"/>
            </svg>
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
          <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••••••" required
                 class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 pr-11 text-sm text-[#1a2e1c] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
          <button type="button" class="password-toggle-btn absolute right-3.5 top-1/2 -translate-y-1/2 text-[#9e9e9e] hover:text-[#5a7a5c] transition-colors" data-target="confirm_password" aria-label="Show password">
            <svg class="w-4 h-4 icon-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <svg class="w-4 h-4 icon-eye-off hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 012.132-3.532m3.32-2.454A9.958 9.958 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.973 9.973 0 01-4.132 5.411M14.121 14.121A3 3 0 019.88 9.88M9.879 9.879l4.242 4.242M9.879 9.879L3 3m6.879 6.879L21 21"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Terms Acceptance -->
      <div class="flex items-start gap-3 pt-1">
        <input type="checkbox" id="accept_terms" name="accept_terms" value="1" required
               class="mt-0.5 w-4 h-4 rounded border-gray-300 text-[#17611f] focus:ring-2 focus:ring-[#52b788]/40 focus:ring-offset-0 cursor-pointer" />
        <label for="accept_terms" class="text-sm text-[#5a7a5c] cursor-pointer leading-relaxed">
          By creating an account, you agree to Luntiang H.A.P.A.G.'s
          <a href="{{ route('terms') }}" target="_blank" class="text-[#17611f] font-medium hover:underline">Terms of Service</a> 
          and 
          <a href="{{ route('privacy') }}" target="_blank" class="text-[#17611f] font-medium hover:underline">Privacy Policy</a>.
          <span class="text-red-500">*</span>
        </label>
      </div>

      <button type="submit" class="w-full rounded-xl bg-[#17611f] text-white text-sm font-medium py-3.5 hover:bg-[#14521a] transition-colors">Create Account</button>

      <div class="flex items-center justify-between pt-1">
        <span class="text-sm text-[#5a7a5c]">Already have an account?</span>
        <a href="{{ route('login') }}" class="text-sm text-[#17611f] hover:text-[#14521a] transition-colors">Sign in →</a>
      </div>
    </form>
  </div>
</main>

@push('scripts')
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

  const passwordInput = document.getElementById('password');
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
    const value = passwordInput.value;

    Object.values(passwordRules).forEach(({ element, test }) => {
      if(!element) return;
      const isMet = test(value);
      const bullet = element.querySelector('span');

      element.classList.toggle('text-green-600', isMet);
      element.classList.toggle('text-[#5a7a5c]', !isMet);
      bullet.classList.toggle('text-green-600', isMet);
      bullet.classList.toggle('text-[#9e9e9e]', !isMet);
      bullet.textContent = isMet ? '✓' : '•';
    });
  }

  if (passwordInput && passwordRequirements) {
    passwordInput.addEventListener('focus', () => {
      passwordRequirements.classList.remove('hidden');
      updatePasswordChecklist();
    });

    passwordInput.addEventListener('blur', () => {
      passwordRequirements.classList.add('hidden');
    });

    passwordInput.addEventListener('input', updatePasswordChecklist);
    updatePasswordChecklist();
  }
</script>
@endpush

@endsection
