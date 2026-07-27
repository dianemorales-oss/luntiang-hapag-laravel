@extends('admin.layouts.app')
@section('title', 'Support Tickets | Admin')
@section('header', 'Support Tickets')
@section('content')

  @php
    function statusBadgeTickets($status)
    {
        $map = [
            'open' => ['blue', 'Open'],
            'in_progress' => ['amber', 'In Progress'],
            'resolved' => ['green', 'Resolved'],
            'closed' => ['gray', 'Closed'],
        ];
        [$color, $label] = $map[$status] ?? ['gray', ucfirst($status)];
        $colors = [
            'blue' => 'text-blue-600 bg-blue-500', 
            'amber' => 'text-amber-600 bg-amber-500', 
            'green' => 'text-green-600 bg-green-500', 
            'gray' => 'text-gray-400 bg-gray-400'
        ];
        [$textColor, $dotColor] = explode(' ', $colors[$color]);
        return "<span class=\"inline-flex items-center gap-1.5 text-[13px] font-bold $textColor\"><span class=\"w-1.5 h-1.5 rounded-full $dotColor\"></span>$label</span>";
    }

    function priorityBadgeTickets($priority)
    {
        $colors = [
            'Low' => 'text-[#5a7a5c] bg-gray-100', 
            'Medium' => 'text-amber-600 bg-amber-50', 
            'High' => 'text-red-600 bg-red-50'
        ];
        $classes = $colors[$priority] ?? 'text-[#5a7a5c] bg-gray-100';
        return "<span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-[12px] font-bold $classes\">" . e($priority) . "</span>";
    }
  @endphp

  <!-- Status Filter Tabs & Search -->
  <div class="flex flex-wrap items-center gap-2 mb-6">
    <a href="{{ route('admin.tickets.index') }}" class="px-4 py-2 rounded-full text-[13px] font-bold {{ $statusFilter === 'all' ? 'bg-[#17611f] text-white shadow-sm' : 'bg-white border border-[rgba(27,94,32,0.12)] text-[#5a7a5c] hover:bg-gray-50' }}">All <span class="opacity-70">({{ $totalCount }})</span></a>
    @foreach (['open', 'in_progress', 'resolved', 'closed'] as $s)
      <a href="?status={{ $s }}" class="px-4 py-2 rounded-full text-[13px] font-bold {{ $statusFilter === $s ? 'bg-[#17611f] text-white shadow-sm' : 'bg-white border border-[rgba(27,94,32,0.12)] text-[#5a7a5c] hover:bg-gray-50' }}">{{ ucwords(str_replace('_', ' ', $s)) }} <span class="opacity-70">({{ $statusCounts[$s] ?? 0 }})</span></a>
    @endforeach

    <form method="GET" action="{{ route('admin.tickets.index') }}" class="ml-auto flex items-center gap-2">
      <input type="hidden" name="status" value="{{ $statusFilter }}" />
      <input type="text" name="q" value="{{ $search }}" placeholder="Search customer, email, subject..." class="w-64 rounded-full border border-[rgba(27,94,32,0.12)] px-4 py-2 text-sm placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/30 bg-white" />
      <button type="submit" class="px-4 py-2 rounded-full bg-[#17611f] text-white text-sm font-bold shadow-sm hover:bg-[#14521a] transition-all">Search</button>
    </form>
  </div>

  <!-- Tickets Table -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left">
        <thead>
          <tr class="text-[11px] uppercase tracking-wide text-[#9e9e9e] border-b border-gray-100 bg-[#f4faf5]">
            <th class="py-3 px-4 font-medium">Ticket</th>
            <th class="py-3 px-4 font-medium">Customer</th>
            <th class="py-3 px-4 font-medium">Email</th>
            <th class="py-3 px-4 font-medium">Subject</th>
            <th class="py-3 px-4 font-medium">Category</th>
            <th class="py-3 px-4 font-medium">Priority</th>
            <th class="py-3 px-4 font-medium">Status</th>
            <th class="py-3 px-4 font-medium">Date</th>
            <th class="py-3 px-4 font-medium">Action</th>
          </tr>
        </thead>
        <tbody>
          @if ($tickets->isEmpty())
            <tr>
              <td colspan="9" class="py-10 px-4 text-center text-sm text-[#9e9e9e] font-semibold">No tickets found.</td>
            </tr>
          @else
            @foreach ($tickets as $t)
              <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50/60 transition-colors">
                <td class="py-3 px-4 text-[13px] font-bold text-[#1a2e1c]">#LH-{{ str_pad($t->id, 4, '0', STR_PAD_LEFT) }}</td>
                <td class="py-3 px-4 text-[13px] font-semibold text-[#1a2e1c]">{{ $t->user->first_name ?? 'Anonymous' }} {{ $t->user->last_name ?? '' }}</td>
                <td class="py-3 px-4 text-[13px] font-medium text-[#5a7a5c]">{{ $t->user->email ?? '—' }}</td>
                <td class="py-3 px-4 text-[13px] text-[#5a7a5c] max-w-[200px] truncate font-medium">{{ $t->subject }}</td>
                <td class="py-3 px-4 text-[13px] text-[#5a7a5c] font-medium">{{ $t->category }}</td>
                <td class="py-3 px-4">{!! priorityBadgeTickets($t->priority ?? 'Medium') !!}</td>
                <td class="py-3 px-4">{!! statusBadgeTickets($t->status) !!}</td>
                <td class="py-3 px-4 text-[13px] text-[#9e9e9e] font-medium">{{ $t->created_at->format('M j, Y') }}</td>
                <td class="py-3 px-4">
                  <a href="{{ route('admin.tickets.show', $t->id) }}" class="text-[12px] font-bold border rounded-full px-3 py-1 text-[#17611f] hover:bg-[#17611f] hover:text-white transition-all">View</a>
                </td>
              </tr>
            @endforeach
          @endif
        </tbody>
      </table>
    </div>
  </div>

@endsection
