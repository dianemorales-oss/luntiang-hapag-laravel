@extends('layouts.app')
@section('title','Review Your Ticket')
@section('content')

@push('styles')
<style>
  body { background: #F3F0E4 !important; }
  .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem; animation: fadeIn 0.3s ease; }
  .modal-content { max-width: 560px; width: 100%; max-height: 90vh; overflow-y: auto; animation: slideUp 0.3s ease; }
  @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
  @keyframes slideUp { from { transform: translateY(20px); opacity:0; } to { transform: translateY(0); opacity:1; } }
  .confirm-field { background: #f8f6f2; border-radius: 0.75rem; padding: 0.75rem 1rem; border: 1px solid rgba(27,94,32,0.06); }
  .confirm-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; display: block; margin-bottom: 0.25rem; }
  .confirm-value { color: #1f2937; font-size: 0.95rem; word-wrap: break-word; font-weight: 600; }
</style>
@endpush

<main class="flex-1 max-w-3xl w-full mx-auto px-6 py-12">
  <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-sm text-[#17611f] hover:text-[#14521a] font-bold mb-8">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    Back to Home
  </a>

  <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-10 opacity-50 pointer-events-none">
    <span class="inline-block text-[11px] font-black tracking-wide text-[#17611f] bg-[#e8f5e9] rounded-full px-3 py-1 mb-5">QUICK SUPPORT</span>
    <h1 class="font-black text-3xl text-[#1a2e1c] mb-3">Submit a Ticket</h1>
    <p class="text-[#5a7a5c] text-[15px]">Report an issue with your order or product and our team will follow up with you shortly.</p>
  </div>
</main>

<div class="modal-overlay" id="confirmationModal">
  <div class="modal-content bg-white rounded-3xl shadow-2xl p-8 border border-[rgba(27,94,32,0.08)]">
    <div class="flex items-start justify-between mb-6">
      <div>
        <span class="inline-block text-[11px] font-black tracking-wide text-[#17611f] bg-[#e8f5e9] rounded-full px-3 py-1 mb-3">REVIEW & CONFIRM</span>
        <h2 class="font-black text-2xl text-[#1a2e1c]">Review Your Ticket</h2>
        <p class="text-[#5a7a5c] text-sm mt-1">Please verify all details before submitting.</p>
      </div>
      <a href="{{ route('tickets.create') }}" class="text-gray-400 hover:text-gray-600 transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100">✕</a>
    </div>

    <div class="space-y-4">
      <div class="confirm-field">
        <span class="confirm-label">Subject</span>
        <p class="confirm-value">{{ $submittedData['subject'] }}</p>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div class="confirm-field">
          <span class="confirm-label">Category</span>
          <p class="confirm-value">{{ $submittedData['category'] }}</p>
        </div>
        <div class="confirm-field">
          <span class="confirm-label">Priority</span>
          <p class="confirm-value">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold
              {{ $submittedData['priority']==='High' ? 'bg-red-100 text-red-700' : ($submittedData['priority']==='Medium' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700') }}">
              {{ $submittedData['priority'] }}
            </span>
          </p>
        </div>
      </div>

      @if(!empty($submittedData['order_number']))
        <div class="confirm-field">
          <span class="confirm-label">Order Number</span>
          <p class="confirm-value font-mono">{{ $submittedData['order_number'] }}</p>
        </div>
      @endif

      <div class="confirm-field">
        <span class="confirm-label">Issue Description</span>
        <p class="confirm-value whitespace-pre-line text-sm font-normal">{{ $submittedData['issue_description'] }}</p>
      </div>

      @if(!empty($submittedData['attachment_names']))
        <div class="confirm-field">
          <span class="confirm-label">Attachment{{ count($submittedData['attachment_names'])>1 ? 's' : '' }}</span>
          @foreach($submittedData['attachment_names'] as $n)
            <p class="confirm-value text-[#17611f] text-sm font-bold mt-1 flex items-center gap-1.5">📎 {{ $n }}</p>
          @endforeach
        </div>
      @endif
    </div>

    <div class="flex flex-wrap gap-3 mt-6 pt-6 border-t border-gray-100">
      <form method="POST" action="{{ route('tickets.store') }}" class="inline">
        @csrf
        <input type="hidden" name="confirm_submit" value="1">
        <button type="submit" class="px-6 py-3 rounded-full bg-[#17611f] text-white text-sm font-black hover:bg-[#14521a] active:scale-[0.98] transition-all flex items-center gap-2 shadow-sm">
          <span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center text-xs">✓</span>
          Confirm & Submit
        </button>
      </form>
      <a href="{{ route('tickets.create') }}" class="px-6 py-3 rounded-full border border-gray-300 text-gray-700 text-sm font-bold hover:bg-gray-50 transition-colors flex items-center gap-2">
        ← Edit Details
      </a>
    </div>

    <p class="text-[11px] text-[#9e9e9e] mt-5 leading-relaxed bg-[#f4faf5] rounded-xl p-3 border border-[rgba(27,94,32,0.06)]">
      By submitting, you agree to our <a href="{{ route('terms') }}" class="text-[#17611f] font-bold hover:underline">terms of service</a>. Our team will respond within 24-48 hours. You can track updates through My Support Tickets.
    </p>
  </div>
</div>

@endsection
