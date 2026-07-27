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
  @if (request()->has('saved'))
    <div class="mb-4 rounded-xl px-4 py-3 text-sm bg-[#e8f5e9] text-[#17611f] border border-[#c8e6c9] shadow-sm">
      Address saved.
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
        <h2 class="font-black text-lg mb-4">Support</h2>
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
          <a href="{{ route('chat.index') }}" class="block text-center py-2 rounded-xl border text-sm font-bold hover:bg-[#e8f5e9]">Live Chat</a>
        </div>
      </div>
    </div>

  @elseif ($section === 'orders')
    <div class="flex flex-wrap gap-1.5 mb-4">
      @foreach (['active'=>'Active','preparing'=>'Preparing','ready'=>'Ready','completed'=>'Completed'] as $k=>$l)
        <a href="?section=orders&otab={{ $k }}" class="px-3 py-1.5 rounded-full text-xs font-bold {{ $orderTab === $k ? 'bg-[#17611f] text-white' : 'bg-white border text-[#5a7a5c]' }}">{{ $l }}</a>
      @endforeach
    </div>
    @if ($allOrders->isEmpty())
      <div class="text-center py-16 bg-white rounded-xl border">
        <p class="text-[#5a7a5c]">No orders</p>
      </div>
    @else
      <div class="space-y-3">
        @foreach ($allOrders as $o)
          <div class="bg-white rounded-xl border p-5">
            <div class="flex justify-between flex-wrap gap-2 mb-2">
              <div>
                <span class="font-black">#{{ $o->order_number }}</span>
                <span class="text-xs text-[#5a7a5c] ml-2">{{ $o->created_at->format('M j, Y') }}</span>
              </div>
              <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ in_array($o->status, ['completed', 'delivered']) ? 'bg-green-100 text-green-700' : (in_array($o->status, ['cancelled']) ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                {{ $statusLabels[$o->status] ?? $o->status }}
              </span>
            </div>
            <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-[#5a7a5c] mb-2">
              <span>Method: <b>{{ $o->delivery_method === 'pickup' ? 'Pick-Up' : 'Delivery' }}</b></span>
              <span>Payment: <b>{{ strtoupper($o->payment_method) }}</b></span>
              <span>Total: <b class="text-[#17611f]">₱{{ number_format($o->total, 2) }}</b></span>
            </div>
            <div class="flex flex-wrap gap-2">
              <a href="{{ route('orders.tracking', ['order' => $o->order_number]) }}" class="px-3 py-1.5 rounded-lg bg-[#e8f5e9] text-[#17611f] text-xs font-bold hover:bg-[#c8e6c9]">Track</a>
              @if (in_array($o->status, ['completed', 'delivered']))
                <a href="?section=orders&reorder={{ $o->id }}" class="px-3 py-1.5 rounded-lg border text-xs font-bold hover:bg-[#e8f5e9]">Reorder</a>
              @endif
            </div>
          </div>
        @endforeach
      </div>
    @endif

  @elseif ($section === 'addresses')
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
          <button type="submit" class="w-full py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a] transition-colors">Save Address</button>
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
            <p class="text-xs text-[#9e9e9e] mt-2">
              {{ $c->discount_type === 'percentage' ? $c->discount_value.'% off' : '₱'.number_format($c->discount_value, 2).' off' }}
              {{ $c->is_free_delivery ? ' + Free Delivery' : '' }}
              {{ $c->min_order > 0 ? ' (min ₱'.number_format($c->min_order, 2).')' : '' }}
            </p>
            <p class="text-[10px] text-[#9e9e9e] mt-2">Claimed: {{ date('M j, Y', strtotime($c->claimed_at)) }}</p>
          </div>
        @endforeach
      </div>
    @endif

  @elseif ($section === 'support')
    <h2 class="font-black text-lg mb-4">Customer Support</h2>
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
      <a href="{{ route('tickets.create') }}" class="group bg-white rounded-xl border p-5 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
        <div class="w-12 h-12 rounded-full bg-[#fff8e1] flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
          <svg class="w-5 h-5 text-[#f9a825]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </div>
        <p class="font-bold text-sm">Submit Ticket</p>
        <p class="text-xs text-[#5a7a5c] mt-1">Report an issue</p>
      </a>

      <a href="{{ route('returns.index') }}" class="group bg-white rounded-xl border p-5 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
        <div class="w-12 h-12 rounded-full bg-[#e8f5e9] flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
          <svg class="w-5 h-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8h-1"/>
          </svg>
        </div>
        <p class="font-bold text-sm">Return & Refund</p>
        <p class="text-xs text-[#5a7a5c] mt-1">Request return</p>
      </a>

      <a href="{{ route('chat.index') }}" class="group bg-white rounded-xl border p-5 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
        <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
          <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
          </svg>
        </div>
        <p class="font-bold text-sm">Live Chat</p>
        <p class="text-xs text-[#5a7a5c] mt-1">Talk to us now</p>
      </a>

      <a href="{{ route('contact') }}" class="group bg-white rounded-xl border p-5 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
        <div class="w-12 h-12 rounded-full bg-purple-50 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
          <svg class="w-5 h-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
          </svg>
        </div>
        <p class="font-bold text-sm">Send Feedback</p>
        <p class="text-xs text-[#5a7a5c] mt-1">Share thoughts</p>
      </a>

      <a href="{{ route('faq') }}" class="group bg-white rounded-xl border p-5 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
        <div class="w-12 h-12 rounded-full bg-pink-50 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
          <svg class="w-5 h-5 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <p class="font-bold text-sm">FAQs</p>
        <p class="text-xs text-[#5a7a5c] mt-1">Common questions</p>
      </a>
    </div>

  @elseif ($section === 'profile')
    <div class="bg-white rounded-xl border p-6">
      <h2 class="font-black text-lg mb-4">Profile Information</h2>
      <div class="grid md:grid-cols-2 gap-4 mb-4">
        <div>
          <p class="text-xs text-[#5a7a5c] font-bold">First Name</p>
          <p class="text-sm font-bold">{{ $user->first_name }}</p>
        </div>
        <div>
          <p class="text-xs text-[#5a7a5c] font-bold">Last Name</p>
          <p class="text-sm font-bold">{{ $user->last_name }}</p>
        </div>
        <div>
          <p class="text-xs text-[#5a7a5c] font-bold">Email</p>
          <p class="text-sm font-bold">{{ $user->email }}</p>
        </div>
        <div>
          <p class="text-xs text-[#5a7a5c] font-bold">Phone</p>
          <p class="text-sm font-bold">{{ $user->phone ?: '-' }}</p>
        </div>
        <div class="md:col-span-2">
          <p class="text-xs text-[#5a7a5c] font-bold">Address</p>
          <p class="text-sm font-bold">{{ $user->address ?: '-' }}</p>
        </div>
      </div>
      <div class="flex gap-3">
        <a href="{{ route('profile.edit') }}" class="px-4 py-2 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a] transition-colors">Edit Profile</a>
        <a href="{{ route('profile.change-password') }}" class="px-4 py-2 rounded-xl border text-sm font-bold hover:bg-[#e8f5e9] transition-colors">Change Password</a>
      </div>
    </div>
  @endif
</main>

@endsection
