@extends('admin.layouts.app')
@section('title', 'Return & Refund Requests | Admin')
@section('header', 'Return & Refund Requests')
@section('content')

  @php
    function returnBadge($status)
    {
        $map = [
            'pending' => ['amber', 'Pending'],
            'approved' => ['green', 'Approved'],
            'denied' => ['red', 'Denied'],
            'completed' => ['blue', 'Completed']
        ];
        [$color, $label] = $map[$status] ?? ['gray', ucfirst($status)];
        $colors = [
            'amber' => 'text-amber-600 bg-amber-500', 
            'green' => 'text-green-600 bg-green-500', 
            'red' => 'text-red-600 bg-red-400', 
            'blue' => 'text-blue-600 bg-blue-500', 
            'gray' => 'text-gray-400 bg-gray-400'
        ];
        [$textColor, $dotColor] = explode(' ', $colors[$color]);
        return "<span class=\"inline-flex items-center gap-1.5 text-[13px] font-bold $textColor\"><span class=\"w-1.5 h-1.5 rounded-full $dotColor\"></span>$label</span>";
    }
  @endphp

  <!-- Status Filter Tabs -->
  <div class="flex flex-wrap items-center gap-2 mb-6">
    <a href="{{ route('admin.returns.index') }}" class="px-4 py-2 rounded-full text-[13px] font-bold {{ $statusFilter === 'all' ? 'bg-[#17611f] text-white shadow-sm' : 'bg-white border border-[rgba(27,94,32,0.12)] text-[#5a7a5c] hover:bg-gray-50' }}">All <span class="opacity-70">({{ $totalCount }})</span></a>
    @foreach (['pending', 'approved', 'denied', 'completed'] as $s)
      <a href="?status={{ $s }}" class="px-4 py-2 rounded-full text-[13px] font-bold {{ $statusFilter === $s ? 'bg-[#17611f] text-white shadow-sm' : 'bg-white border border-[rgba(27,94,32,0.12)] text-[#5a7a5c] hover:bg-gray-50' }}">{{ ucfirst($s) }} <span class="opacity-70">({{ $statusCounts[$s] ?? 0 }})</span></a>
    @endforeach
  </div>

  <!-- Requests List -->
  <div class="grid grid-cols-1 gap-4">
    @if ($requests->isEmpty())
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center text-sm text-[#9e9e9e] font-semibold">No return requests found.</div>
    @else
      @foreach ($requests as $r)
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
          <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-3 mb-2">
                <p class="text-[12px] text-[#9e9e9e] font-semibold">Request #{{ $r->id }} · {{ $r->created_at->format('M j, Y') }}</p>
                {!! returnBadge($r->status) !!}
              </div>
              <h3 class="font-bold text-[#1a2e1c] mb-1 text-sm">Order #{{ $r->order_number }}{{ !empty($r->product_name) ? ' · ' . $r->product_name : '' }}</h3>
              <p class="text-[13px] text-[#5a7a5c] mb-1">
                @if (!empty($r->reason_category))
                  Reason: <span class="font-semibold text-[#1a2e1c]">{{ $r->reason_category }}</span> ·
                @endif
                @if (!empty($r->product_condition))
                  Condition: <span class="font-semibold text-[#1a2e1c]">{{ $r->product_condition }}</span> ·
                @endif
                Purchased: <span class="font-semibold text-[#1a2e1c]">{{ !empty($r->purchase_date) ? date('M j, Y', strtotime($r->purchase_date)) : '—' }}</span>
              </p>
              <p class="text-[13px] text-[#5a7a5c] mb-3 leading-relaxed">{{ $r->reason }}</p>
              
              @php
                $proofAttachments = $r->proof_of_purchase_path ? json_decode($r->proof_of_purchase_path, true) : [];
                $damageAttachments = $r->damage_photo_path ? json_decode($r->damage_photo_path, true) : [];
              @endphp
              @if (!empty($proofAttachments) || !empty($damageAttachments))
                <div class="flex flex-wrap gap-4 mb-3">
                  @foreach ($proofAttachments as $idx => $path)
                    <a href="{{ asset($path) }}" target="_blank" rel="noopener" class="text-[12px] font-bold text-[#17611f] hover:underline">View Proof of Purchase{{ count($proofAttachments) > 1 ? ' ' . ($idx + 1) : '' }}</a>
                  @endforeach
                  @foreach ($damageAttachments as $idx => $path)
                    <a href="{{ asset($path) }}" target="_blank" rel="noopener" class="text-[12px] font-bold text-[#17611f] hover:underline">View Damage Photo{{ count($damageAttachments) > 1 ? ' ' . ($idx + 1) : '' }}</a>
                  @endforeach
                </div>
              @endif
              <p class="text-[12px] text-[#5a7a5c] font-medium">
                Customer: <span class="font-bold text-[#1a2e1c]">{{ $r->user->first_name ?? 'Anonymous' }} {{ $r->user->last_name ?? '' }}</span> · 
                {{ $r->user->email ?? '—' }}
                @if (!empty($r->user->address))
                  <br><span class="text-[#5a7a5c]">Address: </span><span class="font-bold text-[#1a2e1c] whitespace-pre-line">{{ $r->user->address }}</span>
                @endif
              </p>
            </div>

            <!-- Update Form -->
            <form method="POST" action="{{ route('admin.returns.update', $r->id) }}" class="flex-shrink-0 w-full md:w-80 space-y-2">
              @csrf
              @method('PUT')
              <label class="block text-[12px] font-bold text-[#5a7a5c]">Admin Note <span class="text-[#9e9e9e] font-normal">(visible to customer)</span></label>
              <textarea name="admin_note" rows="3" data-return-note placeholder="e.g. Your refund has been approved." class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-3 py-2 text-[13px] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors resize-none bg-white">{{ $r->admin_note }}</textarea>
              <p class="text-[11px] text-[#9e9e9e]">Changing the status below fills this in automatically — feel free to edit it before saving.</p>
              <div class="flex items-center gap-2">
                <select name="new_status" data-return-status-select class="flex-1 rounded-full border border-[rgba(27,94,32,0.12)] px-3 py-2 text-[13px] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white text-[#1a2e1c] font-bold">
                  <option value="pending" data-note="{{ \App\Helpers\AdminNoteHelper::defaultAdminNote('return', 'pending') }}" {{ $r->status === 'pending' ? 'selected' : '' }}>Pending</option>
                  <option value="approved" data-note="{{ \App\Helpers\AdminNoteHelper::defaultAdminNote('return', 'approved') }}" {{ $r->status === 'approved' ? 'selected' : '' }}>Approve</option>
                  <option value="denied" data-note="{{ \App\Helpers\AdminNoteHelper::defaultAdminNote('return', 'denied') }}" {{ $r->status === 'denied' ? 'selected' : '' }}>Deny</option>
                  <option value="completed" data-note="{{ \App\Helpers\AdminNoteHelper::defaultAdminNote('return', 'completed') }}" {{ $r->status === 'completed' ? 'selected' : '' }}>Mark Completed</option>
                </select>
                <button type="submit" class="px-4 py-2 rounded-full bg-[#17611f] text-white text-[13px] font-bold hover:bg-[#14521a] transition-all shadow-sm">Save</button>
              </div>
              @if (!empty($r->updated_at))
                <p class="text-[11px] text-[#9e9e9e] font-semibold mt-1">Last updated {{ $r->updated_at->format('M j, Y g:i A') }}</p>
              @endif
            </form>
          </div>
        </div>
      @endforeach
    @endif
  </div>

@endsection

@push('scripts')
  <script>
    document.querySelectorAll('[data-return-status-select]').forEach(function (select) {
      select.addEventListener('change', function () {
        var form = select.closest('form');
        var textarea = form ? form.querySelector('[data-return-note]') : null;
        var option = select.options[select.selectedIndex];
        if (textarea && option && option.dataset.note) {
          textarea.value = option.dataset.note;
        }
      });
    });
  </script>
@endpush
