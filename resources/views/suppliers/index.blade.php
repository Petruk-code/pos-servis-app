@extends('layouts.app')
@section('page-title','Supplier')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">Kelola supplier untuk inventaris/pembelian.</p>
    <a href="{{ route('suppliers.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Supplier</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<div class="card border-0 shadow-sm"><div class="card-body">
<form method="GET" class="mb-3"><div class="input-group"><input name="search" class="form-control" value="{{ $search }}" placeholder="Cari nama/HP supplier"><button class="btn btn-primary">Cari</button><a href="{{ route('suppliers.index') }}" class="btn btn-secondary">Reset</a></div></form>
<div class="table-responsive"><table class="table table-hover align-middle"><thead class="table-light"><tr><th>Nama</th><th>Kontak</th><th>HP</th><th>Email</th><th>Status</th><th width="150">Aksi</th></tr></thead><tbody>
@forelse($suppliers as $s)
<tr>
<td class="fw-semibold">{{ $s->name }}</td><td>{{ $s->contact_name ?: '-' }}</td><td>{{ $s->phone ?: '-' }}</td><td>{{ $s->email ?: '-' }}</td>
<td>@if($s->is_active)<span class="badge bg-success">Aktif</span>@else<span class="badge bg-secondary">Nonaktif</span>@endif</td>
<td><a href="{{ route('suppliers.edit',$s) }}" class="btn btn-sm btn-warning">Edit</a>
<form action="{{ route('suppliers.destroy',$s) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus supplier ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">Hapus</button></form></td>
</tr>
@empty<tr><td colspan="6" class="text-center text-muted py-4">Belum ada supplier.</td></tr>@endforelse
</tbody></table></div>
<div class="mt-3">{{ $suppliers->links() }}</div>
</div></div>
@endsection
