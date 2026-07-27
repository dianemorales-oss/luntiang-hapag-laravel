@extends('admin.layouts.app')
@section('title', 'Products | Admin')
@section('header', 'Products')
@section('content')

  <h1 class="text-2xl font-black mb-1">Product Management</h1>
  <p class="text-sm text-[#5a7a5c] mb-6">{{ $products->count() }} products total</p>

  <!-- Edit Product Section (Toggled dynamically) -->
  @if ($editProduct)
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-6 mb-6 shadow-sm">
      <h2 class="font-black text-lg mb-4 text-[#1a2e1c]">Edit: {{ $editProduct->name }}</h2>
      <form method="POST" action="{{ route('admin.products.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="{{ $editProduct->id }}">
        
        <div>
          <label class="text-xs font-bold text-[#5a7a5c]">Name</label>
          <input name="name" value="{{ $editProduct->name }}" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" required>
        </div>
        <div>
          <label class="text-xs font-bold text-[#5a7a5c]">Variety</label>
          <input name="variety" value="{{ $editProduct->variety }}" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
        </div>
        <div class="col-span-2">
          <label class="text-xs font-bold text-[#5a7a5c]">Description</label>
          <textarea name="description" rows="2" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">{{ $editProduct->description }}</textarea>
        </div>
        <div>
          <label class="text-xs font-bold text-[#5a7a5c]">Price</label>
          <input name="price" type="number" step="0.01" value="{{ $editProduct->price }}" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" required>
        </div>
        <div>
          <label class="text-xs font-bold text-[#5a7a5c]">Unit</label>
          <input name="unit" value="{{ $editProduct->unit }}" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
        </div>
        <div>
          <label class="text-xs font-bold text-[#5a7a5c]">Plants Available</label>
          <input name="plants_available" type="number" value="{{ $editProduct->plants_available }}" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
        </div>
        <div>
          <label class="text-xs font-bold text-[#5a7a5c]">Shelf Life</label>
          <input name="shelf_life" value="{{ $editProduct->shelf_life }}" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
        </div>
        <div class="col-span-2">
          <label class="text-xs font-bold text-[#5a7a5c]">Best For</label>
          <input name="best_for" value="{{ $editProduct->best_for }}" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
        </div>
        <div class="col-span-2">
          <label class="text-xs font-bold text-[#5a7a5c]">Storage Instructions</label>
          <textarea name="storage_instructions" rows="2" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">{{ $editProduct->storage_instructions }}</textarea>
        </div>
        <div class="flex gap-4 col-span-2 py-1">
          <label class="flex items-center gap-2 text-sm text-[#1a2e1c] font-semibold cursor-pointer">
            <input type="checkbox" name="is_best_seller" {{ $editProduct->is_best_seller ? 'checked' : '' }} class="rounded border-gray-300 text-[#17611f] focus:ring-[#17611f]/40"> Best Seller
          </label>
          <label class="flex items-center gap-2 text-sm text-[#1a2e1c] font-semibold cursor-pointer">
            <input type="checkbox" name="is_new" {{ $editProduct->is_new ? 'checked' : '' }} class="rounded border-gray-300 text-[#17611f] focus:ring-[#17611f]/40"> New
          </label>
          <label class="flex items-center gap-2 text-sm text-[#1a2e1c] font-semibold cursor-pointer">
            <input type="checkbox" name="is_active" {{ $editProduct->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-[#17611f] focus:ring-[#17611f]/40"> Active
          </label>
        </div>
        <div class="col-span-2 flex gap-3 pt-2">
          <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a] transition-all shadow-sm">Save Changes</button>
          <a href="{{ route('admin.products.index') }}" class="px-5 py-2.5 rounded-xl border border-[rgba(27,94,32,0.12)] text-[#1a2e1c] text-sm font-bold hover:bg-[#e8f5e9] transition-colors">Cancel</a>
        </div>
      </form>
    </div>
  @endif

  <!-- Add Product form (If not editing) -->
  @if (!$editProduct)
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-6 mb-6 shadow-sm">
      <h2 class="font-black text-lg mb-4 text-[#1a2e1c]">Add Product</h2>
      <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        <input type="hidden" name="action" value="create">
        <div>
          <label class="text-xs font-bold text-[#5a7a5c]">Name</label>
          <input name="name" placeholder="Romaine Lettuce" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2.5 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" required>
        </div>
        <div>
          <label class="text-xs font-bold text-[#5a7a5c]">Variety</label>
          <input name="variety" placeholder="Giulia NH" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2.5 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
        </div>
        <div class="col-span-2">
          <label class="text-xs font-bold text-[#5a7a5c]">Description</label>
          <textarea name="description" rows="2" placeholder="Description of the product..." class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40"></textarea>
        </div>
        <div>
          <label class="text-xs font-bold text-[#5a7a5c]">Price</label>
          <input name="price" type="number" step="0.01" placeholder="45.00" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2.5 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" required>
        </div>
        <div>
          <label class="text-xs font-bold text-[#5a7a5c]">Unit</label>
          <input name="unit" placeholder="per cup" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2.5 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
        </div>
        <div>
          <label class="text-xs font-bold text-[#5a7a5c]">Plants Available (Stock)</label>
          <input name="plants_available" type="number" placeholder="150" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2.5 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
        </div>
        <div>
          <label class="text-xs font-bold text-[#5a7a5c]">Image Upload</label>
          <input type="file" name="image" class="w-full border border-[rgba(27,94,32,0.12)] rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none">
        </div>
        <div class="flex gap-4 col-span-2 py-1">
          <label class="flex items-center gap-2 text-sm text-[#1a2e1c] font-semibold cursor-pointer">
            <input type="checkbox" name="is_best_seller" class="rounded border-gray-300 text-[#17611f] focus:ring-[#17611f]/40"> Best Seller
          </label>
          <label class="flex items-center gap-2 text-sm text-[#1a2e1c] font-semibold cursor-pointer">
            <input type="checkbox" name="is_new" class="rounded border-gray-300 text-[#17611f] focus:ring-[#17611f]/40"> New
          </label>
        </div>
        <div class="col-span-2 flex gap-3 pt-2">
          <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a] transition-all shadow-sm">Add Product</button>
        </div>
      </form>
    </div>
  @endif

  <!-- KPI Cards -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-4 text-center">
      <p class="text-2xl font-black text-[#17611f]">{{ $plantsAvailableSum }}</p>
      <p class="text-xs text-[#5a7a5c] font-bold mt-1">Plants Available</p>
    </div>
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-4 text-center">
      <p class="text-2xl font-black text-amber-600">{{ $lowAvailability }}</p>
      <p class="text-xs text-[#5a7a5c] font-bold mt-1">Low Availability</p>
    </div>
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-4 text-center">
      <p class="text-2xl font-black text-red-500">{{ $outOfStock }}</p>
      <p class="text-xs text-[#5a7a5c] font-bold mt-1">Out of Stock</p>
    </div>
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-4 text-center">
      <p class="text-2xl font-black text-[#17611f]">{{ $activeCount }}</p>
      <p class="text-xs text-[#5a7a5c] font-bold mt-1">Active</p>
    </div>
  </div>

  <!-- Products Grid Table -->
  <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-[#f4faf5] text-[#5a7a5c] text-xs uppercase border-b">
            <th class="p-3 text-left">Product</th>
            <th class="p-3 text-left">Price</th>
            <th class="p-3 text-left">Available</th>
            <th class="p-3 text-left">Status</th>
            <th class="p-3 text-left">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($products as $p)
            <tr class="border-t border-[rgba(27,94,32,0.05)] hover:bg-gray-50/50 transition-colors {{ !$p->is_active ? 'opacity-50' : '' }}">
              <td class="p-3">
                <div class="flex items-center gap-3">
                  <img src="{{ asset($p->image ?: 'images/lettuce/hero-farm.png') }}" onerror="this.onerror=null;this.src='{{ asset('images/lettuce/hero-farm.png') }}';" class="w-10 h-10 rounded-lg object-cover border" alt="">
                  <div>
                    <p class="font-bold text-sm text-[#1a2e1c]">{{ $p->name }}</p>
                    <p class="text-xs text-[#5a7a5c]">{{ $p->variety ?: $p->unit }}</p>
                  </div>
                </div>
              </td>
              <td class="p-3 font-bold text-[#17611f]">₱{{ number_format($p->price, 2) }}</td>
              <td class="p-3 font-semibold text-sm">
                <span class="{{ $p->plants_available > 50 ? 'text-green-600' : ($p->plants_available > 0 ? 'text-amber-600' : 'text-red-600') }}">
                  {{ $p->plants_available }}
                </span>
              </td>
              <td class="p-3">
                @if (!$p->is_active)
                  <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-[#9e9e9e]">Inactive</span>
                @elseif ($p->plants_available == 0)
                  <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">Unavailable</span>
                @elseif ($p->plants_available <= 20)
                  <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">Limited</span>
                @else
                  <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-[#e8f5e9] text-[#17611f]">Available</span>
                @endif
                @if ($p->is_best_seller)
                  <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-bold bg-[#fff8e1] text-[#e65100]">Best Seller</span>
                @endif
              </td>
              <td class="p-3">
                <div class="flex gap-1 flex-wrap">
                  <a href="?edit={{ $p->id }}" class="px-3 py-1.5 rounded-lg border border-[rgba(27,94,32,0.12)] text-[#1a2e1c] text-xs font-bold hover:bg-[#e8f5e9] transition-colors">Edit</a>
                  <form method="POST" action="{{ route('admin.products.store') }}" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="{{ $p->id }}">
                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold text-[#5a7a5c] hover:bg-[#e8f5e9] transition-colors">
                      {{ $p->is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                  </form>
                  <form method="POST" action="{{ route('admin.products.destroy', $p->id) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete {{ addslashes($p->name) }}?\n\nThis action cannot be undone and will permanently remove the product, its images, and related bundle links.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-red-50 text-red-600 border border-red-100 text-xs font-bold hover:bg-red-100 hover:text-red-700 transition-colors">Delete</button>
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
