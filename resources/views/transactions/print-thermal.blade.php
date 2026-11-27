<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Nota Thermal - {{ $transaction->invoice_number }}</title>
<style>@page{margin:0}body{font-family:monospace;font-size:11px;width:80mm;margin:0 auto;padding:6px;color:#000}.center{text-align:center}.right{text-align:right}.line{border-top:1px dashed #000;margin:6px 0}table{width:100%;border-collapse:collapse}td{vertical-align:top;padding:1px 0}.toolbar{margin:6px 0;display:flex;gap:4px}.btn{font-size:10px;padding:4px;border:1px solid #000;background:#fff;text-decoration:none;color:#000}@media print{.toolbar{display:none}}
</style></head><body>
<div class="toolbar"><a class="btn" href="{{ route('transactions.print',['transaction'=>$transaction,'format'=>'a4']) }}">A4</a><button class="btn" onclick="window.print()">Cetak</button></div>
<div class="center"><strong>POS SERVIS</strong><br>{{ $transaction->invoice_number }}<br>{{ $transaction->created_at->format('d/m/Y H:i') }}</div>
<div class="line"></div>
<div>Kasir: {{ $transaction->user->name ?? '-' }}<br>Status: {{ strtoupper($transaction->status) }}<br>Bayar: {{ strtoupper($transaction->payment_method) }}</div>
<div class="line"></div>
<table>@foreach($transaction->details as $d)<tr><td>{{ \Illuminate\Support\Str::limit($d->product_name,18) }} x{{ $d->quantity }}</td><td class="right">Rp {{ number_format($d->subtotal,0,',','.') }}</td></tr>@endforeach</table>
<div class="line"></div>
<table>
<tr><td><strong>TOTAL</strong></td><td class="right"><strong>Rp {{ number_format($transaction->total,0,',','.') }}</strong></td></tr>
<tr><td>Dibayar</td><td class="right">Rp {{ number_format($transaction->paid_amount,0,',','.') }}</td></tr>
<tr><td>Kembali</td><td class="right">Rp {{ number_format($transaction->change_amount,0,',','.') }}</td></tr>
<tr><td>Sisa</td><td class="right">Rp {{ number_format($transaction->due_amount ?? 0,0,',','.') }}</td></tr>
</table>
<div class="center" style="margin-top:8px">Terima kasih</div>
</body></html>
