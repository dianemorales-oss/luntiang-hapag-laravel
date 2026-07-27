@extends('admin.layouts.app')
@section('title', 'Customers | Admin')
@section('header', 'Customers')
@section('content')

  @if ($emailParam && $customer)
    <a href="{{ route('admin.customers.index') }}" class="inline-flex items-center gap-2 text-sm text-[#17611f] font-bold hover:underline mb-6">Back to Customers</a>
    
    <div class="bg-white rounded-xl border p-6 mb-6 flex items-center gap-4 shadow-sm">
      <div class="w-14 h-14 rounded-full bg-[#17611f] text-white font-black flex items-center justify-center text-lg">
        {{ strtoupper(substr($customer->first_name, 0, 1) . substr($customer->last_name, 0, 1)) }}
      </div>
      <div>
        <h1 class="font-black text-xl text-[#1a2e1c]">{{ $customer->first_name }} {{ $customer->last_name }}</h1>
        <p class="text-sm text-[#5a7a5c]">
          {{ $customer->email }} &nbsp;·&nbsp; {{ $customer->phone }} &nbsp;·&nbsp; Joined {{ $customer->created_at->format('M j, Y') }}
        </p>
      </div>
      <button onclick="document.getElementById('editForm').classList.toggle('hidden')" class="ml-auto px-4 py-2 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a] transition-all">Edit Profile</button>
    </div>

    <!-- Edit Form -->
    <div id="editForm" class="hidden bg-white rounded-xl border p-6 mb-6 shadow-sm">
      <h2 class="font-black text-sm mb-4 text-[#1a2e1c]">Edit Customer Information</h2>
      <form method="POST" action="{{ route('admin.customers.index', ['email' => $customer->email]) }}" class="grid grid-cols-2 gap-4">
        @csrf
        <input type="hidden" name="save_customer" value="1">
        <div>
          <label class="text-xs font-bold text-[#5a7a5c]">First Name</label>
          <input name="first_name" value="{{ $customer->first_name }}" class="w-full border rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" required>
        </div>
        <div>
          <label class="text-xs font-bold text-[#5a7a5c]">Last Name</label>
          <input name="last_name" value="{{ $customer->last_name }}" class="w-full border rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" required>
        </div>
        <div>
          <label class="text-xs font-bold text-[#5a7a5c]">Email</label>
          <input type="email" name="email" value="{{ $customer->email }}" class="w-full border rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" required>
        </div>
        <div>
          <label class="text-xs font-bold text-[#5a7a5c]">Phone</label>
          <input name="phone" value="{{ $customer->phone }}" class="w-full border rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
        </div>
        <div class="col-span-2">
          <label class="text-xs font-bold text-[#5a7a5c]">Address</label>
          <textarea name="address" rows="2" class="w-full border rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">{{ $customer->address }}</textarea>
        </div>
        <div class="col-span-2 flex gap-3">
          <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold shadow-sm hover:bg-[#14521a] transition-all">Save Changes</button>
          <button type="button" onclick="document.getElementById('editForm').classList.add('hidden')" class="px-5 py-2.5 rounded-xl border text-sm font-bold hover:bg-gray-50 transition-colors">Cancel</button>
        </div>
      </form>
    </div>

    <!-- Stats Lists -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
      <div class="bg-white rounded-xl border p-5 shadow-sm">
        <h3 class="font-black text-sm mb-3 text-[#1a2e1c]">Orders ({{ count($orders) }})</h3>
        @if ($orders->isEmpty())
          <p class="text-sm text-[#9e9e9e]">None yet.</p>
        @else
          <div class="space-y-2">
            @foreach ($orders as $o)
              <div class="text-sm">
                #{{ $o->order_number }} - ₱{{ number_format($o->total, 2) }} 
                <span class="text-xs text-[#9e9e9e]">({{ ucwords(str_replace('_', ' ', $o->status)) }})</span>
              </div>
            @endforeach
          </div>
        @endif
      </div>

      <div class="bg-white rounded-xl border p-5 shadow-sm">
        <h3 class="font-black text-sm mb-3 text-[#1a2e1c]">Tickets ({{ count($tickets) }})</h3>
        @if ($tickets->isEmpty())
          <p class="text-sm text-[#9e9e9e]">None yet.</p>
        @else
          <div class="space-y-2">
            @foreach ($tickets as $t)
              <a href="{{ route('admin.tickets.show', $t->id) }}" class="block text-sm hover:text-[#17611f] font-semibold">
                {{ $t->subject }} 
                <span class="text-xs text-[#9e9e9e]">({{ ucfirst($t->status) }})</span>
              </a>
            @endforeach
          </div>
        @endif
      </div>

      <div class="bg-white rounded-xl border p-5 shadow-sm">
        <h3 class="font-black text-sm mb-3 text-[#1a2e1c]">Returns ({{ count($returns) }})</h3>
        @if ($returns->isEmpty())
          <p class="text-sm text-[#9e9e9e]">None yet.</p>
        @else
          <div class="space-y-2">
            @foreach ($returns as $r)
              <div class="text-sm">
                #{{ $r->order_number }} 
                <span class="text-xs text-[#9e9e9e]">({{ ucfirst($r->status) }})</span>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>

  @else
    <h1 class="text-2xl font-black mb-4">Customers</h1>
    <form method="GET" action="{{ route('admin.customers.index') }}" class="flex gap-2 mb-6">
      <input type="text" name="q" value="{{ $search }}" placeholder="Search by name or email..." class="w-full max-w-md rounded-xl border px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white">
      <button type="submit" class="px-4 py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold shadow-sm hover:bg-[#14521a]">Search</button>
    </form>

    <div class="bg-white rounded-xl border overflow-hidden shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-[#f4faf5] text-[#5a7a5c] text-xs uppercase border-b">
              <th class="p-3 text-left">Customer</th>
              <th class="p-3 text-left">Email</th>
              <th class="p-3 text-left">Orders</th>
              <th class="p-3 text-left">Tickets</th>
              <th class="p-3 text-left">Returns</th>
              <th class="p-3 text-left">Joined</th>
              <th class="p-3 text-left">Action</th>
            </tr>
          </thead>
          <tbody>
            @if ($customers->isEmpty())
              <tr>
                <td colspan="7" class="p-6 text-center text-[#9e9e9e] font-semibold">No customers found.</td>
              </tr>
            @else
              @foreach ($customers as $c)
                <tr class="border-t border-[rgba(27,94,32,0.05)] hover:bg-gray-50/50 transition-colors">
                  <td class="p-3 font-bold text-[#1a2e1c]">{{ $c->first_name }} {{ $c->last_name }}</td>
                  <td class="p-3 text-[#5a7a5c] font-medium">{{ $c->email }}</td>
                  <td class="p-3 font-semibold">{{ $c->order_count }}</td>
                  <td class="p-3 font-semibold">{{ $c->ticket_count }}</td>
                  <td class="p-3 font-semibold">{{ $c->return_count }}</td>
                  <td class="p-3 text-xs text-[#9e9e9e] font-medium">{{ $c->created_at->format('M j, Y') }}</td>
                  <td class="p-3">
                    <a href="?email={{ urlencode($c->email) }}" class="text-[#17611f] font-bold text-xs hover:underline">View</a>
                  </td>
                </tr>
              @endforeach
            @endif
          </tbody>
        </table>
      </div>
    </div>
  @endif

@endsection
