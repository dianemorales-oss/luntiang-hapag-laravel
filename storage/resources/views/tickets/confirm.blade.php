@extends('layouts.app')
@section('title','Confirm Ticket')
@section('content')
<main class="max-w-2xl mx-auto px-6 py-10">
  <div class="bg-white rounded-3xl shadow-xl p-8">
    <h2 class="font-black text-2xl mb-4">Review Your Ticket</h2>
    <div class="space-y-3">
      <div class="bg-[#f8f6f2] rounded-xl p-3"><p class="text-xs text-gray-400">SUBJECT</p><p class="font-bold">{{ $submittedData['subject'] }}</p></div>
      <div class="grid grid-cols-2 gap-3">
        <div class="bg-[#f8f6f2] rounded-xl p-3"><p class="text-xs text-gray-400">CATEGORY</p><p>{{ $submittedData['category'] }}</p></div>
        <div class="bg-[#f8f6f2] rounded-xl p-3"><p class="text-xs text-gray-400">PRIORITY</p><p>{{ $submittedData['priority'] }}</p></div>
      </div>
      <div class="bg-[#f8f6f2] rounded-xl p-3"><p class="text-xs text-gray-400">DESCRIPTION</p><p>{{ $submittedData['issue_description'] }}</p></div>
      @if(!empty($submittedData['attachment_names']))
        <div class="bg-[#f8f6f2] rounded-xl p-3"><p class="text-xs text-gray-400">ATTACHMENTS</p>@foreach($submittedData['attachment_names'] as $n)<p class="text-sm text-[#17611f]">📎 {{ $n }}</p>@endforeach</div>
      @endif
    </div>
    <div class="flex gap-3 mt-6">
      <form method="POST" action="{{ route('tickets.store') }}">
        @csrf
        <input type="hidden" name="confirm_submit" value="1">
        <button type="submit" class="px-6 py-3 rounded-full bg-[#17611f] text-white font-bold">Confirm & Submit</button>
      </form>
      <a href="{{ route('tickets.create') }}" class="px-6 py-3 rounded-full border font-bold">Edit Details</a>
    </div>
  </div>
</main>
@endsection
