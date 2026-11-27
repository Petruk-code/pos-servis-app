<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><style>
body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#111} h2,h3{margin:0 0 8px} table{width:100%;border-collapse:collapse;margin-bottom:14px} th,td{border:1px solid #999;padding:4px 6px} th{background:#eee} .text-end{text-align:right} .muted{color:#555}
</style></head><body>
<h2>Laporan POS Servis</h2>
<div class="muted">Periode: {{ $startDate }} s/d {{ $endDate }}</div><br>
<h3>Ringkasan</h3>
<table><tbody>
<tr><td>Total Omzet</td><td class="text-end">Rp {{ number_format($totalSales,0,',','.') }}</td></tr>
<tr><td>Total Transaksi</td><td class="text-end">{{ $totalTransactions }}</td></tr>
<tr><td>Produk Terjual</td><td class="text-end">{{ $totalProductsSold }}</td></tr>
<tr><td>HPP/COGS</td><td class="text-end">Rp {{ number_format($cogs,0,',','.') }}</td></tr>
<tr><td>Laba Kotor Est.</td><td class="text-end">Rp {{ number_format($totalSales-$cogs,0,',','.') }}</td></tr>
<tr><td>Omzet Jasa Servis</td><td class="text-end">Rp {{ number_format($serviceRevenue,0,',','.') }}</td></tr>
<tr><td>Omzet Sparepart Servis</td><td class="text-end">Rp {{ number_format($servicePartsRevenue,0,',','.') }}</td></tr>
<tr><td>Tiket Servis / Selesai</td><td class="text-end">{{ $serviceTotal }} / {{ $serviceCompleted }}</td></tr>
</tbody></table>
<h3>Transaksi Terbaru (max 100)</h3>
<table><thead><tr><th>Invoice</th><th>Tanggal</th><th>Kasir</th><th>Metode</th><th class="text-end">Total</th></tr></thead><tbody>
@foreach($transactions as $t)
<tr><td>{{ $t->invoice_number }}</td><td>{{ $t->created_at->format('d/m/Y H:i') }}</td><td>{{ $t->user?->name }}</td><td>{{ $t->payment_method }}</td><td class="text-end">Rp {{ number_format($t->total,0,',','.') }}</td></tr>
@endforeach
</tbody></table>
<h3>Tiket Servis Terbaru (max 100)</h3>
<table><thead><tr><th>Tiket</th><th>Tanggal</th><th>Pelanggan</th><th>Status</th><th class="text-end">Total</th></tr></thead><tbody>
@foreach($services as $s)
<tr><td>{{ $s->ticket_number }}</td><td>{{ $s->created_at->format('d/m/Y') }}</td><td>{{ $s->customer_name }}</td><td>{{ $s->status }}</td><td class="text-end">Rp {{ number_format($s->grand_total,0,',','.') }}</td></tr>
@endforeach
</tbody></table>
</body></html>
