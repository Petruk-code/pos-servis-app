@extends('layouts.app')
@section('page-title','Buat Purchase Order')
@section('content')
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('purchase-orders.store') }}">@csrf
<div class="card border-0 shadow-sm mb-3"><div class="card-body row g-3">
<div class="col-md-4"><label class="form-label">Supplier *</label><select name="supplier_id" class="form-select" required><option value="">Pilih supplier</option>@foreach($suppliers as $s)<option value="{{ $s->id }}" @selected(old('supplier_id')==$s->id)>{{ $s->name }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Tanggal PO *</label><input type="date" name="order_date" class="form-control" value="{{ old('order_date', now()->toDateString()) }}" required></div>
<div class="col-md-4"><label class="form-label">Catatan</label><input name="notes" class="form-control" value="{{ old('notes') }}"></div>
</div></div>
<div class="card border-0 shadow-sm"><div class="card-body">
<div class="table-responsive"><table class="table table-sm align-middle" id="po-items"><thead class="table-light"><tr><th>Produk</th><th style="width:120px">Qty</th><th style="width:160px">Harga Modal</th><th style="width:50px"></th></tr></thead><tbody></tbody></table></div>
<button type="button" class="btn btn-outline-primary btn-sm mb-3" id="add-item">Tambah Item</button>
<div class="d-flex gap-2"><button class="btn btn-primary">Simpan PO</button><a href="{{ route('purchase-orders.index') }}" class="btn btn-secondary">Batal</a></div>
</div></div>
</form>
<template id="item-row"><tr><td><select name="items[IDX][product_id]" class="form-select form-select-sm" required><option value="">Pilih produk</option>@foreach($products as $p)<option value="{{ $p->id }}" data-cost="{{ $p->cost_price }}">{{ $p->sku }} - {{ $p->name }}</option>@endforeach</select></td><td><input type="number" min="1" name="items[IDX][quantity]" class="form-control form-control-sm" value="1" required></td><td><input type="number" min="0" step="0.01" name="items[IDX][unit_cost]" class="form-control form-control-sm cost" value="0" required></td><td><button type="button" class="btn btn-sm btn-outline-danger remove">x</button></td></tr></template>
<script>
let idx=0; const body=document.querySelector('#po-items tbody'); const tpl=document.getElementById('item-row').innerHTML;
function addRow(){ body.insertAdjacentHTML('beforeend', tpl.replaceAll('IDX', idx++)); }
body.addEventListener('change',e=>{ if(e.target.matches('select')){ const cost=e.target.selectedOptions[0]?.dataset.cost; const c=e.target.closest('tr').querySelector('.cost'); if(cost && (!c.value||c.value==='0')) c.value=cost; }});
body.addEventListener('click',e=>{ if(e.target.classList.contains('remove')) e.target.closest('tr').remove(); });
document.getElementById('add-item').addEventListener('click',addRow); addRow();
</script>
@endsection
