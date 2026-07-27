@extends('layouts.app')
@section('title','Submit Ticket')
@section('content')
<main class="flex-1 max-w-3xl w-full mx-auto px-6 py-16">
  <a href="{{ route('profile.index') }}" class="inline-flex items-center gap-2 text-sm text-[#17611f] mb-8">← Back to Dashboard</a>
  <div class="bg-white rounded-3xl border shadow-sm p-10">
    <span class="inline-block text-[11px] font-semibold tracking-wide text-[#17611f] bg-[#e8f5e9] rounded-full px-3 py-1 mb-5">QUICK SUPPORT</span>
    <h1 class="font-black text-3xl mb-4">Submit a Ticket</h1>
    <p class="text-[#5a7a5c] text-[15px] mb-6">Report an issue and our team will follow up.</p>
    @if(session('error'))<div class="rounded-xl bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700 mb-4">{{ session('error') }}</div>@endif
    <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="space-y-5">
      @csrf
      <div><label class="block text-sm font-medium mb-2">Subject</label><input type="text" name="subject" required value="{{ $formData['subject'] }}" class="w-full rounded-xl border px-4 py-3 text-sm"></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="text-sm font-medium">Category</label><select name="category" required class="w-full rounded-xl border px-4 py-3 text-sm mt-1">@foreach($categories as $cat)<option value="{{ $cat }}" {{ $formData['category']===$cat?'selected':'' }}>{{ $cat }}</option>@endforeach</select></div>
        <div><label class="text-sm font-medium">Priority</label><select name="priority" required class="w-full rounded-xl border px-4 py-3 text-sm mt-1">@foreach($priorities as $p)<option value="{{ $p }}" {{ $formData['priority']===$p?'selected':'' }}>{{ $p }}</option>@endforeach</select></div>
      </div>
      <div><label class="text-sm font-medium">Order Number (optional)</label><input type="text" name="order_number" value="{{ $formData['order_number'] }}" placeholder="LH-0000" class="w-full rounded-xl border px-4 py-3 text-sm"></div>
      <div><label class="text-sm font-medium">Issue Description</label><textarea name="issue_description" required rows="4" class="w-full rounded-xl border px-4 py-3 text-sm">{{ $formData['issue_description'] }}</textarea></div>
      <div><label class="text-sm font-medium">Attachment (optional)</label><input type="file" name="attachment[]" multiple accept=".jpg,.jpeg,.png,.pdf" class="w-full border rounded-xl p-2 text-sm"></div>
      <button type="submit" class="w-full py-3 rounded-xl bg-[#17611f] text-white font-bold">Submit Ticket</button>
    </form>
  </div>
</main>
@endsection
