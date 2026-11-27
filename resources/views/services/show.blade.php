@extends('layouts.app')

@section('title', 'Detail Servis ' . $ticket->ticket_number)
@section('page-title', 'Detail Servis')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                Tiket <span class="text-primary">{{ $ticket->ticket_number }}</span>
                @if($ticket->transaction_id)
                    <span class="badge bg-success ms-2">LUNAS ({{ $ticket->transaction->invoice_number }})</span>
                @endif
            </h4>
            <p class="text-muted small mb-0">Diterima pada {{ $ticket->created_at->format('d M Y H:i') }} oleh {{ $ticket->receiver?->name ?? '-' }}</p>
        </div>
        <div class="d-flex gap-2">
            @can('print', $ticket)
            <a href="{{ route('services.print', $ticket) }}" target="_blank" class="btn btn-outline-dark">
                <i class="bi bi-printer me-1"></i> Cetak Tanda Terima / Nota
            </a>
            @endcan
            <a href="{{ route('services.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-transparent fw-bold">Informasi Pelanggan & Unit</div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width: 38%;">Pelanggan</td>
                            <td class="fw-semibold">{{ $ticket->customer_name }} ({{ $ticket->customer_phone }})</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Email</td>
                            <td>
                                {{ $ticket->customer_email ?: '-' }}
                                @if($ticket->notify_customer)
                                    <span class="badge bg-info text-dark ms-1">Notifikasi Email Aktif</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Perangkat</td>
                            <td class="fw-semibold">{{ $ticket->device_type }} - {{ $ticket->device_brand_model }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Serial Number (SN)</td>
                            <td>{{ $ticket->serial_number ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Password Perangkat</td>
                            <td>
                                @if($ticket->device_password)
                                    @can('viewDevicePassword', $ticket)
                                        <code>{{ $ticket->device_password }}</code>
                                    @else
                                        <span class="text-muted fst-italic">Disembunyikan</span>
                                    @endcan
                                @elseif(in_array($ticket->status, ['diambil','batal'], true))
                                    <span class="text-muted fst-italic">Dihapus setelah unit selesai/diambil (kebijakan retensi)</span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Kelengkapan</td>
                            <td>{{ $ticket->accessories ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Checklist Masuk</td>
                            <td>
                                @if(!empty($ticket->intake_checklist))
                                    <ul class="mb-0 ps-3 small">
                                        @foreach($ticket->intake_checklist as $item)<li>{{ $item }}</li>@endforeach
                                    </ul>
                                @else - @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Foto Masuk/Keluar</td>
                            <td class="small">
                                @if($ticket->intake_photo_path)<a href="{{ asset('storage/'.$ticket->intake_photo_path) }}" target="_blank">Foto Masuk</a>@endif
                                @if($ticket->handover_photo_path)<span class="mx-1">|</span><a href="{{ asset('storage/'.$ticket->handover_photo_path) }}" target="_blank">Foto Keluar</a>@endif
                                @if(!$ticket->intake_photo_path && !$ticket->handover_photo_path)-@endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tanda Terima</td>
                            <td class="small">Masuk: {{ $ticket->received_signature_name ?: '-' }} | Ambil: {{ $ticket->pickup_signature_name ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Keluhan Awal</td>
                            <td class="text-danger fw-semibold">{{ $ticket->problem_description }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Estimasi Awal</td>
                            <td>Rp {{ number_format($ticket->estimated_cost, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent fw-bold">Update Pengerjaan & Biaya Jasa</div>
                <div class="card-body">
                    <form action="{{ route('services.update', $ticket) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Status Pengerjaan</label>
                                <select name="status" class="form-select" {{ $ticket->transaction_id ? 'disabled' : '' }}>
                                    <option value="antre" {{ $ticket->status === 'antre' ? 'selected' : '' }}>Antre</option>
                                    <option value="dicek" {{ $ticket->status === 'dicek' ? 'selected' : '' }}>Sedang Dicek</option>
                                    <option value="menunggu_sparepart" {{ $ticket->status === 'menunggu_sparepart' ? 'selected' : '' }}>Menunggu Sparepart</option>
                                    <option value="selesai" {{ $ticket->status === 'selesai' ? 'selected' : '' }}>Selesai (Siap Diambil)</option>
                                    <option value="diambil" {{ $ticket->status === 'diambil' ? 'selected' : '' }}>Sudah Diambil</option>
                                    <option value="batal" {{ $ticket->status === 'batal' ? 'selected' : '' }}>Dibatalkan</option>
                                </select>
                                @if($ticket->transaction_id)
                                    <input type="hidden" name="status" value="{{ $ticket->status }}">
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teknisi Penanggung Jawab</label>
                                <select name="technician_id" class="form-select" {{ $ticket->transaction_id ? 'disabled' : '' }}>
                                    <option value="">-- Belum Ditentukan --</option>
                                    @foreach($technicians as $tech)
                                        <option value="{{ $tech->id }}" {{ $ticket->technician_id == $tech->id ? 'selected' : '' }}>
                                            {{ $tech->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($ticket->transaction_id)
                                    <input type="hidden" name="technician_id" value="{{ $ticket->technician_id }}">
                                @endif
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan Diagnosa / Tindakan Teknisi</label>
                                <textarea name="technician_notes" rows="3" class="form-control"
                                          placeholder="Contoh: Ganti thermal paste, install ulang Windows 11, upgrade SSD 512GB..."
                                          {{ $ticket->transaction_id ? 'readonly' : '' }}>{{ old('technician_notes', $ticket->technician_notes) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Biaya Jasa Servis (Rp)</label>
                                <input type="number" name="service_fee" class="form-control" min="0" step="1000"
                                       value="{{ old('service_fee', (int) $ticket->service_fee) }}"
                                       {{ $ticket->transaction_id ? 'readonly' : '' }} required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Garansi Servis (Hari)</label>
                                <input type="number" name="warranty_days" class="form-control" min="0"
                                       value="{{ old('warranty_days', $ticket->warranty_days) }}"
                                       {{ $ticket->transaction_id ? 'readonly' : '' }} required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Pelanggan</label>
                                <input type="email" name="customer_email" class="form-control"
                                       value="{{ old('customer_email', $ticket->customer_email) }}"
                                       {{ $ticket->transaction_id ? 'readonly' : '' }}>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="notify_customer" value="1" id="notify_customer_show"
                                           {{ old('notify_customer', $ticket->notify_customer) ? 'checked' : '' }}
                                           {{ $ticket->transaction_id ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="notify_customer_show">Kirim update status ke email</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Foto Kondisi Masuk</label>
                                <input type="file" name="intake_photo" class="form-control" accept="image/*" {{ $ticket->transaction_id ? 'disabled' : '' }}>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Foto Kondisi Keluar/Ambil</label>
                                <input type="file" name="handover_photo" class="form-control" accept="image/*" {{ $ticket->transaction_id ? 'disabled' : '' }}>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Penerima Masuk</label>
                                <input type="text" name="received_signature_name" class="form-control" value="{{ old('received_signature_name', $ticket->received_signature_name) }}" {{ $ticket->transaction_id ? 'readonly' : '' }}>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Pengambil</label>
                                <input type="text" name="pickup_signature_name" class="form-control" value="{{ old('pickup_signature_name', $ticket->pickup_signature_name) }}" {{ $ticket->transaction_id ? 'readonly' : '' }}>
                            </div>
                        </div>

                        @if(!$ticket->transaction_id)
                            <div class="mt-3 text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan Servis
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-transparent fw-bold">Sparepart / Komponen Terpakai</div>
                <div class="card-body">
                    @can('manageParts', $ticket)
                        <form action="{{ route('services.parts.add', $ticket) }}" method="POST" class="row g-2 mb-3">
                            @csrf
                            <div class="col-md-7">
                                <select name="product_id" class="form-select" required>
                                    <option value="">-- Pilih Sparepart dari Stok --</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">
                                            {{ $product->name }} (Stok: {{ $product->stock }} | Rp {{ number_format($product->selling_price, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-dark w-100">
                                    <i class="bi bi-plus-lg"></i> Tambah
                                </button>
                            </div>
                        </form>
                    @endcan

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Sparepart</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Harga</th>
                                    <th class="text-end">Subtotal</th>
                                    @can('manageParts', $ticket)
                                        <th class="text-center">Aksi</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ticket->parts as $part)
                                    <tr>
                                        <td>{{ $part->product->name ?? '-' }}</td>
                                        <td class="text-center">{{ $part->quantity }}</td>
                                        <td class="text-end">Rp {{ number_format($part->unit_price, 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($part->subtotal, 0, ',', '.') }}</td>
                                        @can('manageParts', $ticket)
                                            <td class="text-center">
                                                <form action="{{ route('services.parts.remove', [$ticket, $part]) }}" method="POST" onsubmit="return confirm('Hapus sparepart ini dan kembalikan stok?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        @endcan
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ auth()->user()->can('manageParts', $ticket) ? 5 : 4 }}" class="text-center text-muted py-3">Belum ada sparepart yang dipasang.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-transparent fw-bold">Riwayat Status Servis</div>
                <div class="card-body">
                    @if($ticket->statusHistories->isEmpty())
                        <div class="text-muted small">Belum ada riwayat status.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Dari</th>
                                        <th>Ke</th>
                                        <th>Oleh</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($ticket->statusHistories as $history)
                                    <tr>
                                        <td>{{ $history->created_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ $history->from_status ? ucfirst(str_replace('_', ' ', $history->from_status)) : '-' }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $history->to_status)) }}</span></td>
                                        <td>{{ $history->user?->name ?? '-' }}</td>
                                        <td>{{ $history->notes ?: '-' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent fw-bold">Ringkasan Biaya & Pembayaran Kasir</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Biaya Jasa Servis:</span>
                        <strong>Rp {{ number_format($ticket->service_fee, 0, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Sparepart:</span>
                        <strong>Rp {{ number_format($ticket->parts->sum('subtotal'), 0, ',', '.') }}</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-bold">TOTAL TAGIHAN:</span>
                        <span class="fw-bold text-primary fs-5">Rp {{ number_format($ticket->grand_total, 0, ',', '.') }}</span>
                    </div>

                    @if($ticket->transaction_id)
                        <div class="alert alert-success mb-0">
                            <div class="fw-semibold">Sudah dibayar lunas pada {{ $ticket->picked_up_at?->format('d M Y H:i') }}.</div>
                            <div class="small mt-1">
                                No. Invoice: {{ $ticket->transaction->invoice_number }} |
                                Bayar: Rp {{ number_format($ticket->transaction->paid_amount, 0, ',', '.') }} |
                                Kembali: Rp {{ number_format($ticket->transaction->change_amount, 0, ',', '.') }}
                            </div>
                        </div>
                    @elseif(auth()->user()->can('pay', $ticket))
                        <form action="{{ route('services.pay', $ticket) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Jumlah Uang Diterima (Tunai / Rp)</label>
                                <input type="number" name="paid_amount" class="form-control" min="{{ (int) $ticket->grand_total }}" step="1" value="{{ old('paid_amount', (int) $ticket->grand_total) }}" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-cash-coin me-1"></i> Bayar Lunas & Masuk Laporan POS
                            </button>
                        </form>
                    @else
                        <div class="alert alert-info mb-0">
                            Jika pengerjaan sudah selesai, ubah status menjadi <strong>Selesai</strong> agar Kasir dapat memproses pembayaran.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
