@extends('admin.layouts.app')
@section('title', 'FAQ Management')
@section('header', 'FAQ Management')
@section('content')

@php
  $categoryColors = [
      'Freshness' => 'bg-[#e8f5e9] text-[#17611f]',
      'Orders' => 'bg-gray-100 text-[#5a7a5c]',
      'Care' => 'bg-green-50 text-green-600',
      'Delivery' => 'bg-[#fff8e1] text-amber-600',
      'Returns' => 'bg-blue-50 text-blue-600',
      'Quality' => 'bg-red-50 text-red-500',
      'Payment' => 'bg-purple-50 text-purple-600',
      'Account' => 'bg-indigo-50 text-indigo-600',
      'Products' => 'bg-lime-50 text-lime-700',
      'Technical Support' => 'bg-sky-50 text-sky-600',
      'General' => 'bg-gray-100 text-[#5a7a5c]',
  ];

  $selectedCategory = old('category', $editFaq->category ?? 'General');
@endphp

<div class="space-y-6">
  {{-- Add / Edit FAQ Form --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
    <h3 class="text-sm font-semibold text-[#1a2e1c] mb-5">
      {{ $editFaq ? 'Edit FAQ' : 'Add a New FAQ' }}
    </h3>

    <form method="POST"
          action="{{ $editFaq ? route('admin.faqs.update', $editFaq->id) : route('admin.faqs.store') }}"
          class="space-y-5">
      @csrf
      @if ($editFaq)
        @method('PUT')
      @endif

      <div>
        <label for="question" class="block text-sm font-medium text-[#1a2e1c] mb-2">Question</label>
        <input id="question"
               type="text"
               name="question"
               required
               value="{{ old('question', $editFaq->question ?? '') }}"
               placeholder="e.g. What is the return policy?"
               class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
        @error('question')
          <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
      </div>

      <div>
        <label for="answer" class="block text-sm font-medium text-[#1a2e1c] mb-2">Answer</label>
        <textarea id="answer"
                  name="answer"
                  rows="4"
                  required
                  placeholder="Write the answer customers will see..."
                  class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors">{{ old('answer', $editFaq->answer ?? '') }}</textarea>
        @error('answer')
          <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
      </div>

      <div class="max-w-xs">
        <label for="category" class="block text-sm font-medium text-[#1a2e1c] mb-2">Category</label>
        <select id="category"
                name="category"
                class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors bg-white">
          @foreach ($categories as $cat)
            <option value="{{ $cat }}" {{ $selectedCategory === $cat ? 'selected' : '' }}>{{ $cat }}</option>
          @endforeach
        </select>
        @error('category')
          <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
      </div>

      <div class="flex items-center gap-3">
        <button type="submit" class="px-7 py-3 rounded-full bg-[#17611f] text-white text-sm font-semibold hover:bg-[#14521a] transition-colors shadow-sm">
          {{ $editFaq ? 'Save Changes' : 'Add FAQ' }}
        </button>

        @if ($editFaq)
          <a href="{{ route('admin.faqs.index') }}" class="px-7 py-3 rounded-full border border-gray-300 text-[#1a2e1c] text-sm font-semibold hover:bg-gray-100 transition-colors">
            Cancel
          </a>
        @endif
      </div>
    </form>
  </div>

  {{-- FAQ List --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
      <h3 class="text-sm font-semibold text-[#1a2e1c]">All FAQs</h3>
      <span class="text-[12px] text-[#9e9e9e]">{{ $totalFaqs }} total</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full">
        <thead>
          <tr class="bg-gray-50 text-[11px] uppercase tracking-wide text-[#9e9e9e]">
            <th class="text-left font-medium py-3 px-4">Question</th>
            <th class="text-left font-medium py-3 px-4">Category</th>
            <th class="text-left font-medium py-3 px-4">Last Updated</th>
            <th class="text-left font-medium py-3 px-4">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($faqs as $faq)
            @php
              $category = $faq->category ?: 'General';
              $badgeClass = $categoryColors[$category] ?? 'bg-gray-100 text-[#5a7a5c]';
              $updatedAt = $faq->updated_at ?? $faq->created_at;
            @endphp

            <tr class="border-b border-gray-100 last:border-0">
              <td class="py-4 px-4 text-[13px] font-medium text-[#1a2e1c] max-w-xl">
                {{ $faq->question }}
              </td>
              <td class="py-4 px-4">
                <span class="inline-block text-[11px] font-medium {{ $badgeClass }} rounded-full px-3 py-1">
                  {{ $category }}
                </span>
              </td>
              <td class="py-4 px-4 text-[13px] text-[#5a7a5c] whitespace-nowrap">
                {{ optional($updatedAt)->format('M j, Y') }}
              </td>
              <td class="py-4 px-4">
                <div class="flex gap-2">
                  <a href="{{ route('admin.faqs.index', ['edit' => $faq->id]) }}"
                     class="text-[11px] font-medium border border-[rgba(27,94,32,0.12)] rounded-full px-3 py-1 text-[#5a7a5c] hover:bg-gray-50 transition-colors">
                    Edit
                  </a>

                  <form method="POST"
                        action="{{ route('admin.faqs.destroy', $faq->id) }}"
                        onsubmit="return confirm('Delete this FAQ? This cannot be undone.');"
                        class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-[11px] font-medium border border-red-200 rounded-full px-3 py-1 text-red-500 hover:bg-red-50 transition-colors">
                      Delete
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="py-10 px-4 text-center text-sm text-[#9e9e9e]">
                No FAQs yet — add your first one above.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@endsection
