@extends('admin.layouts.app')
@section('title', 'Promotions | Admin')
@section('header', 'Promotions')
@section('content')

  <h1 class="text-2xl font-black mb-1">Promo Codes</h1>
  <p class="text-sm text-[#5a7a5c] mb-6">Manage discount codes visible to customers</p>

  <!-- Create Promo Section -->
  <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-6 mb-6 shadow-sm">
    <h2 class="font-black text-sm mb-1 text-[#1a2e1c]">Create Promo Code</h2>
    <p class="text-xs text-[#5a7a5c] mb-4">Set expiration for claimed coupons – e.g., 7 days after claim. Expired claimed coupons are auto-removed from customer available coupons.</p>
    <form method="POST" action="{{ route('admin.promotions.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
      @csrf
      <input type="hidden" name="action" value="create">
      <input name="code" placeholder="CODE (e.g., FRESH10)" class="border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white" required>
      <input name="description" placeholder="Description" class="border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white">
      <select name="discount_type" class="border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white">
        <option value="percentage">Percentage</option>
        <option value="fixed">Fixed Amount</option>
      </select>
      <input name="discount_value" type="number" step="0.01" placeholder="Discount Value" class="border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white" required>
      <input name="min_order" type="number" step="0.01" placeholder="Min order (0=none)" value="0" class="border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white">
      <input name="expires_at" type="date" placeholder="Global Expiry" class="border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white">
      <div class="md:col-span-2">
        <label class="block text-[11px] font-bold text-[#5a7a5c] uppercase tracking-wider mb-1">Claimed Validity (days)</label>
        <input name="claimed_validity_days" type="number" min="1" max="365" placeholder="e.g., 7" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white">
        <p class="text-[10px] text-[#9e9e9e] mt-1">Days after claim before auto-expiry if not used. Leave empty for no auto-expiry.</p>
      </div>
      <div class="flex items-center gap-4 col-span-2 py-2">
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
            <th class="p-3 text-left">Claimed Validity</th>
            <th class="p-3 text-left">Global Expiry</th>
            <th class="p-3 text-left">Used</th>
            <th class="p-3 text-left">Status</th>
            <th class="p-3 text-left">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($promos as $p)
            <tr class="border-t border-[rgba(27,94,32,0.05)] hover:bg-gray-50/50 transition-colors">
              <td class="p-3 font-bold text-[#1a2e1c] text-sm">{{ $p->code }}</td>
              <td class="p-3 text-[#5a7a5c] font-medium max-w-[180px] truncate">{{ $p->description ?: '—' }}</td>
              <td class="p-3 font-semibold text-xs">
                {{ $p->discount_type === 'percentage' ? $p->discount_value.'%' : '₱'.number_format($p->discount_value, 2) }}
                {{ $p->is_free_delivery ? ' + Free Delivery' : '' }}
              </td>
              <td class="p-3 text-xs">
                @if($p->claimed_validity_days)
                  <span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-100 font-bold">{{ $p->claimed_validity_days }} days after claim</span>
                @else
                  <span class="text-[#9e9e9e]">No auto-expiry</span>
                @endif
              </td>
              <td class="p-3 text-xs font-medium">{{ $p->expires_at ? $p->expires_at->format('M j, Y') : 'No expiry' }}</td>
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
                  <form method="POST" action="{{ route('admin.promotions.store') }}" onsubmit="return confirm('Delete coupon? This will not affect already claimed but will prevent new claims.')" class="inline">
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
    <div class="bg-[#f4faf5] p-3 text-[11px] text-[#5a7a5c]">
      <strong>How claimed expiry works:</strong> When admin sets e.g., 7 days, the customer's claimed coupon gets expires_at = claimed_at + 7 days. Once expired and not used, it is automatically hidden from customer's available coupons and cannot be applied at checkout.
    </div>
  </div>

@endsection
