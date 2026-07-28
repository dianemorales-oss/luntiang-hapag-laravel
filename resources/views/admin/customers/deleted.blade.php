@extends('admin.layouts.app')
@section('title', 'Deleted Customer Accounts | Admin')
@section('header', 'Deleted Customers')
@section('content')

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-black mb-1">Deleted Customer Accounts</h1>
      <p class="text-sm text-[#5a7a5c]">Manage soft-deleted customer accounts (restore or permanently delete)</p>
    </div>
    <a href="{{ route('admin.customers.index') }}" class="px-4 py-2 rounded-xl bg-white border border-[rgba(27,94,32,0.15)] text-sm font-bold text-[#17611f] hover:bg-[#e8f5e9] transition-colors">← Back to Active Customers</a>
  </div>

  @if(session('success'))
    <div class="mb-5 rounded-xl bg-[#e8f5e9] border border-[#c8e6c9] px-4 py-3 text-sm text-[#17611f]">{{ session('success') }}</div>
  @endif

  <!-- Search Form -->
  <form method="GET" action="{{ route('admin.customers.deleted') }}" class="flex gap-2 mb-6">
    <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="Search by name or email..." class="w-full max-w-md rounded-xl border px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white">
    <button type="submit" class="px-4 py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold shadow-sm hover:bg-[#14521a]">Search</button>
    @if(!empty($search))
      <a href="{{ route('admin.customers.deleted') }}" class="px-4 py-2.5 rounded-xl border bg-white text-sm font-bold flex items-center">Clear</a>
    @endif
  </form>

  <div class="bg-white rounded-xl border overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-[#f4faf5] text-[#5a7a5c] text-xs uppercase border-b">
            <th class="p-3.5 text-left">Full Name</th>
            <th class="p-3.5 text-left">Email Address</th>
            <th class="p-3.5 text-left">Account Status</th>
            <th class="p-3.5 text-left">Date Deleted</th>
            <th class="p-3.5 text-left">Deleted By</th>
            <th class="p-3.5 text-left">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($deletedCustomers as $dc)
            <tr class="border-t border-[rgba(27,94,32,0.05)] hover:bg-gray-50/50 transition-colors">
              <td class="p-3.5 font-bold text-[#1a2e1c]">{{ $dc->first_name }} {{ $dc->last_name }}</td>
              <td class="p-3.5 text-[#5a7a5c] font-medium">{{ $dc->email }}</td>
              <td class="p-3.5">
                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">Deleted</span>
              </td>
              <td class="p-3.5 text-xs text-[#9e9e9e] font-medium">{{ optional($dc->deleted_at)->format('M j, Y g:i A') ?? 'N/A' }}</td>
              <td class="p-3.5 text-xs text-[#5a7a5c] font-semibold">{{ $dc->deleted_by ?: 'System / Admin' }}</td>
              <td class="p-3.5">
                <div class="flex items-center gap-2">
                  <!-- Restore Form -->
                  <form method="POST" action="{{ route('admin.customers.restore', $dc->id) }}" onsubmit="return confirm('Restore customer account {{ addslashes($dc->first_name . ' ' . $dc->last_name) }}? This will reactivate their account and restore access to their orders and support history.');">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 rounded-lg border border-green-200 bg-green-50 text-green-700 text-xs font-bold hover:bg-green-100 transition-colors">Restore</button>
                  </form>

                  <!-- Permanent Delete Form -->
                  <form method="POST" action="{{ route('admin.customers.forceDelete', $dc->id) }}" onsubmit="return confirm('WARNING: Permanently delete customer account {{ addslashes($dc->first_name . ' ' . $dc->last_name) }}?\n\nThis action is irreversible and will completely remove the account from the database.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3 py-1.5 rounded-lg border border-red-200 bg-red-50 text-red-700 text-xs font-bold hover:bg-red-100 transition-colors">Permanently Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="p-8 text-center text-sm text-[#9e9e9e]">No deleted customer accounts found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

@endsection
