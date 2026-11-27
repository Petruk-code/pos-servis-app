<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ServiceTicket;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->role?->name === 'owner', 403);
        // Periode default: bulan ini
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $validated['start_date']
            ?? now()->startOfMonth()->toDateString();

        $endDate = $validated['end_date']
            ?? now()->toDateString();

        // Query transaksi yang berhasil
        $completedTransactions = Transaction::query()
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        // Ringkasan penjualan
        $totalSales = (clone $completedTransactions)->sum('total');

        $totalTransactions = (clone $completedTransactions)->count();

        // Total produk terjual
        $totalProductsSold = DB::table('transaction_details')
            ->join(
                'transactions',
                'transaction_details.transaction_id',
                '=',
                'transactions.id'
            )
            ->where('transactions.status', 'completed')
            ->whereDate('transactions.created_at', '>=', $startDate)
            ->whereDate('transactions.created_at', '<=', $endDate)
            ->sum('transaction_details.quantity');

        // Jumlah produk aktif yang terdaftar
        $registeredProducts = Product::where('is_active', true)->count();

        // Produk terlaris
        $topProducts = DB::table('transaction_details')
            ->join(
                'transactions',
                'transaction_details.transaction_id',
                '=',
                'transactions.id'
            )
            ->where('transactions.status', 'completed')
            ->whereDate('transactions.created_at', '>=', $startDate)
            ->whereDate('transactions.created_at', '<=', $endDate)
            ->select(
                'transaction_details.product_name',
                DB::raw('SUM(transaction_details.quantity) as total_sold')
            )
            ->groupBy('transaction_details.product_name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        // Riwayat transaksi berhasil
        $transactions = (clone $completedTransactions)
            ->with('user')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Ringkasan servis
        $serviceBase = ServiceTicket::query()
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        $serviceTotal = (clone $serviceBase)->count();
        $serviceCompleted = (clone $serviceBase)->whereIn('status', ['selesai', 'diambil'])->count();
        $serviceActive = (clone $serviceBase)->whereNotIn('status', ['selesai', 'diambil', 'batal'])->count();
        $serviceRevenue = (clone $serviceBase)
            ->whereNotNull('transaction_id')
            ->sum('service_fee');
        $servicePartsRevenue = DB::table('service_ticket_parts')
            ->join('service_tickets', 'service_ticket_parts.service_ticket_id', '=', 'service_tickets.id')
            ->whereNotNull('service_tickets.transaction_id')
            ->whereDate('service_tickets.created_at', '>=', $startDate)
            ->whereDate('service_tickets.created_at', '<=', $endDate)
            ->sum('service_ticket_parts.subtotal');

        $serviceByStatus = ServiceTicket::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->groupBy('status')
            ->pluck('total', 'status');

        $topTechnicians = ServiceTicket::query()
            ->join('users', 'service_tickets.technician_id', '=', 'users.id')
            ->select(
                'users.name',
                DB::raw('COUNT(service_tickets.id) as total_tickets'),
                DB::raw("SUM(CASE WHEN service_tickets.status IN ('selesai', 'diambil') THEN 1 ELSE 0 END) as completed_tickets")
            )
            ->whereDate('service_tickets.created_at', '>=', $startDate)
            ->whereDate('service_tickets.created_at', '<=', $endDate)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('completed_tickets')
            ->limit(5)
            ->get();

        return view('reports.index', compact(
            'startDate',
            'endDate',
            'totalSales',
            'totalTransactions',
            'totalProductsSold',
            'registeredProducts',
            'topProducts',
            'transactions',
            'serviceTotal',
            'serviceCompleted',
            'serviceActive',
            'serviceRevenue',
            'servicePartsRevenue',
            'serviceByStatus',
            'topTechnicians'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless(auth()->user()->role?->name === 'owner', 403);
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? now()->toDateString();
        $filename = "laporan-pos-servis-{$startDate}-sd-{$endDate}.csv";

        return response()->streamDownload(function () use ($startDate, $endDate) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Laporan POS Servis']);
            fputcsv($out, ['Periode', $startDate, 's/d', $endDate]);
            fputcsv($out, []);

            $completed = Transaction::query()->where('status', 'completed')
                ->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate);
            $totalSales = (clone $completed)->sum('total');
            $totalTransactions = (clone $completed)->count();
            $totalProductsSold = DB::table('transaction_details')
                ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
                ->where('transactions.status', 'completed')
                ->whereDate('transactions.created_at', '>=', $startDate)
                ->whereDate('transactions.created_at', '<=', $endDate)
                ->sum('transaction_details.quantity');

            fputcsv($out, ['Ringkasan Penjualan']);
            fputcsv($out, ['Total Omzet', $totalSales]);
            fputcsv($out, ['Total Transaksi', $totalTransactions]);
            fputcsv($out, ['Produk Terjual', $totalProductsSold]);
            fputcsv($out, []);

            $serviceBase = ServiceTicket::query()->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate);
            $serviceRevenue = (clone $serviceBase)->whereNotNull('transaction_id')->sum('service_fee');
            $servicePartsRevenue = DB::table('service_ticket_parts')
                ->join('service_tickets', 'service_ticket_parts.service_ticket_id', '=', 'service_tickets.id')
                ->whereNotNull('service_tickets.transaction_id')
                ->whereDate('service_tickets.created_at', '>=', $startDate)
                ->whereDate('service_tickets.created_at', '<=', $endDate)
                ->sum('service_ticket_parts.subtotal');

            fputcsv($out, ['Ringkasan Servis']);
            fputcsv($out, ['Total Tiket', (clone $serviceBase)->count()]);
            fputcsv($out, ['Tiket Selesai/Diambil', (clone $serviceBase)->whereIn('status', ['selesai','diambil'])->count()]);
            fputcsv($out, ['Omzet Jasa', $serviceRevenue]);
            fputcsv($out, ['Omzet Sparepart Servis', $servicePartsRevenue]);
            fputcsv($out, []);

            fputcsv($out, ['Transaksi Selesai']);
            fputcsv($out, ['Invoice', 'Tanggal', 'Kasir', 'Total', 'Metode', 'Status']);
            Transaction::query()->with('user')->where('status', 'completed')
                ->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)
                ->orderBy('created_at')
                ->chunk(200, function ($rows) use ($out) {
                    foreach ($rows as $t) {
                        fputcsv($out, [$t->invoice_number, $t->created_at->format('Y-m-d H:i'), $t->user?->name, $t->total, $t->payment_method, $t->status]);
                    }
                });
            fputcsv($out, []);

            fputcsv($out, ['Tiket Servis']);
            fputcsv($out, ['Tiket', 'Tanggal', 'Pelanggan', 'Perangkat', 'Status', 'Teknisi', 'Jasa', 'Sparepart', 'Total']);
            ServiceTicket::query()->with(['technician','parts'])->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)
                ->orderBy('created_at')
                ->chunk(200, function ($rows) use ($out) {
                    foreach ($rows as $s) {
                        fputcsv($out, [$s->ticket_number, $s->created_at->format('Y-m-d H:i'), $s->customer_name, $s->device_brand_model, $s->status, $s->technician?->name, $s->service_fee, $s->parts->sum('subtotal'), $s->grand_total]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
