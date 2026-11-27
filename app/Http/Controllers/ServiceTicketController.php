<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ServiceTicket;
use App\Models\ServiceTicketPart;
use App\Models\ServiceTicketStatusHistory;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\ServiceTicketStatusNotification;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ServiceTicketController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ServiceTicket::class);

        $validated = $request->validate([
            'status' => ['nullable', 'in:antre,dicek,menunggu_sparepart,selesai,diambil,batal'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $status = $validated['status'] ?? null;
        $search = trim($validated['search'] ?? '');

        $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $tickets = ServiceTicket::with(['technician', 'receiver', 'parts'])
            ->when(auth()->user()->role?->name === 'teknisi', function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('technician_id')
                        ->orWhere('technician_id', auth()->id());
                });
            })
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search, $likeOperator) {
                $q->where(function ($sub) use ($search, $likeOperator) {
                    $sub->where('ticket_number', $likeOperator, "%{$search}%")
                        ->orWhere('customer_name', $likeOperator, "%{$search}%")
                        ->orWhere('customer_phone', $likeOperator, "%{$search}%")
                        ->orWhere('device_brand_model', $likeOperator, "%{$search}%")
                        ->orWhere('serial_number', $likeOperator, "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('services.index', compact('tickets', 'status', 'search'));
    }

    public function create(): View
    {
        $this->authorize('create', ServiceTicket::class);

        $technicians = User::whereHas('role', fn($q) => $q->whereIn('name', ['teknisi', 'owner']))
            ->where('is_active', true)
            ->get();

        return view('services.create', compact('technicians'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ServiceTicket::class);

        $validated = $request->validate([
            'customer_name'      => ['required', 'string', 'max:150'],
            'customer_phone'     => ['required', 'string', 'max:25'],
            'device_type'        => ['required', 'string', 'max:100'],
            'device_brand_model' => ['required', 'string', 'max:150'],
            'serial_number'      => ['nullable', 'string', 'max:100'],
            'device_password'    => ['nullable', 'string', 'max:100'],
            'accessories'        => ['nullable', 'string'],
            'problem_description' => ['required', 'string'],
            'estimated_cost'     => ['nullable', 'numeric', 'min:0'],
            'technician_id'      => ['nullable', 'exists:users,id'],
        ]);

        $ticket = DB::transaction(function () use ($validated) {
            $today = now()->format('Ymd');

            for ($attempt = 0; $attempt < 5; $attempt++) {
                $lastTicket = ServiceTicket::query()
                    ->whereDate('created_at', today())
                    ->lockForUpdate()
                    ->orderByDesc('id')
                    ->first();

                $nextSequence = 1;
                if ($lastTicket && preg_match('/(\d+)$/', $lastTicket->ticket_number, $matches)) {
                    $nextSequence = ((int) $matches[1]) + 1;
                }

                $ticketNumber = 'SRV-' . $today . '-' . str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);

                try {
                    $ticket = ServiceTicket::create([
                        ...$validated,
                        'ticket_number'  => $ticketNumber,
                        'estimated_cost' => $validated['estimated_cost'] ?? 0,
                        'received_by'    => auth()->id(),
                        'status'         => 'antre',
                    ]);

                    ServiceTicketStatusHistory::create([
                        'service_ticket_id' => $ticket->id,
                        'changed_by' => auth()->id(),
                        'from_status' => null,
                        'to_status' => 'antre',
                        'notes' => 'Tiket dibuat',
                    ]);

                    return $ticket;
                } catch (QueryException $exception) {
                    if ($exception->getCode() !== '23505') {
                        throw $exception;
                    }
                }
            }

            throw new \RuntimeException('Gagal membuat nomor tiket unik. Silakan coba lagi.');
        });

        $this->notifyServiceStatus($ticket, 'baru', 'antre', 'Tiket servis dibuat');

        return redirect()
            ->route('services.show', $ticket)
            ->with('success', "Tiket servis {$ticket->ticket_number} berhasil dibuat.");
    }

    public function show(ServiceTicket $service): View
    {
        $this->authorize('view', $service);

        $service->load(['technician', 'receiver', 'parts.product', 'transaction', 'statusHistories.user']);

        $products = Product::where('is_active', true)
            ->where('stock', '>', 0)
            ->where('sku', '!=', 'SRV-JASA')
            ->orderBy('name')
            ->get();

        $technicians = User::whereHas('role', fn($q) => $q->whereIn('name', ['teknisi', 'owner']))
            ->where('is_active', true)
            ->get();

        return view('services.show', [
            'ticket'      => $service,
            'products'    => $products,
            'technicians' => $technicians,
        ]);
    }

    public function update(Request $request, ServiceTicket $service): RedirectResponse
    {
        $this->authorize('update', $service);

        $validated = $request->validate([
            'status'           => ['required', 'in:antre,dicek,menunggu_sparepart,selesai,diambil,batal'],
            'technician_id'    => ['nullable', 'exists:users,id'],
            'technician_notes' => ['nullable', 'string'],
            'service_fee'      => ['required', 'numeric', 'min:0'],
            'warranty_days'    => ['required', 'integer', 'min:0'],
        ]);

        if ($validated['status'] === 'selesai' && !$service->completed_at) {
            $validated['completed_at'] = now();
        }
        if ($validated['status'] === 'diambil' && !$service->picked_up_at) {
            $validated['picked_up_at'] = now();
        }
        if (in_array($validated['status'], ['diambil', 'batal'], true)) {
            $validated['device_password'] = null; // Retensi: hapus kredensial perangkat setelah selesai/diambil/batal
        }

        $fromStatus = $service->status;
        $service->update($validated);

        if ($fromStatus !== $service->status) {
            ServiceTicketStatusHistory::create([
                'service_ticket_id' => $service->id,
                'changed_by' => auth()->id(),
                'from_status' => $fromStatus,
                'to_status' => $service->status,
                'notes' => 'Status diperbarui dari halaman detail servis',
            ]);
            $this->notifyServiceStatus($service, $fromStatus, $service->status, 'Status servis diperbarui');
        }

        return back()->with('success', 'Status & rincian servis berhasil diperbarui.');
    }

    public function addPart(Request $request, ServiceTicket $service): RedirectResponse
    {
        $this->authorize('manageParts', $service);

        if ($service->transaction_id) {
            return back()->with('error', 'Tiket sudah dibayar, sparepart tidak dapat diubah.');
        }

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($validated, $service) {
            $product = Product::lockForUpdate()->findOrFail($validated['product_id']);
            $qty = (int) $validated['quantity'];

            if ($product->stock < $qty) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'product_id' => "Stok {$product->name} tidak mencukupi (Tersisa: {$product->stock}).",
                ]);
            }

            $unitPrice = (float) $product->selling_price;
            $subtotal = $unitPrice * $qty;

            $existingPart = $service->parts()->where('product_id', $product->id)->first();
            if ($existingPart) {
                $existingPart->update([
                    'quantity' => $existingPart->quantity + $qty,
                    'subtotal' => ($existingPart->quantity + $qty) * $unitPrice,
                ]);
            } else {
                $service->parts()->create([
                    'product_id' => $product->id,
                    'quantity'   => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal'   => $subtotal,
                ]);
            }

            $stockBefore = $product->stock;
            $stockAfter = $stockBefore - $qty;
            $product->update(['stock' => $stockAfter]);

            StockMovement::create([
                'product_id'    => $product->id,
                'user_id'       => auth()->id(),
                'movement_type' => 'outgoing',
                'quantity'      => -$qty,
                'stock_before'  => $stockBefore,
                'stock_after'   => $stockAfter,
                'notes'         => "Pemakaian sparepart servis - {$service->ticket_number}",
            ]);
        });

        return back()->with('success', 'Sparepart ditambahkan dan stok otomatis berkurang.');
    }

    public function removePart(ServiceTicket $service, ServiceTicketPart $part): RedirectResponse
    {
        $this->authorize('manageParts', $service);

        if ($service->transaction_id) {
            return back()->with('error', 'Tiket sudah dibayar, sparepart tidak dapat dihapus.');
        }

        DB::transaction(function () use ($service, $part) {
            $lockedPart = $service->parts()->lockForUpdate()->findOrFail($part->id);
            $product = Product::lockForUpdate()->findOrFail($lockedPart->product_id);
            $stockBefore = $product->stock;
            $stockAfter = $stockBefore + $lockedPart->quantity;

            $product->update(['stock' => $stockAfter]);

            StockMovement::create([
                'product_id'    => $product->id,
                'user_id'       => auth()->id(),
                'movement_type' => 'incoming',
                'quantity'      => $lockedPart->quantity,
                'stock_before'  => $stockBefore,
                'stock_after'   => $stockAfter,
                'notes'         => "Batal pakai sparepart servis - {$service->ticket_number}",
            ]);

            $lockedPart->delete();
        });

        return back()->with('success', 'Sparepart dihapus dan stok dikembalikan ke gudang.');
    }

    public function printReceipt(ServiceTicket $service): View
    {
        $this->authorize('print', $service);

        $service->load(['technician', 'receiver', 'parts.product', 'transaction']);
        return view('services.print', ['ticket' => $service]);
    }

    public function pay(Request $request, ServiceTicket $service): RedirectResponse
    {
        $this->authorize('pay', $service);

        if ($service->transaction_id) {
            return back()->with('error', 'Tiket servis ini sudah dibayar.');
        }

        $service->load('parts.product');
        $grandTotal = (int) round($service->grand_total);

        if ($grandTotal <= 0) {
            return back()->with('error', 'Total tagihan Rp 0. Isi Biaya Jasa atau Sparepart terlebih dahulu.');
        }

        $validated = $request->validate([
            'paid_amount' => ['required', 'numeric', "min:{$grandTotal}"],
        ]);

        $fromStatus = $service->status;
        DB::transaction(function () use ($service, $validated, $grandTotal, $fromStatus) {
            $paidAmount = (int) round((float) $validated['paid_amount']);
            $changeAmount = $paidAmount - $grandTotal;
            $invoiceNumber = 'TRX-SRV-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));

            $transaction = Transaction::create([
                'invoice_number' => $invoiceNumber,
                'user_id'        => auth()->id(),
                'total'          => $grandTotal,
                'payment_method' => 'cash',
                'paid_amount'    => $paidAmount,
                'change_amount'  => $changeAmount,
                'status'         => 'completed',
            ]);

            if ((float) $service->service_fee > 0) {
                $category = Category::firstOrCreate(['name' => 'Jasa Servis']);
                $jasaProduct = Product::firstOrCreate(
                    ['sku' => 'SRV-JASA'],
                    [
                        'category_id'   => $category->id,
                        'name'          => 'Jasa Servis Komputer',
                        'unit'          => 'jasa',
                        'cost_price'    => 0,
                        'selling_price' => 0,
                        'stock'         => 999999,
                        'minimum_stock' => 0,
                        'is_active'     => false,
                    ]
                );
                $transaction->details()->create([
                    'product_id'   => $jasaProduct->id,
                    'product_name' => "Jasa Servis ({$service->ticket_number} - {$service->device_brand_model})",
                    'sku'          => $jasaProduct->sku,
                    'quantity'     => 1,
                    'unit_price'   => (int) round((float) $service->service_fee),
                    'subtotal'     => (int) round((float) $service->service_fee),
                ]);
            }
            foreach ($service->parts as $part) {
                $transaction->details()->create([
                    'product_id'   => $part->product_id,
                    'product_name' => $part->product->name . " ({$service->ticket_number})",
                    'sku'          => $part->product->sku,
                    'quantity'     => $part->quantity,
                    'unit_price'   => (int) round((float) $part->unit_price),
                    'subtotal'     => (int) round((float) $part->subtotal),
                ]);
            }
            $service->update([
                'transaction_id' => $transaction->id,
                'status'         => 'diambil',
                'completed_at'   => $service->completed_at ?? now(),
                'picked_up_at'   => now(),
                'device_password' => null, // Retensi: hapus kredensial perangkat setelah unit diambil
            ]);

            ServiceTicketStatusHistory::create([
                'service_ticket_id' => $service->id,
                'changed_by' => auth()->id(),
                'from_status' => $fromStatus,
                'to_status' => 'diambil',
                'notes' => "Pembayaran servis lunas ({$transaction->invoice_number})",
            ]);
        });
        $service->refresh();
        $this->notifyServiceStatus($service, $fromStatus ?? 'selesai', 'diambil', 'Pembayaran servis lunas');

        return redirect()
            ->route('services.show', $service)
            ->with('success', 'Pembayaran servis berhasil! Transaksi tercatat di Riwayat & Laporan.');
    }

    private function notifyServiceStatus(ServiceTicket $ticket, string $fromStatus, string $toStatus, ?string $note = null): void
    {
        $ticket->loadMissing(['receiver', 'technician']);
        $recipients = User::query()
            ->where('is_active', true)
            ->where(function ($q) use ($ticket) {
                $q->whereHas('role', fn($r) => $r->whereIn('name', ['owner', 'kasir']))
                    ->orWhere('id', $ticket->received_by)
                    ->orWhere('id', $ticket->technician_id);
            })
            ->get()
            ->unique('id');

        foreach ($recipients as $user) {
            $user->notify(new ServiceTicketStatusNotification($ticket, $fromStatus, $toStatus, $note));
        }
    }
}
