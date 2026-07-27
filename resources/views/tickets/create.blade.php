@extends('layouts.app')
@section('title','Submit a Ticket | Luntiang H.A.P.A.G.')
@section('content')

@push('styles')
<style>
  body { background: #F3F0E4 !important; }
  .font-serif { font-family: 'Fraunces', 'Nunito', serif; }
  .modal-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center;
    z-index: 1000; padding: 1rem;
    animation: fadeIn 0.3s ease;
  }
  .modal-content { max-width: 560px; width: 100%; max-height: 90vh; overflow-y: auto; animation: slideUp 0.3s ease; }
  @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
  @keyframes slideUp { from { transform: translateY(20px); opacity:0; } to { transform: translateY(0); opacity:1; } }
  .confirm-field { background: #f8f6f2; border-radius: 0.75rem; padding: 0.75rem 1rem; }
  .confirm-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; display: block; margin-bottom: 0.25rem; }
  .confirm-value { color: #1f2937; font-size: 0.95rem; word-wrap: break-word; }
  .upload-area { border: 2px dashed #e5e7eb; border-radius: 0.75rem; padding: 1rem; transition: all 0.2s; }
  .upload-area:hover { border-color: #17611f; background: #f4faf5; }
</style>
@endpush

<main class="flex-1 max-w-3xl w-full mx-auto px-6 py-12">
  <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-sm text-[#17611f] hover:text-[#14521a] transition-colors mb-8 font-bold">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    Back to Home
  </a>

  <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8 sm:p-10">
    <span class="inline-block text-[11px] font-black tracking-wide text-[#17611f] bg-[#e8f5e9] rounded-full px-3 py-1 mb-5">QUICK SUPPORT</span>
    <h1 class="font-black text-3xl text-[#1a2e1c] mb-3">Submit a Ticket</h1>
    <p class="text-[#5a7a5c] text-[15px] leading-relaxed mb-2">Report an issue with your order or product and our team will follow up with you shortly.</p>
    <p class="text-xs text-[#9e9e9e] mb-6">Our support team typically responds within 24-48 business hours. You can track updates through My Support Tickets.</p>

    @if(session('error'))
      <div class="mb-6 rounded-xl bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <form id="ticketForm" class="space-y-5" method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" novalidate>
      @csrf
      <div>
        <label class="block text-sm font-bold text-[#1a2e1c] mb-2">Subject <span class="text-red-500">*</span></label>
        <input type="text" name="subject" required placeholder="Brief summary of your issue" value="{{ $formData['subject'] }}"
               class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#17611f]/30 focus:border-[#17611f] transition-colors" />
      </div>

      <div>
        <label class="block text-sm font-bold text-[#1a2e1c] mb-2">Category <span class="text-red-500">*</span></label>
        <select name="category" required class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#17611f]/30 focus:border-[#17611f] transition-colors">
          <option value="" disabled {{ empty($formData['category']) ? 'selected' : '' }}>Select a category</option>
          @foreach($categories as $cat)
            <option value="{{ $cat }}" {{ $formData['category']===$cat?'selected':'' }}>{{ $cat }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-sm font-bold text-[#1a2e1c] mb-2">Priority <span class="text-red-500">*</span></label>
        <select name="priority" required class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#17611f]/30 focus:border-[#17611f] transition-colors">
          @foreach($priorities as $p)
            <option value="{{ $p }}" {{ $formData['priority']===$p?'selected':'' }}>{{ $p }} Priority</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-sm font-bold text-[#1a2e1c] mb-2">Order Number <span class="text-[#9e9e9e] font-normal">(optional)</span></label>
        <input type="text" name="order_number" maxlength="7" placeholder="LH-0000" value="{{ $formData['order_number'] }}"
               class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#17611f]/30 focus:border-[#17611f] transition-colors uppercase" />
        <p class="mt-1.5 text-[11px] text-[#9e9e9e]">Format: LH-XXXX (e.g., LH-0001)</p>
      </div>

      <div>
        <label class="block text-sm font-bold text-[#1a2e1c] mb-2">Describe the Issue <span class="text-red-500">*</span></label>
        <textarea name="issue_description" id="issueDescription" required rows="5" maxlength="1000" placeholder="Please describe your issue in detail..." class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#17611f]/30 focus:border-[#17611f] transition-colors resize-y">{{ $formData['issue_description'] }}</textarea>
        <div class="flex justify-between mt-1.5">
          <p class="text-[11px] text-[#9e9e9e]">Be as detailed as possible for faster resolution</p>
          <p class="text-[11px] text-[#9e9e9e]"><span id="charCount">{{ mb_strlen($formData['issue_description']) }}</span> / 1000 characters</p>
        </div>
      </div>

      <div>
        <label class="block text-sm font-bold text-[#1a2e1c] mb-2">Attachment <span class="text-[#9e9e9e] font-normal">(optional)</span></label>
        <div class="upload-area">
          <input type="file" name="attachment[]" id="attachmentInput" multiple accept=".jpg,.jpeg,.png,.pdf" class="hidden">
          <div class="flex items-center gap-3">
            <button type="button" id="uploadBtn" class="px-4 py-2 rounded-full bg-white border border-gray-300 text-xs font-bold text-[#1a2e1c] hover:bg-[#f4faf5] transition-colors flex items-center gap-1.5">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
              Upload Files
            </button>
            <span id="fileChosenText" class="text-xs text-[#9e9e9e]">No file chosen</span>
          </div>
          <div id="filePreviewList" class="mt-3 space-y-1.5 hidden"></div>
        </div>
        <p class="mt-1.5 text-[11px] text-[#9e9e9e]">JPG, JPEG, PNG, or PDF. You can attach multiple files — 5 MB total combined.</p>
      </div>

      <div class="bg-[#f4faf5] border border-[rgba(27,94,32,0.08)] rounded-xl p-4 flex gap-3">
        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-[#e8f5e9] flex items-center justify-center"><span class="text-sm">💡</span></div>
        <div>
          <p class="text-xs font-bold text-[#1a2e1c]">What happens next?</p>
          <p class="text-[11px] text-[#5a7a5c] mt-1 leading-relaxed">Our support team typically responds within 24-48 business hours. You can track updates through <a href="{{ route('profile.index') }}?section=support" class="text-[#17611f] font-bold hover:underline">My Support Tickets</a>.</p>
        </div>
      </div>

      <button type="submit" class="w-full py-3.5 rounded-full bg-[#17611f] text-white text-sm font-black hover:bg-[#14521a] active:scale-[0.98] transition-all shadow-sm flex items-center justify-center gap-2">
        <span>Submit Support Ticket</span>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
      </button>
    </form>
  </div>
</main>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const textarea = document.getElementById('issueDescription');
  const charCount = document.getElementById('charCount');
  const fileInput = document.getElementById('attachmentInput');
  const uploadBtn = document.getElementById('uploadBtn');
  const fileChosen = document.getElementById('fileChosenText');
  const previewList = document.getElementById('filePreviewList');
  const form = document.getElementById('ticketForm');
  const orderInput = document.querySelector('input[name="order_number"]');

  if(textarea && charCount){
    const updateCount = ()=>{ charCount.textContent = textarea.value.length; charCount.classList.toggle('text-red-500', textarea.value.length > 900); };
    textarea.addEventListener('input', updateCount);
    updateCount();
  }

  if(orderInput){
    orderInput.addEventListener('input', function(){
      this.value = this.value.toUpperCase().replace(/[^A-Z0-9\-]/g,'').slice(0,7);
      if(this.value.length>0 && !this.value.startsWith('LH-') && this.value.length>=2){
        if(/^\d/.test(this.value)){ this.value = 'LH-' + this.value.replace(/[^0-9]/g,''); }
      }
    });
  }

  if(uploadBtn && fileInput){
    uploadBtn.addEventListener('click', ()=> fileInput.click());
    fileInput.addEventListener('change', function(){
      const files = Array.from(this.files);
      if(files.length===0){ fileChosen.textContent='No file chosen'; previewList.classList.add('hidden'); previewList.innerHTML=''; return; }
      let totalSize = files.reduce((acc,f)=>acc+f.size,0);
      fileChosen.textContent = files.length + ' file(s) chosen (' + (totalSize/1024).toFixed(1) + ' KB)';
      previewList.innerHTML='';
      files.forEach((file, idx)=>{
        const div=document.createElement('div');
        div.className='flex items-center gap-2 text-xs bg-white border rounded-lg px-3 py-2';
        const icon = file.type.includes('pdf') ? '📄' : '🖼️';
        div.innerHTML=`<span>${icon}</span><span class="flex-1 truncate font-medium">${file.name}</span><span class="text-[10px] text-[#9e9e9e]">${(file.size/1024).toFixed(1)} KB</span><button type="button" data-idx="${idx}" class="ml-auto text-red-500 hover:text-red-700 font-bold">✕</button>`;
        previewList.appendChild(div);
      });
      previewList.classList.remove('hidden');
      // Remove individual
      previewList.querySelectorAll('button[data-idx]').forEach(btn=>{
        btn.addEventListener('click', function(){
          const idx=parseInt(this.dataset.idx);
          const dt=new DataTransfer();
          Array.from(fileInput.files).forEach((f,i)=>{ if(i!==idx) dt.items.add(f); });
          fileInput.files=dt.files;
          fileInput.dispatchEvent(new Event('change'));
        });
      });
      if(totalSize > 5*1024*1024){
        fileChosen.textContent += ' – Exceeds 5MB total!';
        fileChosen.classList.add('text-red-500');
      } else {
        fileChosen.classList.remove('text-red-500');
      }
    });
  }

  if(form){
    form.addEventListener('submit', function(e){
      const orderVal=document.querySelector('input[name="order_number"]').value.trim();
      if(orderVal!=='' && !/^LH-\d{4}$/.test(orderVal)){
        e.preventDefault();
        alert('Order Number format must be LH-XXXX (e.g., LH-0001). Leave empty if not applicable.');
        return;
      }
    });
  }
});
</script>
@endpush
@endsection
