<?php

namespace App\Http\Controllers;

use App\Models\CancellationRequest;
use App\Models\Product;
use App\Models\ServiceTicket;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Transaction::class);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'date' => ['nullable', 'date'],
        ]);
        $search = trim($validated['search'] ?? '');
        $date = $validated['date'] ?? null;

        $query = Transaction::with('user')
            ->when(auth()->user()->role?->name === 'kasir', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {

                    // Cari berdasarkan nomor invoice.
                    $query->where('invoice_number', 'like', "%{$search}%")

                        // Cari berdasarkan nama kasir.
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%");
                        })

                        // Cari berdasarkan status transaksi.
                        ->orWhere('status', 'like', "%{$search}%");

                    // Padanan status dalam bahasa Indonesia.
                    $statusKeywords = [
                        'selesai' => 'completed',
                        'dibatalkan' => 'cancelled',
                        'batal' => 'cancelled',
                    ];

                    foreach ($statusKeywords as $keyword => $status) {
                        if (
                            stripos($keyword, $search) !== false ||
                            stripos($search, $keyword) !== false
                        ) {
                            $query->orWhere('status', $status);
                        }
                    }
                });
            })
            // Filter berdasarkan tanggal transaksi.
            ->when($date, function ($query) use ($date) {
                $query->whereDate('created_at', $date);
            })
            ->latest();

        $transactions = $query
            ->paginate(10)
            ->withQueryString();

        return view('transactions.index', compact(
            'transactions',
            'search',
            'date'
        ));
    }

    public function show(Transaction $transaction): View
    {
        $this->authorize('view', $transaction);

        $transaction->load([
            'user',
            'details',
            'cancelledBy',
        ]);

        $pendingCancellationRequest = $transaction
            ->cancellationRequests()
            ->where('status', 'pending')
            ->latest()
            ->first();

        $latestCancellationRequest = $transaction
            ->cancellationRequests()
            ->with(['requester', 'reviewer'])
            ->latest()
            ->first();

        return view('transactions.show', compact(
            'transaction',
            'pendingCancellationRequest',
            'latestCancellationRequest'
        ));
    }

    public function printReceipt(Request $request, Transaction $transaction): View
    {
        $this->authorize('view', $transaction);
        $format = $request->query('format', 'a4');
        if (!in_array($format, ['a4', 'thermal'], true)) $format = 'a4';
        $transaction->load(['user', 'details']);
        $view = $format === 'thermal' ? 'transactions.print-thermal' : 'transactions.print-a4';
        return view($view, compact('transaction', 'format'));
    }

    public function requestCancellation(
        Request $request,
        Transaction $transaction
    ): RedirectResponse {
        $this->authorize('requestCancellation', $transaction);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'reason.required' => 'Alasan pembatalan wajib diisi.',
            'reason.min' => 'Alasan pembatalan minimal 5 karakter.',
        ]);

        DB::transaction(function () use ($transaction, $validated) {

            // Kunci transaksi agar tidak terjadi pengajuan bersamaan.
            $lockedTransaction = Transaction::whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Pastikan transaksi masih selesai.
            if ($lockedTransaction->status !== 'completed') {
                throw ValidationException::withMessages([
                    'transaction' => 'Transaksi ini tidak dapat diajukan pembatalannya.',
                ]);
            }

            // Cegah pengajuan ganda yang masih menunggu.
            $existingRequest = CancellationRequest::where(
                'transaction_id',
                $lockedTransaction->id
            )
                ->where('status', 'pending')
                ->exists();

            if ($existingRequest) {
                throw ValidationException::withMessages([
                    'reason' => 'Pengajuan pembatalan untuk transaksi ini masih menunggu persetujuan Owner.',
                ]);
            }

            // Simpan pengajuan pembatalan.
            CancellationRequest::create([
                'transaction_id' => $lockedTransaction->id,
                'requested_by' => auth()->id(),
                'reason' => $validated['reason'],
                'status' => 'pending',
            ]);
        }, 3);

        return redirect()
            ->route('transactions.show', $transaction)
            ->with(
                'success',
                'Pengajuan pembatalan berhasil dikirim dan sedang menunggu persetujuan Owner.'
            );
    }

    public function cancel(
        Request $request,
        Transaction $transaction
    ): RedirectResponse {
        $this->authorize('cancel', $transaction);

        $validated = $request->validate([
            'cancellation_reason' => [
                'required',
                'string',
                'min:5',
                'max:1000',
            ],
        ], [
            'cancellation_reason.required' => 'Alasan pembatalan wajib diisi.',
            'cancellation_reason.min' => 'Alasan pembatalan minimal 5 karakter.',
        ]);

        DB::transaction(function () use ($transaction, $validated) {

            // Kunci transaksi agar tidak dibatalkan secara bersamaan.
            $transaction = Transaction::whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Pastikan transaksi belum pernah dibatalkan.
            if ($transaction->status !== 'completed') {
                throw ValidationException::withMessages([
                    'transaction' => 'Transaksi ini sudah dibatalkan atau tidak dapat dibatalkan.',
                ]);
            }

            $details = $transaction->details;

            // Kunci semua produk berdasarkan urutan ID.
            $products = Product::whereIn(
                'id',
                $details->pluck('product_id')->unique()
            )
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Kembalikan stok setiap produk.
            foreach ($details as $detail) {
                $product = $products->get($detail->product_id);

                if (!$product) {
                    throw ValidationException::withMessages([
                        'transaction' => "Produk {$detail->product_name} tidak ditemukan.",
                    ]);
                }

                $quantity = (int) $detail->quantity;

                $stockBefore = (int) $product->stock;
                $stockAfter = $stockBefore + $quantity;

                $product->update([
                    'stock' => $stockAfter,
                ]);

                // Catat pengembalian stok.
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'transaction_id' => $transaction->id,
                    'movement_type' => 'incoming',
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'notes' => 'Pengembalian stok akibat pembatalan transaksi '
                        . $transaction->invoice_number
                        . '. Alasan: '
                        . $validated['cancellation_reason'],
                ]);
            }

            // Simpan informasi pembatalan.
            $transaction->status = 'cancelled';
            $transaction->cancelled_at = now();
            $transaction->cancelled_by = auth()->id();
            $transaction->cancellation_reason = $validated['cancellation_reason'];

            $transaction->save();
            Audit::log('transaction.cancelled', $transaction, ['status' => 'completed'], [
                'status' => 'cancelled',
                'cancelled_by' => auth()->id(),
                'cancellation_reason' => $validated['cancellation_reason'],
            ], 'Transaksi dibatalkan langsung oleh owner');

            ServiceTicket::where('transaction_id', $transaction->id)
                ->lockForUpdate()
                ->get()
                ->each(function (ServiceTicket $ticket): void {
                    $fromStatus = $ticket->status;
                    $ticket->update([
                        'transaction_id' => null,
                        'status' => 'selesai',
                        'picked_up_at' => null,
                    ]);

                    \App\Models\ServiceTicketStatusHistory::create([
                        'service_ticket_id' => $ticket->id,
                        'changed_by' => auth()->id(),
                        'from_status' => $fromStatus,
                        'to_status' => 'selesai',
                        'notes' => 'Transaksi dibatalkan; tiket dikembalikan ke selesai',
                    ]);
                });
        }, 3);

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'Transaksi berhasil dibatalkan dan stok telah dikembalikan.');
    }
}
