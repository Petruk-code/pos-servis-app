@extends('layouts.app')

@section('title', 'Terima Servis Baru')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-pc-display me-2"></i>Form Penerimaan Unit Servis</h4>
            <p class="text-muted small mb-0">Catat identitas pelanggan, detail perangkat, kelengkapan, dan keluhan kerusakan.</p>
        </div>
        <a href="{{ route('services.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <form action="{{ route('services.store') }}" method="POST">
        @csrf
        <div class="row g-4">
            {{-- Kolom Kiri: Data Pelanggan & Perangkat --}}
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-transparent fw-bold">1. Data Pelanggan & Perangkat</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Pelanggan <span class="text-danger">*</span></label>
                                <input type="text" name="customer_name" class="form-control @error('customer_name') is-invalid @enderror"
                                       value="{{ old('customer_name') }}" required>
                                @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No. HP / WhatsApp <span class="text-danger">*</span></label>
                                <input type="text" name="customer_phone" class="form-control @error('customer_phone') is-invalid @enderror"
                                       value="{{ old('customer_phone') }}" placeholder="0812..." required>
                                @error('customer_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Pelanggan (opsional)</label>
                                <input type="email" name="customer_email" class="form-control @error('customer_email') is-invalid @enderror"
                                       value="{{ old('customer_email') }}" placeholder="pelanggan@email.com">
                                @error('customer_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="notify_customer" value="1" id="notify_customer" {{ old('notify_customer') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_customer">
                                        Kirim update status ke email pelanggan
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Jenis Perangkat <span class="text-danger">*</span></label>
                                <select name="device_type" class="form-select" required>
                                    <option value="Laptop" {{ old('device_type') === 'Laptop' ? 'selected' : '' }}>Laptop / Notebook</option>
                                    <option value="PC Rakitan / Desktop" {{ old('device_type') === 'PC Rakitan / Desktop' ? 'selected' : '' }}>PC Rakitan / Desktop</option>
                                    <option value="All-in-One PC" {{ old('device_type') === 'All-in-One PC' ? 'selected' : '' }}>All-in-One PC</option>
                                    <option value="Printer / Scanner" {{ old('device_type') === 'Printer / Scanner' ? 'selected' : '' }}>Printer / Scanner</option>
                                    <option value="Monitor" {{ old('device_type') === 'Monitor' ? 'selected' : '' }}>Monitor</option>
                                    <option value="Komponen / Lainnya" {{ old('device_type') === 'Komponen / Lainnya' ? 'selected' : '' }}>Komponen / Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Merk & Tipe / Model <span class="text-danger">*</span></label>
                                <input type="text" name="device_brand_model" class="form-control @error('device_brand_model') is-invalid @enderror"
                                       value="{{ old('device_brand_model') }}" placeholder="Contoh: Asus Vivobook A416 / PC Core i5" required>
                                @error('device_brand_model')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Serial Number (SN)</label>
                                <input type="text" name="serial_number" class="form-control"
                                       value="{{ old('serial_number') }}" placeholder="Opsional">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password Windows / BIOS</label>
                                <input type="text" name="device_password" class="form-control"
                                       value="{{ old('device_password') }}" placeholder="Kosongkan jika tidak ada">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Kelengkapan yang Dititipkan</label>
                                <input type="text" name="accessories" class="form-control"
                                       value="{{ old('accessories') }}" placeholder="Contoh: Unit + Charger Ori + Tas Laptop">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Keluhan & Penugasan --}}
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-transparent fw-bold">2. Keluhan Kerusakan & Estimasi</div>
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Keluhan / Gejala Kerusakan <span class="text-danger">*</span></label>
                                <textarea name="problem_description" rows="4" class="form-control @error('problem_description') is-invalid @enderror"
                                          placeholder="Contoh: Mati total, layar bergaris, install ulang Windows 11 + Office, ganti SSD..." required>{{ old('problem_description') }}</textarea>
                                @error('problem_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estimasi Biaya Awal (Rp)</label>
                                <input type="number" name="estimated_cost" class="form-control" min="0" step="1000"
                                       value="{{ old('estimated_cost', 0) }}">
                                <div class="form-text">Isi 0 jika menunggu pengecekan teknisi.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tugaskan ke Teknisi</label>
                                <select name="technician_id" class="form-select">
                                    <option value="">-- Pilih Teknisi (Opsional) --</option>
                                    @foreach($technicians as $tech)
                                        <option value="{{ $tech->id }}" {{ old('technician_id') == $tech->id ? 'selected' : '' }}>
                                            {{ $tech->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                            <a href="{{ route('services.index') }}" class="btn btn-light">Batal</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-1"></i> Simpan Tiket Servis
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
