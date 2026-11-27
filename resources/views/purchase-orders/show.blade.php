@extends('layouts.app')
@section('page-title','Detail Purchase Order')
@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<div class="row g-2">
<div class="col-md-3"><div class="text-muted small">No. PO</div><div class="fw-bold">{{ $po->po_number }}</div></div>
<div class="col-md-3"><div class="text-muted small">Supplier</div><div>{{ $po->supplier?->name }}</div></div>
<div class="col-md-2"><div class="text-muted small">Tanggal</div><div>{{ $po->order_date->format('d/m/Y') }}</div></div>
<div class="col-md-2"><div class="text-muted small">Status</div><span class="badge {{ $po->status==='received'?'bg-success':($po->status==='cancelled'?'bg-secondary':'bg-warning text-dark') }}">{{ $po->status }}</span></div>
<div class="col-md-2"><div class="text-muted small">Total</div><div class="fw-bold">Rp {{ number_format($po->total,0,',','.') }}</div></div>
@if($po->notes)<div class="col-12"><div class="text-muted small">Catatan</div><div>{{ $po->notes }}</div></div>@endif
</div></div></div>
<div class="card border-0 shadow-sm mb-3"><div class="card-body table-responsive">
<table class="table table-sm align-middle"><thead class="table-light"><tr><th>Produk</th><th class="text-end">Qty</th><th class="text-end">Harga Modal</th><th class="text-end">Subtotal</th></tr></thead><tbody>
@foreach($po->items as $item)
<tr><td>{{ $item->product?->name }}</td><td class="text-end">{{ $item->quantity }}</td><td class="text-end">Rp {{ number_format($item->unit_cost,0,',','.') }}</td><td class="text-end">Rp {{ number_format($item->subtotal,0,',','.') }}</td></tr>
@endforeach
</tbody></table>
</div></div>
<div class="d-flex gap-2">
<a href="{{ route('purchase-orders.index') }}" class="btn btn-secondary">Kembali</a>
@can('receive', $po)
<form method="POST" action="{{ route('purchase-orders.receive',$po) }}" onsubmit="return confirm('Terima PO ini dan tambah stok?')">@csrf<button class="btn btn-success">Terima Barang / Tambah Stok</button></form>
@endcan
@can('cancel', $po)
<form method="POST" action="{{ route('purchase-orders.cancel',$po) }}" onsubmit="return confirm('Batalkan PO ini?')">@csrf<button class="btn btn-outline-danger">Batalkan PO</button></form>
@endcan
</div>
@endsection
