@extends('layouts.app')

@section('title', 'Antrean Servis Komputer')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-tools me-2"></i>Antrean Servis Komputer</h4>
            <p class="text-muted small mb-0">Kelola penerimaan unit, diagnosa teknisi, sparepart, dan pembayaran servis.</p>
        </div>
        @can('create', App\Models\ServiceTicket::class)
        <a href="{{ route('services.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Terima Servis Baru
        </a>
        @endcan
    </div>

    {{-- Filter & Pencarian --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('services.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Cari Tiket / Pelanggan / Perangkat / SN</label>
                    <input type="text" name="search" class="form-control" value="{{ $search }}"
                           placeholder="Contoh: SRV-2026..., Budi, Asus ROG, SN...">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Filter Status</label>
                    <select name="status" class="form-select">
                        <option value="">-- Semua Status --</option>
                        <option value="antre" {{ $status === 'antre' ? 'selected' : '' }}>Antre</option>
                        <option value="dicek" {{ $status === 'dicek' ? 'selected' : '' }}>Sedang Dicek</option>
                        <option value="menunggu_sparepart" {{ $status === 'menunggu_sparepart' ? 'selected' : '' }}>Menunggu Sparepart</option>
                        <option value="selesai" {{ $status === 'selesai' ? 'selected' : '' }}>Selesai (Siap Diambil)</option>
                        <option value="diambil" {{ $status === 'diambil' ? 'selected' : '' }}>Sudah Diambil / Lunas</option>
                        <option value="batal" {{ $status === 'batal' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100"><i class="bi bi-search me-1"></i> Filter</button>
                    <a href="{{ route('services.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabel Tiket --}}
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No. Tiket & Tgl</th>
                        <th>Pelanggan</th>
                        <th>Perangkat & SN</th>
                        <th>Keluhan</th>
                        <th>Teknisi</th>
                        <th>Status</th>
                        <th class="text-end">Total Tagihan</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        @php
                            $badgeMap = [
                                'antre' => 'bg-secondary',
                                'dicek' => 'bg-info text-dark',
                                'menunggu_sparepart' => 'bg-warning text-dark',
                                'selesai' => 'bg-primary',
                                'diambil' => 'bg-success',
                                'batal' => 'bg-danger',
                            ];
                            $labelMap = [
                                'antre' => 'Antre',
                                'dicek' => 'Sedang Dicek',
                                'menunggu_sparepart' => 'Menunggu Sparepart',
                                'selesai' => 'Selesai',
                                'diambil' => 'Diambil / Lunas',
                                'batal' => 'Batal',
                            ];
                        @endphp
                        <tr>
                            <td>
                                <span class="fw-bold text-primary">{{ $ticket->ticket_number }}</span>
                                <div class="small text-muted">{{ $ticket->created_at->format('d M Y H:i') }}</div>
                                @if($ticket->estimated_finish_at)
                                    <div class="small {{ (!in_array($ticket->status,['selesai','diambil','batal']) && $ticket->estimated_finish_at->isPast()) ? 'text-danger fw-semibold' : 'text-muted' }}">
                                        SLA: {{ $ticket->estimated_finish_at->format('d/m H:i') }}
                                        @if(!in_array($ticket->status,['selesai','diambil','batal']) && $ticket->estimated_finish_at->isPast()) OVERDUE @endif
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $ticket->customer_name }}</div>
                                <div class="small text-muted"><i class="bi bi-whatsapp text-success me-1"></i>{{ $ticket->customer_phone }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $ticket->device_type }}</span>
                                <div class="fw-semibold mt-1">{{ $ticket->device_brand_model }}</div>
                                @if($ticket->serial_number)
                                    <div class="small text-muted">SN: {{ $ticket->serial_number }}</div>
                                @endif
                            </td>
                            <td style="max-width: 220px;">
                                <div class="text-truncate" title="{{ $ticket->problem_description }}">
                                    {{ $ticket->problem_description }}
                                </div>
                            </td>
                            <td>{{ $ticket->technician?->name ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $badgeMap[$ticket->status] ?? 'bg-secondary' }}">
                                    {{ $labelMap[$ticket->status] ?? $ticket->status }}
                                </span>
                                @if($ticket->transaction_id)
                                    <div><span class="badge bg-success-subtle text-success border border-success mt-1">LUNAS</span></div>
                                @endif
                            </td>
                            <td class="text-end fw-bold">
                                Rp {{ number_format($ticket->grand_total, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('services.show', $ticket) }}" class="btn btn-outline-primary" title="Detail & Proses">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                    <a href="{{ route('services.print', $ticket) }}" target="_blank" class="btn btn-outline-secondary" title="Cetak Nota">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                Belum ada data tiket servis. Klik <strong>Terima Servis Baru</strong> untuk memulai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tickets->hasPages())
            <div class="card-footer">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
