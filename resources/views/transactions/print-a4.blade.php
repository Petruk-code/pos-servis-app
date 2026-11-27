<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><title>Nota Transaksi A4 - {{ $transaction->invoice_number }}</title>
<style>
body{font-family:Arial,sans-serif;font-size:13px;color:#111;max-width:800px;margin:20px auto}.toolbar{margin-bottom:12px;display:flex;gap:8px;justify-content:flex-end}
.btn{padding:8px 12px;border:1px solid #444;background:#fff;cursor:pointer;text-decoration:none;color:#111;font-size:12px}.header{text-align:center;border-bottom:2px solid #000;padding-bottom:10px;margin-bottom:15px}
.items{width:100%;border-collapse:collapse;margin:15px 0}.items th,.items td{border:1px solid #999;padding:6px 8px}.items th{background:#f2f2f2}.text-end{text-align:right}
@media print{.toolbar{display:none}body{margin:0;max-width:none}}
</style></head><body>
<div class="toolbar">
  <a class="btn" href="{{ route('transactions.print', ['transaction'=>$transaction,'format'=>'thermal']) }}">Versi Thermal</a>
  <button class="btn" onclick="window.print()">Cetak A4</button>
</div>
<div class="header"><h2 style="margin:0">POS SERVIS</h2><p>Nota Transaksi</p><p><strong>{{ $transaction->invoice_number }}</strong> | {{ $transaction->created_at->format('d/m/Y H:i') }}</p></div>
<div>Kasir: {{ $transaction->user->name ?? '-' }} | Status: {{ strtoupper($transaction->status) }} | Bayar: {{ strtoupper($transaction->payment_status ?? 'paid') }} ({{ strtoupper($transaction->payment_method) }})</div>
<table class="items"><thead><tr><th>Produk</th><th style="width:60px;text-align:center">Qty</th><th class="text-end">Harga</th><th class="text-end">Subtotal</th></tr></thead><tbody>
@foreach($transaction->details as $d)
<tr><td>{{ $d->product_name }}<div style="font-size:11px;color:#555">{{ $d->sku }}</div></td><td style="text-align:center">{{ $d->quantity }}</td><td class="text-end">Rp {{ number_format($d->unit_price,0,',','.') }}</td><td class="text-end">Rp {{ number_format($d->subtotal,0,',','.') }}</td></tr>
@endforeach
</tbody><tfoot>
<tr><th colspan="3" class="text-end">TOTAL</th><th class="text-end">Rp {{ number_format($transaction->total,0,',','.') }}</th></tr>
<tr><td colspan="3" class="text-end">Dibayar</td><td class="text-end">Rp {{ number_format($transaction->paid_amount,0,',','.') }}</td></tr>
<tr><td colspan="3" class="text-end">Kembalian</td><td class="text-end">Rp {{ number_format($transaction->change_amount,0,',','.') }}</td></tr>
<tr><td colspan="3" class="text-end">Sisa</td><td class="text-end">Rp {{ number_format($transaction->due_amount ?? 0,0,',','.') }}</td></tr>
</tfoot></table>
<div style="text-align:center;margin-top:20px">Terima kasih atas kunjungan Anda.</div>
</body></html>
