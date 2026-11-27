<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota Thermal - {{ $ticket->ticket_number }}</title>
    <style>
        @page { margin: 0; }
        body { font-family: monospace; font-size: 11px; width: 80mm; margin: 0 auto; padding: 6px; color: #000; }
        .center { text-align: center; } .right { text-align: right; } .line { border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; } td { vertical-align: top; padding: 1px 0; }
        .toolbar { margin: 6px 0; display:flex; gap:4px; } .btn{font-size:10px;padding:4px;border:1px solid #000;background:#fff;text-decoration:none;color:#000}
        @media print { .toolbar { display: none; } body { width: 80mm; } }
        .small { font-size: 10px; }
    </style>
</head>
<body>
    <div class="toolbar">
        <a class="btn" href="{{ route('services.print', ['service'=>$ticket,'format'=>'a4']) }}">A4</a>
        <button class="btn" onclick="window.print()">Cetak</button>
    </div>
    <div class="center">
        <strong>POS SERVIS</strong><br>
        Nota Servis<br>
        {{ $ticket->ticket_number }}<br>
        {{ $ticket->created_at->format('d/m/Y H:i') }}
    </div>
    <div class="line"></div>
    <div>
        Pelanggan: {{ $ticket->customer_name }}<br>
        HP: {{ $ticket->customer_phone }}<br>
        Unit: {{ $ticket->device_brand_model }}<br>
        SN: {{ $ticket->serial_number ?: '-' }}<br>
        Status: {{ strtoupper($ticket->status) }}/{{ strtoupper($ticket->payment_status ?? 'unpaid') }}
    </div>
    <div class="line"></div>
    <table>
        <tr><td>Jasa Servis</td><td class="right">Rp {{ number_format($ticket->service_fee,0,',','.') }}</td></tr>
        @foreach($ticket->parts as $part)
        <tr><td>{{ \Illuminate\Support\Str::limit($part->product->name,20) }} x{{ $part->quantity }}</td><td class="right">Rp {{ number_format($part->subtotal,0,',','.') }}</td></tr>
        @endforeach
    </table>
    <div class="line"></div>
    <table>
        <tr><td><strong>TOTAL</strong></td><td class="right"><strong>Rp {{ number_format($ticket->grand_total,0,',','.') }}</strong></td></tr>
        <tr><td>Dibayar</td><td class="right">Rp {{ number_format($ticket->paid_amount ?? 0,0,',','.') }}</td></tr>
        <tr><td>Sisa</td><td class="right">Rp {{ number_format(max(0,$ticket->grand_total-($ticket->paid_amount ?? 0)),0,',','.') }}</td></tr>
    </table>
    <div class="line"></div>
    <div class="small">Garansi: {{ $ticket->warranty_days }} hari. Nota wajib dibawa saat ambil. Backup data tanggung jawab pelanggan.</div>
    <div class="center" style="margin-top:8px">Terima kasih</div>
</body>
</html>
