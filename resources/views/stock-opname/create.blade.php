@extends('layouts.app')
@section('page-title', 'Stok Opname')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">Isi stok fisik. Hanya produk yang berubah akan dicatat sebagai penyesuaian.</p>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<form method="GET" class="row g-2"><div class="col-md-10"><input name="search" class="form-control" value="{{ $search }}" placeholder="Cari SKU/nama produk..."></div><div class="col-md-2 d-flex gap-2"><button class="btn btn-primary w-100">Cari</button><a href="{{ route('stock-opname.create') }}" class="btn btn-secondary">Reset</a></div></form>
</div></div>
<form method="POST" action="{{ route('stock-opname.store', ['search'=>$search]) }}">
@csrf
<div class="card border-0 shadow-sm"><div class="card-body">
<div class="mb-3"><label class="form-label">Catatan opname (opsional)</label><input name="notes" class="form-control" value="{{ old('notes') }}" placeholder="Contoh: Opname gudang akhir bulan"></div>
<div class="table-responsive"><table class="table table-sm align-middle"><thead class="table-light"><tr><th>Produk</th><th>SKU</th><th class="text-end">Stok Sistem</th><th style="width:160px">Stok Fisik</th><th class="text-end">Selisih</th></tr></thead><tbody>
@forelse($products as $i => $product)
@php $oldCount = old('items.'.$i.'.counted_stock', $product->stock); @endphp
<tr>
<td class="fw-semibold">{{ $product->name }}<div class="small text-muted">{{ $product->unit }}</div></td>
<td>{{ $product->sku }}</td>
<td class="text-end system-stock" data-stock="{{ $product->stock }}">{{ $product->stock }}</td>
<td>
<input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $product->id }}">
<input type="number" min="0" step="1" class="form-control form-control-sm counted-stock" name="items[{{ $i }}][counted_stock]" value="{{ $oldCount }}">
</td>
<td class="text-end diff-cell fw-semibold">0</td>
</tr>
@empty
<tr><td colspan="5" class="text-center text-muted py-4">Tidak ada produk aktif.</td></tr>
@endforelse
</tbody></table></div>
<div class="d-flex gap-2"><button class="btn btn-primary" @if($products->isEmpty()) disabled @endif>Simpan Stok Opname</button><a href="{{ route('stock-movements.index') }}" class="btn btn-secondary">Kembali</a></div>
</div></div>
</form>
<script>
document.querySelectorAll('tr').forEach(row => {
  const sys = row.querySelector('.system-stock');
  const inp = row.querySelector('.counted-stock');
  const diff = row.querySelector('.diff-cell');
  if(!sys||!inp||!diff) return;
  const calc = () => { const d = (parseInt(inp.value||0,10) - parseInt(sys.dataset.stock,10)); diff.textContent = (d>0?'+':'')+d; diff.className = 'text-end diff-cell fw-semibold ' + (d===0?'text-muted':d>0?'text-success':'text-danger'); };
  inp.addEventListener('input', calc); calc();
});
</script>
@endsection
