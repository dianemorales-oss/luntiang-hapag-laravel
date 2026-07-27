@extends('admin.layouts.app')
@section('title','Customers')
@section('header','Customers')
@section('content')
<div class="bg-white rounded-xl border p-5">
  <h2 class="font-black mb-3">Customers ({{ $customers->count() }})</h2>
  <table class="w-full text-sm"><thead><tr class="text-left text-[#5a7a5c]"><th class="p-2">Name</th><th>Email</th><th>Phone</th><th>Address</th></tr></thead>
  <tbody>@foreach($customers as $c)<tr class="border-t"><td class="p-2">{{ $c->first_name }} {{ $c->last_name }}</td><td>{{ $c->email }}</td><td>{{ $c->phone }}</td><td class="text-xs">{{ $c->address }}</td></tr>@endforeach</tbody></table>
</div>
@endsection
