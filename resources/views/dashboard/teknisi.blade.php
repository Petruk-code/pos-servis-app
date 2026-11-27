@extends('layouts.app')

@section('title', 'Dashboard Teknisi - POS Servis')
@section('page-title', 'Dashboard Teknisi')

@section('content')
<div class="alert alert-primary border-0 shadow-sm">
    Selamat datang, <strong>{{ Auth::user()->name }}</strong>!
    Berikut ringkasan antrean dan pekerjaan servis Anda.
</div>

<div class="row g-3">
    <div class="col-md-6 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-2">Tugas Aktif Saya</p>
                <h4 class="fw-bold mb-0">{{ number_format($assignedToMe) }}</h4>
                <small class="text-muted">Tiket servis yang ditugaskan ke Anda</small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-2">Antre / Belum Dicek</p>
                <h4 class="fw-bold text-secondary mb-0">{{ number_format($queueCount) }}</h4>
                <small class="text-muted">Tiket menunggu penanganan</small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-2">Sedang Dicek</p>
                <h4 class="fw-bold text-info mb-0">{{ number_format($inProgressCount) }}</h4>
                <small class="text-muted">Tiket sedang dikerjakan</small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-2">Menunggu Sparepart</p>
                <h4 class="fw-bold text-warning mb-0">{{ number_format($waitingPartCount) }}</h4>
                <small class="text-muted">Perlu menunggu komponen</small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-2">Selesai (Siap Diambil)</p>
                <h4 class="fw-bold text-primary mb-0">{{ number_format($readyCount) }}</h4>
                <small class="text-muted">Menunggu pelanggan mengambil</small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-2">Selesai Hari Ini</p>
                <h4 class="fw-bold text-success mb-0">{{ number_format($completedToday) }}</h4>
                <small class="text-muted">Tiket yang Anda selesaikan hari ini</small>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-2">Overdue SLA</p>
                <h4 class="fw-bold text-danger mb-0">{{ number_format($overdueCount ?? 0) }}</h4>
                <small class="text-muted">Lewat estimasi selesai, belum selesai/diambil</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Antrean Servis Aktif</h5>
                    <a href="{{ route('services.index') }}" class="btn btn-outline-primary btn-sm">Lihat Semua</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tiket</th>
                                <th>Pelanggan / Perangkat</th>
                                <th>Status</th>
                                <th>Diterima</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activeTickets as $ticket)
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
                                        'diambil' => 'Diambil',
                                        'batal' => 'Batal',
                                    ];
                                @endphp
                                <tr>
                                    <td>
                                        <strong class="text-primary">{{ $ticket->ticket_number }}</strong>
                                        <div class="small text-muted">{{ $ticket->created_at->format('d/m/Y H:i') }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $ticket->customer_name }}</div>
                                        <div class="small text-muted">{{ $ticket->device_brand_model }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $badgeMap[$ticket->status] ?? 'bg-secondary' }}">
                                            {{ $labelMap[$ticket->status] ?? $ticket->status }}
                                        </span>
                                    </td>
                                    <td>{{ $ticket->receiver?->name ?? '-' }}</td>
                                    <td>
                                        <a href="{{ route('services.show', $ticket) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Tidak ada tiket servis aktif.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h5 class="mb-3">Tiket Terakhir Saya Tangani</h5>

                @forelse($recentTickets as $ticket)
                    <div class="py-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <a href="{{ route('services.show', $ticket) }}" class="fw-semibold text-decoration-none">{{ $ticket->ticket_number }}</a>
                                <div class="small text-muted">{{ $ticket->customer_name }} - {{ $ticket->device_brand_model }}</div>
                            </div>
                            <span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">Belum ada tiket yang ditangani.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
