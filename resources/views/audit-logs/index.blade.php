@extends('layouts.app')
@section('page-title','Audit Log')
@section('content')
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<form method="GET" class="row g-2"><div class="col-md-5"><input name="event" class="form-control" value="{{ $event }}" placeholder="Filter event..."></div><div class="col-md-5"><input name="user" class="form-control" value="{{ $user }}" placeholder="Filter user..."></div><div class="col-md-2 d-flex gap-2"><button class="btn btn-primary w-100">Filter</button><a href="{{ route('audit-logs.index') }}" class="btn btn-secondary">Reset</a></div></form>
</div></div>
<div class="card border-0 shadow-sm"><div class="card-body table-responsive">
<table class="table table-sm align-middle"><thead class="table-light"><tr><th>Waktu</th><th>User</th><th>Event</th><th>Auditable</th><th>Deskripsi</th></tr></thead><tbody>
@forelse($logs as $log)
<tr>
<td class="small">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
<td>{{ $log->user?->name ?? '-' }}</td>
<td><span class="badge bg-light text-dark border">{{ $log->event }}</span></td>
<td class="small">{{ class_basename($log->auditable_type) }}#{{ $log->auditable_id }}</td>
<td class="small">{{ $log->description }}</td>
</tr>
@empty<tr><td colspan="5" class="text-center text-muted py-4">Belum ada audit log.</td></tr>@endforelse
</tbody></table>
<div class="mt-3">{{ $logs->links() }}</div>
</div></div>
@endsection
