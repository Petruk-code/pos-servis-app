<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota Servis - {{ $ticket->ticket_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #111; max-width: 720px; margin: 20px auto; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .header h2 { margin: 0; font-size: 18px; }
        .header p { margin: 3px 0; font-size: 12px; }
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .grid td { padding: 4px 6px; vertical-align: top; }
        .items { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .items th, .items td { border: 1px solid #999; padding: 6px 8px; }
        .items th { background: #f2f2f2; text-align: left; }
        .text-end { text-align: right; }
        .footer { margin-top: 25px; display: flex; justify-content: space-between; text-align: center; }
        .terms { font-size: 11px; color: #444; border-top: 1px dashed #999; padding-top: 8px; margin-top: 15px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 15px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 16px; cursor: pointer;">Cetak Nota</button>
    </div>

    <div class="header">
        <h2>POS SERVIS</h2>
        <p>Tanda Terima Servis & Nota Pembayaran</p>
        <p><strong>No. Tiket: {{ $ticket->ticket_number }}</strong> | Tanggal: {{ $ticket->created_at->format('d/m/Y H:i') }}</p>
    </div>

    <table class="grid">
        <tr>
            <td style="width: 50%;">
                <strong>Data Pelanggan:</strong><br>
                Nama: {{ $ticket->customer_name }}<br>
                No. HP/WA: {{ $ticket->customer_phone }}
            </td>
            <td style="width: 50%;">
                <strong>Detail Perangkat:</strong><br>
                Unit: {{ $ticket->device_type }} - {{ $ticket->device_brand_model }}<br>
                Serial Number: {{ $ticket->serial_number ?: '-' }}<br>
                Kelengkapan: {{ $ticket->accessories ?: '-' }}
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding-top: 8px;">
                <strong>Keluhan Kerusakan:</strong> {{ $ticket->problem_description }}<br>
                @if($ticket->technician_notes)
                    <strong>Tindakan / Diagnosa Teknisi:</strong> {{ $ticket->technician_notes }}
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Deskripsi Jasa / Sparepart</th>
                <th style="width: 60px; text-align: center;">Qty</th>
                <th class="text-end" style="width: 130px;">Harga</th>
                <th class="text-end" style="width: 140px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Biaya Jasa Servis ({{ $ticket->device_brand_model }})</td>
                <td style="text-align: center;">1</td>
                <td class="text-end">Rp {{ number_format($ticket->service_fee, 0, ',', '.') }}</td>
                <td class="text-end">Rp {{ number_format($ticket->service_fee, 0, ',', '.') }}</td>
            </tr>
            @foreach($ticket->parts as $part)
                <tr>
                    <td>{{ $part->product->name }}</td>
                    <td style="text-align: center;">{{ $part->quantity }}</td>
                    <td class="text-end">Rp {{ number_format($part->unit_price, 0, ',', '.') }}</td>
                    <td class="text-end">Rp {{ number_format($part->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-end">TOTAL TAGIHAN</th>
                <th class="text-end">Rp {{ number_format($ticket->grand_total, 0, ',', '.') }}</th>
            </tr>
            @if($ticket->transaction)
                <tr>
                    <td colspan="3" class="text-end">Status Pembayaran ({{ $ticket->transaction->invoice_number }})</td>
                    <td class="text-end"><strong>LUNAS</strong></td>
                </tr>
            @else
                <tr>
                    <td colspan="3" class="text-end">Estimasi Awal</td>
                    <td class="text-end">Rp {{ number_format($ticket->estimated_cost, 0, ',', '.') }}</td>
                </tr>
            @endif
        </tfoot>
    </table>

    <div class="terms">
        <strong>Ketentuan Servis & Garansi:</strong><br>
        1. Nota ini wajib dibawa saat pengambilan unit servis.<br>
        2. Garansi servis berlaku <strong>{{ $ticket->warranty_days }} hari</strong> sejak tanggal pengambilan untuk kerusakan yang sama (tidak berlaku akibat cairan, jatuh, atau segel rusak).<br>
        3. Toko tidak bertanggung jawab atas kehilangan data; harap backup data penting sebelum servis.
    </div>

    <div class="footer">
        <div>
            Pelanggan,<br><br><br>
            ( {{ $ticket->customer_name }} )
        </div>
        <div>
            Hormat Kami,<br><br><br>
            ( {{ $ticket->receiver?->name ?? 'Admin Toko' }} )
        </div>
    </div>
</body>
</html>`