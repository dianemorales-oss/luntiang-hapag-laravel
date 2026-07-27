@extends('layouts.app')
@section('title', 'Dashboard | Luntiang H.A.P.A.G.')
@section('content')

@php
  $statusLabels = [
      'preparing' => '🌱 Preparing Order',
      'ready' => 'Ready',
      'delivered' => 'Delivered/Picked Up',
      'completed' => '🎉 Completed',
      'cancelled' => '❌ Cancelled'
  ];
  $ticketStatusColors = [
      'open' => 'bg-amber-100 text-amber-700',
      'in_progress' => 'bg-blue-100 text-blue-700',
      'resolved' => 'bg-green-100 text-green-700',
      'closed' => 'bg-gray-100 text-gray-600',
      'pending' => 'bg-amber-100 text-amber-700'
  ];
@endphp

<main class="max-w-7xl mx-auto px-6 py-8">
  <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
    <div>
      <h1 class="text-2xl font-black">Welcome, {{ $user->first_name }}</h1>
      <p class="text-[#5a7a5c] text-sm">Manage your orders, deliveries, and account</p>
    </div>
    <div class="flex gap-2">
      <a href="{{ route('products.index') }}" class="px-4 py-2 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a]">Shop Now</a>
      <a href="{{ route('cart.index') }}" class="px-4 py-2 rounded-xl border text-sm font-bold hover:bg-[#e8f5e9]">Cart</a>
    </div>
  </div>

  <div class="flex flex-wrap gap-1.5 mb-6 pb-4 border-b border-[rgba(27,94,32,0.08)]">
    @foreach (['overview'=>'Overview','orders'=>'Orders','addresses'=>'Addresses','coupons'=>'Coupons','support'=>'Support','profile'=>'Profile'] as $k=>$l)
      <a href="?section={{ $k }}" class="px-4 py-2 rounded-full text-sm font-bold {{ $section === $k ? 'bg-[#17611f] text-white' : 'bg-white border text-[#5a7a5c] hover:bg-[#e8f5e9]' }}">{{ $l }}</a>
    @endforeach
  </div>

  @if (session('success'))
    <div class="mb-4 rounded-xl px-4 py-3 text-sm bg-[#e8f5e9] text-[#17611f] border border-[#c8e6c9] shadow-sm">
      {{ session('success') }}
    </div>
  @endif

  @if ($section === 'overview')
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-8">
      @foreach ([
        ['Total Orders', $totalOrders, 'text-[#17611f]'],
        ['Preparing', $orderStats['preparing'], 'text-amber-600'],
        ['Ready', $orderStats['ready'], 'text-[#17611f]'],
        ['Delivered', $orderStats['delivered'], 'text-green-600'],
        ['Completed', $orderStats['completed'], 'text-[#17611f]'],
        ['Cancelled', $orderStats['cancelled'], 'text-red-600']
      ] as $c)
        <div class="bg-white rounded-xl border p-4">
          <p class="text-2xl font-black {{ $c[2] }}">{{ $c[1] }}</p>
          <p class="text-xs text-[#5a7a5c] font-bold mt-1">{{ $c[0] }}</p>
        </div>
      @endforeach
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
      <div class="bg-white rounded-xl border p-6">
        <h2 class="font-black text-lg mb-4">Current Order</h2>
        @if ($activeOrder)
          <div class="space-y-3">
            <div class="flex justify-between">
              <span class="text-sm text-[#5a7a5c]">#{{ $activeOrder->order_number }}</span>
              <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">{{ $statusLabels[$activeOrder->status] ?? $activeOrder->status }}</span>
            </div>
            @foreach ($activeItems as $ai)
              <p class="text-sm">{{ $ai->quantity }} x {{ $ai->product_name }}</p>
            @endforeach
            <div class="flex justify-between text-sm pt-2 border-t">
              <span class="text-[#5a7a5c]">Total</span>
              <span class="font-black text-[#17611f]">₱{{ number_format($activeOrder->total, 2) }}</span>
            </div>
            <a href="{{ route('orders.tracking', ['order' => $activeOrder->order_number]) }}" class="block text-center py-2 rounded-xl bg-[#e8f5e9] text-[#17611f] text-sm font-bold hover:bg-[#c8e6c9]">Track</a>
          </div>
        @else
          <p class="text-[#5a7a5c] text-sm py-4">No active orders. <a href="{{ route('products.index') }}" class="text-[#17611f] font-bold hover:underline">Shop now</a></p>
        @endif
      </div>

      <div class="bg-white rounded-xl border p-6">
        <h2 class="font-black text-lg mb-4">Support Overview</h2>
        <div class="flex gap-4 mb-4">
          <div class="flex-1 bg-[#f4faf5] rounded-xl p-4 text-center">
            <p class="text-2xl font-black">{{ $openTickets }}</p>
            <p class="text-xs text-[#5a7a5c]">Open Tickets</p>
          </div>
          <div class="flex-1 bg-[#f4faf5] rounded-xl p-4 text-center">
            <p class="text-2xl font-black">{{ $pendingReturns }}</p>
            <p class="text-xs text-[#5a7a5c]">Pending Returns</p>
          </div>
        </div>
        <div class="space-y-2">
          <a href="{{ route('tickets.create') }}" class="block text-center py-2 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a]">Submit Ticket</a>
          <a href="?section=support" class="block text-center py-2 rounded-xl border text-sm font-bold hover:bg-[#e8f5e9]">View Support History</a>
          <a href="{{ route('chat.index') }}" class="block text-center py-2 rounded-xl border text-sm font-bold hover:bg-[#e8f5e9]">Live Chat</a>
        </div>
      </div>
    </div>

  @elseif ($section === 'orders')
    {{-- Order Search Bar - Instant filtering --}}
    <div class="bg-white rounded-xl border p-4 mb-4 shadow-sm">
      <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
        <div class="flex flex-wrap gap-1.5">
          @foreach (['all'=>'All','active'=>'Active','preparing'=>'Preparing','ready'=>'Ready','completed'=>'Completed'] as $k=>$l)
            <a href="?section=orders&otab={{ $k }}" class="px-3 py-1.5 rounded-full text-xs font-bold {{ $orderTab === $k ? 'bg-[#17611f] text-white' : 'bg-white border text-[#5a7a5c]' }}">{{ $l }}</a>
          @endforeach
        </div>
        <div class="relative w-full sm:w-80">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#9e9e9e]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
          <input id="orderSearchInput" type="text" placeholder="Search by Order ID, Product, Date, Status..." class="w-full pl-10 pr-4 py-2 rounded-full border border-[rgba(27,94,32,0.12)] text-sm bg-[#f4faf5] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" />
        </div>
      </div>
    </div>

    @if ($allOrders->isEmpty())
      <div class="text-center py-16 bg-white rounded-xl border"><p class="text-[#5a7a5c]">No orders</p></div>
    @else
      <div id="ordersList" class="space-y-3">
        @foreach ($allOrders as $o)
          <div class="order-card bg-white rounded-xl border p-5 transition-all hover:shadow-md" data-order-id="{{ $o->order_number }}" data-status="{{ $o->status }}" data-date="{{ $o->created_at->format('M j, Y') }}" data-products="{{ $o->items->pluck('product_name')->implode(' ') }}">
            <div class="flex justify-between flex-wrap gap-2 mb-2">
              <div>
                <span class="font-black">#{{ $o->order_number }}</span>
                <span class="text-xs text-[#5a7a5c] ml-2">{{ $o->created_at->format('M j, Y g:i A') }}</span>
              </div>
              <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ in_array($o->status, ['completed', 'delivered']) ? 'bg-green-100 text-green-700' : (in_array($o->status, ['cancelled']) ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ $statusLabels[$o->status] ?? $o->status }}</span>
            </div>
            <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-[#5a7a5c] mb-3">
              <span>Method: <b>{{ $o->delivery_method === 'pickup' ? 'Pick-Up' : 'Delivery' }}</b></span>
              <span>Payment: <b>{{ strtoupper($o->payment_method) }}</b></span>
              <span>Total: <b class="text-[#17611f]">₱{{ number_format($o->total, 2) }}</b></span>
              <span>Items: <b>{{ $o->items->count() }}</b></span>
            </div>
            <div class="text-xs text-[#5a7a5c] mb-3">
              @foreach($o->items->take(2) as $it)
                <span class="inline-block mr-2">{{ $it->product_name }} × {{ $it->quantity }}</span>
              @endforeach
              @if($o->items->count() > 2) <span>+{{ $o->items->count()-2 }} more</span> @endif
            </div>
            <div class="flex flex-wrap gap-2">
              <button class="track-toggle-btn px-3 py-1.5 rounded-lg bg-[#e8f5e9] text-[#17611f] text-xs font-bold hover:bg-[#c8e6c9] transition-colors" data-order="{{ $o->order_number }}">📍 Track</button>
              @if (in_array($o->status, ['completed', 'delivered']))
                <a href="?section=orders&reorder={{ $o->id }}" class="px-3 py-1.5 rounded-lg border text-xs font-bold hover:bg-[#e8f5e9]">Reorder</a>
              @endif
              <a href="{{ route('orders.tracking', ['order' => $o->order_number]) }}" class="px-3 py-1.5 rounded-lg border text-xs font-bold hover:bg-[#f4faf5] text-[#5a7a5c]">Full Page →</a>
            </div>

            {{-- Expandable Tracking Details --}}
            <div id="track-detail-{{ $o->order_number }}" class="hidden mt-4 pt-4 border-t border-[rgba(27,94,32,0.08)]">
              <h4 class="font-black text-sm mb-3">Tracking Details – #{{ $o->order_number }}</h4>
              <div class="flex gap-1.5 mb-4 flex-wrap">
                @foreach(['preparing'=>'Preparing','ready'=>'Ready','delivered'=>'Delivered','completed'=>'Completed'] as $stepKey=>$stepLabel)
                  @php
                    $orderSteps = ['preparing','ready','delivered','completed'];
                    $currentIdx = array_search($o->status, $orderSteps);
                    $stepIdx = array_search($stepKey, $orderSteps);
                    $isDone = $currentIdx !== false && $stepIdx !== false && $stepIdx <= $currentIdx;
                    $isCurrent = $o->status === $stepKey;
                  @endphp
                  <span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $isCurrent ? 'bg-[#17611f] text-white' : ($isDone ? 'bg-[#e8f5e9] text-[#17611f]' : 'bg-gray-100 text-[#9e9e9e]') }}">{{ $stepLabel }} {{ $isDone ? '✓' : '' }}</span>
                @endforeach
              </div>
              <div class="space-y-2 text-xs">
                <div class="flex justify-between"><span class="text-[#5a7a5c]">Order Date:</span><span class="font-bold">{{ $o->created_at->format('M j, Y g:i A') }}</span></div>
                <div class="flex justify-between"><span class="text-[#5a7a5c]">Delivery Method:</span><span class="font-bold">{{ $o->delivery_method === 'pickup' ? 'Pick-Up' : 'Delivery' }}</span></div>
                <div class="flex justify-between"><span class="text-[#5a7a5c]">Payment:</span><span class="font-bold">{{ strtoupper($o->payment_method) }}</span></div>
                <div class="flex justify-between"><span class="text-[#5a7a5c]">Total:</span><span class="font-black text-[#17611f]">₱{{ number_format($o->total,2) }}</span></div>
                @if($o->delivery_address)
                  <div class="pt-2 border-t mt-2"><p class="text-[#5a7a5c]">Address:</p><p class="font-bold">{{ $o->delivery_address }}, {{ $o->delivery_city }}, {{ $o->delivery_province }} {{ $o->delivery_zip }}</p></div>
                @endif
              </div>
              <div class="mt-3 pt-3 border-t">
                <p class="text-xs font-bold mb-2">Items:</p>
                @foreach($o->items as $item)
                  <div class="flex justify-between text-xs py-1.5 border-b border-[rgba(27,94,32,0.05)] last:border-0"><span>{{ $item->product_name }} × {{ $item->quantity }}</span><span class="font-bold">₱{{ number_format($item->price*$item->quantity,2) }}</span></div>
                @endforeach
              </div>
            </div>
          </div>
        @endforeach
      </div>
      <p id="noOrdersFound" class="hidden text-center py-10 bg-white rounded-xl border text-sm text-[#5a7a5c] mt-4">No orders match your search.</p>
    @endif

  @elseif ($section === 'addresses')
    {{-- addresses unchanged --}}
    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <h2 class="font-black text-lg mb-4">Saved Addresses</h2>
        @if ($addresses->isEmpty())
          <p class="text-[#5a7a5c] text-sm">No saved addresses.</p>
        @endif
        @foreach ($addresses as $a)
          <div class="bg-white rounded-xl border p-4 mb-3">
            <div class="flex justify-between mb-1">
              <span class="font-bold text-sm">{{ $a->label }} {{ $a->is_default ? '(Default)' : '' }}</span>
              <div class="flex gap-2 text-xs">
                @if (!$a->is_default)
                  <a href="?section=addresses&setdefault={{ $a->id }}" class="text-[#17611f] font-bold">Set Default</a>
                @endif
                <a href="?section=addresses&deladdr={{ $a->id }}" class="text-red-500 font-bold" onclick="return confirm('Delete address?')">Delete</a>
              </div>
            </div>
            <p class="text-sm text-[#5a7a5c]">{{ $a->address }}, {{ $a->city }}, {{ $a->province }} {{ $a->zip }}</p>
          </div>
        @endforeach
      </div>

      <div class="bg-white rounded-xl border p-5">
        <h2 class="font-black text-lg mb-4">Add Address</h2>
        <form method="POST" action="{{ route('profile.index', ['section' => 'addresses']) }}" class="space-y-3">
          @csrf
          <input type="hidden" name="save_address" value="1">
          <div>
            <label class="text-xs font-bold text-[#5a7a5c]">Label</label>
            <select name="address_label" class="w-full border rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
              <option>Home</option>
              <option>Office</option>
              <option>Restaurant</option>
            </select>
          </div>
          <div>
            <label class="text-xs font-bold text-[#5a7a5c]">Address</label>
            <textarea name="address" rows="2" class="w-full border rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" required></textarea>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <input name="city" placeholder="City" class="border rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" required>
            <input name="province" placeholder="Province" class="border rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" required>
          </div>
          <input name="zip" placeholder="ZIP Code" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4)" class="w-full border rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
          <button type="submit" class="w-full py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a]">Save Address</button>
        </form>
      </div>
    </div>

  @elseif ($section === 'coupons')
    <h2 class="font-black text-lg mb-4">My Claimed Coupons</h2>
    @if ($coupons->isEmpty())
      <p class="text-[#5a7a5c] text-sm">No coupons claimed yet. Visit the <a href="{{ route('home') }}" class="text-[#17611f] font-bold hover:underline">homepage</a> to claim available coupons.</p>
    @else
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($coupons as $c)
          <div class="bg-white rounded-xl border p-5">
            <p class="font-black text-lg text-[#17611f]">{{ $c->code }}</p>
            <p class="text-sm text-[#5a7a5c] mt-1">{{ $c->description }}</p>
            <p class="text-xs text-[#9e9e9e] mt-2">{{ $c->discount_type === 'percentage' ? $c->discount_value.'% off' : '₱'.number_format($c->discount_value,2).' off' }} {{ $c->is_free_delivery ? ' + Free Delivery' : '' }}</p>
            <p class="text-[10px] text-[#9e9e9e] mt-2">Claimed: {{ date('M j, Y', strtotime($c->claimed_at)) }}</p>
          </div>
        @endforeach
      </div>
    @endif

  @elseif ($section === 'support')
    <h2 class="font-black text-lg mb-4">Customer Support</h2>
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-8">
      <a href="{{ route('tickets.create') }}" class="group bg-white rounded-xl border p-5 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
        <div class="w-12 h-12 rounded-full bg-[#fff8e1] flex items-center justify-center mx-auto mb-3"><span class="text-xl">🎫</span></div>
        <p class="font-bold text-sm">Submit Ticket</p><p class="text-xs text-[#5a7a5c] mt-1">Report an issue</p>
      </a>
      <a href="{{ route('returns.index') }}" class="group bg-white rounded-xl border p-5 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
        <div class="w-12 h-12 rounded-full bg-[#e8f5e9] flex items-center justify-center mx-auto mb-3"><span class="text-xl">↩️</span></div>
        <p class="font-bold text-sm">Return & Refund</p><p class="text-xs text-[#5a7a5c] mt-1">Request return</p>
      </a>
      <a href="{{ route('chat.index') }}" class="group bg-white rounded-xl border p-5 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
        <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center mx-auto mb-3"><span class="text-xl">💬</span></div>
        <p class="font-bold text-sm">Live Chat</p><p class="text-xs text-[#5a7a5c] mt-1">Talk to us now</p>
      </a>
      <a href="{{ route('contact') }}" class="group bg-white rounded-xl border p-5 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
        <div class="w-12 h-12 rounded-full bg-purple-50 flex items-center justify-center mx-auto mb-3"><span class="text-xl">💜</span></div>
        <p class="font-bold text-sm">Send Feedback</p><p class="text-xs text-[#5a7a5c] mt-1">Share thoughts</p>
      </a>
      <a href="{{ route('faq') }}" class="group bg-white rounded-xl border p-5 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
        <div class="w-12 h-12 rounded-full bg-pink-50 flex items-center justify-center mx-auto mb-3"><span class="text-xl">❓</span></div>
        <p class="font-bold text-sm">FAQs</p><p class="text-xs text-[#5a7a5c] mt-1">Common questions</p>
      </a>
    </div>

    {{-- Support Requests Summary Table - All support-related submissions --}}
    <div class="bg-white rounded-2xl border border-[rgba(27,94,32,0.08)] p-6 shadow-sm">
      <div class="flex items-center justify-between mb-3">
        <div>
          <h3 class="font-black text-base flex items-center gap-2">📋 Support Requests Summary</h3>
          <p class="text-xs text-[#5a7a5c] mt-0.5">All support submissions: Tickets, Returns & Refunds, Feedback</p>
        </div>
        <span class="text-xs bg-[#f4faf5] px-2.5 py-1 rounded-full font-bold text-[#5a7a5c]">{{ $supportHistory->count() }} total</span>
      </div>

      {{-- Filter Options --}}
      <div class="flex flex-wrap gap-1.5 mb-5 pb-3 border-b border-[rgba(27,94,32,0.06)]">
        <button data-filter="all" class="support-filter-btn active px-4 py-1.5 rounded-full text-xs font-bold bg-[#17611f] text-white shadow-sm">All</button>
        <button data-filter="Submit Ticket" class="support-filter-btn px-4 py-1.5 rounded-full text-xs font-bold bg-white border text-[#5a7a5c] hover:bg-[#e8f5e9]">Submit Ticket</button>
        <button data-filter="Return & Refund" class="support-filter-btn px-4 py-1.5 rounded-full text-xs font-bold bg-white border text-[#5a7a5c] hover:bg-[#e8f5e9]">Return & Refund</button>
        <button data-filter="Feedback" class="support-filter-btn px-4 py-1.5 rounded-full text-xs font-bold bg-white border text-[#5a7a5c] hover:bg-[#e8f5e9]">Feedback</button>
      </div>

      @if($supportHistory->isEmpty())
        <div class="text-center py-12 bg-[#f4faf5] rounded-xl">
          <p class="text-3xl mb-2">📭</p>
          <p class="text-sm font-bold text-[#5a7a5c]">No support requests yet</p>
          <p class="text-xs text-[#9e9e9e] mt-1 max-w-sm mx-auto">When you submit a support ticket, return request, or feedback, it will appear here with complete history and status tracking.</p>
          <div class="flex justify-center gap-2 mt-4">
            <a href="{{ route('tickets.create') }}" class="px-4 py-1.5 rounded-full bg-[#17611f] text-white text-xs font-bold hover:bg-[#14521a]">Submit Ticket</a>
            <a href="{{ route('returns.index') }}" class="px-4 py-1.5 rounded-full border text-xs font-bold hover:bg-[#e8f5e9]">Return & Refund</a>
            <a href="{{ route('feedback') }}" class="px-4 py-1.5 rounded-full border text-xs font-bold hover:bg-[#e8f5e9]">Feedback</a>
          </div>
        </div>
      @else
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-[11px] uppercase tracking-wider text-[#5a7a5c] border-b">
                <th class="text-left py-2.5 px-2 font-bold">ID</th>
                <th class="text-left py-2.5 px-2 font-bold">Type</th>
                <th class="text-left py-2.5 px-2 font-bold">Subject</th>
                <th class="text-left py-2.5 px-2 font-bold">Category</th>
                <th class="text-left py-2.5 px-2 font-bold">Date Submitted</th>
                <th class="text-left py-2.5 px-2 font-bold">Current Status</th>
                <th class="text-left py-2.5 px-2 font-bold">Last Updated</th>
                <th class="text-right py-2.5 px-2 font-bold">Action</th>
              </tr>
            </thead>
            <tbody id="supportHistoryBody" class="divide-y divide-[rgba(27,94,32,0.05)]">
              @foreach($supportHistory as $item)
                <tr class="support-row hover:bg-[#f4faf5]/70 transition-colors" data-type="{{ $item['type'] }}">
                  <td class="py-3 px-2 font-mono font-bold text-xs">{{ $item['id'] }}</td>
                  <td class="py-3 px-2"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $item['type_color'] }}">{{ $item['type'] }}</span></td>
                  <td class="py-3 px-2 font-semibold truncate max-w-[180px]">{{ \Str::limit($item['subject'], 30) }}</td>
                  <td class="py-3 px-2"><span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-[#e8f5e9] text-[#17611f]">{{ $item['category'] }}</span></td>
                  <td class="py-3 px-2 text-xs text-[#5a7a5c]">{{ $item['date_submitted']->format('M j, Y') }}<br><span class="text-[10px] text-[#9e9e9e]">{{ $item['date_submitted']->format('g:i A') }}</span></td>
                  <td class="py-3 px-2"><span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $ticketStatusColors[$item['status']] ?? ($item['status']==='submitted' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600') }}">{{ ucfirst(str_replace('_',' ',$item['status'])) }}</span></td>
                  <td class="py-3 px-2 text-xs text-[#5a7a5c]">{{ $item['last_updated']->diffForHumans() }}<br><span class="text-[10px] text-[#9e9e9e]">{{ $item['last_updated']->format('M j, g:i A') }}</span></td>
                  <td class="py-3 px-2 text-right"><a href="{{ $item['link'] }}" class="inline-flex px-3 py-1.5 rounded-full bg-[#17611f] text-white text-[11px] font-bold hover:bg-[#14521a] transition-colors">{{ $item['link_text'] }}</a></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <p id="noSupportFiltered" class="hidden text-center py-8 text-sm text-[#5a7a5c] bg-[#f4faf5] rounded-xl mt-4">No requests match this filter.</p>
        <p class="text-[11px] text-[#9e9e9e] mt-4 text-center">Showing <span id="supportVisibleCount">{{ $supportHistory->count() }}</span> of {{ $supportHistory->count() }} support-related submissions – filtered without reload.</p>
      @endif
    </div>

  @elseif ($section === 'profile')
    <div class="bg-white rounded-xl border p-6">
      <h2 class="font-black text-lg mb-4">Profile Information</h2>
      <div class="grid md:grid-cols-2 gap-4 mb-4">
        <div><p class="text-xs text-[#5a7a5c] font-bold">First Name</p><p class="text-sm font-bold">{{ $user->first_name }}</p></div>
        <div><p class="text-xs text-[#5a7a5c] font-bold">Last Name</p><p class="text-sm font-bold">{{ $user->last_name }}</p></div>
        <div><p class="text-xs text-[#5a7a5c] font-bold">Email</p><p class="text-sm font-bold">{{ $user->email }}</p></div>
        <div><p class="text-xs text-[#5a7a5c] font-bold">Phone</p><p class="text-sm font-bold">{{ $user->phone ?: '-' }}</p></div>
        <div class="md:col-span-2"><p class="text-xs text-[#5a7a5c] font-bold">Address</p><p class="text-sm font-bold">{{ $user->address ?: '-' }}</p></div>
      </div>
      <div class="flex gap-3">
        <a href="{{ route('profile.edit') }}" class="px-4 py-2 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a]">Edit Profile</a>
        <a href="{{ route('profile.change-password') }}" class="px-4 py-2 rounded-xl border text-sm font-bold hover:bg-[#e8f5e9]">Change Password</a>
      </div>
    </div>
  @endif
</main>

@push('scripts')
<script>
// Order Search - instant filter by Order ID, Product Name, Order Date, Order Status
document.addEventListener('DOMContentLoaded', function(){
  const searchInput = document.getElementById('orderSearchInput');
  const ordersList = document.getElementById('ordersList');
  const noFound = document.getElementById('noOrdersFound');
  if(searchInput && ordersList){
    searchInput.addEventListener('input', function(){
      const term = this.value.toLowerCase().trim();
      const cards = ordersList.querySelectorAll('.order-card');
      let visibleCount = 0;
      cards.forEach(card=>{
        const orderId = (card.dataset.orderId||'').toLowerCase();
        const status = (card.dataset.status||'').toLowerCase();
        const date = (card.dataset.date||'').toLowerCase();
        const products = (card.dataset.products||'').toLowerCase();
        const text = card.textContent.toLowerCase();
        const match = term==='' || orderId.includes(term) || status.includes(term) || date.includes(term) || products.includes(term) || text.includes(term);
        card.style.display = match ? '' : 'none';
        if(match) visibleCount++;
      });
      if(noFound) noFound.classList.toggle('hidden', visibleCount!==0 || term==='');
    });
  }

  // Track toggle - expand selected order row
  document.querySelectorAll('.track-toggle-btn').forEach(btn=>{
    btn.addEventListener('click', function(){
      const orderNum = this.dataset.order;
      const detail = document.getElementById('track-detail-'+orderNum);
      if(!detail) return;
      const isHidden = detail.classList.contains('hidden');
      document.querySelectorAll('[id^="track-detail-"]').forEach(d=>{ if(d!==detail) d.classList.add('hidden'); });
      document.querySelectorAll('.track-toggle-btn').forEach(b=>{ if(b!==this) b.textContent='📍 Track'; });
      if(isHidden){
        detail.classList.remove('hidden');
        this.textContent='▲ Hide Tracking';
        detail.scrollIntoView({ behavior:'smooth', block:'nearest' });
      } else {
        detail.classList.add('hidden');
        this.textContent='📍 Track';
      }
    });
  });

  // Support Requests Summary Filter - All, Submit Ticket, Return & Refund, Feedback without reload
  const filterBtns = document.querySelectorAll('.support-filter-btn');
  const rows = document.querySelectorAll('.support-row');
  const noFiltered = document.getElementById('noSupportFiltered');
  const visibleCountEl = document.getElementById('supportVisibleCount');
  if(filterBtns.length && rows.length){
    filterBtns.forEach(btn=>{
      btn.addEventListener('click', function(){
        const filter = this.dataset.filter;
        filterBtns.forEach(b=>{
          b.classList.remove('active','bg-[#17611f]','text-white','shadow-sm');
          b.classList.add('bg-white','border','text-[#5a7a5c]');
        });
        this.classList.add('active','bg-[#17611f]','text-white','shadow-sm');
        this.classList.remove('bg-white','border','text-[#5a7a5c]');
        let visible=0;
        rows.forEach(row=>{
          const type = row.dataset.type;
          const match = filter==='all' || type===filter;
          row.style.display = match ? '' : 'none';
          if(match) visible++;
        });
        if(noFiltered) noFiltered.classList.toggle('hidden', visible!==0);
        if(visibleCountEl) visibleCountEl.textContent = visible;
      });
    });
  }
});
</script>
@endpush

@endsection
