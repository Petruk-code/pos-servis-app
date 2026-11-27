@extends('layouts.app')
@section('page-title','Purchase Order')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><p class="text-muted mb-0">PO/penerimaan barang dari supplier.</p><a href="{{ route('purchase-orders.create') }}" class="btn btn-primary">Buat PO</a></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card border-0 shadow-sm"><div class="card-body table-responsive">
<table class="table table-hover align-middle"><thead class="table-light"><tr><th>No. PO</th><th>Tanggal</th><th>Supplier</th><th>Status</th><th class="text-end">Total</th><th></th></tr></thead><tbody>
@forelse($orders as $po)
<tr>
<td class="fw-semibold">{{ $po->po_number }}</td>
<td>{{ $po->order_date->format('d/m/Y') }}</td>
<td>{{ $po->supplier?->name }}</td>
<td><span class="badge {{ $po->status==='received'?'bg-success':($po->status==='cancelled'?'bg-secondary':'bg-warning text-dark') }}">{{ $po->status }}</span></td>
<td class="text-end">Rp {{ number_format($po->total,0,',','.') }}</td>
<td class="text-end"><a href="{{ route('purchase-orders.show',$po) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
</tr>
@empty<tr><td colspan="6" class="text-center text-muted py-4">Belum ada PO.</td></tr>@endforelse
</tbody></table>
<div class="mt-3">{{ $orders->links() }}</div>
</div></div>
@endsection
