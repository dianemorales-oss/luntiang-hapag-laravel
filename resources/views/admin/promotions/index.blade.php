@extends('admin.layouts.app')
@section('title', 'Promotions | Admin')
@section('header', 'Promotions')
@section('content')

  <h1 class="text-2xl font-black mb-1">Promo Codes</h1>
  <p class="text-sm text-[#5a7a5c] mb-6">Manage discount codes visible to customers</p>

  <!-- Create Promo Section -->
  <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-5 mb-6 shadow-sm">
    <h2 class="font-black text-sm mb-3 text-[#1a2e1c]">Create Promo Code</h2>
    <form method="POST" action="{{ route('admin.promotions.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
      @csrf
      <input type="hidden" name="action" value="create">
      <input name="code" placeholder="CODE" class="border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white" required>
      <input name="description" placeholder="Description" class="border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white">
      <select name="discount_type" class="border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white">
        <option value="percentage">Percentage</option>
        <option value="fixed">Fixed Amount</option>
      </select>
      <input name="discount_value" type="number" step="0.01" placeholder="Discount Value" class="border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white" required>
      <input name="min_order" type="number" step="0.01" placeholder="Min order (0=none)" value="0" class="border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white">
      <div class="flex items-center gap-4 col-span-2 py-1">
        <label class="flex items-center gap-2 text-sm text-[#1a2e1c] font-semibold cursor-pointer">
          <input type="checkbox" name="is_active" checked class="rounded border-gray-300 text-[#17611f] focus:ring-[#17611f]/40"> Active
        </label>
        <label class="flex items-center gap-2 text-sm text-[#1a2e1c] font-semibold cursor-pointer">
          <input type="checkbox" name="is_free_delivery" class="rounded border-gray-300 text-[#17611f] focus:ring-[#17611f]/40"> Free Delivery
        </label>
      </div>
      <button type="submit" class="col-span-full px-5 py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold shadow-sm hover:bg-[#14521a] transition-all">Create Promo</button>
    </form>
  </div>

  <!-- Promotions List -->
  <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-[#f4faf5] text-[#5a7a5c] text-xs uppercase border-b">
            <th class="p-3 text-left">Code</th>
            <th class="p-3 text-left">Description</th>
            <th class="p-3 text-left">Type/Value</th>
            <th class="p-3 text-left">Used</th>
            <th class="p-3 text-left">Status</th>
            <th class="p-3 text-left">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($promos as $p)
            <tr class="border-t border-[rgba(27,94,32,0.05)] hover:bg-gray-50/50 transition-colors">
              <td class="p-3 font-bold text-[#1a2e1c] text-sm">{{ $p->code }}</td>
              <td class="p-3 text-[#5a7a5c] font-medium">{{ $p->description ?: '—' }}</td>
              <td class="p-3 font-semibold">
                {{ $p->discount_type === 'percentage' ? $p->discount_value.'%' : '₱'.number_format($p->discount_value, 2) }}
                {{ $p->is_free_delivery ? ' + Free Delivery' : '' }}
              </td>
              <td class="p-3 font-semibold">{{ $p->used_count }}</td>
              <td class="p-3">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $p->is_active ? 'bg-[#e8f5e9] text-[#17611f]' : 'bg-gray-100 text-[#9e9e9e]' }}">
                  {{ $p->is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="p-3">
                <div class="flex gap-3">
                  <form method="POST" action="{{ route('admin.promotions.store') }}" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="{{ $p->id }}">
                    <button type="submit" class="text-xs text-[#17611f] font-bold hover:underline">
                      {{ $p->is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                  </form>
                  <form method="POST" action="{{ route('admin.promotions.store') }}" onsubmit="return confirm('Delete coupon?')" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="{{ $p->id }}">
                    <button type="submit" class="text-xs text-red-500 font-bold hover:underline">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

@endsection
