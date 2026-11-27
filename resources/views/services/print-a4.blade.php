<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota Servis A4 - {{ $ticket->ticket_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #111; max-width: 800px; margin: 20px auto; }
        .toolbar { margin-bottom: 12px; display: flex; gap: 8px; justify-content: flex-end; }
        .btn { padding: 8px 12px; border: 1px solid #444; background: #fff; cursor: pointer; text-decoration: none; color: #111; font-size: 12px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .header h2 { margin: 0; font-size: 20px; } .header p { margin: 3px 0; font-size: 12px; }
        .grid, .items { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .grid td { padding: 4px 6px; vertical-align: top; }
        .items th, .items td { border: 1px solid #999; padding: 6px 8px; }
        .items th { background: #f2f2f2; text-align: left; }
        .text-end { text-align: right; }
        .footer { margin-top: 30px; display: flex; justify-content: space-between; text-align: center; }
        .terms { font-size: 11px; color: #444; border-top: 1px dashed #999; padding-top: 8px; margin-top: 15px; }
        @media print { .toolbar { display: none; } body { margin: 0; max-width: none; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <a class="btn" href="{{ route('services.print', ['service'=>$ticket,'format'=>'thermal']) }}">Versi Thermal</a>
        <button class="btn" onclick="window.print()">Cetak A4</button>
    </div>
    <div class="header">
        <h2>POS SERVIS</h2>
        <p>Tanda Terima Servis & Nota Pembayaran</p>
        <p><strong>No. Tiket: {{ $ticket->ticket_number }}</strong> | {{ $ticket->created_at->format('d/m/Y H:i') }}</p>
    </div>
    <table class="grid">
        <tr>
            <td style="width:50%"><strong>Pelanggan</strong><br>{{ $ticket->customer_name }}<br>{{ $ticket->customer_phone }}<br>{{ $ticket->customer_email ?? '-' }}</td>
            <td style="width:50%"><strong>Perangkat</strong><br>{{ $ticket->device_type }} - {{ $ticket->device_brand_model }}<br>SN: {{ $ticket->serial_number ?: '-' }}<br>Kelengkapan: {{ $ticket->accessories ?: '-' }}</td>
        </tr>
        <tr><td colspan="2"><strong>Keluhan:</strong> {{ $ticket->problem_description }}@if($ticket->technician_notes)<br><strong>Tindakan:</strong> {{ $ticket->technician_notes }}@endif</td></tr>
        <tr><td colspan="2"><strong>Status:</strong> {{ strtoupper($ticket->status) }} | <strong>Bayar:</strong> {{ strtoupper($ticket->payment_status ?? 'unpaid') }} | <strong>SLA:</strong> {{ $ticket->estimated_finish_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
    </table>
    <table class="items">
        <thead><tr><th>Deskripsi</th><th style="width:60px;text-align:center">Qty</th><th class="text-end" style="width:130px">Harga</th><th class="text-end" style="width:140px">Subtotal</th></tr></thead>
        <tbody>
            <tr><td>Biaya Jasa Servis</td><td style="text-align:center">1</td><td class="text-end">Rp {{ number_format($ticket->service_fee,0,',','.') }}</td><td class="text-end">Rp {{ number_format($ticket->service_fee,0,',','.') }}</td></tr>
            @foreach($ticket->parts as $part)
            <tr><td>{{ $part->product->name }}</td><td style="text-align:center">{{ $part->quantity }}</td><td class="text-end">Rp {{ number_format($part->unit_price,0,',','.') }}</td><td class="text-end">Rp {{ number_format($part->subtotal,0,',','.') }}</td></tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr><th colspan="3" class="text-end">TOTAL</th><th class="text-end">Rp {{ number_format($ticket->grand_total,0,',','.') }}</th></tr>
            <tr><td colspan="3" class="text-end">Dibayar</td><td class="text-end">Rp {{ number_format($ticket->paid_amount ?? 0,0,',','.') }}</td></tr>
            <tr><td colspan="3" class="text-end">Sisa</td><td class="text-end">Rp {{ number_format(max(0,($ticket->grand_total - ($ticket->paid_amount ?? 0))),0,',','.') }}</td></tr>
        </tfoot>
    </table>
    <div class="terms"><strong>Ketentuan:</strong> Nota wajib dibawa saat pengambilan. Garansi {{ $ticket->warranty_days }} hari untuk kerusakan sama (tidak termasuk cairan/jatuh/segel rusak). Backup data tanggung jawab pelanggan.</div>
    <div class="footer"><div>Pelanggan<br><br><br>( {{ $ticket->customer_name }} )</div><div>Petugas<br><br><br>( {{ $ticket->receiver?->name ?? 'Admin' }} )</div></div>
</body>
</html>
