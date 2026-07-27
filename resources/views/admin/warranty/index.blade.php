@extends('admin.layouts.app')
@section('title','Freshness Guarantee')
@section('header','Freshness Guarantee Requests')
@section('content')
<div class="bg-white rounded-xl border p-5">
  <h2 class="font-black mb-3">Requests ({{ $requests->count() }})</h2>
  @foreach($requests as $r)
    <div class="border rounded-xl p-4 mb-3">
      <p class="font-bold text-sm">{{ $r->product_name }} — {{ $r->order_number }} — {{ $r->status }}</p>
      <p class="text-xs text-[#5a7a5c]">{{ $r->user->first_name ?? '' }} • {{ $r->created_at->format('M j, Y') }} • {{ $r->quality_issue }}</p>
      <p class="text-sm mt-2">{{ $r->defect_description }}</p>
      @if($r->proof_of_purchase_path)
        @php $paths = \App\Helpers\FormHelper::decodeAttachmentPaths($r->proof_of_purchase_path); @endphp
        <div class="flex gap-2 mt-2">@foreach($paths as $p)<a href="{{ asset($p) }}" target="_blank" class="text-xs text-[#17611f] underline">📎 {{ basename($p) }}</a>@endforeach</div>
      @endif
      <form method="POST" action="{{ route('admin.warranty.update',$r->id) }}" class="mt-3 flex gap-2">
        @csrf @method('PUT')
        <select name="status" class="border rounded-lg px-2 py-1 text-xs"><option value="pending" {{ $r->status==='pending'?'selected':'' }}>Pending</option><option value="approved" {{ $r->status==='approved'?'selected':'' }}>Approved</option><option value="denied" {{ $r->status==='denied'?'selected':'' }}>Denied</option></select>
        <input type="text" name="admin_note" value="{{ $r->admin_note }}" placeholder="Admin note" class="flex-1 border rounded-lg px-2 py-1 text-xs">
        <button class="px-3 py-1 rounded-lg bg-[#17611f] text-white text-xs">Update</button>
      </form>
    </div>
  @endforeach
</div>
@endsection
