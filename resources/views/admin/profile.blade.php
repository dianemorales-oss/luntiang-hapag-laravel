@extends('admin.layouts.app')
@section('title','Admin Profile | Luntiang H.A.P.A.G.')
@section('header','Admin Profile')

@section('content')
<div class="max-w-3xl mx-auto">
  <div class="bg-white rounded-2xl border border-[rgba(27,94,32,0.08)] shadow-sm overflow-hidden">
    <div class="bg-gradient-to-br from-[#17611f] to-[#0d3311] p-8 text-white relative overflow-hidden">
      <div class="absolute top-0 right-0 w-40 h-40 bg-white/5 rounded-full blur-3xl"></div>
      <div class="relative flex items-center gap-5">
        <div class="relative">
          @if($admin->profile_picture)
            <img src="{{ asset($admin->profile_picture) }}" alt="Profile" class="w-20 h-20 rounded-full object-cover border-4 border-white/20 shadow-lg">
          @else
            <div class="w-20 h-20 rounded-full bg-white/15 border-4 border-white/20 flex items-center justify-center text-2xl font-black">
              {{ strtoupper(substr($admin->first_name ?? $admin->name,0,1) . substr($admin->last_name ?? '',0,1)) }}
            </div>
          @endif
          <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-green-400 rounded-full border-2 border-white flex items-center justify-center"><span class="text-[10px]">✓</span></div>
        </div>
        <div>
          <h1 class="text-2xl font-black">{{ trim(($admin->first_name ?? '').' '.($admin->last_name ?? '')) ?: $admin->name }}</h1>
          <p class="text-sm text-white/70 mt-0.5">{{ $admin->email }} • {{ $admin->role }}</p>
          <p class="text-xs text-white/50 mt-1">Admin since {{ $admin->created_at->format('M Y') }}</p>
        </div>
      </div>
    </div>

    <div class="p-8">
      @if(session('success'))<div class="mb-6 rounded-xl bg-[#e8f5e9] border border-[#c8e6c9] px-4 py-3 text-sm text-[#17611f]">{{ session('success') }}</div>@endif
      @if(session('error'))<div class="mb-6 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>@endif

      <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div>
          <label class="block text-sm font-bold text-[#1a2e1c] mb-3">Profile Picture</label>
          <div class="flex items-start gap-4">
            <div class="w-24 h-24 rounded-2xl bg-[#f4faf5] border-2 border-dashed border-[rgba(27,94,32,0.15)] flex items-center justify-center overflow-hidden">
              @if($admin->profile_picture)
                <img id="previewImg" src="{{ asset($admin->profile_picture) }}" class="w-full h-full object-cover">
              @else
                <img id="previewImg" src="" class="hidden w-full h-full object-cover">
                <span id="previewPlaceholder" class="text-2xl">📷</span>
              @endif
            </div>
            <div class="flex-1">
              <input type="file" name="profile_picture" id="profilePictureInput" accept=".jpg,.jpeg,.png,.webp" class="block w-full text-sm text-[#5a7a5c] file:mr-3 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-[#17611f] file:text-white hover:file:bg-[#14521a] file:cursor-pointer cursor-pointer">
              <p class="text-[11px] text-[#9e9e9e] mt-2">JPG, PNG, WEBP up to 2MB. Will be displayed in admin header.</p>
              <div id="fileNameDisplay" class="text-xs text-[#17611f] font-bold mt-1 hidden"></div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-bold text-[#1a2e1c] mb-2">First Name <span class="text-red-500">*</span></label>
            <input type="text" name="first_name" required value="{{ old('first_name', $admin->first_name) }}" placeholder="John" class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
          </div>
          <div>
            <label class="block text-sm font-bold text-[#1a2e1c] mb-2">Last Name <span class="text-red-500">*</span></label>
            <input type="text" name="last_name" required value="{{ old('last_name', $admin->last_name) }}" placeholder="Doe" class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
          </div>
        </div>

        <div>
          <label class="block text-sm font-bold text-[#1a2e1c] mb-2">Email Address <span class="text-red-500">*</span></label>
          <input type="email" name="email" required value="{{ old('email', $admin->email) }}" placeholder="admin@example.com" class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
          <p class="text-[11px] text-[#9e9e9e] mt-1">Used for login. Must be unique.</p>
        </div>

        <div class="border-t border-[rgba(27,94,32,0.08)] pt-6">
          <h3 class="font-black text-sm mb-1">Change Password</h3>
          <p class="text-xs text-[#5a7a5c] mb-4">Leave blank if you don't want to change password.</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-[#5a7a5c] mb-2">New Password</label>
              <input type="password" name="password" placeholder="••••••••" class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
              <p class="text-[10px] text-[#9e9e9e] mt-1">Min 8 chars, uppercase, lowercase, number, special char</p>
            </div>
            <div>
              <label class="block text-xs font-bold text-[#5a7a5c] mb-2">Confirm New Password</label>
              <input type="password" name="password_confirmation" placeholder="••••••••" class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
            </div>
          </div>
        </div>

        <div class="flex gap-3 pt-2">
          <button type="submit" class="px-6 py-3 rounded-xl bg-[#17611f] text-white text-sm font-black hover:bg-[#14521a] active:scale-[0.98] transition-all shadow-sm">Save Changes</button>
          <a href="{{ route('admin.dashboard') }}" class="px-6 py-3 rounded-xl border border-[rgba(27,94,32,0.12)] text-[#1a2e1c] text-sm font-bold hover:bg-[#f4faf5] transition-colors">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <div class="mt-6 bg-[#e8f5e9] border border-[#c8e6c9] rounded-2xl p-4">
    <p class="text-xs font-bold text-[#17611f]">🔒 Security Tips</p>
    <ul class="text-[11px] text-[#5a7a5c] mt-2 list-disc pl-4 space-y-1">
      <li>Use a strong password with mix of letters, numbers, symbols</li>
      <li>Never share your admin credentials</li>
      <li>Profile picture should be professional and less than 2MB</li>
      <li>Email change will be used for next login</li>
    </ul>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const input = document.getElementById('profilePictureInput');
  const preview = document.getElementById('previewImg');
  const placeholder = document.getElementById('previewPlaceholder');
  const fileName = document.getElementById('fileNameDisplay');
  if(input){
    input.addEventListener('change', function(){
      const file = this.files[0];
      if(!file) return;
      if(file.size > 2*1024*1024){ alert('File must be less than 2MB'); this.value=''; return; }
      const reader = new FileReader();
      reader.onload = e=>{
        preview.src = e.target.result;
        preview.classList.remove('hidden');
        if(placeholder) placeholder.classList.add('hidden');
        fileName.textContent = file.name + ' (' + (file.size/1024).toFixed(1) + ' KB) – Preview below, will save on submit';
        fileName.classList.remove('hidden');
      };
      reader.readAsDataURL(file);
    });
  }
});
</script>
@endpush
